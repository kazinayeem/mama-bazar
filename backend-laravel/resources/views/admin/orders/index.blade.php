@extends('layouts.admin', ['headerTitle' => 'Order Management'])

@section('content')
<div class="space-y-6">

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center gap-2 max-w-lg w-full">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Order ID, Customer name or Phone..."
                class="w-full text-xs rounded-xl border border-slate-200 bg-white p-2.5 focus:border-brand-green-500 focus:outline-none">
            <select name="status" class="text-xs rounded-xl border border-slate-200 bg-white p-2.5 focus:border-brand-green-500 focus:outline-none">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="shipped" {{ request('status') === 'shipped' ? 'selected' : '' }}>Shipped</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-800 text-white text-xs font-bold shrink-0">Filter</button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-soft overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider border-b border-slate-100">
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
                    @forelse($orders as $ord)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="p-4 font-bold text-brand-green-700">
                                <a href="{{ route('admin.orders.show', $ord->id) }}" class="hover:underline">
                                    {{ $ord->order_id }}
                                </a>
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-slate-800 block">{{ $ord->customer_name }}</span>
                                <span class="text-[11px] text-slate-400">{{ $ord->phone }}</span>
                            </td>
                            <td class="p-4 text-slate-600 font-semibold">
                                {{ $ord->items->count() }} items
                            </td>
                            <td class="p-4 font-extrabold text-slate-900">
                                ৳{{ number_format($ord->total_price, 0) }}
                            </td>
                            <td class="p-4">
                                <span class="uppercase text-[10px] font-bold text-slate-700 block">{{ $ord->payment_method }}</span>
                                <span class="text-[10px] {{ $ord->payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600' }} font-semibold">
                                    {{ ucfirst($ord->payment_status) }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                                    {{ $ord->status === 'delivered' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                    {{ in_array($ord->status, ['pending', 'processing']) ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                    {{ in_array($ord->status, ['cancelled', 'refunded']) ? 'bg-red-50 text-red-700 border border-red-200' : '' }}
                                    {{ $ord->status === 'shipped' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                                ">
                                    {{ $ord->status }}
                                </span>
                            </td>
                            <td class="p-4 text-slate-500">{{ $ord->created_at->format('M d, Y') }}</td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.orders.show', $ord->id) }}" class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">
                                        Details
                                    </a>
                                    <a href="{{ route('admin.orders.invoice', $ord->id) }}" target="_blank" class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">
                                        Invoice
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">No orders found matching criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-slate-100 flex items-center justify-center">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
