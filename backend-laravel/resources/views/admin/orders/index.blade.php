@extends('layouts.admin', ['headerTitle' => 'Order Management'])

@section('content')
<div class="space-y-6">

    <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-col gap-3" x-data="{ filtersOpen: {{ request('status') ? 'true' : 'false' }} }">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Order ID, customer name or phone..."
            class="admin-control w-full focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
        <button type="button" @click="filtersOpen = !filtersOpen"
                class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 md:hidden">
            <span>Status{{ request('status') ? ': '.ucfirst(request('status')) : '' }}</span>
            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="filtersOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center"
             :class="filtersOpen ? 'flex' : 'hidden md:flex'">
            <select name="status" class="admin-control w-full focus:border-brand-green-500 focus:outline-none md:flex-1">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Shipped</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <x-admin.button type="submit" size="sm" class="w-full shrink-0 sm:w-auto">Filter</x-admin.button>
        </div>
    </form>

    <div class="admin-table-wrap">
        @if($orders->isEmpty())
            <x-admin.empty-state title="No orders found" description="Try a different search term or status filter." />
        @else
            {{-- Mobile cards --}}
            <div class="divide-y divide-slate-100 md:hidden">
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
                    <div class="p-4">
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

            {{-- Desktop table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50 font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="p-4">Order ID</th>
                            <th class="p-4">Customer</th>
                            <th class="p-4">Items</th>
                            <th class="p-4">Total</th>
                            <th class="p-4">Payment</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Date</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
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
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="p-4 font-bold text-brand-green-700">
                                    <a href="{{ route('admin.orders.show', $ord->id) }}" class="hover:underline">{{ $ord->order_id }}</a>
                                </td>
                                <td class="p-4">
                                    <span class="block font-bold text-slate-800">{{ $ord->customer_name }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $ord->phone }}</span>
                                </td>
                                <td class="p-4 font-semibold text-slate-600">{{ $ord->items->count() }} items</td>
                                <td class="p-4 font-extrabold text-slate-900">৳{{ number_format($ord->total_price, 0) }}</td>
                                <td class="p-4">
                                    <span class="block text-[10px] font-bold uppercase text-slate-700">{{ $ord->payment_method }}</span>
                                    <span class="text-[10px] font-semibold {{ $ord->payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst($ord->payment_status) }}</span>
                                </td>
                                <td class="p-4">
                                    <x-admin.badge :variant="$statusVariant">{{ $ord->status }}</x-admin.badge>
                                </td>
                                <td class="p-4 text-slate-500">{{ $ord->created_at->format('M d, Y') }}</td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-admin.button :href="route('admin.orders.show', $ord->id)" variant="outline" size="sm">Details</x-admin.button>
                                        <x-admin.button :href="route('admin.orders.invoice', $ord->id)" variant="outline" size="sm" target="_blank">Invoice</x-admin.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-admin.pagination :paginator="$orders" class="border-t border-slate-100" />
        @endif
    </div>

</div>
@endsection
