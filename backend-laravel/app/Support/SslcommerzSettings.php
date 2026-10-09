<?php

namespace App\Support;

use App\Http\Middleware\EnsureAdminPermission;
use App\Models\PaymentMethod;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Protected configuration provider for SSLCOMMERZ.
 *
 * Credentials saved from the admin panel live encrypted (Crypt / APP_KEY) in
 * payment_methods.config['gateway'] of the `sslcommerz` row and take
 * precedence over the SSLCOMMERZ_* environment values. Secrets are only
 * reachable through storeId()/storePassword() and are never serialized.
 */
final class SslcommerzSettings implements \JsonSerializable
{
    public const METHOD_CODE = 'sslcommerz';

    public const CURRENCIES = ['BDT'];

    public const SOURCE_DATABASE = 'database';

    public const SOURCE_ENVIRONMENT = 'environment';

    public const SOURCE_NONE = 'none';

    /**
     * @param  array{ok?: bool, message?: string, mode?: string, at?: string}  $lastTest
     */
    private function __construct(
        private readonly ?string $storeId,
        private readonly ?string $storePassword,
        public readonly string $storeIdSource,
        public readonly string $storePasswordSource,
        public readonly bool $sandbox,
        public readonly string $currency,
        public readonly bool $serverEnabled,
        public readonly bool $methodEnabled,
        public readonly bool $maintenanceMode,
        public readonly array $lastTest,
        private readonly ?string $validatedFingerprint,
        public readonly ?string $updatedAt,
    ) {}

    public static function load(): self
    {
        $method = self::method();
        $gateway = self::gatewayConfig($method);
        $env = (array) config('services.sslcommerz', []);

        [$storeId, $storeIdSource] = self::resolveSecret($gateway['store_id'] ?? null, $env['store_id'] ?? null);
        [$storePassword, $passwordSource] = self::resolveSecret($gateway['store_password'] ?? null, $env['store_password'] ?? null);

        $currency = strtoupper((string) ($gateway['currency'] ?? $env['currency'] ?? 'BDT'));

        return new self(
            storeId: $storeId,
            storePassword: $storePassword,
            storeIdSource: $storeIdSource,
            storePasswordSource: $passwordSource,
            sandbox: array_key_exists('sandbox', $gateway) ? (bool) $gateway['sandbox'] : filter_var($env['sandbox'] ?? true, FILTER_VALIDATE_BOOLEAN),
            currency: in_array($currency, self::CURRENCIES, true) ? $currency : 'BDT',
            serverEnabled: filter_var($env['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            methodEnabled: (bool) ($method?->enabled ?? false),
            maintenanceMode: (bool) ($method?->maintenance_mode ?? false),
            lastTest: is_array($gateway['last_test'] ?? null) ? $gateway['last_test'] : [],
            validatedFingerprint: isset($gateway['validated_fingerprint']) ? (string) $gateway['validated_fingerprint'] : null,
            updatedAt: isset($gateway['updated_at']) ? (string) $gateway['updated_at'] : null,
        );
    }

    /**
     * Guards against code being deployed before its migration has run.
     */
    public static function transactionsTableExists(): bool
    {
        return once(function (): bool {
            try {
                return Schema::hasTable('sslcommerz_transactions');
            } catch (\Throwable) {
                return false;
            }
        });
    }

    public static function method(): ?PaymentMethod
    {
        return PaymentMethod::query()->where('code', self::METHOD_CODE)->first();
    }

    public function storeId(): ?string
    {
        return $this->storeId;
    }

    public function storePassword(): ?string
    {
        return $this->storePassword;
    }

    public function hasCredentials(): bool
    {
        return filled($this->storeId) && filled($this->storePassword);
    }

    public function mode(): string
    {
        return $this->sandbox ? 'sandbox' : 'live';
    }

    public function baseUrl(?string $mode = null): string
    {
        $mode ??= $this->mode();

        return rtrim((string) config($mode === 'live' ? 'services.sslcommerz.live_url' : 'services.sslcommerz.sandbox_url'), '/');
    }

    /**
     * Identifies the exact credential + mode combination a successful test applied to,
     * so any later change to the store ID, password or mode invalidates the result.
     */
    public function fingerprint(): ?string
    {
        if (! $this->hasCredentials()) {
            return null;
        }

        return hash_hmac('sha256', implode('|', [$this->storeId, $this->storePassword, $this->mode()]), (string) config('app.key'));
    }

    public function isVerified(): bool
    {
        $fingerprint = $this->fingerprint();

        return $fingerprint !== null && $this->validatedFingerprint !== null
            && hash_equals($this->validatedFingerprint, $fingerprint);
    }

    public function isLiveVerified(): bool
    {
        return ! $this->sandbox && $this->isVerified();
    }

    /**
     * Returns why the gateway cannot be enabled for customers, or null when it can.
     */
    public function enableBlocker(?object $user = null): ?string
    {
        if (! $this->serverEnabled) {
            return 'SSLCOMMERZ is disabled on the server (SSLCOMMERZ_ENABLED=false).';
        }
        if (! self::transactionsTableExists()) {
            return 'Database migrations are pending (sslcommerz_transactions table is missing). Run php artisan migrate.';
        }
        if (! $this->hasCredentials()) {
            return 'Configure the SSLCOMMERZ Store ID and Store Password before enabling the gateway.';
        }
        if (! $this->sandbox) {
            if ($user !== null && ! EnsureAdminPermission::allows($user, ['payment_methods.enable_live'])) {
                return 'Only a Super Admin or a member with "Activate Live Payments" permission can enable live payments.';
            }
            if (! $this->isVerified()) {
                return 'Run Test Configuration successfully in Live mode before enabling live payments.';
            }
        }

        return null;
    }

    public function isCheckoutReady(): bool
    {
        return $this->methodEnabled && ! $this->maintenanceMode && $this->enableBlocker() === null;
    }

    /**
     * @return 'not_configured'|'configured'|'sandbox_verified'|'live_unverified'|'live_verified'
     */
    public function readiness(): string
    {
        if (! $this->hasCredentials()) {
            return 'not_configured';
        }
        if ($this->sandbox) {
            return $this->isVerified() ? 'sandbox_verified' : 'configured';
        }

        return $this->isVerified() ? 'live_verified' : 'live_unverified';
    }

    public static function mask(?string $value, int $visible = 3): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $length = mb_strlen($value);
        if ($length <= $visible + 2) {
            return str_repeat('•', 6);
        }

        return mb_substr($value, 0, $visible).str_repeat('•', min(8, $length - $visible));
    }

    /**
     * Secret-free status for the admin UI.
     *
     * @return array{serverEnabled: bool, enabled: bool, maintenanceMode: bool, checkoutReady: bool, mode: string, currency: string, readiness: string, storeIdMasked: ?string, storeIdSource: string, passwordConfigured: bool, passwordSource: string, verified: bool, lastTest: array{ok?: bool, message?: string, mode?: string, at?: string}, updatedAt: ?string}
     */
    public function summary(): array
    {
        return [
            'serverEnabled' => $this->serverEnabled,
            'enabled' => $this->methodEnabled,
            'maintenanceMode' => $this->maintenanceMode,
            'checkoutReady' => $this->isCheckoutReady(),
            'mode' => $this->mode(),
            'currency' => $this->currency,
            'readiness' => $this->readiness(),
            'storeIdMasked' => self::mask($this->storeId),
            'storeIdSource' => $this->storeIdSource,
            'passwordConfigured' => filled($this->storePassword),
            'passwordSource' => $this->storePasswordSource,
            'verified' => $this->isVerified(),
            'lastTest' => $this->lastTest,
            'updatedAt' => $this->updatedAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->summary();
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return $this->summary();
    }

    /**
     * Persist gateway settings. Pass a string to replace a credential, '' to clear it,
     * or omit the key to keep the stored value.
     *
     * @param  array{store_id?: string, store_password?: string, sandbox?: bool, currency?: string, last_test?: array<string, mixed>|null, validated_fingerprint?: string|null, updated_by?: int|null}  $changes
     */
    public static function persist(array $changes): self
    {
        DB::transaction(function () use ($changes): void {
            $method = PaymentMethod::query()->where('code', self::METHOD_CODE)->lockForUpdate()->first();
            if (! $method) {
                $defaults = collect(PaymentMethod::defaultSeedRows())->firstWhere('code', self::METHOD_CODE);
                $method = PaymentMethod::create($defaults);
            }

            $config = $method->config_array;
            $gateway = is_array($config['gateway'] ?? null) ? $config['gateway'] : [];

            foreach (['store_id', 'store_password'] as $secretKey) {
                if (! array_key_exists($secretKey, $changes)) {
                    continue;
                }
                $value = trim((string) $changes[$secretKey]);
                if ($value === '') {
                    unset($gateway[$secretKey]);
                } else {
                    $gateway[$secretKey] = Crypt::encryptString($value);
                }
            }

            foreach (['sandbox', 'currency', 'last_test', 'validated_fingerprint', 'updated_by'] as $plainKey) {
                if (array_key_exists($plainKey, $changes)) {
                    $gateway[$plainKey] = $changes[$plainKey];
                }
            }
            $gateway['updated_at'] = now()->toIso8601String();

            $config['gateway'] = $gateway;

            DB::table('payment_methods')->where('id', $method->id)->update([
                'config' => json_encode($config),
                'updated_at' => now(),
            ]);
        });

        return self::load();
    }

    /**
     * @return array<string, mixed>
     */
    private static function gatewayConfig(?PaymentMethod $method): array
    {
        $gateway = $method?->config_array['gateway'] ?? [];

        return is_array($gateway) ? $gateway : [];
    }

    /**
     * @return array{0: ?string, 1: string}
     */
    private static function resolveSecret(mixed $encrypted, mixed $envValue): array
    {
        if (is_string($encrypted) && $encrypted !== '') {
            try {
                return [Crypt::decryptString($encrypted), self::SOURCE_DATABASE];
            } catch (DecryptException) {
                Log::warning('SSLCOMMERZ stored credential could not be decrypted (APP_KEY changed?). Falling back to environment.');
            }
        }

        if (is_string($envValue) && trim($envValue) !== '') {
            return [trim($envValue), self::SOURCE_ENVIRONMENT];
        }

        return [null, self::SOURCE_NONE];
    }
}
