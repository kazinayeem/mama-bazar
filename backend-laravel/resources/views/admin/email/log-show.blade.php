@extends('layouts.admin', ['headerTitle' => 'Email Log'])

@section('content')
<div class="admin-page max-w-3xl">
    <x-admin.page-header :title="'Email #'.$log->id" :subtitle="$log->subject">
        <x-slot:actions>
            <x-admin.button :href="route('admin.email.logs.index')" variant="outline" size="sm">← Back to logs</x-admin.button>
            @if($log->isRetryable())
                <form action="{{ route('admin.email.logs.retry', $log->id) }}" method="POST">
                    @csrf
                    <x-admin.button type="submit" size="sm">Retry</x-admin.button>
                </form>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-surface p-5">
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
            <dt class="text-slate-500">Status</dt><dd class="sm:col-span-2">@include('admin.email.partials.status-badge', ['status' => $log->status])</dd>
            <dt class="text-slate-500">Recipient</dt><dd class="sm:col-span-2">{{ $log->recipient_name ? $log->recipient_name.' · ' : '' }}{{ $log->recipient_email }}</dd>
            <dt class="text-slate-500">Type</dt><dd class="sm:col-span-2">{{ \App\Models\EmailLog::TYPES[$log->email_type] ?? $log->email_type }}</dd>
            <dt class="text-slate-500">Template</dt><dd class="sm:col-span-2 font-mono text-xs">{{ $log->template_key ?: '—' }}</dd>
            @if($log->order)
                <dt class="text-slate-500">Order</dt><dd class="sm:col-span-2"><a href="{{ route('admin.orders.show', $log->order_id) }}" class="text-brand-green-700 hover:underline">{{ $log->order->order_id }}</a></dd>
            @endif
            @if($log->campaign)
                <dt class="text-slate-500">Campaign</dt><dd class="sm:col-span-2"><a href="{{ route('admin.email.campaigns.show', $log->campaign_id) }}" class="text-brand-green-700 hover:underline">{{ $log->campaign->name }}</a></dd>
            @endif
            <dt class="text-slate-500">Created</dt><dd class="sm:col-span-2">{{ $log->created_at?->format('d M Y, h:i:s A') }}</dd>
            <dt class="text-slate-500">Last attempt</dt><dd class="sm:col-span-2">{{ $log->last_attempt_at?->format('d M Y, h:i:s A') ?? '—' }}</dd>
            <dt class="text-slate-500">Accepted by SMTP</dt><dd class="sm:col-span-2">{{ $log->sent_at?->format('d M Y, h:i:s A') ?? '—' }}</dd>
            <dt class="text-slate-500">Attempts</dt><dd class="sm:col-span-2">{{ $log->attempts }}</dd>
            <dt class="text-slate-500">Message ID</dt><dd class="sm:col-span-2 font-mono text-xs break-all">{{ $log->message_id ?: '—' }}</dd>
            @if(! empty($log->metadata['attachments']))
                <dt class="text-slate-500">Attachments</dt><dd class="sm:col-span-2 text-xs">{{ implode(', ', (array) $log->metadata['attachments']) }}</dd>
            @endif
            @if(! empty($log->metadata['invoice_attachment_failed']))
                <dt class="text-slate-500">Invoice PDF</dt><dd class="sm:col-span-2 text-xs text-amber-700">PDF generation failed — the email was sent without the attachment. Use “Email Invoice” on the order to resend.</dd>
            @endif
        </dl>
        @if($log->error_message)
            <div class="mt-4 rounded-[6px] border border-red-200 bg-red-50 p-3 text-xs text-red-700 break-words">
                <strong>Error:</strong> {{ $log->error_message }}
            </div>
        @endif
        <p class="mt-4 text-[11px] text-slate-400">Email bodies are not stored. Errors are sanitized — credentials, OTP codes and reset links are never recorded.</p>
    </div>
</div>
@endsection
