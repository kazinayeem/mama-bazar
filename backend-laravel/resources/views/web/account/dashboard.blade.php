@extends('web.account.layout')

@section('account-title', 'Dashboard')

@section('account-content')
<div class="space-y-6">

    {{-- Welcome banner --}}
    <div class="rounded-2xl border border-brand-green-100 bg-gradient-to-r from-brand-green-50/70 via-white to-brand-green-50/30 p-5 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-brand-green-700">Account Overview</span>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-0.5">
                    Welcome back, {{ $user->name }}!
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Manage your recent orders, shipping addresses, security credentials, and profile settings all in one place.
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('shop') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span>Continue Shopping</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Order Statistics KPI Grid --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Order Overview</h3>
            <a href="{{ route('account.orders') }}" class="text-xs font-semibold text-brand-green-700 hover:underline">View All Orders &rarr;</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            {{-- Total Orders --}}
            <a href="{{ route('account.orders') }}" class="group rounded-2xl border border-slate-200 bg-white p-3.5 shadow-xs hover:border-brand-green-300 hover:shadow-sm transition">
                <div class="text-[11px] font-semibold text-slate-500">Total Orders</div>
                <div class="mt-1 text-2xl font-black text-slate-900 group-hover:text-brand-green-700 transition">{{ $totalOrders }}</div>
                <div class="mt-1 flex items-center text-[10px] font-medium text-slate-400">All lifetime orders</div>
            </a>

            {{-- Pending --}}
            <a href="{{ route('account.orders', ['status' => 'pending']) }}" class="group rounded-2xl border border-amber-200 bg-amber-50/40 p-3.5 shadow-xs hover:border-amber-300 hover:bg-amber-50/70 transition">
                <div class="text-[11px] font-semibold text-amber-800">Pending</div>
                <div class="mt-1 text-2xl font-black text-amber-900">{{ $pendingOrders }}</div>
                <div class="mt-1 flex items-center text-[10px] font-medium text-amber-700">Awaiting confirmation</div>
            </a>

            {{-- Processing --}}
            <a href="{{ route('account.orders', ['status' => 'processing']) }}" class="group rounded-2xl border border-blue-200 bg-blue-50/40 p-3.5 shadow-xs hover:border-blue-300 hover:bg-blue-50/70 transition">
                <div class="text-[11px] font-semibold text-blue-800">Processing</div>
                <div class="mt-1 text-2xl font-black text-blue-900">{{ $processingOrders }}</div>
                <div class="mt-1 flex items-center text-[10px] font-medium text-blue-700">Packaging in progress</div>
            </a>

            {{-- Shipped --}}
            <a href="{{ route('account.orders', ['status' => 'shipped']) }}" class="group rounded-2xl border border-purple-200 bg-purple-50/40 p-3.5 shadow-xs hover:border-purple-300 hover:bg-purple-50/70 transition">
                <div class="text-[11px] font-semibold text-purple-800">Shipped</div>
                <div class="mt-1 text-2xl font-black text-purple-900">{{ $shippedOrders }}</div>
                <div class="mt-1 flex items-center text-[10px] font-medium text-purple-700">On the way to you</div>
            </a>

            {{-- Delivered --}}
            <a href="{{ route('account.orders', ['status' => 'delivered']) }}" class="group rounded-2xl border border-emerald-200 bg-emerald-50/40 p-3.5 shadow-xs hover:border-emerald-300 hover:bg-emerald-50/70 transition">
                <div class="text-[11px] font-semibold text-emerald-800">Delivered</div>
                <div class="mt-1 text-2xl font-black text-emerald-900">{{ $deliveredOrders }}</div>
                <div class="mt-1 flex items-center text-[10px] font-medium text-emerald-700">Successfully received</div>
            </a>

            {{-- Cancelled --}}
            <a href="{{ route('account.orders', ['status' => 'cancelled']) }}" class="group rounded-2xl border border-slate-200 bg-slate-50/60 p-3.5 shadow-xs hover:border-slate-300 transition">
                <div class="text-[11px] font-semibold text-slate-600">Cancelled</div>
                <div class="mt-1 text-2xl font-black text-slate-800">{{ $cancelledOrders }}</div>
                <div class="mt-1 flex items-center text-[10px] font-medium text-slate-400">Cancelled/Returned</div>
            </a>
        </div>
    </div>

    {{-- Quick Action Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <a href="{{ route('account.orders') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs hover:border-brand-green-300 hover:bg-brand-green-50/30 transition">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-green-100 text-brand-green-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-900">View All Orders</div>
                <div class="text-[11px] text-slate-500">Track shipments & invoices</div>
            </div>
        </a>

        <a href="{{ route('account.profile') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs hover:border-brand-green-300 hover:bg-brand-green-50/30 transition">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-900">Update Profile</div>
                <div class="text-[11px] text-slate-500">Name, phone & details</div>
            </div>
        </a>

        <a href="{{ route('account.addresses') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs hover:border-brand-green-300 hover:bg-brand-green-50/30 transition">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-900">Manage Addresses</div>
                <div class="text-[11px] text-slate-500">Saved shipping locations</div>
            </div>
        </a>

        <a href="{{ route('account.settings') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs hover:border-brand-green-300 hover:bg-brand-green-50/30 transition">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-900">Password & Security</div>
                <div class="text-[11px] text-slate-500">Protect your account</div>
            </div>
        </a>
    </div>

    {{-- Recent Orders & Primary Address Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Recent Orders Table / Cards (2 columns on large screen) --}}
        <div class="lg:col-span-2 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Recent Orders</h3>
                <a href="{{ route('account.orders') }}" class="text-xs font-semibold text-brand-green-700 hover:underline">View All &rarr;</a>
            </div>

            @if($recentOrders->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center space-y-3">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-green-50 text-brand-green-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">No orders placed yet</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Explore our daily grocery essentials and exclusive deals.</p>
                    </div>
                    <a href="{{ route('shop') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-600 px-4 py-2 text-xs font-bold text-white hover:bg-brand-green-700 transition">
                        <span>Browse Shop</span>
                    </a>
                </div>
            @else
                <div class="divide-y divide-slate-100 rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
                    @foreach($recentOrders as $order)
                        <div class="p-4 sm:p-5 hover:bg-slate-50/60 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-1.5 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-extrabold text-sm text-slate-900">{{ $order->order_id }}</span>
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
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $statusStyles }}">
                                        {{ str_replace('_', ' ', $order->status) }}
                                    </span>

                                    {{-- Payment Status Badge --}}
                                    @php
                                        $payStyles = match(strtolower($order->payment_status)) {
                                            'paid', 'success', 'verified' => 'text-emerald-700 bg-emerald-50',
                                            'refunded' => 'text-purple-700 bg-purple-50',
                                            default => 'text-amber-700 bg-amber-50',
                                        };
                                    @endphp
                                    <span class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold {{ $payStyles }}">
                                        Payment: {{ ucfirst($order->payment_status) }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 text-xs text-slate-500">
                                    <span>Placed: {{ $order->created_at?->format('M d, Y · h:i A') }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('item', $order->items->count()) }}</span>
                                </div>
                                @if($order->items->isNotEmpty())
                                    <p class="text-[11px] text-slate-400 truncate max-w-md">
                                        {{ $order->items->pluck('product_title')->filter()->join(', ') }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                <div class="text-left sm:text-right">
                                    <div class="text-xs text-slate-400 font-medium">Grand Total</div>
                                    <div class="text-sm font-black text-brand-green-700">৳{{ number_format($order->total_price, 0) }}</div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('account.orders.show', $order->order_id) }}" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-xs hover:border-brand-green-300 hover:bg-brand-green-50 transition">
                                        <span>Details</span>
                                        <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Primary Shipping Address Card --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Default Address</h3>
                <a href="{{ route('account.addresses') }}" class="text-xs font-semibold text-brand-green-700 hover:underline">Manage &rarr;</a>
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-4">
                @if($defaultAddress)
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-green-100 text-brand-green-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            </span>
                            <span class="text-xs font-bold text-slate-900">{{ $defaultAddress->recipient_name }}</span>
                        </div>
                        <span class="rounded-full bg-brand-green-50 px-2 py-0.5 text-[10px] font-bold text-brand-green-700 border border-brand-green-200">
                            Primary
                        </span>
                    </div>

                    <div class="space-y-1 text-xs text-slate-600">
                        <p class="font-medium text-slate-800">{{ $defaultAddress->address }}</p>
                        @if($defaultAddress->apartment)
                            <p class="text-[11px] text-slate-500">Apartment/House: {{ $defaultAddress->apartment }}</p>
                        @endif
                        <p class="text-[11px] text-slate-500">
                            {{ implode(', ', array_filter([$defaultAddress->area, $defaultAddress->district, $defaultAddress->division, $defaultAddress->postal_code])) }}
                        </p>
                        <p class="pt-2 text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ $defaultAddress->phone }}
                        </p>
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                        <a href="{{ route('account.addresses') }}" class="block text-center rounded-xl border border-slate-200 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Edit or Add Address
                        </a>
                    </div>
                @else
                    <div class="py-6 text-center space-y-2">
                        <p class="text-xs text-slate-500">No shipping address saved yet.</p>
                        <a href="{{ route('account.addresses') }}" class="inline-flex items-center gap-1 text-xs font-bold text-brand-green-700 hover:underline">
                            + Add Shipping Address
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
