@extends('web.account.layout')

@section('account-title', 'Order ' . $order->order_id)

@section('account-content')
<div class="space-y-6">

    {{-- Header with Back Button and Quick Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('account.orders') }}" class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-xs hover:border-slate-300 hover:bg-slate-50 transition" title="Back to Orders">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Order #{{ $order->order_id }}</h2>
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
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Placed on {{ $order->created_at?->format('l, F j, Y · h:i A') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('order.invoice', $order->order_id) }}" target="_blank"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:border-brand-green-300 hover:bg-brand-green-50 transition">
                <svg class="h-4 w-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Download Invoice</span>
            </a>

            <a href="{{ route('track', ['order_id' => $order->order_id, 'phone' => $order->phone]) }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                <span>Live Tracker</span>
            </a>
        </div>
    </div>

    {{-- Order Milestone Progress Tracker --}}
    @if(in_array(strtolower($order->status), ['cancelled', 'returned']))
        <div class="rounded-2xl border border-rose-200 bg-rose-50/70 p-4 sm:p-5 flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 font-bold">
                ✕
            </div>
            <div>
                <h3 class="text-sm font-bold text-rose-900">Order {{ ucfirst($order->status) }}</h3>
                <p class="text-xs text-rose-700">This order has been {{ strtolower($order->status) }}. If you have any inquiries or need a refund verification, please reach out to customer helpline.</p>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-brand-green-100 bg-white p-5 sm:p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Delivery Status Timeline</span>
                @if($order->courier_tracking_number)
                    <span class="text-xs font-semibold text-brand-green-700 bg-brand-green-50 px-2.5 py-1 rounded-lg border border-brand-green-200">
                        Courier Tracking #: {{ $order->courier_tracking_number }}
                    </span>
                @endif
            </div>

            <div class="py-2">
                <div class="grid grid-cols-5 text-center text-xs gap-1">
                    @foreach($milestones as $idx => $m)
                        @php $done = ($currentIdx >= $idx); @endphp
                        <div class="flex flex-col items-center gap-2">
                            <div class="h-9 w-9 rounded-full flex items-center justify-center font-black text-xs transition {{ $done ? 'bg-brand-green-500 text-white shadow-sm ring-4 ring-brand-green-100' : 'bg-slate-100 text-slate-400' }}">
                                @if($done && $currentIdx > $idx)
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                @else
                                    {{ $idx + 1 }}
                                @endif
                            </div>
                            <span class="font-bold text-[11px] leading-tight {{ $done ? 'text-slate-900' : 'text-slate-400' }}">
                                {{ $m['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Horizontal Progress Bars --}}
                <div class="flex mt-[-32px] mb-8 px-[10%]">
                    @foreach($milestones as $idx => $m)
                        @if($idx < count($milestones) - 1)
                            <div class="flex-1 h-1 mt-4 mx-1 rounded-full {{ $idx < $currentIdx ? 'bg-brand-green-500' : 'bg-slate-200' }}"></div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Two Column Layout: Order Items + Summary and Address --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- Items List (8 cols) --}}
        <div class="lg:col-span-8 rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden space-y-0">
            <div class="bg-slate-50/70 p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                    Ordered Products ({{ $order->items->count() }})
                </h3>
                <span class="text-xs font-semibold text-slate-500">Unit Price & Subtotal</span>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($order->items as $item)
                    <div class="p-4 sm:p-5 flex items-center gap-4 hover:bg-slate-50/50 transition">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border border-slate-100 bg-slate-50 p-1">
                            @if($item->product && $item->product->featured_image)
                                <img src="{{ asset('storage/' . $item->product->featured_image) }}" alt="{{ $item->product_title }}" class="h-full w-full object-contain rounded-lg">
                            @else
                                <svg class="h-7 w-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0 space-y-1">
                            <h4 class="text-sm font-bold text-slate-900 truncate">
                                {{ $item->product_title }}
                            </h4>
                            <div class="flex flex-wrap items-center gap-x-3 text-xs text-slate-500">
                                @if($item->product_sku)
                                    <span>SKU: <strong class="text-slate-700">{{ $item->product_sku }}</strong></span>
                                @endif
                                @if($item->size)
                                    <span>Size: <strong class="text-slate-700">{{ $item->size }}</strong></span>
                                @endif
                                @if($item->color)
                                    <span>Color: <strong class="text-slate-700">{{ $item->color }}</strong></span>
                                @endif
                                @if($item->variant_name)
                                    <span>Variant: <strong class="text-slate-700">{{ $item->variant_name }}</strong></span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-600">
                                <span>৳{{ number_format($item->price, 0) }} &times; {{ $item->quantity }}</span>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <div class="text-sm font-black text-slate-900">
                                ৳{{ number_format($item->price * $item->quantity, 0) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Price Breakdown Summary Footer --}}
            <div class="p-5 bg-slate-50/60 border-t border-slate-100 space-y-2 text-xs">
                <div class="flex items-center justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-bold text-slate-800">৳{{ number_format($order->subtotal, 0) }}</span>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span>Delivery Charge:</span>
                    <span class="font-bold text-slate-800">৳{{ number_format($order->shipping_cost, 0) }}</span>
                </div>
                @if($order->discount > 0)
                    <div class="flex items-center justify-between text-emerald-700">
                        <span>Discount Coupon @if($order->coupon_code) ({{ $order->coupon_code }}) @endif:</span>
                        <span class="font-bold">-৳{{ number_format($order->discount, 0) }}</span>
                    </div>
                @endif
                @if($order->tax > 0)
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Estimated Tax / VAT:</span>
                        <span class="font-bold text-slate-800">৳{{ number_format($order->tax, 0) }}</span>
                    </div>
                @endif
                <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-sm font-extrabold text-slate-900">
                    <span>Grand Total:</span>
                    <span class="text-base text-brand-green-700">৳{{ number_format($order->total_price, 0) }}</span>
                </div>
            </div>
        </div>

        {{-- Shipping & Payment Information Sidebar (4 cols) --}}
        <div class="lg:col-span-4 space-y-5">

            {{-- Shipping Address Card --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    <span>Shipping Address</span>
                </h3>
                <div class="space-y-1.5 text-xs text-slate-600">
                    <p class="font-bold text-slate-900 text-sm">{{ $order->customer_name }}</p>
                    <p class="text-slate-800 font-medium">{{ $order->address }}</p>
                    @if($order->apartment)
                        <p class="text-[11px] text-slate-500">Apartment / House: {{ $order->apartment }}</p>
                    @endif
                    <p class="text-[11px] text-slate-500">
                        {{ implode(', ', array_filter([$order->area, $order->district, $order->division, $order->postal_code])) }}
                    </p>
                    <p class="pt-2 text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $order->phone }}
                    </p>
                    @if($order->display_alternative_phone)
                        <p class="text-xs text-slate-500">Alt Phone: {{ $order->display_alternative_phone }}</p>
                    @endif
                </div>
            </div>

            {{-- Payment Information Card --}}
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <span>Payment Information</span>
                </h3>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Payment Method:</span>
                        <span class="font-bold text-slate-800 uppercase">{{ $order->payment_method }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Payment Status:</span>
                        <span class="font-bold text-slate-800 capitalize">{{ $order->payment_status }}</span>
                    </div>
                    @if($order->transaction_id)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Transaction ID:</span>
                            <span class="font-mono text-slate-800">{{ $order->transaction_id }}</span>
                        </div>
                    @endif
                    @if($order->sender_number)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Sender Number:</span>
                            <span class="text-slate-800">{{ $order->sender_number }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Delivery Method Card --}}
            @if($order->shipping_method_name)
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-2 text-xs">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Shipping Option</h3>
                    <p class="font-bold text-slate-800">{{ $order->shipping_method_name }}</p>
                    @if($order->order_note)
                        <div class="pt-2 border-t border-slate-100">
                            <span class="text-[11px] text-slate-400 block">Customer Delivery Note:</span>
                            <p class="text-slate-600 italic mt-0.5">{{ $order->order_note }}</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Audit status notes if any --}}
            @if($order->statusHistory->isNotEmpty())
                <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Activity History</h3>
                    <div class="space-y-2 pt-1 text-[11px]">
                        @foreach($order->statusHistory as $hist)
                            <div class="flex items-start gap-2 text-slate-600">
                                <span class="h-1.5 w-1.5 rounded-full bg-brand-green-500 mt-1.5 shrink-0"></span>
                                <div>
                                    <span class="font-bold capitalize text-slate-800">{{ str_replace('_', ' ', $hist->status) }}</span>
                                    <span class="text-slate-400 ml-1">&bull; {{ $hist->created_at?->format('M d, Y · h:i A') }}</span>
                                    @if($hist->note)
                                        <p class="text-slate-500 italic mt-0.5">{{ $hist->note }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
