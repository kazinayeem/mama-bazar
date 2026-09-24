<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brands';
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'banner_image',
        'description',
        'website',
        'country_of_origin',
        'featured',
        'homepage_visibility',
        'sort_order',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'status',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'homepage_visibility' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
