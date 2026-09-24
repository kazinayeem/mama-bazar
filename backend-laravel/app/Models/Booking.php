<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'customer_name',
        'phone',
        'email',
        'user_id',
        'booking_type',
        'service',
        'product_id',
        'start_date',
        'end_date',
        'quantity',
        'price',
        'discount',
        'additional_cost',
        'total_amount',
        'payment_status',
        'status',
        'notes',
        'attachment_url',
        'created_by_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'quantity' => 'integer',
        'price' => 'float',
        'discount' => 'float',
        'additional_cost' => 'float',
        'total_amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
