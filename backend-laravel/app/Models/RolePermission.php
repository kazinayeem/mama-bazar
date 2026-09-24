<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permissions';
    const UPDATED_AT = null;

    protected $fillable = [
        'role_name',
        'permission_code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function permission()
    {
        return $this->belongsTo(AdminPermission::class, 'permission_code', 'code');
    }
}
