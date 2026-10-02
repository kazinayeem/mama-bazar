<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'actor_type',
        'actor_id',
        'actor_name',
        'actor_role',
        'event_name',
        'module',
        'subject_type',
        'subject_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'source',
        'status',
        'ip_address',
        'location',
        'user_agent',
        'correlation_id',
        'occurred_at',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeModule(Builder $query, ?string $module): Builder
    {
        return $module ? $query->where('module', $module) : $query;
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeSource(Builder $query, ?string $source): Builder
    {
        return $source ? $query->where('source', $source) : $query;
    }

    public function scopeRole(Builder $query, ?string $role): Builder
    {
        return $role ? $query->where('actor_role', $role) : $query;
    }

    public function scopeActorType(Builder $query, ?string $actorType): Builder
    {
        return $actorType ? $query->where('actor_type', $actorType) : $query;
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('occurred_at', '>=', $from.' 00:00:00');
        }
        if ($to) {
            $query->where('occurred_at', '<=', $to.' 23:59:59');
        }

        return $query;
    }

    public function scopeFailedOnly(Builder $query): Builder
    {
        return $query->where('status', 'failure');
    }

    public function scopeSecurityOnly(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('module', 'security')
                ->orWhere('event_name', 'like', 'security.%')
                ->orWhere('event_name', 'like', 'login.%')
                ->orWhere('event_name', 'like', '%.password%')
                ->orWhere('event_name', 'like', '%.role%')
                ->orWhere('event_name', 'like', '%.permission%');
        });
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (! $keyword) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('description', 'like', "%{$keyword}%")
                ->orWhere('actor_name', 'like', "%{$keyword}%")
                ->orWhere('event_name', 'like', "%{$keyword}%")
                ->orWhere('subject_id', 'like', "%{$keyword}%")
                ->orWhere('ip_address', 'like', "%{$keyword}%");
        });
    }

    public function getHumanActionAttribute(): string
    {
        return ucwords(str_replace(['.', '_'], ' ', $this->event_name));
    }
}
