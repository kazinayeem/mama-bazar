<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    const UPDATED_AT = null;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'price',
        'sale_price',
        'discount',
        'cost_price',
        'profit_margin',
        'tax',
        'vat',
        'shipping_charge',
        'cod_fee',
        'flash_sale_price',
        'wholesale_price',
        'dealer_price',
        'category_id',
        'sub_category_id',
        'child_category_id',
        'collection_id',
        'brand_id',
        'brand',
        'vendor_id',
        'supplier_id',
        'supplier',
        'country_of_origin',
        'sku',
        'barcode',
        'tags',
        'warranty',
        'weight',
        'dimensions',
        'features',
        'return_policy',
        'warehouse',
        'video_url',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'canonical_url',
        'og_image',
        'twitter_image',
        'structured_data',
        'draft',
        'emi_available',
        'is_featured',
        'is_trending',
        'is_flash_sale',
        'is_new_arrival',
        'is_best_seller',
        'is_limited_edition',
        'is_official',
        'is_hot_deal',
        'is_archived',
        'meta',
        'stock',
        'low_stock_alert',
        'min_order',
        'max_order',
        'unlimited_stock',
        'backorder',
        'track_inventory',
        'stock_status',
        'product_status',
        'images',
        'size_options',
        'color_options',
        'payment_methods',
        'payment_phone_number',
        'status',
    ];

    protected $casts = [
        'price' => 'float',
        'sale_price' => 'float',
        'discount' => 'float',
        'cost_price' => 'float',
        'profit_margin' => 'float',
        'tax' => 'float',
        'vat' => 'float',
        'shipping_charge' => 'float',
        'cod_fee' => 'float',
        'flash_sale_price' => 'float',
        'wholesale_price' => 'float',
        'dealer_price' => 'float',
        'tags' => 'array',
        'features' => 'array',
        'structured_data' => 'array',
        'draft' => 'array',
        'emi_available' => 'boolean',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'is_flash_sale' => 'boolean',
        'is_new_arrival' => 'boolean',
        'is_best_seller' => 'boolean',
        'is_limited_edition' => 'boolean',
        'is_official' => 'boolean',
        'is_hot_deal' => 'boolean',
        'is_archived' => 'boolean',
        'meta' => 'array',
        'stock' => 'integer',
        'low_stock_alert' => 'integer',
        'min_order' => 'integer',
        'max_order' => 'integer',
        'unlimited_stock' => 'boolean',
        'backorder' => 'boolean',
        'track_inventory' => 'boolean',
        'images' => 'array',
        'size_options' => 'array',
        'color_options' => 'array',
        'payment_methods' => 'array',
        'created_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function childCategory()
    {
        return $this->belongsTo(Category::class, 'child_category_id');
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function supplierRel()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function brandRel()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function specs()
    {
        return $this->hasMany(ProductSpec::class, 'product_id')->orderBy('sort_order');
    }

    public function productRelations()
    {
        return $this->hasMany(ProductRelation::class, 'product_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id');
    }
}
