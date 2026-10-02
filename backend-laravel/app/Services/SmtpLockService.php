<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class SmtpLockService
{
    public const SESSION_KEY = 'smtp_settings_unlocked_at';

    /**
     * Get the configured security PIN.
     */
    public static function getConfiguredPin(): ?string
    {
        $pin = config('email_system.smtp_pin');

        if ($pin === null || $pin === '') {
            return '6969';
        }

        return (string) $pin;
    }

    /**
     * Verify the supplied PIN against the configured PIN or hash.
     */
    public static function verifyPin(?string $pin): bool
    {
        if ($pin === null || $pin === '') {
            return false;
        }

        $configured = self::getConfiguredPin();
        if ($configured === null || $configured === '') {
            return false;
        }

        $isHashed = str_starts_with($configured, '$2y$')
            || str_starts_with($configured, '$argon2i$')
            || str_starts_with($configured, '$argon2id$');

        $matched = $isHashed
            ? Hash::check($pin, $configured)
            : hash_equals($configured, (string) $pin);

        if ($matched) {
            self::unlock();
        }

        return $matched;
    }

    /**
     * Determine whether SMTP settings are currently unlocked in the session.
     */
    public static function isUnlocked(): bool
    {
        $unlockedAt = session(self::SESSION_KEY);
        if (! $unlockedAt || ! is_numeric($unlockedAt)) {
            return false;
        }

        $durationMinutes = (int) config('email_system.smtp_unlock_duration_minutes', 15);
        $ttlSeconds = max(60, $durationMinutes * 60);

        if ((now()->timestamp - (int) $unlockedAt) > $ttlSeconds) {
            self::lock();

            return false;
        }

        return true;
    }

    /**
     * Mark the session as unlocked with the current timestamp.
     */
    public static function unlock(): void
    {
        session([self::SESSION_KEY => now()->timestamp]);
    }

    /**
     * Lock the session by removing the unlock timestamp.
     */
    public static function lock(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
