<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $table = 'collections';
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'banner',
        'featured',
        'homepage_visibility',
        'sort_order',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'homepage_visibility' => 'boolean',
        'sort_order' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'collection_id');
    }
}
