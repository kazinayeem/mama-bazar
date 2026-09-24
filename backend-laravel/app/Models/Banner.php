<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'banners';

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'image_mobile',
        'image_tablet',
        'link',
        'position',
        'button_text',
        'priority',
        'status',
    ];

    protected $casts = [
        'priority' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
