<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberLoginHistory extends Model
{
    use HasFactory;

    protected $table = 'member_login_histories';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'login_at',
        'ip_address',
        'user_agent',
        'browser',
        'os',
        'country',
        'region',
        'city',
        'status',
        'failure_reason',
        'created_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getLocationStringAttribute(): string
    {
        $parts = array_filter([$this->city, $this->region, $this->country]);

        return $parts ? implode(', ', $parts) : 'Location unavailable';
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
