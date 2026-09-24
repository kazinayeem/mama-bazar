<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingIntegration extends Model
{
    protected $table = 'marketing_integrations';

    protected $fillable = [
        'name',
        'type',
        'pixel_id',
        'script_code',
        'access_token',
        'test_event_code',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
