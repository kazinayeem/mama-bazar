<?php

namespace App\Models;

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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActiveCheckout(Builder $query): Builder
    {
        return $query->where('enabled', true)
            ->where('maintenance_mode', false)
            ->ordered();
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
        $config = $this->config_array;

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
                'enabled' => true,
                'sort_order' => 6,
                'maintenance_mode' => true,
                'config' => [
                    'instructions' => 'Online card payment is coming soon.',
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
