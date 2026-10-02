<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'invoice_number',
        'access_token',
        'idempotency_key',
        'ip_hash',
        'ip_truncated',
        'user_agent',
        'browser',
        'os_platform',
        'device_type',
        'referrer',
        'landing_page',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'order_source',
        'marketing_consent',
        'fb_event_id',
        'purchase_tracked_at',
        'user_id',
        'customer_name',
        'phone',
        'alternative_phone',
        'email',
        'country',
        'division',
        'district',
        'upazila',
        'area',
        'address',
        'apartment',
        'postal_code',
        'shipping_method_id',
        'shipping_method_name',
        'shipping_cost',
        'subtotal',
        'coupon_code',
        'discount',
        'tax',
        'order_note',
        'checkout_notes',
        'admin_notes',
        'total_price',
        'payment_method',
        'transaction_id',
        'sender_number',
        'payment_screenshot',
        'payment_date',
        'amount_sent',
        'payment_instructions',
        'courier_tracking_number',
        'payment_status',
        'status',
    ];

    protected $casts = [
        'shipping_cost' => 'float',
        'subtotal' => 'float',
        'discount' => 'float',
        'tax' => 'float',
        'total_price' => 'float',
        'amount_sent' => 'float',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alternative phone safe for display: null when empty or identical to the
     * primary phone (after whitespace/dash normalization), so invoices never
     * render the same number twice. Saved data is left untouched.
     */
    public function getDisplayAlternativePhoneAttribute(): ?string
    {
        $norm = fn ($v) => preg_replace('/[\s\-]/', '', trim((string) $v));
        $alt = trim((string) $this->alternative_phone);
        if ($alt === '') {
            return null;
        }

        return $norm($alt) !== $norm($this->phone) ? $alt : null;
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }
}
