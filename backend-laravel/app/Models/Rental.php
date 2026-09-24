<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    protected $table = 'rentals';

    protected $fillable = [
        'rental_item',
        'product_id',
        'customer_name',
        'phone',
        'email',
        'user_id',
        'quantity',
        'start_date',
        'end_date',
        'return_date',
        'rate_type',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        'rate',
        'duration_units',
        'security_deposit',
        'discount',
        'additional_charge',
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
        'return_date' => 'datetime',
        'quantity' => 'integer',
        'daily_rate' => 'float',
        'weekly_rate' => 'float',
        'monthly_rate' => 'float',
        'rate' => 'float',
        'duration_units' => 'integer',
        'security_deposit' => 'float',
        'discount' => 'float',
        'additional_charge' => 'float',
        'total_amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
