<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCostHistory extends Model
{
    protected $table = 'product_cost_histories';

    const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'user_id',
        'user_name',
        'field',
        'old_value',
        'new_value',
        'source',
        'ip_address',
    ];

    protected $casts = [
        'old_value' => 'float',
        'new_value' => 'float',
        'created_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
