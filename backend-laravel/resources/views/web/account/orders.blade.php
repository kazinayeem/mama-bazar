@extends('web.account.layout')

@section('account-title', 'My Orders')

@section('account-content')
<div class="space-y-6">

    {{-- Orders Page Header & Search Filter Bar --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Order History</h2>
                <p class="text-xs text-slate-500">Track and manage your current and past Mama Bazar purchases.</p>
            </div>
            <div class="text-xs font-semibold text-slate-500">
                Total Orders Found: <span class="font-bold text-slate-900">{{ $orders->total() }}</span>
            </div>
        </div>

        {{-- Filter Pills & Search Input --}}
        <div class="pt-3 border-t border-slate-100 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            {{-- Status Tabs --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 text-xs">
                @php
                    $tabs = [
                        'all' => 'All Orders',
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ];
                @endphp
                @foreach($tabs as $key => $label)
                    <a href="{{ route('account.orders', array_merge(request()->query(), ['status' => $key, 'page' => 1])) }}"
                       class="whitespace-nowrap rounded-xl px-3 py-1.5 font-bold transition {{ ($currentStatus === $key) ? 'bg-brand-green-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Keyword Search Form --}}
            <form action="{{ route('account.orders') }}" method="GET" class="flex items-center gap-2 shrink-0">
                @if(request('status') && request('status') !== 'all')
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative w-full sm:w-64">
                    <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Order #, invoice or tracking…"
                           class="w-full rounded-xl border border-slate-200 py-1.5 pl-8 pr-3 text-xs focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                    <svg class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="rounded-xl bg-slate-900 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-slate-800 transition">
                    Search
                </button>
                @if($searchQuery || request('status'))
                    <a href="{{ route('account.orders') }}" class="rounded-xl border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition" title="Clear filters">
                        Clear
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Orders List --}}
    @if($orders->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center space-y-3">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-green-50 text-brand-green-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <div>
                <h3 class="text-base font-extrabold text-slate-900">No orders found</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    @if($searchQuery || $currentStatus !== 'all')
                        No orders match the selected filters. Try searching with a different keyword or resetting your filter.
                    @else
                        You have not placed any orders with this account yet.
                    @endif
                </p>
            </div>
            <div class="pt-2">
                @if($searchQuery || $currentStatus !== 'all')
                    <a href="{{ route('account.orders') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                        Reset All Filters
                    </a>
                @else
                    <a href="{{ route('shop') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-brand-green-700 transition">
                        Start Shopping Now
                    </a>
                @endif
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach($orders as $order)
                <div class="rounded-2xl border border-slate-200/90 bg-white shadow-xs overflow-hidden hover:border-slate-300 transition">
                    {{-- Order Header Bar --}}
                    <div class="bg-slate-50/70 p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                            <div>
                                <span class="text-slate-400 font-medium">Order Number:</span>
                                <span class="font-black text-slate-900 ml-1">{{ $order->order_id }}</span>
                            </div>
                            <div class="text-slate-300 hidden sm:inline">&bull;</div>
                            <div>
                                <span class="text-slate-400 font-medium">Placed:</span>
                                <span class="font-semibold text-slate-700 ml-1">{{ $order->created_at?->format('M d, Y · h:i A') }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            {{-- Status Badge --}}
                            @php
                                $statusStyles = match(strtolower($order->status)) {
                                    'delivered' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'shipped', 'out_for_delivery' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    'processing', 'confirmed' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'cancelled', 'returned' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    default => 'bg-amber-100 text-amber-800 border-amber-200',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $statusStyles }}">
                                {{ str_replace('_', ' ', $order->status) }}
                            </span>

                            {{-- Payment Badge --}}
                            @php
                                $payStyles = match(strtolower($order->payment_status)) {
                                    'paid', 'success', 'verified' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'refunded' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-semibold {{ $payStyles }}">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </div>
                    </div>

                    {{-- Order Items Preview & Details --}}
                    <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        {{-- Items Thumbnails & Summary --}}
                        <div class="space-y-3 flex-1 min-w-0">
                            <div class="flex items-center gap-3 overflow-x-auto pb-1">
                                @foreach($order->items->take(4) as $item)
                                    <div class="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-slate-100 bg-slate-50 p-1" title="{{ $item->product_title }}">
                                        @if($item->product && $item->product->featured_image)
                                            <img src="{{ asset('storage/' . $item->product->featured_image) }}" alt="{{ $item->product_title }}" class="h-full w-full object-contain rounded-lg">
                                        @else
                                            <svg class="h-6 w-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        @endif
                                        <span class="absolute -top-1.5 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-slate-800 px-1 text-[9px] font-bold text-white">
                                            {{ $item->quantity }}
                                        </span>
                                    </div>
                                @endforeach

                                @if($order->items->count() > 4)
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 text-xs font-bold text-slate-500">
                                        +{{ $order->items->count() - 4 }}
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-0.5">
                                <p class="text-xs font-semibold text-slate-800 truncate">
                                    {{ $order->items->pluck('product_title')->filter()->take(3)->join(', ') }}
                                    @if($order->items->count() > 3)
                                        <span class="text-slate-400 font-normal">and {{ $order->items->count() - 3 }} more</span>
                                    @endif
                                </p>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
                                    <span>Method: {{ strtoupper($order->payment_method) }}</span>
                                    @if($order->shipping_method_name)
                                        <span>&bull;</span>
                                        <span>Shipping: {{ $order->shipping_method_name }}</span>
                                    @endif
                                    @if($order->courier_tracking_number)
                                        <span>&bull;</span>
                                        <span class="font-medium text-brand-green-700">Tracking: {{ $order->courier_tracking_number }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Total & Action Buttons --}}
                        <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-center gap-3 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100">
                            <div class="text-left md:text-right">
                                <div class="text-[11px] text-slate-400 font-medium">Grand Total</div>
                                <div class="text-base font-black text-brand-green-700">৳{{ number_format($order->total_price, 0) }}</div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('account.orders.show', $order->order_id) }}"
                                   class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition">
                                    <span>View Details</span>
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>

                                <a href="{{ route('track', ['order_id' => $order->order_id, 'phone' => $order->phone]) }}"
                                   class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:border-brand-green-300 hover:bg-brand-green-50 transition" title="Track Live Status">
                                    <svg class="h-3.5 w-3.5 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    <span class="hidden sm:inline">Track</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="pt-4">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
