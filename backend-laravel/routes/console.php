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

Artisan::command('email:diagnose-smtp {--host=} {--port=} {--encryption=}', function () {
    $this->info('=== Mama Bazar Production SMTP Diagnostics ===');

    // 1. PHP Environment
    $this->line("\n<info>1. PHP Environment:</info>");
    $this->line('  PHP Version: '.PHP_VERSION);
    $this->line('  OpenSSL Extension: '.(extension_loaded('openssl') ? '<info>LOADED</info>' : '<error>MISSING</error>'));
    $this->line('  Sockets Extension: '.(extension_loaded('sockets') ? '<info>LOADED</info>' : '<comment>NOT LOADED (stream sockets fallback active)</comment>'));

    // 2. Active Configuration
    $settings = EmailSettingService::all();
    $host = (string) ($this->option('host') ?: ($settings['mail_host'] ?: config('mail.mailers.smtp.host', 'mail.mama-bazar.com')));
    $port = (int) ($this->option('port') ?: ($settings['mail_port'] ?? config('mail.mailers.smtp.port', 465)));
    $encryption = (string) ($this->option('encryption') ?: ($settings['mail_encryption'] ?? config('mail.mailers.smtp.encryption', 'ssl')));
    $username = (string) ($settings['mail_username'] ?: config('mail.mailers.smtp.username', ''));
    $hasPassword = EmailSettingService::passwordSource() !== 'none';

    $this->line("\n<info>2. Active SMTP Target:</info>");
    $this->line("  Host: {$host}");
    $this->line("  Port: {$port}");
    $this->line("  Encryption: {$encryption}");
    $this->line("  Username: {$username}");
    $this->line('  Password Configured: '.($hasPassword ? '<info>YES ('.EmailSettingService::passwordSource().')</info>' : '<error>NO</error>'));

    // 3. DNS Resolution
    $this->line("\n<info>3. DNS Resolution:</info>");
    $t0 = microtime(true);
    $ip = gethostbyname($host);
    $dnsTime = round((microtime(true) - $t0) * 1000, 2);
    if ($ip === $host && ! filter_var($host, FILTER_VALIDATE_IP)) {
        $this->error("  FAILED: Could not resolve '{$host}' to an IP address ({$dnsTime} ms).");
    } else {
        $this->info("  RESOLVED: '{$host}' -> {$ip} ({$dnsTime} ms)");
    }

    // 4. Low-Level TCP Port Checks
    $this->line("\n<info>4. Outbound TCP Port Checks:</info>");
    $testPorts = array_values(array_unique([$port, 465, 587, 25]));
    foreach ($testPorts as $p) {
        $t0 = microtime(true);
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $p, $errno, $errstr, 4);
        $elapsed = round((microtime(true) - $t0) * 1000);
        if ($fp) {
            fclose($fp);
            $this->info("  [+] {$host}:{$p} - OPEN and reachable ({$elapsed} ms)");
        } else {
            $errDetail = trim("{$errstr} (errno: {$errno})");
            $this->warn("  [-] {$host}:{$p} - BLOCKED / REFUSED: {$errDetail} ({$elapsed} ms)");
        }
    }

    // 5. Test Localhost / Loopback if applicable
    $this->line("\n<info>5. Localhost (127.0.0.1) Port Checks (if local mail server):</info>");
    foreach ([25, 587, 465] as $p) {
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen('127.0.0.1', $p, $errno, $errstr, 2);
        if ($fp) {
            fclose($fp);
            $this->info("  [+] 127.0.0.1:{$p} - LISTENING locally on this server");
        } else {
            $this->line("  [-] 127.0.0.1:{$p} - Not listening / refused");
        }
    }

    // 6. Full SMTP Test via EmailSettingService
    $this->line("\n<info>6. Live SMTP Handshake & Authentication Probe:</info>");
    $res = EmailSettingService::testConnection([
        'host' => $host,
        'port' => $port,
        'encryption' => $encryption,
    ]);
    if ($res['success']) {
        $this->info("  SUCCESS: {$res['message']}");
    } else {
        $this->error("  FAILED: {$res['message']}");
        if (! empty($res['diagnostic'])) {
            $this->line("\n  <comment>RECOMMENDATION / ACTION PLAN:</comment>");
            $this->line('  '.$res['diagnostic']);
        }
    }
})->purpose('Diagnose production SMTP connectivity, DNS, firewall, and authentication');

Artisan::command('email:process-queue {--limit=100}', function () {
    $conn = (string) config('email_system.queue_connection', 'database');
    $queue = (string) config('email_system.queue_name', 'emails');

    if ($conn === 'sync') {
        $this->info("Queue connection is 'sync'. Transactional emails are executed synchronously.");

        return 0;
    }

    $pendingCount = 0;
    if ($conn === 'database') {
        try {
            $pendingCount = DB::table('jobs')->where('queue', $queue)->count();
        } catch (Throwable $e) {
            $pendingCount = 0;
        }
    }

    $this->info("Processing email queue (Connection: {$conn}, Queue: {$queue}, Pending: {$pendingCount})...");

    $exitCode = Artisan::call('queue:work', [
        'connection' => $conn,
        '--queue' => "{$queue},default",
        '--stop-when-empty' => true,
        '--max-time' => 50,
        '--tries' => 3,
    ]);

    $remaining = 0;
    if ($conn === 'database') {
        try {
            $remaining = DB::table('jobs')->where('queue', $queue)->count();
        } catch (Throwable $e) {
            $remaining = 0;
        }
    }

    $this->info("Queue processing finished. Remaining in '{$queue}': {$remaining}.");

    return $exitCode;
})->purpose('Process pending email jobs from the email queue');

Schedule::command('email:dispatch-scheduled-campaigns')->everyMinute()->withoutOverlapping();
Schedule::command('email:send-review-invitations')->hourly()->withoutOverlapping();
Schedule::command('email:prune')->dailyAt('03:15');
Schedule::command('checkout-sessions:prune --days=30')->dailyAt('03:30')->withoutOverlapping();

// Shared-hosting friendly worker: drains the email queue every minute via cron.
if (config('email_system.scheduler_queue_worker') && config('email_system.queue_connection') !== 'sync') {
    Schedule::command(sprintf(
        'queue:work %s --queue=%s,default --stop-when-empty --max-time=50 --tries=3 --sleep=3',
        config('email_system.queue_connection'),
        config('email_system.queue_name', 'emails')
    ))->everyMinute()->withoutOverlapping(5);
}
