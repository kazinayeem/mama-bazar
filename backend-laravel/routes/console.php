<?php

use App\Models\EmailCampaign;
use App\Models\EmailLog;
use App\Models\EmailOtp;
use App\Models\Order;
use App\Services\EmailCampaignService;
use App\Services\EmailSettingService;
use App\Services\OrderEmailService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('email:dispatch-scheduled-campaigns', function () {
    $due = EmailCampaign::where('status', 'scheduled')
        ->whereNotNull('confirmed_at')
        ->where('scheduled_at', '<=', now())
        ->get();

    foreach ($due as $campaign) {
        $result = EmailCampaignService::start($campaign);
        $this->line("Campaign #{$campaign->id}: {$result['message']}");
    }

    // Recover recipients left "processing" by a crashed worker.
    foreach (EmailCampaign::where('status', 'sending')->get() as $campaign) {
        $stale = $campaign->recipients()->where('status', 'processing')->where('updated_at', '<', now()->subMinutes(10))->count();
        if ($stale > 0) {
            EmailCampaignService::releaseStale($campaign);
            EmailCampaignService::dispatchPending($campaign);
            $this->line("Campaign #{$campaign->id}: re-queued {$stale} stale recipients.");
        }
    }
})->purpose('Start scheduled email campaigns that are due');

Artisan::command('email:send-review-invitations', function () {
    if (! EmailSettingService::isAutomationEnabled('review_invitation')) {
        return;
    }

    $delayDays = max(1, (int) config('email_system.review_invitation_delay_days', 3));
    $deliveredBetween = [now()->subDays($delayDays + 14), now()->subDays($delayDays)];

    $orderIds = DB::table('order_status_history')
        ->where('status', 'delivered')
        ->whereBetween('created_at', $deliveredBetween)
        ->distinct()
        ->pluck('order_id');

    $queued = 0;
    Order::whereIn('id', $orderIds)
        ->where('status', 'delivered')
        ->whereNotNull('email')
        ->where('email', '!=', '')
        ->chunkById(100, function ($orders) use (&$queued) {
            foreach ($orders as $order) {
                $queued += OrderEmailService::queue($order, OrderEmailService::TRIGGER_REVIEW) ? 1 : 0;
            }
        });

    $this->line("Review invitations queued: {$queued}");
})->purpose('Invite customers to review delivered orders');

Artisan::command('email:prune', function () {
    $days = max(7, (int) config('email_system.log_retention_days', 180));
    $logs = EmailLog::where('created_at', '<', now()->subDays($days))->delete();
    $otps = EmailOtp::where('created_at', '<', now()->subDay())->delete();

    $this->line("Pruned {$logs} email logs older than {$days} days and {$otps} expired OTP records.");
})->purpose('Delete old email logs and expired OTP records');

Schedule::command('email:dispatch-scheduled-campaigns')->everyMinute()->withoutOverlapping();
Schedule::command('email:send-review-invitations')->hourly()->withoutOverlapping();
Schedule::command('email:prune')->dailyAt('03:15');

// Shared-hosting friendly worker: drains the email queue every minute via cron.
if (config('email_system.scheduler_queue_worker') && config('email_system.queue_connection') !== 'sync') {
    Schedule::command(sprintf(
        'queue:work %s --queue=%s,default --stop-when-empty --max-time=50 --tries=3 --sleep=3',
        config('email_system.queue_connection'),
        config('email_system.queue_name', 'emails')
    ))->everyMinute()->withoutOverlapping(5);
}
