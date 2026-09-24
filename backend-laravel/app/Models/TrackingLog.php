<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingLog extends Model
{
    protected $table = 'tracking_logs';
    const UPDATED_AT = null;

    protected $fillable = [
        'event_name',
        'platform',
        'payload',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];
}
