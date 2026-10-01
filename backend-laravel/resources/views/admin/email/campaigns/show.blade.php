@extends('layouts.admin', ['headerTitle' => 'Campaign'])

@section('content')
@php
    $isDraft = $campaign->status === 'draft';
    $isScheduled = $campaign->status === 'scheduled';
    $isActive = in_array($campaign->status, ['queued', 'sending'], true);
    $canManage = \App\Http\Middleware\EnsureAdminPermission::allows(auth()->user(), ['email.campaigns.manage']);
@endphp
<div class="admin-page" @if($isActive) x-data x-init="setTimeout(() => window.location.reload(), 15000)" @endif>
    <x-admin.page-header :title="$campaign->name" :subtitle="$campaign->subject">
        <x-slot:meta>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                @include('admin.email.partials.status-badge', ['status' => $campaign->status])
                <span>{{ $templateLabel }}</span> · <span>{{ $audienceLabel }}</span>
                @if($campaign->creator) · <span>by {{ $campaign->creator->name }}</span>@endif
            </div>
        </x-slot:meta>
        <x-slot:actions>
            <x-admin.button :href="route('admin.email.campaigns.index')" variant="outline" size="sm">← Campaigns</x-admin.button>
            <x-admin.button :href="route('admin.email.campaigns.preview', $campaign->id)" variant="outline" size="sm" target="_blank" rel="noopener">Preview ↗</x-admin.button>
            @if($canManage && $campaign->isEditable())
                <x-admin.button :href="route('admin.email.campaigns.edit', $campaign->id)" variant="outline" size="sm">Edit</x-admin.button>
            @endif
            @if($canManage)
                <form action="{{ route('admin.email.campaigns.duplicate', $campaign->id) }}" method="POST">@csrf<x-admin.button type="submit" variant="ghost" size="sm">Duplicate</x-admin.button></form>
            @endif
        </x-slot:actions>
    </x-admin.page-header>
    @include('admin.email.partials.tabs')

    @if($campaign->last_error)
        <div class="rounded-[8px] border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 break-words">{{ $campaign->last_error }}</div>
    @endif

    @if($campaign->total_recipients > 0)
        <div class="admin-metric-grid" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));">
            <x-admin.metric-card label="Recipients" :value="number_format($campaign->total_recipients)" />
            <x-admin.metric-card label="Pending" :value="number_format($campaign->queued_count)" tone="warning" />
            <x-admin.metric-card label="Accepted by SMTP" :value="number_format($campaign->sent_count)" tone="success" />
            <x-admin.metric-card label="Failed" :value="number_format($campaign->failed_count)" :tone="$campaign->failed_count ? 'danger' : 'default'" />
            <x-admin.metric-card label="Skipped" :value="number_format($campaign->skipped_count)" hint="Unsubscribed / cancelled" />
        </div>
        <div class="admin-surface p-4">
            <div class="mb-1 flex justify-between text-xs text-slate-500"><span>Progress</span><span>{{ $campaign->progressPercent() }}%</span></div>
            <div class="h-2 rounded bg-slate-100"><div class="h-2 rounded bg-brand-green-500 transition-all" style="width: {{ $campaign->progressPercent() }}%"></div></div>
            <p class="mt-2 text-[11px] text-slate-400">
                Started {{ $campaign->started_at?->format('d M Y, h:i A') ?? '—' }}
                @if($campaign->completed_at) · Finished {{ $campaign->completed_at->format('d M Y, h:i A') }} @endif
                @if($isActive) · Refreshes every 15 seconds @endif
            </p>
        </div>
    @endif

    @if($canSend && ($isActive || in_array($campaign->status, ['paused', 'completed', 'failed'], true)))
        <div class="admin-surface flex flex-wrap items-center gap-2 p-4">
            @if($isActive)
                <form action="{{ route('admin.email.campaigns.pause', $campaign->id) }}" method="POST">@csrf<x-admin.button type="submit" variant="outline" size="sm">Pause</x-admin.button></form>
            @endif
            @if($campaign->status === 'paused')
                <form action="{{ route('admin.email.campaigns.resume', $campaign->id) }}" method="POST">@csrf<x-admin.button type="submit" size="sm">Resume</x-admin.button></form>
            @endif
            @if($campaign->failed_count > 0)
                <form action="{{ route('admin.email.campaigns.retry-failed', $campaign->id) }}" method="POST">@csrf<x-admin.button type="submit" variant="outline" size="sm">Retry failed recipients</x-admin.button></form>
            @endif
            @if($isActive || $campaign->status === 'paused')
                <form action="{{ route('admin.email.campaigns.cancel', $campaign->id) }}" method="POST" onsubmit="return confirm('Cancel this campaign? Pending recipients will not be emailed. This cannot be undone.')">@csrf<x-admin.button type="submit" variant="destructive" size="sm">Cancel campaign</x-admin.button></form>
            @endif
        </div>
    @endif

    @if($isDraft || $isScheduled)
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="space-y-4">
                <div class="admin-surface p-5 space-y-3">
                    <h2 class="text-sm font-bold text-slate-900">Step 1 · Send yourself a test</h2>
                    <form action="{{ route('admin.email.campaigns.test', $campaign->id) }}" method="POST" class="flex gap-2">
                        @csrf
                        <input type="email" name="test_email" required value="{{ auth()->user()->email }}" class="admin-control flex-1" placeholder="you@example.com">
                        <x-admin.button type="submit" variant="outline" size="sm" :disabled="! $canManage">Send test</x-admin.button>
                    </form>
                </div>

                <div class="admin-surface p-5 space-y-3">
                    <h2 class="text-sm font-bold text-slate-900">Step 2 · Review &amp; confirm</h2>
                    <dl class="grid grid-cols-3 gap-1 text-xs">
                        <dt class="text-slate-500">Audience</dt><dd class="col-span-2">{{ $audienceLabel }}</dd>
                        <dt class="text-slate-500">Eligible now</dt><dd class="col-span-2 font-bold text-slate-900">{{ number_format($audienceCount) }} recipients</dd>
                        <dt class="text-slate-500">Subject</dt><dd class="col-span-2">{{ $campaign->subject }}</dd>
                    </dl>
                    <p class="text-[11px] text-slate-400">Consent and the suppression list are re-checked when sending starts and again for every recipient.</p>

                    @if($problems)
                        <ul class="list-disc space-y-1 rounded bg-red-50 p-3 pl-6 text-xs text-red-700">
                            @foreach($problems as $problem)<li>{{ $problem }}</li>@endforeach
                        </ul>
                    @endif

                    @if($isScheduled)
                        <div class="rounded bg-sky-50 p-3 text-xs text-sky-800">
                            Scheduled for <strong>{{ $campaign->scheduled_at?->format('d M Y, h:i A') }}</strong> ({{ config('app.timezone') }}).
                        </div>
                        @if($canSend)
                            <form action="{{ route('admin.email.campaigns.unschedule', $campaign->id) }}" method="POST">@csrf<x-admin.button type="submit" variant="outline" size="sm">Unschedule (back to draft)</x-admin.button></form>
                        @endif
                    @elseif(! $canSend)
                        <p class="text-xs text-slate-500">You can prepare this campaign; a team member with “Send Campaigns” permission must confirm it.</p>
                    @elseif(! $problems && $audienceCount > 0)
                        <form action="{{ route('admin.email.campaigns.confirm', $campaign->id) }}" method="POST" class="space-y-3" x-data="{ mode: 'now' }">
                            @csrf
                            <div class="flex gap-4 text-xs">
                                <label class="inline-flex items-center gap-1.5"><input type="radio" name="send_mode" value="now" x-model="mode"> Send now</label>
                                <label class="inline-flex items-center gap-1.5"><input type="radio" name="send_mode" value="schedule" x-model="mode"> Schedule</label>
                            </div>
                            <div x-show="mode === 'schedule'">
                                <input type="datetime-local" name="scheduled_at" class="admin-control w-full" min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                                <p class="mt-1 text-[11px] text-slate-400">Timezone: {{ config('app.timezone') }}</p>
                            </div>
                            @if($audienceCount >= $largeThreshold)
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-700">Type the recipient count ({{ $audienceCount }}) to confirm</label>
                                    <input type="number" name="confirm_count" required class="admin-control w-40" autocomplete="off">
                                </div>
                            @endif
                            <label class="flex items-start gap-2 text-xs">
                                <input type="checkbox" name="acknowledge" value="1" required class="mt-0.5 rounded border-slate-300">
                                <span>I have sent a test, reviewed the content and confirm sending to {{ number_format($audienceCount) }} consented recipients.</span>
                            </label>
                            <x-admin.button type="submit" size="sm" onclick="return confirm('Final confirmation: start this campaign?')">Confirm campaign</x-admin.button>
                        </form>
                    @endif
                </div>

                @if($canManage && $isDraft)
                    <form action="{{ route('admin.email.campaigns.destroy', $campaign->id) }}" method="POST" onsubmit="return confirm('Delete this draft?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Delete draft</button>
                    </form>
                @endif
            </div>

            <div class="admin-surface overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-2 text-xs font-bold text-slate-700">Preview</div>
                <iframe sandbox="" title="Campaign preview" class="h-[620px] w-full bg-slate-50" src="{{ route('admin.email.campaigns.preview', $campaign->id) }}"></iframe>
            </div>
        </div>
    @endif

    @if($campaign->total_recipients > 0)
        <div class="admin-table-wrap">
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">Recipients</h2>
                <form method="GET" class="flex gap-2">
                    <select name="recipient_status" class="admin-control w-40" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach(['pending', 'processing', 'sent', 'failed', 'skipped'] as $s)
                            <option value="{{ $s }}" @selected(request('recipient_status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <table class="admin-table">
                <thead><tr><th>Email</th><th>Status</th><th>Attempts</th><th>Sent</th><th>Error</th></tr></thead>
                <tbody>
                    @foreach($recipients as $r)
                        <tr>
                            <td class="text-xs">{{ $r->email }}<span class="block text-[11px] text-slate-400">{{ $r->name }}</span></td>
                            <td>@include('admin.email.partials.status-badge', ['status' => $r->status])</td>
                            <td class="text-xs text-slate-500">{{ $r->attempts }}</td>
                            <td class="text-xs text-slate-500 whitespace-nowrap">{{ $r->sent_at?->format('d M, h:i A') ?? '—' }}</td>
                            <td class="max-w-xs truncate text-[11px] text-red-600" title="{{ $r->error_message }}">{{ $r->error_message }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <x-admin.pagination :paginator="$recipients" />
        </div>
    @endif
</div>
@endsection
