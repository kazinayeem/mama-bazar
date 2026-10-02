<?php

namespace App\Services;

use App\Jobs\SendCampaignBatchJob;
use App\Jobs\SendTemplatedEmailJob;
use App\Jobs\SendTransactionalEmailJob;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailLog;
use App\Models\Order;
use App\Support\EmailQueue;

/**
 * Re-queues a failed email from its stored replay descriptor. OTP and
 * password-reset emails carry no descriptor (their secrets are never
 * stored), so they cannot be retried — the customer simply requests again.
 */
class EmailRetryService
{
    /**
     * @return array{success: bool, message: string}
     */
    public static function retry(EmailLog $log): array
    {
        if (! $log->isRetryable()) {
            return ['success' => false, 'message' => 'This email cannot be retried. OTP and password reset emails must be requested again by the customer.'];
        }

        $replay = (array) ($log->metadata['replay'] ?? []);
        $log->update(['status' => 'queued', 'error_message' => null]);

        if (! EmailQueue::isBackground() && ($replay['kind'] ?? null) === 'order') {
            $order = Order::find((int) ($replay['order_id'] ?? 0));
            if (! $order) {
                return ['success' => false, 'message' => 'Order no longer exists.'];
            }
            $result = OrderEmailService::send($order, (string) ($replay['trigger'] ?? 'invoice'), $log->dedupe_key);
            if ($result['success'] ?? false) {
                return ['success' => true, 'message' => 'Email successfully sent via SMTP.'];
            }

            return ['success' => false, 'message' => 'Email delivery failed: '.($result['error'] ?? 'Check SMTP logs.')];
        }

        $queued = match ($replay['kind'] ?? null) {
            'order' => EmailQueue::dispatch(new SendTransactionalEmailJob((int) $replay['order_id'], (string) $replay['trigger'], $log->dedupe_key)),
            'template' => EmailQueue::dispatch(new SendTemplatedEmailJob(
                (string) $replay['template_key'],
                (string) $log->recipient_email,
                $replay['name'] ?? $log->recipient_name,
                (array) ($replay['data'] ?? []),
                (string) ($replay['type'] ?? $log->email_type),
                ['log_id' => $log->id, 'user_id' => $log->user_id]
            )),
            'campaign' => self::retryCampaignRecipient((int) $replay['campaign_id'], (int) $replay['recipient_id']),
            default => false,
        };

        if (! $queued) {
            $log->update(['status' => 'failed', 'error_message' => 'Retry could not be queued.']);

            return ['success' => false, 'message' => 'Retry could not be queued. Check the queue configuration.'];
        }

        return ['success' => true, 'message' => 'Email re-queued for delivery.'];
    }

    protected static function retryCampaignRecipient(int $campaignId, int $recipientId): bool
    {
        $campaign = EmailCampaign::find($campaignId);
        if (! $campaign || in_array($campaign->status, ['cancelled', 'draft', 'scheduled'], true)) {
            return false;
        }

        $reset = EmailCampaignRecipient::whereKey($recipientId)
            ->where('campaign_id', $campaignId)
            ->whereIn('status', [EmailCampaignRecipient::STATUS_FAILED, EmailCampaignRecipient::STATUS_SKIPPED])
            ->update(['status' => EmailCampaignRecipient::STATUS_PENDING, 'error_message' => null]);
        if ($reset === 0) {
            return false;
        }

        if ($campaign->status !== 'paused') {
            $campaign->update(['status' => 'sending', 'completed_at' => null]);
        }

        return EmailQueue::dispatch(new SendCampaignBatchJob($campaignId, [$recipientId]));
    }
}
