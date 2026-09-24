<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPermission extends Model
{
    protected $table = 'admin_permissions';
    const UPDATED_AT = null;

    protected $fillable = [
        'code',
        'module',
        'label',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
