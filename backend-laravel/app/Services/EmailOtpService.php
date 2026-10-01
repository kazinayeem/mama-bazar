<?php

namespace App\Services;

use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Email one-time passwords. Codes are generated with random_int(), stored
 * only as bcrypt hashes, single-use, short-lived, attempt-limited and
 * rate-limited per address. Raw codes are never logged or persisted.
 */
class EmailOtpService
{
    public const TYPE_ACCOUNT = 'account_verification';

    public const TYPE_LOGIN = 'login';

    public const TYPE_EMAIL_CHANGE = 'email_change';

    /** @var array<string, array{template: string, automation: string|null}> */
    public const TYPES = [
        self::TYPE_ACCOUNT => ['template' => 'account_verification_otp', 'automation' => 'account_otp'],
        self::TYPE_LOGIN => ['template' => 'login_otp', 'automation' => 'login_otp'],
        self::TYPE_EMAIL_CHANGE => ['template' => 'email_change_otp', 'automation' => null],
    ];

    public static function length(): int
    {
        return max(4, min(10, (int) config('email_system.otp.length', 6)));
    }

    public static function expiresMinutes(): int
    {
        return max(1, (int) config('email_system.otp.expires_minutes', 5));
    }

    public static function cooldownSeconds(): int
    {
        return max(0, (int) config('email_system.otp.resend_cooldown_seconds', 60));
    }

    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Seconds until another code may be requested (0 = allowed now).
     */
    public static function cooldownRemaining(string $email, string $type): int
    {
        $latest = EmailOtp::where('email', self::normalize($email))
            ->where('type', $type)
            ->latest('id')
            ->first();

        if (! $latest) {
            return 0;
        }

        $elapsed = (int) $latest->created_at->diffInSeconds(now(), true);

        return max(0, self::cooldownSeconds() - $elapsed);
    }

    /**
     * Generate a new code (invalidating earlier unused codes).
     *
     * @return array{success: bool, code?: string, expires_minutes?: int, cooldown?: bool, remaining_seconds?: int, rate_limited?: bool, message: string}
     */
    public static function generateOtp(string $email, string $type = self::TYPE_ACCOUNT, ?int $userId = null): array
    {
        $email = self::normalize($email);

        $remaining = self::cooldownRemaining($email, $type);
        if ($remaining > 0) {
            return [
                'success' => false,
                'cooldown' => true,
                'remaining_seconds' => $remaining,
                'message' => "Please wait {$remaining} seconds before requesting a new code.",
            ];
        }

        $hourlyCount = EmailOtp::where('email', $email)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($hourlyCount >= (int) config('email_system.otp.max_per_hour', 5)) {
            return [
                'success' => false,
                'rate_limited' => true,
                'message' => 'Too many codes requested. Please try again in an hour.',
            ];
        }

        // Earlier codes stop working; their rows are kept (consumed) so the hourly cap holds.
        EmailOtp::where('email', $email)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->update(['verified_at' => now(), 'attempts' => DB::raw('max_attempts')]);

        $length = self::length();
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        EmailOtp::create([
            'user_id' => $userId ?? User::whereRaw('LOWER(email) = ?', [$email])->value('id'),
            'email' => $email,
            'otp_hash' => Hash::make($code),
            'type' => $type,
            'attempts' => 0,
            'max_attempts' => max(1, (int) config('email_system.otp.max_attempts', 5)),
            'expires_at' => now()->addMinutes(self::expiresMinutes()),
        ]);

        return [
            'success' => true,
            'code' => $code,
            'expires_minutes' => self::expiresMinutes(),
            'message' => 'A verification code has been sent to your email.',
        ];
    }

    /**
     * @return array{success: bool, expired?: bool, exceeded?: bool, message: string, user?: User|null}
     */
    public static function verifyOtp(string $email, string $code, string $type = self::TYPE_ACCOUNT): array
    {
        $email = self::normalize($email);
        $cleanCode = preg_replace('/\D/', '', trim($code)) ?? '';
        $length = self::length();

        if (strlen($cleanCode) !== $length) {
            return ['success' => false, 'message' => "Please enter the {$length}-digit code."];
        }

        $otp = EmailOtp::where('email', $email)
            ->where('type', $type)
            ->latest('id')
            ->first();

        if (! $otp || ($otp->verified_at && ! $otp->hasExceededAttempts())) {
            return ['success' => false, 'message' => 'No active code found. Please request a new one.'];
        }

        if ($otp->hasExceededAttempts()) {
            return ['success' => false, 'exceeded' => true, 'message' => 'Too many incorrect attempts. Please request a new code.'];
        }

        if ($otp->isExpired()) {
            return ['success' => false, 'expired' => true, 'message' => 'This code has expired. Please request a new one.'];
        }

        if (! Hash::check($cleanCode, $otp->otp_hash)) {
            $otp->increment('attempts');
            $left = $otp->max_attempts - $otp->attempts;
            if ($left <= 0) {
                $otp->update(['verified_at' => now()]);

                return ['success' => false, 'exceeded' => true, 'message' => 'Too many incorrect attempts. Please request a new code.'];
            }

            return ['success' => false, 'message' => "Incorrect code. {$left} ".($left === 1 ? 'attempt' : 'attempts').' remaining.'];
        }

        // Atomic consume: a code can only ever be used once.
        $consumed = EmailOtp::where('id', $otp->id)->whereNull('verified_at')->update(['verified_at' => now()]);
        if ($consumed === 0) {
            return ['success' => false, 'message' => 'This code has already been used. Please request a new one.'];
        }

        $user = $otp->user ?: User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user && $type === self::TYPE_ACCOUNT && ! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return ['success' => true, 'message' => 'Verification successful.', 'user' => $user];
    }

    /**
     * Generate a code and email it immediately (never queued, so the code
     * never touches the queue payload).
     *
     * @return array{success: bool, sent?: bool, cooldown?: bool, remaining_seconds?: int, rate_limited?: bool, message: string}
     */
    public static function issueAndSend(string $email, string $type, ?string $name = null, ?int $userId = null): array
    {
        $definition = self::TYPES[$type] ?? null;
        if (! $definition) {
            return ['success' => false, 'message' => 'Unsupported verification type.'];
        }

        if ($definition['automation'] && ! EmailSettingService::isAutomationEnabled($definition['automation'])) {
            return ['success' => false, 'disabled' => true, 'message' => 'Email verification is temporarily unavailable.'];
        }

        $otp = self::generateOtp($email, $type, $userId);
        if (! $otp['success']) {
            return $otp;
        }

        $result = EmailDispatcherService::sendTemplate(
            $definition['template'],
            self::normalize($email),
            $name,
            ['otp_code' => $otp['code'], 'otp_expires_minutes' => (string) $otp['expires_minutes']],
            'otp',
            ['user_id' => $userId, 'redact' => [$otp['code']]]
        );

        if (! $result['success']) {
            return [
                'success' => false,
                'sent' => false,
                'message' => 'We could not send the email right now. Please try again shortly.',
            ];
        }

        return ['success' => true, 'sent' => true, 'message' => 'We sent a '.self::length().'-digit code to your email.'];
    }
}
