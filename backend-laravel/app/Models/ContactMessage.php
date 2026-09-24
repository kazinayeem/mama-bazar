<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $table = 'contact_messages';
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'message',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
