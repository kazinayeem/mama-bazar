<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailSuppression;
use App\Services\EmailCampaignService;
use App\Services\EmailDispatcherService;
use App\Support\EmailQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sends one batch of campaign recipients. Each recipient is claimed
 * atomically (pending → processing) and every send carries a per-campaign
 * dedupe key, so overlapping or re-dispatched batches never double-send.
 */
class SendCampaignBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    /** Consecutive SMTP failures that auto-pause the campaign. */
    private const FAILURE_PAUSE_THRESHOLD = 5;

    /**
     * @param  array<int, int>  $recipientIds
     */
    public function __construct(
        public int $campaignId,
        public array $recipientIds
    ) {}

    public function handle(): void
    {
        $campaign = EmailCampaign::find($this->campaignId);
        if (! $campaign || ! in_array($campaign->status, ['queued', 'sending'], true)) {
            return;
        }

        $maxAttempts = max(1, (int) config('email_system.campaigns.max_attempts_per_recipient', 3));
        $retryIds = [];
        $consecutiveFailures = 0;

        foreach ($this->recipientIds as $index => $recipientId) {
            if ($index > 0 && $index % 5 === 0) {
                $status = EmailCampaign::whereKey($this->campaignId)->value('status');
                if (! in_array($status, ['queued', 'sending'], true)) {
                    break;
                }
            }

            $claimed = EmailCampaignRecipient::whereKey($recipientId)
                ->where('campaign_id', $this->campaignId)
                ->where('status', EmailCampaignRecipient::STATUS_PENDING)
                ->update(['status' => EmailCampaignRecipient::STATUS_PROCESSING, 'updated_at' => now()]);
            if ($claimed === 0) {
                continue;
            }

            $recipient = EmailCampaignRecipient::find($recipientId);

            if (EmailSuppression::isSuppressed($recipient->email)) {
                $recipient->update(['status' => EmailCampaignRecipient::STATUS_SKIPPED, 'error_message' => 'Unsubscribed or suppressed']);

                continue;
            }

            $rendered = EmailCampaignService::render($campaign, $recipient->email, $recipient->name);
            $result = EmailDispatcherService::send(
                $recipient->email,
                $recipient->name,
                $rendered['subject'],
                $rendered['html'],
                $rendered['plain'],
                'campaign',
                null,
                $campaign->id,
                [],
                [
                    'dedupe_key' => "campaign:{$campaign->id}:".sha1($recipient->email),
                    'template_key' => $campaign->template_key,
                    'user_id' => $recipient->user_id,
                    'marketing' => true,
                    'unsubscribe_email' => $recipient->email,
                    'from_name' => $campaign->sender_name ?: null,
                    'replay' => ['kind' => 'campaign', 'campaign_id' => $campaign->id, 'recipient_id' => $recipient->id],
                ]
            );

            $attempts = $recipient->attempts + 1;

            if ($result['success'] || ($result['duplicate'] ?? false)) {
                $consecutiveFailures = 0;
                $recipient->update([
                    'status' => EmailCampaignRecipient::STATUS_SENT,
                    'attempts' => $attempts,
                    'email_log_id' => $result['log_id'],
                    'sent_at' => now(),
                    'error_message' => null,
                ]);

                continue;
            }

            if ($result['skipped'] ?? false) {
                $recipient->update([
                    'status' => EmailCampaignRecipient::STATUS_SKIPPED,
                    'attempts' => $attempts,
                    'email_log_id' => $result['log_id'],
                    'error_message' => $result['error'],
                ]);

                continue;
            }

            $consecutiveFailures++;
            $retryable = $attempts < $maxAttempts;
            $recipient->update([
                'status' => $retryable ? EmailCampaignRecipient::STATUS_PENDING : EmailCampaignRecipient::STATUS_FAILED,
                'attempts' => $attempts,
                'email_log_id' => $result['log_id'],
                'error_message' => $result['error'],
            ]);
            if ($retryable) {
                $retryIds[] = $recipient->id;
            }

            if ($consecutiveFailures >= self::FAILURE_PAUSE_THRESHOLD) {
                $campaign->update([
                    'status' => 'paused',
                    'paused_at' => now(),
                    'last_error' => 'Paused automatically after repeated SMTP failures: '.$result['error'],
                ]);
                EmailCampaignService::syncCounts($campaign->fresh());

                return;
            }
        }

        if ($retryIds !== []) {
            EmailQueue::dispatch(new self($this->campaignId, $retryIds), now()->addMinutes(5));
        }

        EmailCampaignService::syncCounts($campaign->fresh());
    }
}
