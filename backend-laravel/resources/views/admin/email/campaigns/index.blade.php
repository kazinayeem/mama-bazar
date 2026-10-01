@extends('layouts.admin', ['headerTitle' => 'Email Campaigns'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Email Campaigns" subtitle="Marketing emails go only to customers who opted in. Unsubscribed and suppressed addresses are always excluded.">
        <x-slot:actions>
            @if(\App\Http\Middleware\EnsureAdminPermission::allows(auth()->user(), ['email.campaigns.manage']))
                <x-admin.button :href="route('admin.email.campaigns.create')" size="sm">New campaign</x-admin.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>
    @include('admin.email.partials.tabs')

    <form method="GET" class="admin-filter-bar">
        <select name="status" class="admin-control w-full sm:w-44" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach(\App\Models\EmailCampaign::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    <div class="admin-table-wrap">
        @if($campaigns->isEmpty())
            <x-admin.empty-state title="No campaigns yet" description="Create a campaign, send yourself a test, then review and confirm to send." />
        @else
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead><tr><th>Campaign</th><th>Audience</th><th>Status</th><th>Progress</th><th>Schedule</th><th class="text-right"></th></tr></thead>
                    <tbody>
                        @foreach($campaigns as $c)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.email.campaigns.show', $c->id) }}" class="block font-semibold text-slate-900 hover:underline">{{ $c->name }}</a>
                                    <span class="block max-w-xs truncate text-[11px] text-slate-400">{{ $c->subject }}</span>
                                </td>
                                <td class="text-xs text-slate-600">{{ $audiences[$c->audience_filter] ?? $c->audience_filter }}</td>
                                <td>@include('admin.email.partials.status-badge', ['status' => $c->status])</td>
                                <td class="text-xs">
                                    @if($c->total_recipients > 0)
                                        <div class="h-1.5 w-28 rounded bg-slate-100"><div class="h-1.5 rounded bg-brand-green-500" style="width: {{ $c->progressPercent() }}%"></div></div>
                                        <span class="text-[11px] text-slate-500">{{ $c->sent_count }} accepted · {{ $c->failed_count }} failed / {{ $c->total_recipients }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="text-xs text-slate-500 whitespace-nowrap">
                                    {{ $c->scheduled_at?->format('d M Y, h:i A') ?? ($c->started_at?->format('d M Y, h:i A') ?? '—') }}
                                </td>
                                <td class="text-right"><a href="{{ route('admin.email.campaigns.show', $c->id) }}" class="text-xs font-semibold text-brand-green-700 hover:underline">Open</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.pagination :paginator="$campaigns" />
        @endif
    </div>
</div>
@endsection
