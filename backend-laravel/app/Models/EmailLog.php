<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory;

    /**
     * "sent" means the SMTP server accepted the message — not inbox delivery.
     * "bounced" / "delivered" are only set when a delivery feed reports them.
     */
    public const STATUSES = [
        'queued' => 'Queued',
        'sent' => 'Accepted by SMTP',
        'failed' => 'Failed',
        'skipped' => 'Skipped',
        'bounced' => 'Bounced',
        'delivered' => 'Delivery confirmed',
    ];

    public const TYPES = [
        'otp' => 'OTP / Verification',
        'auth' => 'Account & Security',
        'transactional' => 'Transactional',
        'order' => 'Order Notification',
        'invoice' => 'Invoice',
        'campaign' => 'Marketing Campaign',
        'notification' => 'Internal Notification',
        'test' => 'Test',
    ];

    protected $table = 'email_logs';

    protected $fillable = [
        'recipient_email',
        'recipient_name',
        'subject',
        'email_type',
        'template_key',
        'order_id',
        'campaign_id',
        'user_id',
        'status',
        'attempts',
        'sent_at',
        'last_attempt_at',
        'error_message',
        'message_id',
        'dedupe_key',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'attempts' => 'integer',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    /**
     * Only messages with a replay descriptor can be re-sent. OTP and
     * password-reset messages never get one, so their secrets are never
     * reconstructed from the log.
     */
    public function isRetryable(): bool
    {
        return in_array($this->status, ['failed', 'skipped'], true)
            && ! empty($this->metadata['replay']);
    }
}
