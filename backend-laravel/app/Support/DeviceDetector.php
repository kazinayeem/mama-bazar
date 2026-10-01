<?php

namespace App\Support;

/**
 * Lightweight privacy-conscious user-agent parser.
 * No fingerprinting, no external calls — simple family/OS/device buckets only.
 */
class DeviceDetector
{
    public static function parse(?string $ua): array
    {
        $ua = (string) ($ua ?? '');

        return [
            'browser' => self::browser($ua),
            'os' => self::os($ua),
            'device' => self::device($ua),
        ];
    }

    public static function browser(string $ua): string
    {
        $u = strtolower($ua);
        if (str_contains($u, 'edg/') || str_contains($u, 'edgа')) return 'Edge';
        if (str_contains($u, 'opr/') || str_contains($u, 'opera')) return 'Opera';
        if (str_contains($u, 'firefox/') || str_contains($u, 'fxios/')) return 'Firefox';
        if (str_contains($u, 'crios/') || (str_contains($u, 'chrome/') && str_contains($u, 'safari/'))) return 'Chrome';
        if (str_contains($u, 'safari/') && !str_contains($u, 'chrome')) return 'Safari';
        if (str_contains($u, 'msie') || str_contains($u, 'trident/')) return 'IE';
        if (str_contains($u, 'samsungbrowser')) return 'Samsung Internet';
        if ($ua === '') return 'Unknown';
        return 'Other';
    }

    public static function os(string $ua): string
    {
        $u = strtolower($ua);
        if (str_contains($u, 'android')) return 'Android';
        if (str_contains($u, 'iphone') || str_contains($u, 'ipad') || str_contains($u, 'ipod') || str_contains($u, 'ios')) return 'iOS';
        if (str_contains($u, 'windows')) return 'Windows';
        if (str_contains($u, 'mac os') || str_contains($u, 'macintosh')) return 'macOS';
        if (str_contains($u, 'linux')) return 'Linux';
        if ($ua === '') return 'Unknown';
        return 'Other';
    }

    public static function device(string $ua): string
    {
        $u = strtolower($ua);
        if (str_contains($u, 'mobile') || str_contains($u, 'android') || str_contains($u, 'iphone') || str_contains($u, 'ipod')) return 'Mobile';
        if (str_contains($u, 'tablet') || str_contains($u, 'ipad')) return 'Tablet';
        if ($ua === '') return 'Unknown';
        return 'Desktop';
    }

    public static function friendlyLabel(array $parsed): string
    {
        // e.g. "Chrome on Android", "Safari on iPhone"
        if (($parsed['browser'] ?? 'Unknown') === 'Unknown' && ($parsed['os'] ?? 'Unknown') === 'Unknown') {
            return 'Unknown';
        }
        return ($parsed['browser'] ?? 'Unknown') . ' on ' . ($parsed['os'] ?? 'Unknown');
    }

    /** Truncate IP to /24 (v4) or /48 (v6) — no precise geo, no identification. */
    public static function truncateIp(?string $ip): ?string
    {
        if (!$ip) return null;
        $ip = trim(explode(',', $ip)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 3)) . '::';
        }
        return null;
    }

    public static function hashIp(?string $ip): ?string
    {
        if (!$ip) return null;
        $ip = trim(explode(',', $ip)[0]);
        return hash('sha256', $ip . '|' . config('app.key'));
    }
}
