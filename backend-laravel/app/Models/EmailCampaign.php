<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    use HasFactory;

    protected $table = 'email_campaigns';

    protected $fillable = [
        'name',
        'subject',
        'sender_name',
        'sender_email',
        'template_id',
        'body_html',
        'body_plain',
        'audience_filter',
        'status',
        'scheduled_at',
        'total_recipients',
        'queued_count',
        'sent_count',
        'failed_count',
        'skipped_count',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_recipients' => 'integer',
        'queued_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'skipped_count' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(EmailLog::class, 'campaign_id');
    }
}
