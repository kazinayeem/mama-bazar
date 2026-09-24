<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = 'product_variants';
    const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'name',
        'options',
        'price',
        'discount_price',
        'sku',
        'barcode',
        'stock',
        'weight',
        'dimensions',
        'images',
        'thumbnail',
        'status',
        'shipping_cost',
        'warranty',
        'availability',
    ];

    protected $casts = [
        'options' => 'array',
        'price' => 'float',
        'discount_price' => 'float',
        'stock' => 'integer',
        'images' => 'array',
        'shipping_cost' => 'float',
        'availability' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
