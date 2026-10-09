<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailLog;
use App\Models\EmailSuppression;
use App\Models\Order;
use App\Services\EmailDispatcherService;
use App\Services\EmailRetryService;
use App\Services\EmailSettingService;
use App\Services\EmailTemplateService;
use App\Support\EmailQueue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminEmailController extends Controller
{
    public function dashboard()
    {
        $today = now()->startOfDay();
        $countWhere = fn (array $where) => EmailLog::where($where)->count();

        $stats = [
            'sent_today' => EmailLog::where('status', 'sent')->where('sent_at', '>=', $today)->count(),
            'failed_today' => EmailLog::where('status', 'failed')->where('updated_at', '>=', $today)->count(),
            'queued' => $countWhere([['status', '=', 'queued']]),
            'total_sent' => $countWhere([['status', '=', 'sent']]),
            'total_failed' => $countWhere([['status', '=', 'failed']]),
            'otp_today' => EmailLog::where('email_type', 'otp')->where('created_at', '>=', $today)->count(),
            'order_today' => EmailLog::whereIn('email_type', ['order', 'invoice'])->where('created_at', '>=', $today)->count(),
            'active_campaigns' => EmailCampaign::whereIn('status', ['queued', 'sending'])->count(),
            'scheduled_campaigns' => EmailCampaign::where('status', 'scheduled')->count(),
            'paused_campaigns' => EmailCampaign::where('status', 'paused')->count(),
            'suppressed' => EmailSuppression::count(),
            'unsubscribed_30d' => EmailSuppression::where('reason', 'unsubscribed')->where('updated_at', '>=', now()->subDays(30))->count(),
        ];

        $sentLast7 = EmailLog::where('status', 'sent')->where('sent_at', '>=', now()->subDays(6)->startOfDay())
            ->get(['sent_at'])->groupBy(fn ($l) => $l->sent_at->format('Y-m-d'))->map->count();
        $chart = collect(range(6, 0))->map(fn ($d) => [
            'label' => now()->subDays($d)->format('D'),
            'count' => (int) ($sentLast7[now()->subDays($d)->format('Y-m-d')] ?? 0),
        ]);

        return view('admin.email.dashboard', [
            'stats' => $stats,
            'chart' => $chart,
            'smtp' => EmailSettingService::forDisplay(),
            'queue' => $this->queueHealth(),
            'recentLogs' => EmailLog::latest('id')->take(10)->get(),
            'activeCampaigns' => EmailCampaign::whereIn('status', ['queued', 'sending', 'paused', 'scheduled'])->latest('id')->take(5)->get(),
        ]);
    }

    public function settings()
    {
        return view('admin.email.settings', [
            'settings' => EmailSettingService::forDisplay(),
            'senderChecks' => EmailSettingService::senderChecks(),
            'dnsChecks' => Cache::get('mamabazar:email_dns_checks'),
            'appUrl' => config('app.url'),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'mail_mailer' => 'required|string|in:smtp,log,sendmail',
            'mail_host' => 'required_if:mail_mailer,smtp|nullable|string|max:255',
            'mail_port' => 'required_if:mail_mailer,smtp|nullable|integer|min:1|max:65535',
            'mail_encryption' => 'required|string|in:ssl,tls,none',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_from_address' => 'required|email:rfc|max:191',
            'mail_from_name' => ['required', 'string', 'max:120', 'regex:/^[^\r\n<>"]+$/'],
            'mail_reply_to' => 'nullable|email:rfc|max:191',
            'mail_timeout' => 'required|integer|min:5|max:120',
        ], [
            'mail_from_name.regex' => 'Sender name cannot contain line breaks, quotes or angle brackets.',
        ]);

        $data['mail_enabled'] = $request->boolean('mail_enabled') ? 1 : 0;
        if (! $request->filled('mail_password')) {
            unset($data['mail_password']);
        }

        EmailSettingService::updateSettings($data);

        if ($request->boolean('clear_password')) {
            EmailSettingService::clearPassword();
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Email settings saved.',
            ]);
        }

        return redirect()->route('admin.email.settings')->with('success', 'Email settings saved.');
    }

    public function testConnection(Request $request)
    {
        $result = EmailSettingService::testConnection();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ]);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate(['test_email' => 'required|email:rfc|max:191']);
        $to = strtolower(trim((string) $request->input('test_email')));

        $settings = EmailSettingService::all();
        $rendered = EmailTemplateService::renderContent(
            'SMTP test from {{business_name}}',
            '<h1 style="margin:0 0 12px 0;font-size:20px;color:#0f4d2c;">SMTP test successful</h1>'
                .'<p>This message was sent from the {{business_name}} admin panel to confirm that outgoing email works.</p>'
                .'<p style="font-size:12px;color:#64748b;">Server: '.e($settings['mail_host']).':'.e((string) $settings['mail_port'])
                .' · Sent at '.e(now()->format('d M Y, h:i A')).'</p>'
                .'<p style="font-size:12px;color:#64748b;">Acceptance by the SMTP server does not guarantee inbox placement. Check the spam folder and SPF/DKIM/DMARC status if this message is missing.</p>',
            null
        );

        $result = EmailDispatcherService::send($to, null, $rendered['subject'], $rendered['html'], $rendered['plain'], 'test', null, null, [], [
            'user_id' => $request->user()?->id,
        ]);

        $msg = $result['success']
            ? "Test email accepted by the SMTP server for {$to}. Check the inbox (and spam folder)."
            : 'Test email failed: '.($result['error'] ?? 'Unknown error');

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $msg,
            ]);
        }

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $msg
        );
    }

    public function checkDns(Request $request)
    {
        $checks = EmailSettingService::deliverabilityChecks();
        Cache::put('mamabazar:email_dns_checks', ['checked_at' => now()->toIso8601String(), 'items' => $checks], now()->addHour());

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'DNS records checked.',
                'items' => $checks,
            ]);
        }

        return back()->with('success', 'DNS records checked.');
    }

    public function automation()
    {
        $settings = EmailSettingService::all();
        $groups = collect(EmailSettingService::AUTOMATIONS)
            ->map(fn ($meta, $key) => $meta + ['key' => $key, 'enabled' => (bool) ($settings[$key] ?? 0)])
            ->groupBy('group');

        return view('admin.email.automation', [
            'groups' => $groups,
            'settings' => $settings,
            'queueBackground' => EmailQueue::isBackground(),
        ]);
    }

    public function updateAutomation(Request $request)
    {
        $request->validate(['email_admin_notification_address' => 'nullable|email:rfc|max:191']);

        $data = [];
        foreach (array_keys(EmailSettingService::AUTOMATIONS) as $key) {
            $data[$key] = $request->boolean($key) ? 1 : 0;
        }
        $data['email_require_registration_email'] = $request->boolean('email_require_registration_email') ? 1 : 0;
        $data['email_verification_enforced'] = $request->boolean('email_verification_enforced') ? 1 : 0;
        $data['email_admin_notification_address'] = (string) $request->input('email_admin_notification_address', '');

        EmailSettingService::updateSettings($data);

        return back()->with('success', 'Automation rules updated.');
    }

    public function logs(Request $request)
    {
        $filters = $request->validate([
            'status' => 'nullable|in:'.implode(',', array_keys(EmailLog::STATUSES)),
            'type' => 'nullable|in:'.implode(',', array_keys(EmailLog::TYPES)),
            'search' => 'nullable|string|max:191',
            'campaign_id' => 'nullable|integer',
            'order' => 'nullable|string|max:50',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $query = EmailLog::query()->latest('id');
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['type'])) {
            $query->where('email_type', $filters['type']);
        }
        if (! empty($filters['search'])) {
            $s = '%'.addcslashes($filters['search'], '%_\\').'%';
            $query->where(fn ($q) => $q->where('recipient_email', 'like', $s)->orWhere('subject', 'like', $s));
        }
        if (! empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }
        if (! empty($filters['order'])) {
            $orderId = Order::where('order_id', $filters['order'])->value('id') ?? (ctype_digit($filters['order']) ? (int) $filters['order'] : 0);
            $query->where('order_id', $orderId);
        }
        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        return view('admin.email.logs', [
            'logs' => $query->with(['order:id,order_id', 'campaign:id,name'])->paginate(25)->withQueryString(),
            'filters' => $filters,
            'retentionDays' => (int) config('email_system.log_retention_days', 180),
        ]);
    }

    public function showLog(int $id)
    {
        $log = EmailLog::with(['order:id,order_id', 'campaign:id,name'])->findOrFail($id);

        return view('admin.email.log-show', ['log' => $log]);
    }

    public function retryLog(int $id)
    {
        $result = EmailRetryService::retry(EmailLog::findOrFail($id));

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function suppressions(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $query = EmailSuppression::query()->latest('id');
        if ($search !== '') {
            $query->where('email', 'like', '%'.addcslashes(strtolower($search), '%_\\').'%');
        }

        return view('admin.email.suppressions', [
            'suppressions' => $query->paginate(30)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function storeSuppression(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email:rfc|max:191',
            'reason' => 'required|in:'.implode(',', array_keys(EmailSuppression::REASONS)),
            'note' => 'nullable|string|max:255',
        ]);

        EmailSuppression::updateOrCreate(
            ['email' => strtolower(trim($data['email']))],
            ['reason' => $data['reason'], 'source' => 'admin', 'note' => $data['note'] ?? null]
        );

        return back()->with('success', 'Address added to the suppression list. It will not receive marketing email.');
    }

    public function destroySuppression(int $id)
    {
        EmailSuppression::whereKey($id)->delete();

        return back()->with('success', 'Suppression removed. The address will only receive marketing email if it has opted in.');
    }

    /**
     * @return array{connection: string, background: bool, pending: int|null, oldest_minutes: int|null, failed_24h: int|null, last_activity: string|null}
     */
    protected function queueHealth(): array
    {
        $connection = EmailQueue::connection();
        $health = [
            'connection' => $connection,
            'background' => EmailQueue::isBackground(),
            'pending' => null,
            'oldest_minutes' => null,
            'failed_24h' => null,
            'last_activity' => EmailLog::whereNotNull('last_attempt_at')->max('last_attempt_at'),
        ];

        try {
            if ($connection === 'database' && Schema::hasTable('jobs')) {
                $jobs = DB::table('jobs')->whereIn('queue', [(string) config('email_system.queue_name', 'emails'), 'default']);
                $health['pending'] = (clone $jobs)->count();
                $oldest = (clone $jobs)->min('created_at');
                $health['oldest_minutes'] = $oldest ? (int) floor((time() - (int) $oldest) / 60) : 0;
            }
            if (Schema::hasTable('failed_jobs')) {
                $health['failed_24h'] = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
            }
        } catch (Throwable $e) {
            // Health panel is informational only.
        }

        return $health;
    }
}
