<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'image',
        'icon',
        'banner',
        'thumbnail',
        'description',
        'featured',
        'sort_order',
        'homepage_visibility',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'status',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'sort_order' => 'integer',
        'homepage_visibility' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
