<?php

namespace App\Models;

use App\Support\SslcommerzSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    protected $fillable = [
        'code',
        'name',
        'type',
        'enabled',
        'sort_order',
        'maintenance_mode',
        'config',
    ];

    /**
     * Raw config may contain encrypted gateway credentials; serialize through
     * toApiArray() / publicConfigArray() instead.
     */
    protected $hidden = ['config'];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_order' => 'integer',
        'maintenance_mode' => 'boolean',
        'config' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TYPES = ['cod', 'mobile_banking', 'bank', 'online'];

    public const TYPE_LABELS = [
        'cod' => 'Cash on Delivery',
        'mobile_banking' => 'Mobile Banking',
        'bank' => 'Bank Transfer',
        'online' => 'Online Gateway',
    ];

    public const CODE_ICONS = [
        'cod' => '💵',
        'bkash' => '৳',
        'nagad' => '৳',
        'rocket' => '৳',
        'bank' => '🏦',
        'stripe' => '💳',
        'sslcommerz' => '🔒',
        'paypal' => '🅿️',
    ];

    /**
     * Config keys holding gateway credentials. They are only written through
     * SslcommerzSettings and are never returned to the browser.
     */
    public const PROTECTED_CONFIG_KEYS = ['gateway'];

    protected static function booted(): void
    {
        static::saving(function (PaymentMethod $method): void {
            if (! $method->exists || ! $method->isDirty('config')) {
                return;
            }

            $original = json_decode((string) ($method->getRawOriginal('config') ?? ''), true);
            $original = is_array($original) ? $original : [];
            $config = $method->config_array;

            foreach (self::PROTECTED_CONFIG_KEYS as $key) {
                if (array_key_exists($key, $original)) {
                    $config[$key] = $original[$key];
                } else {
                    unset($config[$key]);
                }
            }

            $method->config = $config;
        });

        static::creating(function (PaymentMethod $method): void {
            $config = $method->config_array;
            foreach (self::PROTECTED_CONFIG_KEYS as $key) {
                unset($config[$key]);
            }
            $method->config = $config;
        });
    }

    /**
     * Config safe for browsers and API consumers (credentials removed).
     *
     * @return array<string, mixed>
     */
    public function publicConfigArray(): array
    {
        return array_diff_key($this->config_array, array_flip(self::PROTECTED_CONFIG_KEYS));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActiveCheckout(Builder $query): Builder
    {
        $query->where('enabled', true)
            ->where('maintenance_mode', false);

        if (! SslcommerzSettings::load()->isCheckoutReady()) {
            $query->where('code', '!=', SslcommerzSettings::METHOD_CODE);
        }

        return $query->ordered();
    }

    public function getConfigArrayAttribute(): array
    {
        $config = $this->config;
        if (is_string($config)) {
            $decoded = json_decode($config, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($config) ? $config : [];
    }

    public function getIconAttribute(): string
    {
        return self::CODE_ICONS[$this->code] ?? '💳';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    /** React-compatible camelCase payload for API consumers. */
    public function toApiArray(bool $public = false): array
    {
        $config = $this->publicConfigArray();

        if ($public) {
            return [
                'id' => $this->id,
                'code' => $this->code,
                'name' => $this->name,
                'type' => $this->type,
                'config' => $config ?: (object) [],
            ];
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'enabled' => (bool) $this->enabled,
            'sortOrder' => (int) $this->sort_order,
            'maintenanceMode' => (bool) $this->maintenance_mode,
            'config' => $config ?: (object) [],
            'createdAt' => optional($this->created_at)?->toISOString(),
            'updatedAt' => optional($this->updated_at)?->toISOString(),
        ];
    }

    /** Default methods matching React admin presets (COD / bKash / Nagad / etc.). */
    public static function defaultSeedRows(): array
    {
        return [
            [
                'code' => 'cod',
                'name' => 'Cash on Delivery',
                'type' => 'cod',
                'enabled' => true,
                'sort_order' => 1,
                'maintenance_mode' => false,
                'config' => [
                    'instructions' => 'Pay in cash when your order is delivered.',
                ],
            ],
            [
                'code' => 'bkash',
                'name' => 'bKash',
                'type' => 'mobile_banking',
                'enabled' => true,
                'sort_order' => 2,
                'maintenance_mode' => false,
                'config' => [
                    'merchantNumber' => '',
                    'merchantName' => 'Mama Bazar',
                    'instructions' => "Send payment to the bKash number shown below.\nAfter payment, enter your Transaction ID.",
                    'minAmount' => 50,
                    'maxAmount' => 200000,
                    'extraFee' => 0,
                    'extraFeePercent' => 0,
                ],
            ],
            [
                'code' => 'nagad',
                'name' => 'Nagad',
                'type' => 'mobile_banking',
                'enabled' => true,
                'sort_order' => 3,
                'maintenance_mode' => false,
                'config' => [
                    'merchantNumber' => '',
                    'merchantName' => 'Mama Bazar',
                    'instructions' => "Send payment to the Nagad number shown below.\nAfter payment, enter your Transaction ID.",
                    'minAmount' => 50,
                    'maxAmount' => 200000,
                    'extraFee' => 0,
                    'extraFeePercent' => 0,
                ],
            ],
            [
                'code' => 'rocket',
                'name' => 'Rocket',
                'type' => 'mobile_banking',
                'enabled' => false,
                'sort_order' => 4,
                'maintenance_mode' => false,
                'config' => [
                    'merchantNumber' => '',
                    'merchantName' => 'Mama Bazar',
                    'instructions' => 'Send payment to the Rocket number, then submit your Transaction ID.',
                ],
            ],
            [
                'code' => 'bank',
                'name' => 'Bank Transfer',
                'type' => 'bank',
                'enabled' => false,
                'sort_order' => 5,
                'maintenance_mode' => false,
                'config' => [
                    'bankName' => '',
                    'accountName' => 'Mama Bazar',
                    'accountNumber' => '',
                    'routingNumber' => '',
                    'branch' => '',
                    'instructions' => 'Transfer the order total to the bank account below, then submit your reference / Transaction ID.',
                ],
            ],
            [
                'code' => 'sslcommerz',
                'name' => 'Card / Online Gateway',
                'type' => 'online',
                'enabled' => false,
                'sort_order' => 6,
                'maintenance_mode' => false,
                'config' => [
                    'instructions' => 'Pay securely with card, mobile banking or internet banking via SSLCOMMERZ.',
                ],
            ],
        ];
    }

    public static function ensureDefaults(): void
    {
        if (static::query()->exists()) {
            return;
        }

        foreach (static::defaultSeedRows() as $row) {
            static::create($row);
        }
    }
}
