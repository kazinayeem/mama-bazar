@extends('layouts.admin', ['headerTitle' => 'Order Management'])

@section('content')
<div class="admin-page" x-data="{ filtersOpen: {{ request('status') ? 'true' : 'false' }} }">
    <x-admin.page-header title="Orders" :subtitle="$orders->total().' orders'" />

    <form method="GET" action="{{ route('admin.orders.index') }}" class="admin-filter-bar">
        <x-admin.search-input name="search" placeholder="Search by Order ID, customer name or phone..." />
        <button type="button" @click="filtersOpen = !filtersOpen"
                class="flex w-full items-center justify-between rounded-[6px] border border-[var(--admin-border)] bg-[var(--admin-muted)] px-3 py-2.5 text-xs font-semibold text-slate-700 md:hidden">
            <span>Status{{ request('status') ? ': '.ucfirst(request('status')) : '' }}</span>
            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="filtersOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
             :class="filtersOpen ? 'flex' : 'hidden md:flex'">
            <select name="status" class="admin-control w-full sm:w-44">
                <option value="">All Statuses</option>
                @foreach(['pending','confirmed','processing','shipped','delivered','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <x-admin.button type="submit" size="sm" class="w-full sm:w-auto">Filter</x-admin.button>
        </div>
    </form>

    <div class="admin-table-wrap">
        @if($orders->isEmpty())
            <x-admin.empty-state title="No orders found" description="Try a different search term or status filter." />
        @else
            <div class="md:hidden">
                @foreach($orders as $ord)
                    @php
                        $statusVariant = match(true) {
                            $ord->status === 'delivered' => 'success',
                            in_array($ord->status, ['pending', 'processing', 'confirmed'], true) => 'warning',
                            in_array($ord->status, ['cancelled', 'refunded'], true) => 'destructive',
                            $ord->status === 'shipped' => 'secondary',
                            default => 'muted',
                        };
                    @endphp
                    <div class="admin-mobile-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('admin.orders.show', $ord->id) }}" class="text-sm font-bold text-brand-green-700 hover:underline">{{ $ord->order_id }}</a>
                                <p class="mt-0.5 truncate text-sm font-semibold text-slate-900">{{ $ord->customer_name }}</p>
                                <p class="text-xs text-slate-500">{{ $ord->phone }}</p>
                            </div>
                            <x-admin.badge :variant="$statusVariant">{{ $ord->status }}</x-admin.badge>
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-slate-400">Items</dt>
                                <dd class="font-semibold text-slate-800">{{ $ord->items->count() }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-slate-400">Total</dt>
                                <dd class="font-bold text-slate-900">৳{{ number_format($ord->total_price, 0) }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-slate-400">Payment</dt>
                                <dd class="text-slate-700">
                                    <span class="uppercase">{{ $ord->payment_method }}</span>
                                    <span class="{{ $ord->payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600' }}"> · {{ ucfirst($ord->payment_status) }}</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-slate-400">Date</dt>
                                <dd class="text-slate-600">{{ $ord->created_at->format('M d, Y') }}</dd>
                            </div>
                        </dl>
                        <div class="mt-3 flex gap-2">
                            <x-admin.button :href="route('admin.orders.show', $ord->id)" variant="outline" size="sm" class="flex-1">Details</x-admin.button>
                            <x-admin.button :href="route('admin.orders.invoice', $ord->id)" variant="outline" size="sm" class="flex-1" target="_blank">Invoice</x-admin.button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th class="admin-hide-sm">Payment</th>
                            <th>Status</th>
                            <th class="admin-hide-md">Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $ord)
                            @php
                                $statusVariant = match(true) {
                                    $ord->status === 'delivered' => 'success',
                                    in_array($ord->status, ['pending', 'processing', 'confirmed'], true) => 'warning',
                                    in_array($ord->status, ['cancelled', 'refunded'], true) => 'destructive',
                                    $ord->status === 'shipped' => 'secondary',
                                    default => 'muted',
                                };
                            @endphp
                            <tr>
                                <td class="font-bold text-brand-green-700">
                                    <a href="{{ route('admin.orders.show', $ord->id) }}" class="hover:underline">{{ $ord->order_id }}</a>
                                </td>
                                <td>
                                    <span class="block font-semibold text-slate-900">{{ $ord->customer_name }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $ord->phone }}</span>
                                </td>
                                <td class="text-slate-600">{{ $ord->items->count() }}</td>
                                <td class="font-bold text-slate-900">৳{{ number_format($ord->total_price, 0) }}</td>
                                <td class="admin-hide-sm">
                                    <span class="block text-[10px] font-bold uppercase text-slate-700">{{ $ord->payment_method }}</span>
                                    <span class="text-[10px] font-semibold {{ $ord->payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst($ord->payment_status) }}</span>
                                </td>
                                <td><x-admin.badge :variant="$statusVariant">{{ $ord->status }}</x-admin.badge></td>
                                <td class="admin-hide-md text-slate-500">{{ $ord->created_at->format('M d, Y') }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <x-admin.button :href="route('admin.orders.show', $ord->id)" variant="outline" size="sm">Details</x-admin.button>
                                        <x-admin.button :href="route('admin.orders.invoice', $ord->id)" variant="ghost" size="sm" target="_blank">Invoice</x-admin.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.pagination :paginator="$orders" />
        @endif
    </div>
</div>
@endsection
