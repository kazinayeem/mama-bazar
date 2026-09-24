<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $table = 'user_permissions';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'permission_code',
        'granted',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function permission()
    {
        return $this->belongsTo(AdminPermission::class, 'permission_code', 'code');
    }
}
