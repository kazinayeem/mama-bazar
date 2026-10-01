<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Throwable;

class EmailSettingService
{
    public const CACHE_KEY = 'mamabazar:email_settings';

    /**
     * Default configuration matching Mama Bazar production requirements.
     */
    public static function defaults(): array
    {
        return [
            // SMTP Transport Settings
            'mail_mailer' => 'smtp',
            'mail_host' => 'mail.mama-bazar.com',
            'mail_port' => 465,
            'mail_encryption' => 'ssl', // ssl or tls
            'mail_username' => 'contact@mama-bazar.com',
            'mail_password' => '', // Stored encrypted
            'mail_from_address' => 'contact@mama-bazar.com',
            'mail_from_name' => 'Mama Bazar',
            'mail_reply_to' => 'support@mamabazar.com',
            'mail_timeout' => 30,
            'mail_enabled' => 1,

            // Diagnostics and Status
            'mail_last_tested_at' => null,
            'mail_last_status' => null, // connected, failed
            'mail_last_error' => null,
            'mail_last_latency_ms' => null,

            // Automation Toggles
            'email_auto_welcome' => 1,
            'email_auto_account_otp' => 1,
            'email_auto_login_otp' => 1,
            'email_auto_password_reset' => 1,
            'email_auto_order_created' => 1,
            'email_auto_payment_confirmed' => 1,
            'email_auto_order_status' => 1,
            'email_auto_invoice_pdf' => 1,
            'email_auto_review_invitation' => 1,
            'email_auto_contact_form' => 1,
        ];
    }

    /**
     * Get all cached email settings with defaults merged.
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 86400, function () {
            $defaults = self::defaults();
            try {
                $dbRows = SiteSetting::whereIn('key', array_keys($defaults))->pluck('value', 'key')->toArray();
            } catch (Throwable $e) {
                $dbRows = [];
            }

            $settings = [];
            foreach ($defaults as $key => $defaultVal) {
                $settings[$key] = array_key_exists($key, $dbRows) && $dbRows[$key] !== null && $dbRows[$key] !== ''
                    ? $dbRows[$key]
                    : $defaultVal;
            }

            return $settings;
        });
    }

    /**
     * Get a specific setting value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    /**
     * Check if email sending is globally enabled.
     */
    public static function isSendingEnabled(): bool
    {
        return (bool) self::get('mail_enabled', 1);
    }

    /**
     * Check if a specific automated trigger is enabled.
     */
    public static function isAutomationEnabled(string $triggerKey): bool
    {
        if (!self::isSendingEnabled()) {
            return false;
        }

        $settingKey = str_starts_with($triggerKey, 'email_auto_') ? $triggerKey : 'email_auto_' . $triggerKey;
        return (bool) self::get($settingKey, 1);
    }

    /**
     * Get decrypted SMTP password for runtime mailer authentication.
     */
    public static function getDecryptedPassword(): string
    {
        $encrypted = self::get('mail_password');
        if (empty($encrypted)) {
            return '';
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable $e) {
            // Fallback if value was stored in plain text or cannot be decrypted
            return (string) $encrypted;
        }
    }

    /**
     * Persist email settings securely.
     */
    public static function updateSettings(array $input): void
    {
        $defaults = self::defaults();

        foreach ($input as $key => $val) {
            if (!array_key_exists($key, $defaults)) {
                continue;
            }

            // Encrypt password if provided; preserve existing if left empty
            if ($key === 'mail_password') {
                if (!empty($val)) {
                    $val = Crypt::encryptString($val);
                } else {
                    continue; // Keep existing password
                }
            }

            $cleanVal = is_bool($val) ? ($val ? '1' : '0') : (is_string($val) ? trim($val) : (string) $val);

            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $cleanVal]
            );
        }

        self::clearCache();
    }

    /**
     * Clear cached settings.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Apply configured SMTP settings dynamically to Laravel's runtime mailer.
     */
    public static function applyRuntimeConfig(): void
    {
        $settings = self::all();
        $password = self::getDecryptedPassword();

        $port = (int) ($settings['mail_port'] ?? 465);
        $encryption = strtolower((string) ($settings['mail_encryption'] ?? 'ssl'));
        if ($encryption === 'tls' && $port === 465) {
            $encryption = 'ssl'; // 465 is implicit SSL
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $settings['mail_host']);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
        Config::set('mail.mailers.smtp.username', $settings['mail_username']);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.mailers.smtp.timeout', (int) ($settings['mail_timeout'] ?? 30));

        if (!empty($settings['mail_from_address'])) {
            Config::set('mail.from.address', $settings['mail_from_address']);
        }
        if (!empty($settings['mail_from_name'])) {
            Config::set('mail.from.name', $settings['mail_from_name']);
        }

        Mail::purge('smtp');
    }

    /**
     * Test connection to configured SMTP server.
     * Returns array with [success => bool, latency_ms => int, message => string].
     */
    public static function testConnection(): array
    {
        $settings = self::all();
        $host = $settings['mail_host'];
        $port = (int) $settings['mail_port'];
        $encryption = strtolower((string) $settings['mail_encryption']);
        $timeout = (int) ($settings['mail_timeout'] ?? 15);
        $username = $settings['mail_username'];
        $password = self::getDecryptedPassword();

        $startTime = microtime(true);

        try {
            // First: socket connectivity probe
            $socketPrefix = ($encryption === 'ssl' || $port === 465) ? 'ssl://' : '';
            $fp = @fsockopen($socketPrefix . $host, $port, $errno, $errstr, $timeout);

            if (!$fp) {
                throw new \Exception("Cannot connect to {$host}:{$port} ({$errno}: {$errstr})");
            }

            // Read banner
            stream_set_timeout($fp, 5);
            $banner = fgets($fp, 512);
            fclose($fp);

            if (!$banner || !str_starts_with(trim($banner), '220')) {
                throw new \Exception("SMTP server did not return 220 banner: " . substr((string) $banner, 0, 100));
            }

            // Second: Full ESMTP authentication probe
            $tls = in_array($encryption, ['tls', 'ssl'], true);
            $transport = new EsmtpTransport($host, $port, $tls);
            if (!empty($username)) {
                $transport->setUsername($username);
                $transport->setPassword($password);
            }
            $transport->start();
            $transport->stop();

            $latency = (int) round((microtime(true) - $startTime) * 1000);

            // Record success in DB
            SiteSetting::updateOrCreate(['key' => 'mail_last_tested_at'], ['value' => now()->toIso8601String()]);
            SiteSetting::updateOrCreate(['key' => 'mail_last_status'], ['value' => 'connected']);
            SiteSetting::updateOrCreate(['key' => 'mail_last_error'], ['value' => null]);
            SiteSetting::updateOrCreate(['key' => 'mail_last_latency_ms'], ['value' => (string) $latency]);
            self::clearCache();

            return [
                'success' => true,
                'latency_ms' => $latency,
                'message' => "Successfully connected and authenticated with SMTP server {$host}:{$port} ({$latency}ms).",
            ];
        } catch (Throwable $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $errMsg = $e->getMessage();

            SiteSetting::updateOrCreate(['key' => 'mail_last_tested_at'], ['value' => now()->toIso8601String()]);
            SiteSetting::updateOrCreate(['key' => 'mail_last_status'], ['value' => 'failed']);
            SiteSetting::updateOrCreate(['key' => 'mail_last_error'], ['value' => $errMsg]);
            SiteSetting::updateOrCreate(['key' => 'mail_last_latency_ms'], ['value' => (string) $latency]);
            self::clearCache();

            return [
                'success' => false,
                'latency_ms' => $latency,
                'message' => "SMTP Connection failed: {$errMsg}",
            ];
        }
    }
}
