<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminBackup extends Model
{
    protected $table = 'admin_backups';
    const UPDATED_AT = null;

    protected $fillable = [
        'filename',
        'filepath',
        'size',
        'type',
        'table_count',
        'record_count',
        'created_by_id',
    ];

    protected $casts = [
        'size' => 'integer',
        'table_count' => 'integer',
        'record_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
