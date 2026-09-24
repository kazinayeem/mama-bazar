<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $table = 'vendors';
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'description',
        'contact',
        'phone',
        'email',
        'address',
        'notes',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }
}
