<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
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

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }
}
