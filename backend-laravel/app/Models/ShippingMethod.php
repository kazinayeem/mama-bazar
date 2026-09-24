<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $table = 'shipping_methods';
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'charge',
        'estimated_delivery',
        'description',
        'priority',
        'free_shipping_min_amount',
        'cod_available',
        'status',
    ];

    protected $casts = [
        'charge' => 'float',
        'priority' => 'integer',
        'free_shipping_min_amount' => 'float',
        'cod_available' => 'boolean',
        'created_at' => 'datetime',
    ];
}
