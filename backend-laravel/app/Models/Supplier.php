<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';
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
        return $this->hasMany(Product::class, 'supplier_id');
    }
}
