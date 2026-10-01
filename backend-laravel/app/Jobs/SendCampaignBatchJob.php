<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Services\EmailCampaignService;
use App\Services\EmailDispatcherService;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCampaignBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public int $campaignId,
        public array $recipients
    ) {}

    public function handle(): void
    {
        $campaign = EmailCampaign::find($this->campaignId);
        if (!$campaign) {
            return;
        }

        // Honor paused or cancelled state immediately
        if (in_array($campaign->status, ['paused', 'cancelled'], true)) {
            return;
        }

        $sentInBatch = 0;
        $failedInBatch = 0;

        foreach ($this->recipients as $recipient) {
            // Re-check campaign status before sending each item in case admin clicked pause
            $campaign->refresh();
            if (in_array($campaign->status, ['paused', 'cancelled'], true)) {
                break;
            }

            $email = $recipient['email'];
            $name = $recipient['name'] ?? 'Customer';
            $token = $recipient['token'] ?? null;
            $unsubscribeUrl = EmailCampaignService::generateUnsubscribeUrl($email, $token);

            // Dynamic placeholder replacement for this recipient
            $data = [
                'customer_name' => $name,
                'customer_email' => $email,
                'announcement_title' => $campaign->subject,
                'announcement_body' => $campaign->body_html,
                'unsubscribe_url' => $unsubscribeUrl,
                'cta_url' => url('/'),
                'cta_text' => 'Shop Now',
            ];

            $rendered = EmailTemplateService::render('promotional_campaign', $data);

            $result = EmailDispatcherService::send(
                $email,
                $name,
                $campaign->subject,
                $rendered['html'],
                $rendered['plain'],
                'campaign',
                null,
                $campaign->id
            );

            if ($result['success']) {
                $sentInBatch++;
            } else {
                $failedInBatch++;
            }

            // Brief throttle (50ms) to prevent SMTP flooding
            usleep(50000);
        }

        // Atomically update counts
        $campaign->increment('sent_count', $sentInBatch);
        $campaign->increment('failed_count', $failedInBatch);

        // Check if finished
        $campaign->refresh();
        $processedTotal = $campaign->sent_count + $campaign->failed_count + $campaign->skipped_count;
        if ($processedTotal >= $campaign->total_recipients && $campaign->status === 'sending') {
            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}
