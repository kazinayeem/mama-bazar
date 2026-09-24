<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutNotice extends Model
{
    protected $table = 'checkout_notices';
    const UPDATED_AT = null;

    protected $fillable = [
        'text',
        'priority',
        'background_color',
        'text_color',
        'icon',
        'status',
    ];

    protected $casts = [
        'priority' => 'integer',
        'created_at' => 'datetime',
    ];
}
