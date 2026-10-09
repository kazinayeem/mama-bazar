<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutSession extends Model
{
    protected $table = 'checkout_sessions';

    protected $fillable = [
        'session_id',
        'user_id',
        'order_id',
        'status',
        'progress_percent',
        'current_step',
        'completed_fields',
        'milestones',
        'cart_item_count',
        'cart_subtotal',
        'shipping_method_name',
        'payment_method_code',
        'district',
        'device_category',
        'browser',
        'os',
        'ip_address',
        'country',
        'region',
        'city',
        'referrer',
        'landing_page',
        'first_active_at',
        'last_active_at',
        'converted_at',
        'abandoned_at',
    ];

    protected $casts = [
        'completed_fields' => 'array',
        'milestones' => 'array',
        'progress_percent' => 'integer',
        'cart_item_count' => 'integer',
        'cart_subtotal' => 'float',
        'first_active_at' => 'datetime',
        'last_active_at' => 'datetime',
        'converted_at' => 'datetime',
        'abandoned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('status', 'incomplete');
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->where('status', 'converted');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired');
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted' || ! is_null($this->order_id);
    }
}
