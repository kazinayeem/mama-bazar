<?php

namespace App\Services;

/**
 * @deprecated SMTP settings PIN unlock has been removed; SMTP access is directly governed by admin authorization.
 */
class SmtpLockService
{
    public const SESSION_KEY = 'smtp_settings_unlocked_at';

    /**
     * Determine whether SMTP settings are accessible (always true for authorized admins).
     */
    public static function isUnlocked(): bool
    {
        return true;
    }

    /**
     * Legacy verifyPin - no longer used.
     */
    public static function verifyPin(?string $pin): bool
    {
        return true;
    }

    public static function unlock(): void
    {
        // No-op
    }

    public static function lock(): void
    {
        // No-op
    }
}
