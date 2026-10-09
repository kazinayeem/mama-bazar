<?php

namespace App\Models;

use Database\Factories\SslcommerzTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslcommerzTransaction extends Model
{
    /** @use HasFactory<SslcommerzTransactionFactory> */
    use HasFactory;

    public const STATUS_INITIATED = 'initiated';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_HELD = 'held';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_DUPLICATE = 'duplicate';

    protected $fillable = [
        'order_id',
        'tran_id',
        'amount',
        'currency',
        'mode',
        'status',
        'session_key',
        'val_id',
        'bank_tran_id',
        'card_type',
        'validated_amount',
        'store_amount',
        'risk_level',
        'failure_reason',
        'gateway_payload',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'validated_amount' => 'decimal:2',
            'store_amount' => 'decimal:2',
            'risk_level' => 'integer',
            'gateway_payload' => 'array',
            'validated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [self::STATUS_VALIDATED, self::STATUS_HELD, self::STATUS_DUPLICATE], true);
    }
}
