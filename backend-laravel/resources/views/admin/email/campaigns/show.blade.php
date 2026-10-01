@extends('layouts.admin')

@section('content')
<div class="space-y-6 max-w-5xl">

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ $campaign->name }}</h1>
                @if($campaign->status === 'completed')
                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800">Completed</span>
                @elseif($campaign->status === 'sending' || $campaign->status === 'queued')
                    <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-800 animate-pulse">Sending in Background</span>
                @elseif($campaign->status === 'paused')
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">Paused</span>
                @elseif($campaign->status === 'cancelled')
                    <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-800">Cancelled</span>
                @else
                    <span class="rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-bold text-slate-600">Draft</span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-1">Subject: <span class="font-semibold text-slate-800">{{ $campaign->subject }}</span> &middot; Audience: {{ $campaign->audience_filter }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.email.campaigns') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                &larr; Back to Campaigns
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-brand-green-50 border border-brand-green-200 text-brand-green-800 text-xs">
            {{ session('success') }}
        </div>
    @endif

    {{-- Progress Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Delivery Progress</h2>
            <div class="flex items-center gap-2">
                @if($campaign->status === 'draft')
                    <form action="{{ route('admin.email.campaigns.send', $campaign->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('Launch this campaign?');" class="rounded-lg bg-brand-green-600 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-brand-green-700">
                            Launch Campaign
                        </button>
                    </form>
                @elseif($campaign->status === 'sending')
                    <form action="{{ route('admin.email.campaigns.pause', $campaign->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-lg bg-amber-500 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-amber-600">
                            Pause Campaign
                        </button>
                    </form>
                    <form action="{{ route('admin.email.campaigns.cancel', $campaign->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('Are you sure you want to cancel this campaign?');" class="rounded-lg bg-red-600 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-red-700">
                            Cancel
                        </button>
                    </form>
                @elseif($campaign->status === 'paused')
                    <form action="{{ route('admin.email.campaigns.send', $campaign->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-lg bg-brand-green-600 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-brand-green-700">
                            Resume Campaign
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @php
            $total = max(1, $campaign->total_recipients);
            $pct = min(100, round(($campaign->sent_count / $total) * 100));
        @endphp

        <div>
            <div class="flex justify-between text-xs font-bold mb-1">
                <span>{{ $pct }}% Completed</span>
                <span class="font-mono text-slate-500">{{ $campaign->sent_count }} / {{ $campaign->total_recipients }} Sent</span>
            </div>
            <div class="w-full h-3 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full bg-brand-green-600 transition-all duration-500" style="width: {{ $pct }}%"></div>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-3 pt-2 text-center">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400">Total</span>
                <p class="text-base font-extrabold text-slate-800">{{ number_format($campaign->total_recipients) }}</p>
            </div>
            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200">
                <span class="text-[10px] uppercase font-bold text-emerald-600">Sent</span>
                <p class="text-base font-extrabold text-emerald-700">{{ number_format($campaign->sent_count) }}</p>
            </div>
            <div class="p-3 rounded-xl bg-red-50 border border-red-200">
                <span class="text-[10px] uppercase font-bold text-red-600">Failed</span>
                <p class="text-base font-extrabold text-red-700">{{ number_format($campaign->failed_count) }}</p>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400">Started</span>
                <p class="text-[11px] font-semibold text-slate-600 mt-1">{{ $campaign->started_at ? $campaign->started_at->diffForHumans() : 'Not started' }}</p>
            </div>
        </div>
    </div>

    {{-- Send Test Email Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Send Test Email for this Campaign</h3>
        <form action="{{ route('admin.email.campaigns.send-test', $campaign->id) }}" method="POST" class="flex gap-2">
            @csrf
            <input type="email" name="test_email" required value="{{ auth()->user()->email }}" placeholder="admin@example.com" class="admin-control flex-1 text-xs">
            <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2 text-xs font-bold text-white hover:bg-slate-900 shadow-xs transition">
                Send Test
            </button>
        </form>
    </div>

    {{-- Logs for this Campaign --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-soft overflow-hidden">
        <div class="p-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Campaign Delivery Logs</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="p-3">Recipient</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr>
                            <td class="p-3 font-semibold text-slate-900">{{ $log->recipient_email }}</td>
                            <td class="p-3">
                                @if($log->status === 'sent')
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Sent</span>
                                @else
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800">Failed</span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-400">{{ $log->created_at->format('h:i:s A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-6 text-center text-slate-400">No messages dispatched yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
