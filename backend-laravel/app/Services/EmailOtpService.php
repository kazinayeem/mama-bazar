<?php

namespace App\Services;

use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class EmailOtpService
{
    public const OTP_LENGTH = 6;
    public const EXPIRATION_MINUTES = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;

    /**
     * Generate and persist a new hashed OTP.
     * Returns [success => bool, code => string (for immediate delivery only, never logged), message => string].
     */
    public static function generateOtp(string $email, string $type = 'account_verification', ?int $userId = null): array
    {
        $normalizedEmail = strtolower(trim($email));

        // Check resend cooldown
        $recentOtp = EmailOtp::where('email', $normalizedEmail)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentOtp && $recentOtp->created_at->diffInSeconds(now()) < self::RESEND_COOLDOWN_SECONDS) {
            $remaining = self::RESEND_COOLDOWN_SECONDS - $recentOtp->created_at->diffInSeconds(now());
            return [
                'success' => false,
                'cooldown' => true,
                'remaining_seconds' => $remaining,
                'message' => "Please wait {$remaining} seconds before requesting a new OTP.",
            ];
        }

        // Invalidate older unverified OTPs for this email and type
        EmailOtp::where('email', $normalizedEmail)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->delete();

        // Cryptographically secure 6-digit random code
        $rawCode = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::EXPIRATION_MINUTES);

        // Associate user if available
        if (!$userId) {
            $u = User::where('email', $normalizedEmail)->first();
            $userId = $u?->id;
        }

        // Save only hashed OTP to DB
        EmailOtp::create([
            'user_id' => $userId,
            'email' => $normalizedEmail,
            'otp_hash' => Hash::make($rawCode),
            'type' => $type,
            'attempts' => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
            'expires_at' => $expiresAt,
        ]);

        return [
            'success' => true,
            'code' => $rawCode, // For immediate email dispatch only; never logged!
            'expires_at' => $expiresAt,
            'expires_minutes' => self::EXPIRATION_MINUTES,
            'message' => 'A verification code has been sent to your email.',
        ];
    }

    /**
     * Validate an entered OTP.
     * Returns [success => bool, message => string, user => ?User].
     */
    public static function verifyOtp(string $email, string $code, string $type = 'account_verification'): array
    {
        $normalizedEmail = strtolower(trim($email));
        $cleanCode = preg_replace('/\D/', '', trim($code));

        if (strlen($cleanCode) !== self::OTP_LENGTH) {
            return [
                'success' => false,
                'message' => 'Please enter a valid 6-digit verification code.',
            ];
        }

        $otpRecord = EmailOtp::where('email', $normalizedEmail)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otpRecord) {
            return [
                'success' => false,
                'message' => 'No active verification request found. Please request a new code.',
            ];
        }

        if ($otpRecord->isExpired()) {
            return [
                'success' => false,
                'expired' => true,
                'message' => 'The verification code has expired. Please request a new code.',
            ];
        }

        if ($otpRecord->hasExceededAttempts()) {
            return [
                'success' => false,
                'exceeded' => true,
                'message' => 'Too many failed attempts. Please request a new verification code.',
            ];
        }

        // Verify hash
        if (!Hash::check($cleanCode, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            $remaining = $otpRecord->max_attempts - $otpRecord->attempts;
            return [
                'success' => false,
                'message' => $remaining > 0
                    ? "Incorrect code. {$remaining} attempts remaining."
                    : "Too many failed attempts. Please request a new code.",
            ];
        }

        // Mark OTP as verified
        $otpRecord->update(['verified_at' => now()]);

        // If associated with user or account_verification, mark email_verified_at
        $user = $otpRecord->user ?: User::where('email', $normalizedEmail)->first();
        if ($user && $type === 'account_verification') {
            $user->update(['email_verified_at' => now()]);
        }

        return [
            'success' => true,
            'message' => 'Verification successful!',
            'user' => $user,
        ];
    }
}
