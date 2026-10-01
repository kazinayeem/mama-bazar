@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Email Marketing &amp; Automation</h1>
            <p class="text-xs text-slate-500">Monitor email deliverability, outbound queues, marketing campaigns, and SMTP health.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.email.settings') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                <span>⚙️</span> SMTP Settings
            </a>
            <a href="{{ route('admin.email.campaigns.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-green-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-brand-green-700">
                <span>➕</span> New Campaign
            </a>
        </div>
    </div>

    {{-- SMTP Status Card --}}
    <div class="rounded-2xl border p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 {{ $stats['last_status'] === 'connected' ? 'bg-emerald-50/50 border-emerald-200' : ($stats['last_status'] === 'failed' ? 'bg-red-50/50 border-red-200' : 'bg-slate-50/60 border-slate-200') }}">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl flex items-center justify-center text-lg {{ $stats['last_status'] === 'connected' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                {{ $stats['last_status'] === 'connected' ? '⚡' : '📡' }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">SMTP Connection Status:</h3>
                    @if($stats['last_status'] === 'connected')
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Connected ({{ $stats['last_latency'] }}ms)
                        </span>
                    @elseif($stats['last_status'] === 'failed')
                        <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Error
                        </span>
                    @else
                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">Untested</span>
                    @endif
                    @if(!$stats['mail_enabled'])
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">Sending Disabled</span>
                    @endif
                </div>
                @if($stats['last_error'])
                    <p class="text-[11px] text-red-600 font-mono mt-0.5 truncate max-w-lg">{{ $stats['last_error'] }}</p>
                @elseif($stats['last_tested'])
                    <p class="text-[11px] text-slate-500 mt-0.5">Last tested {{ \Carbon\Carbon::parse($stats['last_tested'])->diffForHumans() }}</p>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form action="{{ route('admin.email.settings.test-connection') }}" method="POST">
                @csrf
                <button type="submit" class="rounded-lg bg-white border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-xs">
                    Test Connection
                </button>
            </form>
        </div>
    </div>

    {{-- Metrics Grid --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sent Today</span>
            <p class="mt-1 text-2xl font-black text-slate-900">{{ number_format($stats['sent_today']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Sent</span>
            <p class="mt-1 text-2xl font-black text-emerald-700">{{ number_format($stats['total_sent']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Failed</span>
            <p class="mt-1 text-2xl font-black text-red-600">{{ number_format($stats['total_failed']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Active Campaigns</span>
            <p class="mt-1 text-2xl font-black text-brand-green-700">{{ number_format($stats['active_campaigns']) }}</p>
        </div>
    </div>

    {{-- Navigation Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.email.settings') }}" class="group block p-4 rounded-2xl bg-white border border-slate-200 hover:border-brand-green-500 transition shadow-soft">
            <div class="flex items-center gap-2 text-brand-green-700 font-bold text-sm">
                <span>⚙️</span> SMTP Configuration
            </div>
            <p class="mt-1 text-xs text-slate-500">Host, ports, sender identity, credentials, and test connection tools.</p>
        </a>

        <a href="{{ route('admin.email.templates') }}" class="group block p-4 rounded-2xl bg-white border border-slate-200 hover:border-brand-green-500 transition shadow-soft">
            <div class="flex items-center gap-2 text-brand-green-700 font-bold text-sm">
                <span>📄</span> Email Templates
            </div>
            <p class="mt-1 text-xs text-slate-500">16 editable system templates with dynamic placeholders and live preview.</p>
        </a>

        <a href="{{ route('admin.email.campaigns') }}" class="group block p-4 rounded-2xl bg-white border border-slate-200 hover:border-brand-green-500 transition shadow-soft">
            <div class="flex items-center gap-2 text-brand-green-700 font-bold text-sm">
                <span>📣</span> Email Campaigns
            </div>
            <p class="mt-1 text-xs text-slate-500">Create, schedule, audience filter, and queue marketing announcements.</p>
        </a>

        <a href="{{ route('admin.email.automation') }}" class="group block p-4 rounded-2xl bg-white border border-slate-200 hover:border-brand-green-500 transition shadow-soft">
            <div class="flex items-center gap-2 text-brand-green-700 font-bold text-sm">
                <span>⚡</span> Automation Rules
            </div>
            <p class="mt-1 text-xs text-slate-500">Enable/disable transactional triggers and automated PDF attachments.</p>
        </a>
    </div>

    {{-- Recent Outbound Email Logs Table --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-soft overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Recent Outbound Email Logs</h2>
                <p class="text-xs text-slate-500">Live delivery records across transactional, order, and marketing messages.</p>
            </div>
            <a href="{{ route('admin.email.logs') }}" class="text-xs font-bold text-brand-green-700 hover:underline">View All Logs &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="p-3">Recipient</th>
                        <th class="p-3">Subject</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentLogs as $log)
                        <tr class="hover:bg-slate-50/60">
                            <td class="p-3 font-semibold text-slate-900">{{ $log->recipient_email }}</td>
                            <td class="p-3 text-slate-700 max-w-xs truncate">{{ $log->subject }}</td>
                            <td class="p-3">
                                <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase bg-slate-100 text-slate-600">
                                    {{ $log->email_type }}
                                </span>
                            </td>
                            <td class="p-3">
                                @if($log->status === 'sent')
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Sent</span>
                                @elseif($log->status === 'failed')
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800">Failed</span>
                                @elseif($log->status === 'skipped')
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">Skipped</span>
                                @else
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-800">Queued</span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-400">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400">No outbound email logs recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
