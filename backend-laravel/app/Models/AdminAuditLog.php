<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    protected $table = 'admin_audit_logs';
    const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_email',
        'action',
        'target_type',
        'target_id',
        'details',
        'ip_address',
        'user_agent',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
