@extends('layouts.admin', ['headerTitle' => 'Email Dashboard'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Email Dashboard" subtitle="Outgoing email health, automation activity and campaign progress." />
    @include('admin.email.partials.tabs')

    @if(! $smtp['mail_enabled'])
        <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">Outgoing email is <strong>disabled</strong>. No customer emails are being sent.</div>
    @endif
    @if($smtp['password_source'] === 'none' && $smtp['mail_mailer'] === 'smtp')
        <div class="rounded-[8px] border border-red-200 bg-red-50 p-3 text-xs text-red-700">The SMTP password has not been configured. Enter it in SMTP Settings (or set <code>MAIL_PASSWORD</code> on the server).</div>
    @endif
    @if(! $queue['background'])
        <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">Email queue runs in <strong>sync</strong> mode: transactional emails send after the page response, and bulk campaigns are disabled. Set <code>EMAIL_QUEUE_CONNECTION=database</code> and the cron worker for production.</div>
    @endif

    <div class="admin-metric-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
        <x-admin.metric-card label="Accepted by SMTP today" :value="number_format($stats['sent_today'])" tone="success" />
        <x-admin.metric-card label="Failed today" :value="number_format($stats['failed_today'])" :tone="$stats['failed_today'] ? 'danger' : 'default'" />
        <x-admin.metric-card label="Queued now" :value="number_format($stats['queued'])" tone="warning" />
        <x-admin.metric-card label="OTP emails today" :value="number_format($stats['otp_today'])" />
        <x-admin.metric-card label="Order emails today" :value="number_format($stats['order_today'])" />
        <x-admin.metric-card label="Active campaigns" :value="number_format($stats['active_campaigns'])" :hint="$stats['scheduled_campaigns'].' scheduled · '.$stats['paused_campaigns'].' paused'" />
        <x-admin.metric-card label="Total accepted" :value="number_format($stats['total_sent'])" :hint="number_format($stats['total_failed']).' failed all-time'" />
        <x-admin.metric-card label="Suppressed addresses" :value="number_format($stats['suppressed'])" :hint="$stats['unsubscribed_30d'].' unsubscribed in 30 days'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="admin-surface p-4 space-y-3">
            <h2 class="text-sm font-bold text-slate-900">SMTP Status</h2>
            @php $status = $smtp['mail_last_status'] ?? null; @endphp
            <div class="flex items-center gap-2 text-sm">
                <span class="h-2.5 w-2.5 rounded-full {{ $status === 'connected' ? 'bg-emerald-500' : ($status === 'failed' ? 'bg-red-500' : 'bg-slate-300') }}"></span>
                <span class="font-semibold">{{ $status === 'connected' ? 'Connected' : ($status === 'failed' ? 'Last attempt failed' : 'Not tested yet') }}</span>
            </div>
            <dl class="grid grid-cols-3 gap-1 text-xs">
                <dt class="text-slate-500">Server</dt><dd class="col-span-2 font-mono">{{ $smtp['mail_host'] }}:{{ $smtp['mail_port'] }}</dd>
                <dt class="text-slate-500">Sender</dt><dd class="col-span-2">{{ $smtp['mail_from_name'] }} &lt;{{ $smtp['mail_from_address'] }}&gt;</dd>
                <dt class="text-slate-500">Last check</dt><dd class="col-span-2">{{ $smtp['mail_last_tested_at'] ? \Carbon\Carbon::parse($smtp['mail_last_tested_at'])->diffForHumans() : '—' }}</dd>
            </dl>
            @if($status === 'failed' && ! empty($smtp['mail_last_error']))
                <p class="rounded bg-red-50 p-2 text-[11px] text-red-700 break-words">{{ $smtp['mail_last_error'] }}</p>
            @endif
            <p class="text-[11px] text-slate-400">"Accepted by SMTP" confirms the server took the message; it does not guarantee inbox delivery.</p>
        </div>

        <div class="admin-surface p-4 space-y-3">
            <h2 class="text-sm font-bold text-slate-900">Queue Health</h2>
            <dl class="grid grid-cols-2 gap-1 text-xs">
                <dt class="text-slate-500">Connection</dt><dd class="font-mono">{{ $queue['connection'] }}</dd>
                <dt class="text-slate-500">Pending jobs</dt><dd>{{ $queue['pending'] ?? '—' }}</dd>
                <dt class="text-slate-500">Oldest job</dt><dd class="{{ ($queue['oldest_minutes'] ?? 0) > 5 ? 'font-bold text-red-600' : '' }}">{{ $queue['oldest_minutes'] !== null ? $queue['oldest_minutes'].' min' : '—' }}</dd>
                <dt class="text-slate-500">Failed jobs (24h)</dt><dd class="{{ ($queue['failed_24h'] ?? 0) > 0 ? 'font-bold text-red-600' : '' }}">{{ $queue['failed_24h'] ?? '—' }}</dd>
                <dt class="text-slate-500">Last send attempt</dt><dd>{{ $queue['last_activity'] ? \Carbon\Carbon::parse($queue['last_activity'])->diffForHumans() : '—' }}</dd>
            </dl>
            @if(($queue['oldest_minutes'] ?? 0) > 5)
                <p class="rounded bg-red-50 p-2 text-[11px] text-red-700">Jobs are waiting longer than 5 minutes — check that the cron entry <code>* * * * * php artisan schedule:run</code> is active.</p>
            @endif
        </div>

        <div class="admin-surface p-4 space-y-3">
            <h2 class="text-sm font-bold text-slate-900">Accepted emails · last 7 days</h2>
            @php $max = max(1, $chart->max('count')); @endphp
            <div class="flex h-28 items-end gap-2">
                @foreach($chart as $day)
                    <div class="flex flex-1 flex-col items-center gap-1">
                        <span class="text-[10px] text-slate-500">{{ $day['count'] }}</span>
                        <div class="w-full rounded-t bg-brand-green-500" style="height: {{ max(2, round($day['count'] / $max * 80)) }}px"></div>
                        <span class="text-[10px] text-slate-400">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="admin-table-wrap">
            <div class="flex items-center justify-between px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">Recent Activity</h2>
                <a href="{{ route('admin.email.logs.index') }}" class="text-xs font-semibold text-brand-green-700 hover:underline">All logs →</a>
            </div>
            @if($recentLogs->isEmpty())
                <x-admin.empty-state title="No emails yet" />
            @else
                <table class="admin-table">
                    <tbody>
                        @foreach($recentLogs as $log)
                            <tr>
                                <td>
                                    <span class="block max-w-[16rem] truncate font-medium text-slate-800">{{ $log->subject }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $log->recipient_email }} · {{ \App\Models\EmailLog::TYPES[$log->email_type] ?? $log->email_type }}</span>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    @include('admin.email.partials.status-badge', ['status' => $log->status])
                                    <span class="block text-[10px] text-slate-400">{{ $log->created_at?->diffForHumans() }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="admin-table-wrap">
            <div class="flex items-center justify-between px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">Campaigns in progress</h2>
                <a href="{{ route('admin.email.campaigns.index') }}" class="text-xs font-semibold text-brand-green-700 hover:underline">All campaigns →</a>
            </div>
            @if($activeCampaigns->isEmpty())
                <x-admin.empty-state title="No active campaigns" />
            @else
                <table class="admin-table">
                    <tbody>
                        @foreach($activeCampaigns as $c)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.email.campaigns.show', $c->id) }}" class="font-medium text-slate-800 hover:underline">{{ $c->name }}</a>
                                    <div class="mt-1 h-1.5 w-40 rounded bg-slate-100"><div class="h-1.5 rounded bg-brand-green-500" style="width: {{ $c->progressPercent() }}%"></div></div>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    @include('admin.email.partials.status-badge', ['status' => $c->status])
                                    <span class="block text-[10px] text-slate-400">{{ $c->sent_count }}/{{ $c->total_recipients }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
