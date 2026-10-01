@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Marketing Email Campaigns</h1>
            <p class="text-xs text-slate-500">Create, schedule, audience-target, and queue bulk marketing announcements.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.email.dashboard') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                &larr; Dashboard
            </a>
            <a href="{{ route('admin.email.campaigns.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-green-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-brand-green-700">
                <span>➕</span> New Campaign
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-brand-green-50 border border-brand-green-200 text-brand-green-800 text-xs">
            {{ session('success') }}
        </div>
    @endif

    {{-- Campaigns Table --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-soft overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="p-3.5">Campaign Name</th>
                        <th class="p-3.5">Audience Target</th>
                        <th class="p-3.5">Progress (Sent / Total)</th>
                        <th class="p-3.5 text-center">Status</th>
                        <th class="p-3.5">Created Date</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($campaigns as $camp)
                        <tr class="hover:bg-slate-50/60">
                            <td class="p-3.5 font-bold text-slate-900">
                                <div>{{ $camp->name }}</div>
                                <span class="text-[11px] text-slate-500">{{ $camp->subject }}</span>
                            </td>
                            <td class="p-3.5">
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700">
                                    {{ $audiences[$camp->audience_filter] ?? $camp->audience_filter }}
                                </span>
                            </td>
                            <td class="p-3.5 font-mono">
                                <div>{{ number_format($camp->sent_count) }} / {{ number_format($camp->total_recipients) }}</div>
                                @if($camp->failed_count > 0)
                                    <span class="text-[10px] text-red-600 font-bold">({{ $camp->failed_count }} failed)</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-center">
                                @if($camp->status === 'completed')
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">Completed</span>
                                @elseif($camp->status === 'sending' || $camp->status === 'queued')
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-800 animate-pulse">Sending</span>
                                @elseif($camp->status === 'paused')
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">Paused</span>
                                @elseif($camp->status === 'cancelled')
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-800">Cancelled</span>
                                @else
                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">Draft</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-slate-400">{{ $camp->created_at->format('M d, Y') }}</td>
                            <td class="p-3.5 text-right space-x-1">
                                <a href="{{ route('admin.email.campaigns.show', $camp->id) }}" class="rounded bg-slate-100 border border-slate-200 px-2.5 py-1 text-slate-700 hover:bg-slate-200 font-bold text-[11px]">
                                    View
                                </a>
                                @if($camp->status === 'draft')
                                    <form action="{{ route('admin.email.campaigns.send', $camp->id) }}" method="POST" class="inline" onsubmit="return confirm('Send this campaign to {{ $camp->total_recipients }} recipients?');">
                                        @csrf
                                        <button type="submit" class="rounded bg-brand-green-600 px-2.5 py-1 text-white hover:bg-brand-green-700 font-bold text-[11px]">
                                            Send Now
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">No campaigns created yet. Click "New Campaign" to create your first announcement!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($campaigns->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
