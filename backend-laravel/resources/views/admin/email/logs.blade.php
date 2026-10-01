@extends('layouts.admin', ['headerTitle' => 'Email Logs'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Email Logs" :subtitle="'Every outgoing email and its delivery state. Logs are kept for '.$retentionDays.' days.'">
        <x-slot:actions>
            <x-admin.button :href="route('admin.email.logs.suppressions')" variant="outline" size="sm">Suppression list</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>
    @include('admin.email.partials.tabs')

    <form method="GET" action="{{ route('admin.email.logs.index') }}" class="admin-filter-bar flex-wrap">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Recipient or subject" class="admin-control w-full sm:w-56">
        <select name="status" class="admin-control w-full sm:w-40">
            <option value="">All statuses</option>
            @foreach(\App\Models\EmailLog::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="type" class="admin-control w-full sm:w-44">
            <option value="">All types</option>
            @foreach(\App\Models\EmailLog::TYPES as $key => $label)
                <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="text" name="order" value="{{ $filters['order'] ?? '' }}" placeholder="Order no. (BS-…)" class="admin-control w-full sm:w-36">
        <input type="number" name="campaign_id" value="{{ $filters['campaign_id'] ?? '' }}" placeholder="Campaign ID" class="admin-control w-full sm:w-28">
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="admin-control w-full sm:w-36" aria-label="From date">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="admin-control w-full sm:w-36" aria-label="To date">
        <x-admin.button type="submit" size="sm">Filter</x-admin.button>
        @if(array_filter($filters))
            <a href="{{ route('admin.email.logs.index') }}" class="text-xs font-semibold text-slate-500 hover:underline">Reset</a>
        @endif
    </form>

    <div class="admin-table-wrap">
        @if($logs->isEmpty())
            <x-admin.empty-state title="No emails match these filters" />
        @else
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr><th>Date</th><th>Recipient</th><th>Subject</th><th>Type</th><th>Status</th><th>Attempts</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap text-xs text-slate-500">{{ $log->created_at?->format('d M, h:i A') }}</td>
                                <td class="text-xs">
                                    <span class="block text-slate-800">{{ $log->recipient_email }}</span>
                                    @if($log->order)<a href="{{ route('admin.orders.show', $log->order_id) }}" class="text-[11px] text-brand-green-700 hover:underline">{{ $log->order->order_id }}</a>@endif
                                    @if($log->campaign)<a href="{{ route('admin.email.campaigns.show', $log->campaign_id) }}" class="text-[11px] text-brand-green-700 hover:underline">{{ \Illuminate\Support\Str::limit($log->campaign->name, 24) }}</a>@endif
                                </td>
                                <td class="max-w-xs text-xs">
                                    <span class="block truncate text-slate-800">{{ $log->subject }}</span>
                                    @if($log->status === 'failed' && $log->error_message)
                                        <span class="block truncate text-[11px] text-red-600" title="{{ $log->error_message }}">{{ $log->error_message }}</span>
                                    @endif
                                </td>
                                <td class="text-xs text-slate-600 whitespace-nowrap">{{ \App\Models\EmailLog::TYPES[$log->email_type] ?? $log->email_type }}</td>
                                <td>@include('admin.email.partials.status-badge', ['status' => $log->status])</td>
                                <td class="text-xs text-slate-500">{{ $log->attempts }}</td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('admin.email.logs.show', $log->id) }}" class="text-xs font-semibold text-slate-600 hover:underline">Details</a>
                                    @if($log->isRetryable())
                                        <form action="{{ route('admin.email.logs.retry', $log->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="ml-2 text-xs font-semibold text-brand-green-700 hover:underline">Retry</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.pagination :paginator="$logs" />
        @endif
    </div>
    <p class="text-[11px] text-slate-400">"Accepted by SMTP" means the mail server accepted the message. Bounces arrive later in the sender mailbox. OTP and password-reset emails cannot be retried — customers request a new code instead.</p>
</div>
@endsection
