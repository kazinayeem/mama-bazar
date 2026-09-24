<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    protected $table = 'media_assets';
    const UPDATED_AT = null;

    protected $fillable = [
        'url',
        'public_id',
        'filename',
        'mime_type',
        'size',
        'width',
        'height',
        'provider',
        'folder',
        'alt',
        'uploader_id',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'created_at' => 'datetime',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }
}
