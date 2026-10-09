<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Throwable;

class EmailSettingService
{
    public const CACHE_KEY = 'mamabazar:email_settings';

    /** Runtime mailer name configured from the admin SMTP settings. */
    public const RUNTIME_MAILER = 'mamabazar_smtp';

    /** Standard recognized SMTP ports. */
    public const STANDARD_PORTS = [465, 587, 25, 2525];

    /**
     * Mail provider configuration presets.
     *
     * @var array<string, array{name: string, description: string, default_host?: string, default_port: int, default_encryption: string, default_timeout: int, ports: array<int, array{encryption: string, label: string}>, notes: string}>
     */
    public const PROVIDER_PRESETS = [
        'cpanel' => [
            'name' => 'cPanel / Custom Domain Email',
            'description' => 'Standard configuration for custom domain mailboxes hosted on cPanel, DirectAdmin, Plesk, or standard Linux mail servers.',
            'default_host' => 'mail.mama-bazar.com',
            'default_port' => 465,
            'default_encryption' => 'ssl',
            'default_timeout' => 15,
            'ports' => [
                465 => ['encryption' => 'ssl', 'label' => 'Port 465 (SSL/TLS - Implicit, Recommended)'],
                587 => ['encryption' => 'tls', 'label' => 'Port 587 (STARTTLS - Explicit)'],
            ],
            'notes' => 'Requires the full email address as the username. Standard outgoing configuration matches cPanel "Connect Devices".',
        ],
        'gmail' => [
            'name' => 'Gmail / Google Workspace',
            'description' => 'For personal @gmail.com accounts and Google Workspace custom domains with SMTP relay.',
            'default_host' => 'smtp.gmail.com',
            'default_port' => 587,
            'default_encryption' => 'tls',
            'default_timeout' => 15,
            'ports' => [
                587 => ['encryption' => 'tls', 'label' => 'Port 587 (STARTTLS, Recommended)'],
                465 => ['encryption' => 'ssl', 'label' => 'Port 465 (SSL/TLS)'],
            ],
            'notes' => 'Requires a 16-character Google App Password (requires 2-Step Verification). Normal Google account passwords will be rejected.',
        ],
        'other' => [
            'name' => 'Other SMTP Provider',
            'description' => 'For SendGrid, Mailgun, Amazon SES, Postmark, Brevo, or custom mail relays.',
            'default_host' => '',
            'default_port' => 587,
            'default_encryption' => 'tls',
            'default_timeout' => 15,
            'ports' => [
                587 => ['encryption' => 'tls', 'label' => 'Port 587 (STARTTLS, Standard)'],
                465 => ['encryption' => 'ssl', 'label' => 'Port 465 (SSL/TLS)'],
            ],
            'notes' => 'Enter the SMTP endpoint, submission port, and credentials provided by your third-party mail service.',
        ],
    ];

    /**
     * Per-event automation toggles shown on the Email Automation page.
     *
     * @var array<string, array{label: string, description: string, group: string}>
     */
    public const AUTOMATIONS = [
        'email_auto_account_otp' => ['label' => 'Account Verification OTP', 'description' => 'Send a one-time code when a new account registers.', 'group' => 'Account & Security'],
        'email_auto_welcome' => ['label' => 'Welcome Email', 'description' => 'Send after the customer verifies their email.', 'group' => 'Account & Security'],
        'email_auto_login_otp' => ['label' => 'Passwordless Login OTP', 'description' => 'Allow customers to request a sign-in code by email.', 'group' => 'Account & Security'],
        'email_auto_password_reset' => ['label' => 'Password Reset Link', 'description' => 'Email a secure reset link when requested.', 'group' => 'Account & Security'],
        'email_auto_security_notice' => ['label' => 'Account Security Notices', 'description' => 'Notify customers when their password or email changes.', 'group' => 'Account & Security'],
        'email_auto_order_created' => ['label' => 'Order Confirmation', 'description' => 'Send when a valid order is placed.', 'group' => 'Orders'],
        'email_auto_payment_confirmed' => ['label' => 'Payment Confirmation', 'description' => 'Send only after an admin verifies payment.', 'group' => 'Orders'],
        'email_auto_order_confirmed' => ['label' => 'Order Confirmed', 'description' => 'Status changes to Confirmed.', 'group' => 'Order Status'],
        'email_auto_order_processing' => ['label' => 'Order Processing', 'description' => 'Status changes to Processing.', 'group' => 'Order Status'],
        'email_auto_order_shipped' => ['label' => 'Order Shipped', 'description' => 'Status changes to Shipped.', 'group' => 'Order Status'],
        'email_auto_order_out_for_delivery' => ['label' => 'Out for Delivery', 'description' => 'Status changes to Out for Delivery.', 'group' => 'Order Status'],
        'email_auto_order_delivered' => ['label' => 'Order Delivered', 'description' => 'Status changes to Delivered.', 'group' => 'Order Status'],
        'email_auto_order_cancelled' => ['label' => 'Order Cancelled', 'description' => 'Status changes to Cancelled.', 'group' => 'Order Status'],
        'email_auto_order_refunded' => ['label' => 'Refund Notification', 'description' => 'Status or payment changes to Refunded.', 'group' => 'Order Status'],
        'email_auto_invoice_pdf' => ['label' => 'Attach PDF Invoice', 'description' => 'Attach the invoice PDF to confirmation / payment emails once the order is invoice-ready.', 'group' => 'Orders'],
        'email_auto_review_invitation' => ['label' => 'Review Invitation', 'description' => 'Invite customers to review products a few days after delivery.', 'group' => 'Engagement'],
        'email_auto_contact_form' => ['label' => 'Contact Form Auto-Reply', 'description' => 'Acknowledge customer contact messages.', 'group' => 'Engagement'],
        'email_auto_contact_admin' => ['label' => 'Contact Form Admin Alert', 'description' => 'Forward new contact messages to the support inbox.', 'group' => 'Engagement'],
    ];

    /**
     * Default configuration. The SMTP password is intentionally empty —
     * it must be entered by an administrator or provided via MAIL_PASSWORD.
     */
    public static function defaults(): array
    {
        $defaultHost = config('mail.mailers.smtp.host');
        $defaultPort = config('mail.mailers.smtp.port');
        $defaultEncryption = config('mail.mailers.smtp.encryption');
        $defaultUsername = config('mail.mailers.smtp.username');

        return array_merge([
            'mail_mailer' => config('mail.default', 'smtp'),
            'mail_config_mode' => 'auto',
            'mail_provider' => 'cpanel',
            'mail_host' => ($defaultHost && $defaultHost !== '127.0.0.1') ? $defaultHost : 'mail.mama-bazar.com',
            'mail_port' => (int) ($defaultPort ?: 465),
            'mail_encryption' => $defaultEncryption ?: ((int) $defaultPort === 587 ? 'tls' : 'ssl'),
            'mail_username' => $defaultUsername ?: 'contact@mama-bazar.com',
            'mail_password' => '',
            'mail_from_address' => config('mail.from.address') ?: 'contact@mama-bazar.com',
            'mail_from_name' => config('mail.from.name') ?: 'Mama Bazar',
            'mail_reply_to' => '',
            'mail_timeout' => 15,
            'mail_enabled' => 1,

            'mail_last_tested_at' => null,
            'mail_last_success_at' => null,
            'mail_last_status' => null,
            'mail_last_error' => null,
            'mail_last_error_category' => null,
            'mail_last_latency_ms' => null,

            // Account policy
            'email_require_registration_email' => 1,
            'email_verification_enforced' => 1,
            'email_admin_notification_address' => '',
        ], array_map(fn () => 1, self::AUTOMATIONS), [
            // Passwordless login is opt-in.
            'email_auto_login_otp' => 0,
        ]);
    }

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
            // Safe auto-migration: If mail_password in DB is legacy plaintext, encrypt it immediately
            if (isset($dbRows['mail_password']) && ! empty($dbRows['mail_password']) && ! self::isCiphertextPayload((string) $dbRows['mail_password'])) {
                try {
                    $plain = (string) $dbRows['mail_password'];
                    $encrypted = Crypt::encryptString($plain);
                    SiteSetting::where('key', 'mail_password')->update(['value' => $encrypted]);
                    $dbRows['mail_password'] = $encrypted;
                    Log::info('Legacy plaintext SMTP password has been safely migrated to encrypted storage.');
                } catch (Throwable $e) {
                    // Do nothing on failure
                }
            }

            foreach ($defaults as $key => $defaultVal) {
                $settings[$key] = array_key_exists($key, $dbRows) && $dbRows[$key] !== null && $dbRows[$key] !== ''
                    ? $dbRows[$key]
                    : $defaultVal;
            }

            return $settings;
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return $all[$key] ?? $default;
    }

    /**
     * Settings safe to render in the admin UI (the password is never included).
     */
    public static function forDisplay(): array
    {
        $settings = self::all();
        unset($settings['mail_password']);
        $settings['password_source'] = self::passwordSource();

        $port = (int) ($settings['mail_port'] ?? 465);
        $settings['is_port_standard'] = self::isStandardPort($port);
        $settings['is_port_typo'] = self::isTypoPort($port);
        $settings['recommended_port'] = self::recommendedPortForEncryption($settings['mail_encryption'] ?? 'ssl');
        $settings['recommended_encryption'] = self::recommendedEncryptionForPort($port);
        $settings['provider_presets'] = self::PROVIDER_PRESETS;

        return $settings;
    }

    public static function isSendingEnabled(): bool
    {
        return (bool) self::get('mail_enabled', 1);
    }

    public static function isAutomationEnabled(string $triggerKey): bool
    {
        if (! self::isSendingEnabled()) {
            return false;
        }

        $settingKey = str_starts_with($triggerKey, 'email_auto_') ? $triggerKey : 'email_auto_'.$triggerKey;

        return (bool) self::get($settingKey, 0);
    }

    public static function requiresRegistrationEmail(): bool
    {
        return (bool) self::get('email_require_registration_email', 1);
    }

    public static function verificationEnforced(): bool
    {
        return (bool) self::get('email_verification_enforced', 1);
    }

    /**
     * Inbox that receives internal alerts (e.g. contact form). Falls back to
     * the public support email from Business Information.
     */
    public static function adminNotificationAddress(): ?string
    {
        $address = trim((string) self::get('email_admin_notification_address', ''));
        if ($address === '') {
            $address = (string) (BusinessSettingService::get('support_email') ?: BusinessSettingService::get('primary_email'));
        }

        return filter_var($address, FILTER_VALIDATE_EMAIL) ? $address : null;
    }

    /**
     * Where the SMTP password comes from: encrypted database value,
     * MAIL_PASSWORD environment variable, or nowhere.
     */
    public static function passwordSource(): string
    {
        if (! empty(self::get('mail_password'))) {
            return 'database';
        }

        return ! empty(config('mail.mailers.smtp.password')) ? 'environment' : 'none';
    }

    /**
     * Check if a string has the structural signature of a Laravel encrypted payload.
     */
    public static function isCiphertextPayload(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        $decoded = base64_decode($value, true);
        if (! $decoded) {
            return false;
        }

        $json = json_decode($decoded, true);

        return is_array($json) && isset($json['iv'], $json['value'], $json['mac']);
    }

    /**
     * Verify whether a string is valid ciphertext that can be decrypted with the current APP_KEY.
     */
    public static function isEncrypted(?string $value): bool
    {
        if (! self::isCiphertextPayload($value)) {
            return false;
        }

        try {
            Crypt::decryptString((string) $value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Explicit safe migration method for legacy plaintext passwords.
     */
    public static function migrateLegacyPlaintextPassword(): bool
    {
        try {
            $row = SiteSetting::where('key', 'mail_password')->first();
            if (! $row || empty($row->value)) {
                return false;
            }

            if (! self::isCiphertextPayload((string) $row->value)) {
                $row->value = Crypt::encryptString((string) $row->value);
                $row->save();
                self::clearCache();
                Log::info('Legacy plaintext SMTP password migrated to encrypted storage.');

                return true;
            }
        } catch (Throwable $e) {
            Log::warning('Legacy plaintext SMTP password migration failed: '.$e->getMessage());
        }

        return false;
    }

    public static function getDecryptedPassword(): string
    {
        $encrypted = self::get('mail_password');
        if (empty($encrypted)) {
            return (string) (config('mail.mailers.smtp.password') ?? '');
        }

        // If legacy plaintext was retrieved, migrate and return
        if (! self::isCiphertextPayload((string) $encrypted)) {
            try {
                $plain = (string) $encrypted;
                $encrypted = Crypt::encryptString($plain);
                SiteSetting::updateOrCreate(['key' => 'mail_password'], ['value' => $encrypted]);
                self::clearCache();

                return $plain;
            } catch (Throwable) {
                return (string) $encrypted;
            }
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable $e) {
            Log::warning('Stored SMTP password could not be decrypted (APP_KEY changed?). Re-enter it in Email Settings.');

            return '';
        }
    }

    public static function updateSettings(array $input): void
    {
        $defaults = self::defaults();

        foreach ($input as $key => $val) {
            if (! array_key_exists($key, $defaults)) {
                continue;
            }

            if ($key === 'mail_password') {
                if ($val === null || $val === '') {
                    continue;
                }
                // Do not blindly re-encrypt if already valid ciphertext
                if (! self::isCiphertextPayload((string) $val)) {
                    $val = Crypt::encryptString((string) $val);
                }
            }

            $cleanVal = is_bool($val) ? ($val ? '1' : '0') : (is_string($val) ? trim($val) : (string) $val);

            SiteSetting::updateOrCreate(['key' => $key], ['value' => $cleanVal]);
        }

        self::clearCache();
    }

    public static function clearPassword(): void
    {
        SiteSetting::where('key', 'mail_password')->delete();
        self::clearCache();
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Laravel mailer config array for the admin-managed SMTP server.
     */
    public static function smtpConfig(): array
    {
        $settings = self::all();
        $port = (int) ($settings['mail_port'] ?? config('mail.mailers.smtp.port', 465));
        $encryption = self::normalizeEncryption($settings['mail_encryption'] ?? config('mail.mailers.smtp.encryption', 'ssl'), $port);

        return [
            'transport' => 'smtp',
            'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'host' => (string) ($settings['mail_host'] ?: config('mail.mailers.smtp.host', 'mail.mama-bazar.com')),
            'port' => $port,
            'username' => ($settings['mail_username'] ?: config('mail.mailers.smtp.username')) ?: null,
            'password' => self::getDecryptedPassword() ?: null,
            'timeout' => (int) ($settings['mail_timeout'] ?? config('mail.mailers.smtp.timeout', 30)),
            'require_tls' => $encryption === 'tls',
            'auto_tls' => $encryption !== 'none',
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
        ];
    }

    /**
     * Normalized encryption mode: "ssl" (implicit TLS on port 465), "tls" (STARTTLS on port 587/25) or "none".
     */
    public static function normalizeEncryption(?string $value, int $port): string
    {
        $value = strtolower(trim((string) $value));

        if ($port === 465) {
            return $value === 'none' ? 'none' : 'ssl';
        }

        if ($port === 587) {
            return $value === 'none' ? 'none' : 'tls';
        }

        if (in_array($value, ['tls', 'starttls'], true)) {
            return 'tls';
        }

        if ($value === 'ssl') {
            return 'ssl';
        }

        return 'none';
    }

    /**
     * Standard port check.
     */
    public static function isStandardPort(int $port): bool
    {
        return in_array($port, self::STANDARD_PORTS, true);
    }

    /**
     * Typo check: Detects if port 456 was entered instead of 465.
     */
    public static function isTypoPort(int $port): ?int
    {
        return $port === 456 ? 465 : null;
    }

    /**
     * Recommended port given an encryption mode.
     */
    public static function recommendedPortForEncryption(?string $encryption): int
    {
        $encryption = strtolower(trim((string) $encryption));

        return match ($encryption) {
            'ssl' => 465,
            'none' => 25,
            default => 587,
        };
    }

    /**
     * Recommended encryption given a port.
     */
    public static function recommendedEncryptionForPort(int $port): string
    {
        return match ($port) {
            465 => 'ssl',
            587, 2525 => 'tls',
            25 => 'none',
            default => 'tls',
        };
    }

    /**
     * Check if port and encryption are compatible. Returns error message or null if valid.
     */
    public static function validatePortEncryptionCompatibility(int $port, string $encryption): ?string
    {
        $encryption = strtolower(trim($encryption));

        if ($port === 465 && $encryption === 'tls') {
            return 'Port 465 requires SSL/TLS (implicit TLS). For STARTTLS, use port 587.';
        }

        if ($port === 465 && $encryption === 'none') {
            return 'Port 465 requires SSL/TLS encryption. Unencrypted connections are not supported on port 465.';
        }

        if ($port === 587 && $encryption === 'ssl') {
            return 'Port 587 requires STARTTLS encryption. For implicit SSL/TLS, use port 465.';
        }

        return null;
    }

    /**
     * Human-readable label for connection failure or success category.
     */
    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            'connection_refused' => 'Connection refused',
            'timed_out', 'connection_timeout' => 'Connection timed out',
            'dns_failure' => 'DNS resolution failure',
            'tls_failure' => 'TLS/SSL handshake failure',
            'auth_failure' => 'SMTP authentication failure',
            'sender_rejected' => 'Sender rejected',
            'security_blocked' => 'Security blocked (SSRF)',
            'success' => 'Connection successful',
            default => 'Connection failed',
        };
    }

    /**
     * SSRF defense: Validate that the SMTP host is a public domain or permissible IP.
     *
     * @return array{allowed: bool, ip?: string, reason?: string, category?: string}
     */
    public static function validateHostSecurity(string $host): array
    {
        $clean = trim($host);
        $clean = (string) preg_replace('#^[a-z]+://#i', '', $clean);
        $clean = (string) preg_replace('/:[0-9]+$/', '', $clean);

        if ($clean === '') {
            return [
                'allowed' => false,
                'reason' => 'SMTP host cannot be empty.',
                'category' => 'unknown',
            ];
        }

        if (! filter_var($clean, FILTER_VALIDATE_IP) && ! preg_match('/^[a-zA-Z0-9.-]+$/', $clean)) {
            return [
                'allowed' => false,
                'reason' => 'SMTP hostname contains invalid characters.',
                'category' => 'security_blocked',
            ];
        }

        $ip = filter_var($clean, FILTER_VALIDATE_IP) ? $clean : @gethostbyname($clean);
        if ($ip === $clean && ! filter_var($clean, FILTER_VALIDATE_IP)) {
            return [
                'allowed' => false,
                'reason' => "Server DNS could not resolve '{$clean}' to an IP address.",
                'category' => 'dns_failure',
            ];
        }

        $isPrivateOrReserved = ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isPrivateOrReserved) {
            $isLocalTesting = app()->environment('local', 'testing');
            if ($isLocalTesting && in_array($clean, ['localhost', '127.0.0.1', '::1'], true)) {
                return ['allowed' => true, 'ip' => $ip];
            }

            return [
                'allowed' => false,
                'reason' => "Connecting to private, internal, or reserved network addresses ({$ip}) is prohibited for security.",
                'category' => 'security_blocked',
            ];
        }

        return ['allowed' => true, 'ip' => $ip];
    }

    /**
     * Probe connectivity on supported SMTP ports for a target host.
     *
     * @param  array<int>  $ports
     * @return array{success: bool, host: string, error?: string, ports: array<int, array<string, mixed>>, open_ports: array<int>, recommended: array{port: int, encryption: string, label: string}|null}
     */
    public static function probeHostPorts(string $host, array $ports = [465, 587], int $timeout = 3): array
    {
        $security = self::validateHostSecurity($host);
        if (! $security['allowed']) {
            return [
                'success' => false,
                'host' => $host,
                'error' => $security['reason'] ?? 'Host security check failed',
                'ports' => [],
                'open_ports' => [],
                'recommended' => null,
            ];
        }

        $results = [];
        $openPorts = [];

        foreach ($ports as $port) {
            $t0 = microtime(true);
            $errno = 0;
            $errstr = '';
            $fp = @fsockopen($host, (int) $port, $errno, $errstr, (float) $timeout);
            $latency = (int) round((microtime(true) - $t0) * 1000);

            if ($fp) {
                fclose($fp);
                $results[] = [
                    'port' => (int) $port,
                    'encryption' => (int) $port === 465 ? 'ssl' : 'tls',
                    'status' => 'open',
                    'latency_ms' => $latency,
                ];
                $openPorts[] = (int) $port;
            } else {
                $results[] = [
                    'port' => (int) $port,
                    'encryption' => (int) $port === 465 ? 'ssl' : 'tls',
                    'status' => 'closed',
                    'latency_ms' => $latency,
                    'error' => $errstr ?: "errno: {$errno}",
                ];
            }
        }

        $recommended = null;
        if (in_array(465, $openPorts, true)) {
            $recommended = ['port' => 465, 'encryption' => 'ssl', 'label' => 'Port 465 (SSL/TLS - Verified Open)'];
        } elseif (in_array(587, $openPorts, true)) {
            $recommended = ['port' => 587, 'encryption' => 'tls', 'label' => 'Port 587 (STARTTLS - Verified Open)'];
        }

        return [
            'success' => true,
            'host' => $host,
            'ports' => $results,
            'open_ports' => $openPorts,
            'recommended' => $recommended,
        ];
    }

    /**
     * Name of the mailer to send through. Registers the admin SMTP settings
     * as a dedicated runtime mailer so environment config stays untouched.
     * In the test environment the configured (array) mailer is always used.
     */
    public static function mailerName(): string
    {
        if (app()->runningUnitTests()) {
            return (string) config('mail.default', 'array');
        }

        $driver = (string) self::get('mail_mailer', 'smtp');
        if ($driver === 'log') {
            return 'log';
        }
        if ($driver === 'sendmail') {
            return 'sendmail';
        }

        Config::set('mail.mailers.'.self::RUNTIME_MAILER, self::smtpConfig());
        app('mail.manager')->purge(self::RUNTIME_MAILER);

        return self::RUNTIME_MAILER;
    }

    /**
     * Probe the mail server: TCP/TLS handshake + authentication for SMTP, or binary check for Sendmail.
     *
     * @param  array<string, mixed>|null  $customConfig
     * @return array{success: bool, latency_ms: int, message: string, error_type?: string, category?: string, category_label?: string, diagnostic?: string}
     */
    public static function testConnection(?array $customConfig = null): array
    {
        $driver = (string) self::get('mail_mailer', 'smtp');

        if ($driver === 'log') {
            return [
                'success' => true,
                'latency_ms' => 0,
                'category' => 'success',
                'category_label' => 'Log driver active',
                'message' => 'Mail driver is set to "log". Outgoing emails are recorded in Laravel logs without opening network connections.',
            ];
        }

        if ($driver === 'sendmail') {
            $path = config('mail.mailers.sendmail.path') ?: '/usr/sbin/sendmail -bs';
            $binary = explode(' ', trim($path))[0];
            if (! file_exists($binary) || ! is_executable($binary)) {
                return [
                    'success' => false,
                    'latency_ms' => 0,
                    'error_type' => 'binary_missing',
                    'category' => 'binary_missing',
                    'category_label' => 'Sendmail missing',
                    'diagnostic' => "Sendmail binary not found or not executable at '{$binary}'.",
                    'message' => "Sendmail binary not found or not executable at '{$binary}'.",
                ];
            }

            return [
                'success' => true,
                'latency_ms' => 1,
                'category' => 'success',
                'category_label' => 'Sendmail binary found',
                'message' => "Sendmail binary is available and executable at '{$binary}'. Emails will route locally via system MTA.",
            ];
        }

        $config = self::smtpConfig();
        if (! empty($customConfig)) {
            $config = array_merge($config, $customConfig);
            $port = (int) ($config['port'] ?? 465);
            $encryption = self::normalizeEncryption($customConfig['encryption'] ?? ($port === 587 ? 'tls' : 'ssl'), $port);
            $config['port'] = $port;
            $config['scheme'] = $encryption === 'ssl' ? 'smtps' : 'smtp';
            $config['encryption'] = $encryption === 'none' ? null : $encryption;
            $config['require_tls'] = $encryption === 'tls';
            $config['auto_tls'] = $encryption !== 'none';
        }
        $config['timeout'] = (int) ($config['timeout'] ?: 15);
        $startTime = microtime(true);

        try {
            if (empty($config['host'])) {
                throw new \RuntimeException('SMTP host is not configured.');
            }

            // Security SSRF check
            $securityCheck = self::validateHostSecurity((string) $config['host']);
            if (! $securityCheck['allowed']) {
                $category = $securityCheck['category'] ?? 'security_blocked';
                $reason = $securityCheck['reason'] ?? 'Host blocked by security policy.';
                self::recordStatus('failed', $reason, 0, $category);

                return [
                    'success' => false,
                    'latency_ms' => 0,
                    'error_type' => $category,
                    'category' => $category,
                    'category_label' => self::categoryLabel($category),
                    'diagnostic' => 'The destination host address is blocked to prevent Server-Side Request Forgery (SSRF).',
                    'message' => $reason,
                ];
            }

            if (! empty($config['username']) && empty($config['password'])) {
                throw new \RuntimeException('SMTP password is not set. Enter it in Email Settings or set MAIL_PASSWORD.');
            }

            $transport = self::buildTransport($config);
            $transport->start();
            $transport->stop();

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            self::recordStatus('connected', null, $latency, 'success');

            return [
                'success' => true,
                'latency_ms' => $latency,
                'category' => 'success',
                'category_label' => 'Connection successful',
                'message' => "Connected and authenticated with {$config['host']}:{$config['port']} ({$latency} ms). This confirms SMTP access only, not inbox delivery.",
            ];
        } catch (Throwable $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $safe = self::sanitizeError($e->getMessage());
            $analysis = self::diagnoseError($e->getMessage(), $config);

            $detailedError = $safe;
            if (! empty($analysis['diagnostic'])) {
                $detailedError .= "\n[Hint: {$analysis['diagnostic']}]";
            }

            self::recordStatus('failed', $detailedError, $latency, $analysis['type']);

            Log::warning('SMTP probe failed', [
                'host' => $config['host'],
                'port' => $config['port'],
                'scheme' => $config['scheme'],
                'error_type' => $analysis['type'],
                'error' => $safe,
            ]);

            return [
                'success' => false,
                'latency_ms' => $latency,
                'error_type' => $analysis['type'],
                'category' => $analysis['type'],
                'category_label' => $analysis['category_label'] ?? self::categoryLabel($analysis['type']),
                'diagnostic' => $analysis['diagnostic'],
                'message' => 'SMTP connection failed: '.$safe.(! empty($analysis['diagnostic']) ? ' (Hint: '.$analysis['diagnostic'].')' : ''),
            ];
        }
    }

    /**
     * Diagnose common SMTP connection failures and return actionable troubleshooting hints.
     *
     * @param  array<string, mixed>  $config
     * @return array{type: string, category: string, category_label: string, diagnostic: string}
     */
    public static function diagnoseError(string $rawError, array $config): array
    {
        $host = $config['host'] ?? 'unknown';
        $port = (int) ($config['port'] ?? 0);
        $encryption = (string) ($config['encryption'] ?? 'ssl');
        $type = 'unknown';
        $label = 'Connection failed';
        $diagnostic = '';

        if (stripos($rawError, 'private, internal, or reserved') !== false || (stripos($rawError, 'security') !== false && stripos($rawError, 'prohibited') !== false)) {
            $type = 'security_blocked';
            $label = 'Security blocked (SSRF)';
            $diagnostic = 'Connections to private, internal, or reserved network addresses are blocked for security.';
        } elseif (stripos($rawError, 'Connection refused') !== false || stripos($rawError, 'ECONNREFUSED') !== false) {
            $type = 'connection_refused';
            $label = 'Connection refused';
            if ($port === 456) {
                $diagnostic = "The host {$host}:456 actively rejected the TCP connection. Port 456 is not a standard SMTP port and is likely a transposition typo for standard SSL/TLS port 465. Switch your port to 465.";
            } else {
                $isGoogle = str_contains(strtolower($host), 'gmail') || str_contains(strtolower($host), 'google');
                $diagnostic = "The host {$host}:{$port} actively rejected the TCP connection. On cPanel / WHM or VPS servers, this usually means: (1) cPanel WHM 'SMTP Restrictions' or CSF firewall (SMTP_BLOCK = 1) is blocking non-root outbound SMTP connections to external ports 25, 465, and 587. To fix: In WHM, go to 'Security Center › SMTP Restrictions' and disable it, or in CSF add your cPanel user to SMTP_ALLOWUSER; (2) Alternatively, set Mail Driver to 'sendmail' in Email Settings to send through local Exim without network socket blocks; (3) If connecting to the local server, try host 'localhost' or '127.0.0.1'.";
            }
        } elseif (stripos($rawError, 'timed out') !== false || stripos($rawError, 'Operation timed out') !== false || stripos($rawError, 'ETIMEDOUT') !== false) {
            $type = 'timed_out';
            $label = 'Connection timed out';
            $diagnostic = "Connection to {$host}:{$port} timed out without response. Your cloud or hosting provider firewall/security group is likely dropping outbound packets on port {$port}. Ask your host to unblock port {$port}, test port 587, or switch driver to 'sendmail'.";
        } elseif (stripos($rawError, 'getaddrinfo failed') !== false || stripos($rawError, 'Name or service not known') !== false || stripos($rawError, 'php_network_getaddresses') !== false || stripos($rawError, 'DNS resolution failed') !== false) {
            $type = 'dns_failure';
            $label = 'DNS resolution failure';
            $diagnostic = "Server DNS failed to resolve '{$host}'. Check /etc/resolv.conf and server DNS configuration.";
        } elseif (stripos($rawError, 'certificate verify failed') !== false || stripos($rawError, 'SSL') !== false || stripos($rawError, 'handshake') !== false || stripos($rawError, 'crypto') !== false || stripos($rawError, 'wrong version number') !== false) {
            $type = 'tls_failure';
            $label = 'TLS/SSL handshake failure';
            if ($port === 465 && $encryption === 'tls') {
                $diagnostic = 'Port 465 requires SSL/TLS (implicit TLS). STARTTLS negotiation cannot be performed on port 465. Change encryption to SSL/TLS or port to 587.';
            } elseif ($port === 587 && $encryption === 'ssl') {
                $diagnostic = 'Port 587 requires STARTTLS. Implicit SSL cannot be negotiated on port 587 before STARTTLS is issued. Change encryption to STARTTLS or port to 465.';
            } else {
                $diagnostic = "TLS/SSL negotiation failed with {$host}:{$port}. Check that the SSL certificate covers {$host}, the correct port is selected (465 for SSL, 587 for TLS), and system CA certificates are up to date.";
            }
        } elseif (stripos($rawError, '535') !== false || stripos($rawError, 'authentication failed') !== false || stripos($rawError, 'incorrect authentication') !== false || stripos($rawError, 'Username and Password not accepted') !== false) {
            $type = 'auth_failure';
            $label = 'SMTP authentication failure';
            $isGoogle = str_contains(strtolower($host), 'gmail') || str_contains(strtolower($host), 'google');
            $extra = $isGoogle
                ? " For Google / Gmail: You MUST generate a 16-character 'App Password' from myaccount.google.com/apppasswords (requires 2-Step Verification) instead of your regular Gmail password."
                : '';
            $diagnostic = "SMTP authentication was rejected. Ensure the username is the full email address ('{$config['username']}') and the password is correct.{$extra}";
        } elseif (stripos($rawError, '550') !== false || stripos($rawError, '553') !== false || stripos($rawError, '554') !== false || stripos($rawError, 'relay access denied') !== false || stripos($rawError, 'Sender address rejected') !== false) {
            $type = 'sender_rejected';
            $label = 'Sender rejected';
            $diagnostic = 'The SMTP server rejected the sender address or denied relaying. Ensure the sender address matches an authorized mailbox for this account.';
        }

        return [
            'type' => $type,
            'category' => $type,
            'category_label' => $label,
            'diagnostic' => $diagnostic,
        ];
    }

    protected static function buildTransport(array $config): EsmtpTransport
    {
        $transport = (new EsmtpTransportFactory)->create(new Dsn(
            $config['scheme'],
            $config['host'],
            $config['username'],
            $config['password'],
            $config['port'],
            [
                'require_tls' => $config['require_tls'] ? 'true' : 'false',
                'auto_tls' => $config['auto_tls'] ? 'true' : 'false',
            ]
        ));

        $stream = $transport->getStream();
        if ($stream instanceof SocketStream) {
            $stream->setTimeout((float) $config['timeout']);
        }

        return $transport;
    }

    public static function recordSendFailure(string $message): void
    {
        self::recordStatus('failed', self::sanitizeError($message), null, 'send_failed');
    }

    protected static function recordStatus(string $status, ?string $error, ?int $latency, ?string $category = null): void
    {
        $now = now()->toIso8601String();
        $values = [
            'mail_last_tested_at' => $now,
            'mail_last_status' => $status,
            'mail_last_error' => $error,
            'mail_last_error_category' => $category,
        ];
        if ($status === 'connected') {
            $values['mail_last_success_at'] = $now;
        }
        if ($latency !== null) {
            $values['mail_last_latency_ms'] = (string) $latency;
        }

        foreach ($values as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        self::clearCache();
    }

    /**
     * Strip credentials from transport errors before they are stored or shown.
     */
    public static function sanitizeError(string $message): string
    {
        $password = self::getDecryptedPassword();
        if ($password !== '') {
            $message = str_replace([$password, base64_encode($password)], '[redacted]', $message);
        }

        $message = preg_replace('/(AUTH\s+(?:PLAIN|LOGIN)\s+)\S+/i', '$1[redacted]', $message) ?? $message;

        return mb_substr(trim($message), 0, 500);
    }

    /**
     * Static checks on the sender identity.
     *
     * @return array<int, array{label: string, ok: bool, detail: string}>
     */
    public static function senderChecks(): array
    {
        $settings = self::all();
        $from = strtolower((string) $settings['mail_from_address']);
        $username = strtolower((string) $settings['mail_username']);
        $fromDomain = substr(strrchr($from, '@') ?: '', 1);
        $userDomain = substr(strrchr($username, '@') ?: '', 1);
        $port = (int) $settings['mail_port'];
        $encryption = self::normalizeEncryption($settings['mail_encryption'], $port);

        return [
            [
                'label' => 'Sender address is valid',
                'ok' => (bool) filter_var($from, FILTER_VALIDATE_EMAIL),
                'detail' => $from ?: 'Not set',
            ],
            [
                'label' => 'Sender domain matches SMTP account domain',
                'ok' => $fromDomain !== '' && $fromDomain === $userDomain,
                'detail' => $fromDomain === $userDomain
                    ? "Both use {$fromDomain}"
                    : "From domain \"{$fromDomain}\" differs from SMTP login domain \"{$userDomain}\" — many servers reject or spam-flag this.",
            ],
            [
                'label' => 'Encrypted connection',
                'ok' => $encryption !== 'none',
                'detail' => match ($encryption) {
                    'ssl' => "Implicit SSL/TLS on port {$port}",
                    'tls' => "STARTTLS required on port {$port}",
                    default => 'Unencrypted — credentials would travel in plain text',
                },
            ],
            [
                'label' => 'SMTP password configured',
                'ok' => self::passwordSource() !== 'none',
                'detail' => match (self::passwordSource()) {
                    'database' => 'Stored encrypted in the database',
                    'environment' => 'Provided by MAIL_PASSWORD environment variable',
                    default => 'Missing — emails cannot be sent',
                },
            ],
        ];
    }

    /**
     * Live DNS lookups for SPF / DMARC / DKIM / MX on the sender domain.
     * Results are informational — passing checks do not guarantee inbox placement.
     *
     * @return array<int, array{label: string, status: string, detail: string}>
     */
    public static function deliverabilityChecks(?string $domain = null): array
    {
        $from = (string) self::get('mail_from_address', '');
        $domain = $domain ?: substr(strrchr($from, '@') ?: '', 1);
        if ($domain === '' || ! function_exists('dns_get_record')) {
            return [];
        }

        $txt = fn (string $host) => collect(@dns_get_record($host, DNS_TXT) ?: [])
            ->map(fn ($r) => $r['txt'] ?? implode('', $r['entries'] ?? []))
            ->filter()
            ->values();

        $spf = $txt($domain)->first(fn ($v) => str_starts_with(strtolower($v), 'v=spf1'));
        $dmarc = $txt('_dmarc.'.$domain)->first(fn ($v) => str_starts_with(strtolower($v), 'v=dmarc1'));
        $dkim = $txt('default._domainkey.'.$domain)->first(fn ($v) => str_contains(strtolower($v), 'p='));
        $mx = collect(@dns_get_record($domain, DNS_MX) ?: [])->pluck('target')->filter()->implode(', ');

        return [
            ['label' => 'SPF record', 'status' => $spf ? 'pass' : 'missing', 'detail' => $spf ?: "No v=spf1 TXT record found on {$domain}"],
            ['label' => 'DKIM (selector "default")', 'status' => $dkim ? 'pass' : 'unknown', 'detail' => $dkim ? 'Public key published at default._domainkey' : 'Not found at default._domainkey — enable DKIM in cPanel › Email Deliverability (selector may differ).'],
            ['label' => 'DMARC policy', 'status' => $dmarc ? 'pass' : 'missing', 'detail' => $dmarc ?: "No v=DMARC1 TXT record at _dmarc.{$domain}"],
            ['label' => 'MX records', 'status' => $mx ? 'pass' : 'missing', 'detail' => $mx ?: 'No MX records (needed to receive bounces and replies)'],
        ];
    }
}
