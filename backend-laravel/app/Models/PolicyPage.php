<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyPage extends Model
{
    protected $table = 'policy_pages';
    const UPDATED_AT = null;

    protected $fillable = [
        'slug',
        'title',
        'content',
        'status',
        'last_updated',
        'updated_by',
    ];

    protected $casts = [
        'last_updated' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
    ];
}
