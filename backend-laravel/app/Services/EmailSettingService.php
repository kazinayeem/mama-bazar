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
            'mail_host' => ($defaultHost && $defaultHost !== '127.0.0.1') ? $defaultHost : 'mail.mama-bazar.com',
            'mail_port' => (int) ($defaultPort ?: 465),
            'mail_encryption' => $defaultEncryption ?: ((int) $defaultPort === 587 ? 'tls' : 'ssl'),
            'mail_username' => $defaultUsername ?: 'contact@mama-bazar.com',
            'mail_password' => '',
            'mail_from_address' => config('mail.from.address') ?: 'contact@mama-bazar.com',
            'mail_from_name' => config('mail.from.name') ?: 'Mama Bazar',
            'mail_reply_to' => '',
            'mail_timeout' => (int) (config('mail.mailers.smtp.timeout') ?: 30),
            'mail_enabled' => 1,

            'mail_last_tested_at' => null,
            'mail_last_status' => null,
            'mail_last_error' => null,
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

    public static function getDecryptedPassword(): string
    {
        $encrypted = self::get('mail_password');
        if (empty($encrypted)) {
            return (string) (config('mail.mailers.smtp.password') ?? '');
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
                $val = Crypt::encryptString((string) $val);
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
     * Normalized encryption mode: "ssl" (implicit TLS), "tls" (STARTTLS) or "none".
     */
    public static function normalizeEncryption(?string $value, int $port): string
    {
        $value = strtolower(trim((string) $value));

        return match (true) {
            $value === 'ssl', $port === 465 && $value !== 'none' => 'ssl',
            in_array($value, ['tls', 'starttls'], true) => 'tls',
            default => 'none',
        };
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
     * Probe the SMTP server: TCP/TLS handshake + authentication, without sending.
     *
     * @param  array<string, mixed>|null  $customConfig
     * @return array{success: bool, latency_ms: int, message: string, error_type?: string, diagnostic?: string}
     */
    public static function testConnection(?array $customConfig = null): array
    {
        $config = self::smtpConfig();
        if (! empty($customConfig)) {
            $config = array_merge($config, $customConfig);
            $port = (int) ($config['port'] ?? 465);
            $encryption = self::normalizeEncryption($customConfig['encryption'] ?? ($port === 587 ? 'tls' : 'ssl'), $port);
            $config['port'] = $port;
            $config['scheme'] = $encryption === 'ssl' ? 'smtps' : 'smtp';
            $config['require_tls'] = $encryption === 'tls';
            $config['auto_tls'] = $encryption !== 'none';
        }
        $startTime = microtime(true);

        try {
            if ((string) self::get('mail_mailer', 'smtp') !== 'smtp') {
                throw new \RuntimeException('Mail driver is not SMTP; nothing to probe.');
            }
            if (empty($config['host'])) {
                throw new \RuntimeException('SMTP host is not configured.');
            }
            if (! empty($config['username']) && empty($config['password'])) {
                throw new \RuntimeException('SMTP password is not set. Enter it in Email Settings or set MAIL_PASSWORD.');
            }

            $transport = self::buildTransport($config);
            $transport->start();
            $transport->stop();

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            self::recordStatus('connected', null, $latency);

            return [
                'success' => true,
                'latency_ms' => $latency,
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

            self::recordStatus('failed', $detailedError, $latency);

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
                'diagnostic' => $analysis['diagnostic'],
                'message' => 'SMTP connection failed: '.$safe.(! empty($analysis['diagnostic']) ? ' (Hint: '.$analysis['diagnostic'].')' : ''),
            ];
        }
    }

    /**
     * Diagnose common SMTP connection failures and return actionable troubleshooting hints.
     *
     * @param  array<string, mixed>  $config
     * @return array{type: string, diagnostic: string}
     */
    public static function diagnoseError(string $rawError, array $config): array
    {
        $host = $config['host'] ?? 'unknown';
        $port = (int) ($config['port'] ?? 0);
        $type = 'unknown';
        $diagnostic = '';

        if (stripos($rawError, 'Connection refused') !== false || stripos($rawError, 'ECONNREFUSED') !== false) {
            $type = 'connection_refused';
            $diagnostic = "The host {$host}:{$port} actively rejected the TCP connection. On cPanel / WHM or VPS servers, this usually means: (1) cPanel WHM 'SMTP Restrictions' or CSF firewall (SMTP_BLOCK = 1) is blocking non-root outbound SMTP connections; (2) The server does not allow external loopback to its own public IP (try host 'localhost' or '127.0.0.1'); (3) Port {$port} is blocked by hosting firewall (try port 587 with STARTTLS/TLS).";
        } elseif (stripos($rawError, 'timed out') !== false || stripos($rawError, 'Operation timed out') !== false || stripos($rawError, 'ETIMEDOUT') !== false) {
            $type = 'connection_timeout';
            $diagnostic = "Connection to {$host}:{$port} timed out without response. Your cloud or hosting provider firewall/security group is likely dropping outbound packets on port {$port}. Ask your host to unblock port {$port} or test port 587.";
        } elseif (stripos($rawError, 'getaddrinfo failed') !== false || stripos($rawError, 'Name or service not known') !== false || stripos($rawError, 'php_network_getaddresses') !== false) {
            $type = 'dns_failure';
            $diagnostic = "Server DNS failed to resolve '{$host}'. Check /etc/resolv.conf and server DNS configuration.";
        } elseif (stripos($rawError, 'certificate verify failed') !== false || stripos($rawError, 'SSL') !== false || stripos($rawError, 'handshake') !== false) {
            $type = 'tls_failure';
            $diagnostic = "TLS/SSL negotiation failed with {$host}:{$port}. Check that the SSL certificate covers {$host} and system CA certificates are up to date.";
        } elseif (stripos($rawError, '535') !== false || stripos($rawError, 'authentication failed') !== false || stripos($rawError, 'incorrect authentication') !== false) {
            $type = 'auth_failure';
            $diagnostic = "SMTP authentication was rejected. Ensure the username is the full email address ('{$config['username']}') and the password is correct.";
        }

        return [
            'type' => $type,
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
        self::recordStatus('failed', self::sanitizeError($message), null);
    }

    protected static function recordStatus(string $status, ?string $error, ?int $latency): void
    {
        $values = [
            'mail_last_tested_at' => now()->toIso8601String(),
            'mail_last_status' => $status,
            'mail_last_error' => $error,
        ];
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
