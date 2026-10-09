<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEditHistory extends Model
{
    protected $table = 'order_edit_histories';

    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'user_id',
        'user_name',
        'action',
        'reason',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
