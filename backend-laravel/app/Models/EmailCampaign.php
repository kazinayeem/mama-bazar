<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    use HasFactory;

    public const STATUSES = [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'queued' => 'Queued',
        'sending' => 'Sending',
        'paused' => 'Paused',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ];

    /** States in which content and audience may still be edited. */
    public const EDITABLE_STATUSES = ['draft', 'scheduled'];

    /** States from which the campaign can be launched. */
    public const LAUNCHABLE_STATUSES = ['draft', 'scheduled'];

    protected $table = 'email_campaigns';

    protected $fillable = [
        'name',
        'subject',
        'sender_name',
        'sender_email',
        'template_id',
        'template_key',
        'body_html',
        'body_plain',
        'content_json',
        'audience_filter',
        'audience_params',
        'status',
        'scheduled_at',
        'total_recipients',
        'queued_count',
        'sent_count',
        'failed_count',
        'skipped_count',
        'started_at',
        'completed_at',
        'confirmed_at',
        'confirmed_by',
        'paused_at',
        'cancelled_at',
        'last_error',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'paused_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'content_json' => 'array',
        'audience_params' => 'array',
        'total_recipients' => 'integer',
        'queued_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'skipped_count' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'campaign_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class, 'campaign_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }

    public function progressPercent(): int
    {
        if ($this->total_recipients <= 0) {
            return 0;
        }

        $processed = $this->sent_count + $this->failed_count + $this->skipped_count;

        return (int) min(100, round($processed / $this->total_recipients * 100));
    }
}
