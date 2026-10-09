<?php

namespace App\Services;

use App\Models\MemberLoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

class LoginTrackingService
{
    /**
     * Record a successful login event.
     */
    public static function recordSuccess(User $user, Request $request): ?MemberLoginHistory
    {
        try {
            $ip = $request->ip() ?: '127.0.0.1';
            $userAgent = $request->userAgent() ?: 'Unknown';
            $parsed = self::parseUserAgent($userAgent);
            $location = IpLocationService::lookup($ip);

            // 1. Update user record
            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $ip,
                'last_login_location' => $location['formatted'],
            ])->save();

            // 2. Create login history
            $history = MemberLoginHistory::create([
                'user_id' => $user->id,
                'login_at' => now(),
                'ip_address' => $ip,
                'user_agent' => substr($userAgent, 0, 500),
                'browser' => $parsed['browser'],
                'os' => $parsed['os'],
                'country' => $location['country'] ?: ($location['is_private'] ? 'Local Network' : null),
                'region' => $location['region'],
                'city' => $location['city'],
                'status' => 'success',
                'failure_reason' => null,
            ]);

            // 3. Security Audit Log
            AuditService::log([
                'actorId' => $user->id,
                'actorName' => $user->name,
                'actorEmail' => $user->email,
                'action' => 'login.success',
                'targetType' => 'member',
                'targetId' => $user->id,
                'ipAddress' => $ip,
                'userAgent' => $userAgent,
                'status' => 'success',
                'details' => [
                    'ip' => $ip,
                    'location' => $location['formatted'],
                    'browser' => $parsed['browser'],
                    'os' => $parsed['os'],
                ],
            ]);

            return $history;
        } catch (Throwable $e) {
            logger()->error('Failed to record login tracking: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Record a failed login attempt.
     */
    public static function recordFailure(Request $request, ?string $loginIdentifier, string $reason): ?MemberLoginHistory
    {
        try {
            $ip = $request->ip() ?: '127.0.0.1';
            $userAgent = $request->userAgent() ?: 'Unknown';
            $parsed = self::parseUserAgent($userAgent);
            $location = IpLocationService::lookup($ip);

            // Attempt to resolve target member if an account exists
            $targetUser = null;
            if ($loginIdentifier) {
                $targetUser = User::where('email', $loginIdentifier)
                    ->orWhere('phone', $loginIdentifier)
                    ->first();
            }

            $history = MemberLoginHistory::create([
                'user_id' => $targetUser?->id,
                'login_at' => now(),
                'ip_address' => $ip,
                'user_agent' => substr($userAgent, 0, 500),
                'browser' => $parsed['browser'],
                'os' => $parsed['os'],
                'country' => $location['country'],
                'region' => $location['region'],
                'city' => $location['city'],
                'status' => 'failure',
                'failure_reason' => $reason,
            ]);

            AuditService::log([
                'actorId' => $targetUser?->id,
                'actorName' => $targetUser ? $targetUser->name : 'Unknown User',
                'actorEmail' => $targetUser ? $targetUser->email : $loginIdentifier,
                'action' => 'login.failed',
                'targetType' => 'member',
                'targetId' => $targetUser ? (string) $targetUser->id : null,
                'ipAddress' => $ip,
                'userAgent' => $userAgent,
                'status' => 'failure',
                'details' => [
                    'login_input' => self::maskLoginInput($loginIdentifier),
                    'reason' => $reason,
                    'ip' => $ip,
                    'location' => $location['formatted'],
                    'browser' => $parsed['browser'],
                    'os' => $parsed['os'],
                ],
            ]);

            return $history;
        } catch (Throwable $e) {
            logger()->error('Failed to record failed login tracking: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Parse browser and operating system safely from user agent string.
     *
     * @return array{browser: string, os: string}
     */
    public static function parseUserAgent(?string $ua): array
    {
        $ua = (string) $ua;

        // Detect OS
        $os = 'Unknown OS';
        if (stripos($ua, 'Windows NT 10.0') !== false) {
            $os = 'Windows 10/11';
        } elseif (stripos($ua, 'Windows NT 6.3') !== false) {
            $os = 'Windows 8.1';
        } elseif (stripos($ua, 'Windows NT 6.1') !== false) {
            $os = 'Windows 7';
        } elseif (stripos($ua, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
            $os = 'iOS';
        } elseif (stripos($ua, 'Mac OS X') !== false || stripos($ua, 'Macintosh') !== false) {
            $os = 'macOS';
        } elseif (stripos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($ua, 'CrOS') !== false) {
            $os = 'Chrome OS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        }

        // Detect Browser
        $browser = 'Unknown Browser';
        if (stripos($ua, 'Edg/') !== false || stripos($ua, 'Edge/') !== false) {
            $browser = 'Microsoft Edge';
        } elseif (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) {
            $browser = 'Opera';
        } elseif (stripos($ua, 'Chrome/') !== false && stripos($ua, 'Chromium') === false) {
            $browser = 'Chrome';
        } elseif (stripos($ua, 'Firefox/') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($ua, 'Safari/') !== false && stripos($ua, 'Chrome/') === false) {
            $browser = 'Safari';
        }

        return [
            'browser' => $browser,
            'os' => $os,
        ];
    }

    protected static function maskLoginInput(?string $input): string
    {
        if (! $input) {
            return '—';
        }

        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $input, 2);
            $name = $parts[0];
            $domain = $parts[1] ?? '';
            $maskedName = strlen($name) <= 2 ? $name[0].'*' : substr($name, 0, 2).str_repeat('*', max(1, strlen($name) - 3)).substr($name, -1);

            return $maskedName.'@'.$domain;
        }

        if (strlen($input) >= 7) {
            return substr($input, 0, 3).'****'.substr($input, -3);
        }

        return substr($input, 0, 1).'***';
    }
}
