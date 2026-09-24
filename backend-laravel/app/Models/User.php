<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'password',
        'phone',
        'email',
        'shipping_area',
        'shipping_address',
        'role',
        'custom_role',
        'permissions_json',
        'status',
        'last_login_at',
        'reset_token_hash',
        'reset_token_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reset_token_hash',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function addresses()
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function userPermissions()
    {
        return $this->hasMany(UserPermission::class, 'user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AdminAuditLog::class, 'actor_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'member_id');
    }
}
