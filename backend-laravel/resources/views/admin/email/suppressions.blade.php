@extends('layouts.admin', ['headerTitle' => 'Suppression List'])

@section('content')
<div class="admin-page max-w-4xl">
    <x-admin.page-header title="Suppression List" subtitle="Addresses that never receive marketing campaigns (unsubscribes, bounces, complaints, manual blocks). Transactional order and account emails are unaffected.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.email.logs.index')" variant="outline" size="sm">← Email logs</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>
    @include('admin.email.partials.tabs')

    <div class="admin-surface p-4">
        <form action="{{ route('admin.email.logs.suppressions.store') }}" method="POST" class="flex flex-col gap-2 sm:flex-row">
            @csrf
            <input type="email" name="email" required placeholder="address@example.com" class="admin-control flex-1">
            <select name="reason" class="admin-control sm:w-44">
                @foreach(\App\Models\EmailSuppression::REASONS as $key => $label)
                    <option value="{{ $key }}" @selected($key === 'manual')>{{ $label }}</option>
                @endforeach
            </select>
            <input type="text" name="note" maxlength="255" placeholder="Note (optional)" class="admin-control sm:w-48">
            <x-admin.button type="submit" size="sm">Add</x-admin.button>
        </form>
    </div>

    <form method="GET" class="admin-filter-bar">
        <input type="search" name="search" value="{{ $search }}" placeholder="Search address" class="admin-control w-full sm:w-64">
        <x-admin.button type="submit" size="sm">Search</x-admin.button>
    </form>

    <div class="admin-table-wrap">
        @if($suppressions->isEmpty())
            <x-admin.empty-state title="No suppressed addresses" />
        @else
            <table class="admin-table">
                <thead><tr><th>Email</th><th>Reason</th><th>Source</th><th>Since</th><th class="text-right"></th></tr></thead>
                <tbody>
                    @foreach($suppressions as $s)
                        <tr>
                            <td class="text-xs text-slate-800">{{ $s->email }}@if($s->note)<span class="block text-[11px] text-slate-400">{{ $s->note }}</span>@endif</td>
                            <td><x-admin.badge :variant="$s->reason === 'unsubscribed' ? 'secondary' : 'destructive'">{{ \App\Models\EmailSuppression::REASONS[$s->reason] ?? $s->reason }}</x-admin.badge></td>
                            <td class="text-xs text-slate-500">{{ $s->source ?: '—' }}</td>
                            <td class="text-xs text-slate-500 whitespace-nowrap">{{ $s->updated_at?->format('d M Y') }}</td>
                            <td class="text-right">
                                <form action="{{ route('admin.email.logs.suppressions.destroy', $s->id) }}" method="POST" onsubmit="return confirm('Remove this address from the suppression list? It will only receive marketing email if it has opted in.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <x-admin.pagination :paginator="$suppressions" />
        @endif
    </div>
</div>
@endsection
