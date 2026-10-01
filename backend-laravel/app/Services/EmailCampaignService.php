<?php

namespace App\Services;

use App\Jobs\SendCampaignBatchJob;
use App\Models\EmailCampaign;
use App\Models\Newsletter;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class EmailCampaignService
{
    /**
     * Get list of supported audience groups with human-readable labels.
     */
    public static function audienceOptions(): array
    {
        return [
            'consented_customers' => 'All Marketing-Consented Customers & Subscribers',
            'recent_registered'   => 'Recently Registered Customers (Last 30 Days)',
            'verified_customers'  => 'Email-Verified Customers Only',
            'order_customers'     => 'Customers Who Have Placed Orders',
            'inactive_customers'  => 'Inactive Customers (No Order in 60+ Days)',
            'all_newsletter'      => 'Newsletter Subscribers Only',
        ];
    }

    /**
     * Resolve audience recipients based on filter, excluding unconsented or invalid emails.
     * Returns Collection of items with ['email' => string, 'name' => string, 'user_id' => ?int, 'token' => ?string]
     */
    public static function resolveRecipients(string $audienceFilter): Collection
    {
        $recipients = collect();

        // 1. User base query respecting marketing_opt_in and valid emails
        $usersQuery = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->where('marketing_opt_in', true)
            ->where('status', 'active');

        switch ($audienceFilter) {
            case 'recent_registered':
                $users = $usersQuery->where('created_at', '>=', now()->subDays(30))->get();
                break;

            case 'verified_customers':
                $users = $usersQuery->whereNotNull('email_verified_at')->get();
                break;

            case 'order_customers':
                $orderUserIds = Order::whereNotNull('user_id')->distinct()->pluck('user_id');
                $users = $usersQuery->whereIn('id', $orderUserIds)->get();
                break;

            case 'inactive_customers':
                $recentOrderUserIds = Order::whereNotNull('user_id')
                    ->where('created_at', '>=', now()->subDays(60))
                    ->distinct()
                    ->pluck('user_id');
                $pastOrderUserIds = Order::whereNotNull('user_id')
                    ->where('created_at', '<', now()->subDays(60))
                    ->distinct()
                    ->pluck('user_id');
                $users = $usersQuery->whereIn('id', $pastOrderUserIds)
                    ->whereNotIn('id', $recentOrderUserIds)
                    ->get();
                break;

            case 'all_newsletter':
                $users = collect();
                break;

            case 'consented_customers':
            default:
                $users = $usersQuery->get();
                break;
        }

        foreach ($users as $user) {
            $recipients->put(strtolower($user->email), [
                'email' => strtolower($user->email),
                'name' => $user->name ?: 'Valued Customer',
                'user_id' => $user->id,
                'token' => $user->getOrCreateUnsubscribeToken(),
            ]);
        }

        // Include consented newsletter subscribers if applicable
        if (in_array($audienceFilter, ['consented_customers', 'all_newsletter'], true)) {
            $subscribers = Newsletter::where('status', 'subscribed')->get();
            foreach ($subscribers as $sub) {
                $email = strtolower($sub->email);
                if (!$recipients->has($email)) {
                    $recipients->put($email, [
                        'email' => $email,
                        'name' => 'Newsletter Subscriber',
                        'user_id' => null,
                        'token' => sha1('newsletter:' . $email),
                    ]);
                }
            }
        }

        return $recipients->values();
    }

    /**
     * Get count of recipients for audience preview.
     */
    public static function countAudience(string $audienceFilter): int
    {
        return self::resolveRecipients($audienceFilter)->count();
    }

    /**
     * Generate a signed or token-based safe unsubscribe URL for a recipient.
     */
    public static function generateUnsubscribeUrl(string $email, ?string $token = null): string
    {
        return URL::temporarySignedRoute(
            'email.unsubscribe.form',
            now()->addDays(90),
            ['email' => $email, 'token' => $token ?: sha1($email)]
        );
    }

    /**
     * Queue a campaign for background batch processing.
     */
    public static function queueCampaign(EmailCampaign $campaign): void
    {
        if (in_array($campaign->status, ['queued', 'sending', 'completed'], true)) {
            return;
        }

        $recipients = self::resolveRecipients($campaign->audience_filter);

        $campaign->update([
            'status' => 'queued',
            'total_recipients' => $recipients->count(),
            'queued_count' => $recipients->count(),
            'sent_count' => 0,
            'failed_count' => 0,
            'skipped_count' => 0,
            'started_at' => now(),
        ]);

        // Break recipients into batches of 25 for safe queue processing
        $chunks = $recipients->chunk(25);

        foreach ($chunks as $chunk) {
            SendCampaignBatchJob::dispatch($campaign->id, $chunk->toArray());
        }

        $campaign->update(['status' => 'sending']);
    }
}
