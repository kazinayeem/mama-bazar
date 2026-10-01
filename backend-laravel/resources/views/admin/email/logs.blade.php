@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Outbound Email Logs &amp; Delivery Monitoring</h1>
            <p class="text-xs text-slate-500">Audit trail of all transactional notifications, OTP codes, order receipts, and marketing broadcasts.</p>
        </div>
        <div>
            <a href="{{ route('admin.email.dashboard') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                &larr; Back to Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-brand-green-50 border border-brand-green-200 text-brand-green-800 text-xs">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filter Toolbar --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-soft">
        <form action="{{ route('admin.email.logs') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Search Recipient or Subject</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. user@example.com" class="admin-control w-full text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Status</label>
                <select name="status" class="admin-control w-full text-xs">
                    <option value="">All Statuses</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                    <option value="skipped" {{ request('status') === 'skipped' ? 'selected' : '' }}>Skipped</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Email Type</label>
                <select name="type" class="admin-control w-full text-xs">
                    <option value="">All Types</option>
                    <option value="otp" {{ request('type') === 'otp' ? 'selected' : '' }}>OTP Verification</option>
                    <option value="order" {{ request('type') === 'order' ? 'selected' : '' }}>Order Notification</option>
                    <option value="transactional" {{ request('type') === 'transactional' ? 'selected' : '' }}>Transactional / Welcome</option>
                    <option value="campaign" {{ request('type') === 'campaign' ? 'selected' : '' }}>Campaign Broadcast</option>
                    <option value="test" {{ request('type') === 'test' ? 'selected' : '' }}>SMTP Test</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-brand-green-600 px-4 py-2 text-xs font-bold text-white hover:bg-brand-green-700 transition">
                    Filter Logs
                </button>
                <a href="{{ route('admin.email.logs') }}" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Logs Table --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-soft overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="p-3.5">Recipient</th>
                        <th class="p-3.5">Subject</th>
                        <th class="p-3.5">Type</th>
                        <th class="p-3.5">Related</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5">Timestamp</th>
                        <th class="p-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/60">
                            <td class="p-3.5 font-bold text-slate-900">
                                <div>{{ $log->recipient_email }}</div>
                                @if($log->recipient_name)
                                    <span class="text-[10px] font-normal text-slate-400">{{ $log->recipient_name }}</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-slate-700 max-w-xs">
                                <div class="truncate font-medium">{{ $log->subject }}</div>
                                @if($log->error_message)
                                    <div class="text-[10px] text-red-600 font-mono mt-0.5 truncate">{{ $log->error_message }}</div>
                                @endif
                            </td>
                            <td class="p-3.5">
                                <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                    {{ $log->email_type }}
                                </span>
                            </td>
                            <td class="p-3.5 text-slate-500">
                                @if($log->order)
                                    <span class="font-mono text-brand-green-700 font-bold">#{{ $log->order->order_id }}</span>
                                @elseif($log->campaign)
                                    <span class="text-slate-700 font-semibold">{{ $log->campaign->name }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="p-3.5">
                                @if($log->status === 'sent')
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold text-emerald-800">Sent</span>
                                @elseif($log->status === 'failed')
                                    <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-[10px] font-bold text-red-800">Failed</span>
                                @elseif($log->status === 'skipped')
                                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-bold text-amber-800">Skipped</span>
                                @else
                                    <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-[10px] font-bold text-blue-800">Queued</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-slate-400 whitespace-nowrap">
                                <div>{{ $log->created_at->format('M d, Y') }}</div>
                                <div class="text-[10px]">{{ $log->created_at->format('h:i:s A') }}</div>
                            </td>
                            <td class="p-3.5 text-right">
                                @if($log->status === 'failed')
                                    <form action="{{ route('admin.email.logs.retry', $log->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="rounded bg-slate-100 border border-slate-300 px-2 py-1 text-[10px] font-bold text-slate-700 hover:bg-slate-200">
                                            Retry
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">No email logs matched your search filters.</td>
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
