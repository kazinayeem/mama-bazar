<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Memo extends Model
{
    protected $table = 'memos';
    const UPDATED_AT = null;

    protected $fillable = [
        'title',
        'entity_type',
        'entity_id',
        'url',
        'public_id',
        'filename',
        'mime_type',
        'size',
        'folder',
        'notes',
        'uploaded_by_id',
    ];

    protected $casts = [
        'size' => 'integer',
        'entity_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
