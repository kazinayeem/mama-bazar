<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    protected $table = 'email_logs';

    protected $fillable = [
        'recipient_email',
        'recipient_name',
        'subject',
        'email_type',
        'order_id',
        'campaign_id',
        'status',
        'attempts',
        'sent_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'attempts' => 'integer',
        'metadata' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function campaign()
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }
}
