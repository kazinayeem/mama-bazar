@extends('layouts.admin', ['headerTitle' => 'Reviews'])

@section('content')
<div class="admin-page">
    <x-admin.page-header title="Reviews" :subtitle="$stats['total'].' total · '.$stats['pending'].' pending'" />

    <div class="admin-metric-grid" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Total</p><p class="mt-1 text-2xl font-bold">{{ number_format($stats['total']) }}</p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Pending</p><p class="mt-1 text-2xl font-bold text-amber-600">{{ number_format($stats['pending']) }}</p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Approved</p><p class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($stats['approved']) }}</p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Rejected</p><p class="mt-1 text-2xl font-bold text-red-500">{{ number_format($stats['rejected']) }}</p></div>
        <div class="admin-surface p-4"><p class="text-sm text-slate-500">Avg Rating</p><p class="mt-1 text-2xl font-bold">★ {{ $stats['average'] }}</p></div>
    </div>

    <form method="GET" action="{{ route('admin.reviews.index') }}" class="admin-filter-bar">
        <x-admin.search-input name="search" placeholder="Search comment, title, customer, product..." />
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
            <select name="status" class="admin-control w-full sm:w-36">
                <option value="">All statuses</option>
                @foreach(['pending','approved','rejected'] as $s)
                    <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="rating" class="admin-control w-full sm:w-32">
                <option value="">All ratings</option>
                @for($r = 5; $r >= 1; $r--)
                    <option value="{{ $r }}" @selected(($filters['rating'] ?? '') == $r)>{{ $r }} ★</option>
                @endfor
            </select>
            <select name="sort" class="admin-control w-full sm:w-36">
                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest</option>
                <option value="rating_high" @selected(($filters['sort'] ?? '') === 'rating_high')>Rating ↓</option>
                <option value="rating_low" @selected(($filters['sort'] ?? '') === 'rating_low')>Rating ↑</option>
            </select>
            <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 whitespace-nowrap">
                <input type="checkbox" name="verified" value="1" @checked(($filters['verified'] ?? '') === '1') onchange="this.form.submit()" class="rounded border-slate-300"> Verified
            </label>
            <x-admin.button type="submit" size="sm" class="w-full sm:w-auto">Filter</x-admin.button>
        </div>
    </form>

    <div class="admin-table-wrap">
        @if($reviews->isEmpty())
            <x-admin.empty-state title="No reviews found" description="Try a different search term or filter." />
        @else
            <div class="md:hidden">
                @foreach($reviews as $rev)
                    <div class="admin-mobile-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $rev->title ?: Str::limit($rev->comment, 40) }}</p>
                                @if($rev->title && $rev->comment)
                                    <p class="truncate text-xs text-slate-600">{{ Str::limit($rev->comment, 40) }}</p>
                                @endif
                                <p class="text-xs text-slate-500">{{ $rev->customer_name }} · {{ str_repeat('★', $rev->rating) }}</p>
                                <p class="text-[11px] text-slate-400">{{ $rev->product?->title }}</p>
                            </div>
                            <x-admin.badge :variant="$rev->status === 'approved' ? 'success' : ($rev->status === 'rejected' ? 'destructive' : 'warning')">{{ $rev->status }}</x-admin.badge>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <x-admin.button :href="route('admin.reviews.show', $rev->id)" variant="outline" size="sm" class="flex-1">Details</x-admin.button>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="hidden overflow-x-auto md:block">
                <table class="admin-table">
                    <thead>
                        <tr><th>Review</th><th>Product</th><th>Rating</th><th>Status</th><th>Date</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach($reviews as $rev)
                            <tr>
                                <td>
                                    <span class="block font-semibold text-slate-900">{{ $rev->title ?: Str::limit($rev->comment, 60) }}</span>
                                    @if($rev->title && $rev->comment)
                                        <span class="block text-xs text-slate-500">{{ Str::limit($rev->comment, 60) }}</span>
                                    @endif
                                    <span class="text-[11px] text-slate-400">{{ $rev->customer_name }}</span>
                                </td>
                                <td class="text-slate-600">{{ Str::limit($rev->product?->title ?: '—', 30) }}</td>
                                <td class="font-bold text-amber-500 whitespace-nowrap">{{ str_repeat('★', $rev->rating) }}</td>
                                <td><x-admin.badge :variant="$rev->status === 'approved' ? 'success' : ($rev->status === 'rejected' ? 'destructive' : 'warning')">{{ $rev->status }}</x-admin.badge></td>
                                <td class="text-slate-500">{{ $rev->created_at?->format('M d, Y') }}</td>
                                <td class="text-right">
                                    <x-admin.button :href="route('admin.reviews.show', $rev->id)" variant="outline" size="sm">Details</x-admin.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.pagination :paginator="$reviews" />
        @endif
    </div>
</div>
@endsection
