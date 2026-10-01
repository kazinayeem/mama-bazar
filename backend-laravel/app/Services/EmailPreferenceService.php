<?php

namespace App\Services;

use App\Models\EmailSuppression;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Marketing consent, unsubscribe links and the suppression list.
 * Transactional email (orders, OTP, password reset) is never affected.
 */
class EmailPreferenceService
{
    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function encodeEmail(string $email): string
    {
        return rtrim(strtr(base64_encode(self::normalize($email)), '+/', '-_'), '=');
    }

    public static function decodeEmail(?string $encoded): ?string
    {
        if (! $encoded) {
            return null;
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        return $decoded !== false && filter_var($decoded, FILTER_VALIDATE_EMAIL) ? self::normalize($decoded) : null;
    }

    /**
     * Permanent signed link (signature bound to the APP_KEY) — never expires,
     * so old campaign emails can always be unsubscribed from.
     */
    public static function unsubscribeUrl(string $email): string
    {
        return URL::signedRoute('email.unsubscribe', ['e' => self::encodeEmail($email)]);
    }

    /** RFC 8058 one-click unsubscribe endpoint (POST, no CSRF, signed). */
    public static function oneClickUrl(string $email): string
    {
        return URL::signedRoute('email.unsubscribe.one-click', ['e' => self::encodeEmail($email)]);
    }

    public static function preferencesUrl(string $email): string
    {
        return self::unsubscribeUrl($email);
    }

    public static function isSuppressed(string $email): bool
    {
        return EmailSuppression::isSuppressed($email);
    }

    public static function isMarketingSubscribed(string $email): bool
    {
        $email = self::normalize($email);
        if (self::isSuppressed($email)) {
            return false;
        }

        return User::whereRaw('LOWER(email) = ?', [$email])->where('marketing_opt_in', true)->exists()
            || Newsletter::whereRaw('LOWER(email) = ?', [$email])->where('status', 'subscribed')->exists();
    }

    public static function unsubscribe(string $email, string $source = 'link'): void
    {
        $email = self::normalize($email);

        User::whereRaw('LOWER(email) = ?', [$email])->update(['marketing_opt_in' => false]);
        Newsletter::whereRaw('LOWER(email) = ?', [$email])->update(['status' => 'unsubscribed']);

        $suppression = EmailSuppression::firstOrNew(['email' => $email]);
        if (! $suppression->exists || $suppression->reason === 'unsubscribed') {
            $suppression->fill(['reason' => 'unsubscribed', 'source' => $source])->save();
        }
    }

    /**
     * Explicit re-subscription by the address owner. Bounce/complaint/manual
     * suppressions stay in place.
     */
    public static function resubscribe(string $email, string $source = 'preferences'): bool
    {
        $email = self::normalize($email);

        $blocking = EmailSuppression::where('email', $email)->where('reason', '!=', 'unsubscribed')->exists();
        if ($blocking) {
            return false;
        }

        EmailSuppression::where('email', $email)->where('reason', 'unsubscribed')->delete();

        User::whereRaw('LOWER(email) = ?', [$email])->update([
            'marketing_opt_in' => true,
            'marketing_opt_in_at' => now(),
            'marketing_consent_source' => $source,
        ]);

        $updated = Newsletter::whereRaw('LOWER(email) = ?', [$email])->update(['status' => 'subscribed']);
        if ($updated === 0 && ! User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            Newsletter::create(['email' => $email, 'source' => $source, 'status' => 'subscribed', 'subscribed_at' => now()]);
        }

        return true;
    }

    public static function setUserConsent(User $user, bool $optIn, string $source): void
    {
        if ($optIn) {
            if ($user->email && ! self::resubscribe($user->email, $source)) {
                return;
            }
            $user->forceFill([
                'marketing_opt_in' => true,
                'marketing_opt_in_at' => now(),
                'marketing_consent_source' => $source,
            ])->save();

            return;
        }

        $user->forceFill(['marketing_opt_in' => false])->save();
        if ($user->email) {
            self::unsubscribe($user->email, $source);
        }
    }
}
