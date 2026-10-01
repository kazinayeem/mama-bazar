<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaignRecipient extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'email_campaign_recipients';

    protected $fillable = [
        'campaign_id',
        'email',
        'name',
        'user_id',
        'status',
        'attempts',
        'email_log_id',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }
}
