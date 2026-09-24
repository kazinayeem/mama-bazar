@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    <div class="text-center space-y-2">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Track Your Order</h1>
        <p class="text-xs text-slate-500">Enter your Order ID (e.g. MB-123456) and phone number to check delivery status.</p>
    </div>

    {{-- Search Form --}}
    <div class="bg-white p-6 rounded-3xl border border-brand-green-100 shadow-soft">
        <form action="{{ route('track') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Order ID</label>
                <input type="text" name="order_id" value="{{ request('order_id') }}" placeholder="MB-XXXXXX"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none uppercase">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Phone Number (optional)</label>
                <input type="text" name="phone" value="{{ request('phone') }}" placeholder="017XXXXXXXX"
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold shadow-md transition">
                    Search Order
                </button>
            </div>
        </form>
    </div>

    {{-- Search Results --}}
    @if($searched)
        @if($order)
            @php
                $steps     = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                $currentIdx = array_search(strtolower($order['status']), $steps);
                if ($currentIdx === false) $currentIdx = 0;
                $createdAt = $order['createdAt'] ? \Carbon\Carbon::parse($order['createdAt']) : null;
            @endphp

            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-brand-green-100 shadow-soft space-y-6">

                {{-- Header: Order ID + Total --}}
                <div class="flex flex-wrap items-start justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Order Details</span>
                        <h2 class="text-lg font-black text-brand-green-700 mt-0.5">{{ $order['orderId'] }}</h2>
                        @if($createdAt)
                            <span class="text-xs text-slate-500">{{ $createdAt->format('M d, Y · h:i A') }}</span>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-slate-400 font-semibold block">Total Amount</span>
                        <span class="text-lg font-extrabold text-slate-900">৳{{ number_format((float)$order['totalPrice'], 0) }}</span>
                    </div>
                </div>

                {{-- Status Progress Bar --}}
                <div class="py-2">
                    <div class="grid grid-cols-5 text-center text-xs gap-1">
                        @foreach($steps as $idx => $step)
                            @php $done = $idx <= $currentIdx; @endphp
                            <div class="flex flex-col items-center gap-1.5">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs
                                    {{ $done ? 'bg-brand-green-500 text-white shadow-sm' : 'bg-slate-100 text-slate-400' }}">
                                    @if($done && $idx < $currentIdx)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        {{ $idx + 1 }}
                                    @endif
                                </div>
                                <span class="capitalize leading-tight {{ $done ? 'font-bold text-slate-800' : 'text-slate-400' }}">
                                    {{ $step }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Connector lines --}}
                    <div class="flex mt-[-28px] mb-6 px-[8%]">
                        @foreach($steps as $idx => $step)
                            @if($idx < count($steps) - 1)
                                <div class="flex-1 h-0.5 mt-4 mx-1 {{ $idx < $currentIdx ? 'bg-brand-green-400' : 'bg-slate-200' }}"></div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Ordered Items --}}
                <div class="pt-2 border-t border-slate-100 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Ordered Items</h3>
                    <div class="divide-y divide-slate-100">
                        @foreach($order['items'] as $item)
                            <div class="py-2.5 flex items-center justify-between text-xs gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    @if(!empty($item['product']['image']))
                                        <img src="{{ $item['product']['image'] }}" alt=""
                                             class="w-10 h-10 rounded-lg object-cover border border-slate-100 shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 shrink-0"></div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">
                                            {{ $item['product']['title'] ?? 'Product' }}
                                        </p>
                                        @if(!empty($item['variantName']))
                                            <p class="text-slate-400">{{ $item['variantName'] }}</p>
                                        @endif
                                        <p class="text-slate-500">Qty: {{ $item['quantity'] }}</p>
                                    </div>
                                </div>
                                <span class="font-semibold text-slate-900 shrink-0">
                                    ৳{{ number_format((float)$item['price'] * (int)$item['quantity'], 0) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Subtotal / Shipping / Total summary --}}
                    <div class="pt-3 space-y-1 text-xs">
                        @if((float)($order['discount'] ?? 0) > 0)
                            <div class="flex justify-between text-slate-500">
                                <span>Discount ({{ $order['couponCode'] ?? '' }})</span>
                                <span class="text-red-500">− ৳{{ number_format((float)$order['discount'], 0) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-slate-500">
                            <span>Shipping ({{ $order['shippingMethodName'] ?? 'Standard' }})</span>
                            <span>৳{{ number_format((float)($order['shippingCost'] ?? 0), 0) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-slate-900 border-t border-slate-100 pt-2 mt-1">
                            <span>Total</span>
                            <span>৳{{ number_format((float)$order['totalPrice'], 0) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Customer Details --}}
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs space-y-1.5">
                    <p>
                        <span class="font-semibold text-slate-500">Recipient:</span>
                        {{ $order['customerName'] }}
                        @if(!empty($order['phone'])) · {{ $order['phone'] }} @endif
                    </p>
                    <p>
                        <span class="font-semibold text-slate-500">Address:</span>
                        {{ implode(', ', array_filter([
                            $order['address'] ?? null,
                            $order['area'] ?? null,
                            $order['upazila'] ?? null,
                            $order['district'] ?? null,
                        ])) }}
                    </p>
                    <p>
                        <span class="font-semibold text-slate-500">Payment:</span>
                        {{ strtoupper($order['paymentMethod'] ?? 'N/A') }}
                        <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold
                            {{ ($order['paymentStatus'] ?? '') === 'paid' ? 'bg-brand-green-100 text-brand-green-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ ucfirst($order['paymentStatus'] ?? 'pending') }}
                        </span>
                    </p>
                    @if(!empty($order['courierTrackingNumber']))
                        <p>
                            <span class="font-semibold text-slate-500">Courier Tracking:</span>
                            {{ $order['courierTrackingNumber'] }}
                        </p>
                    @endif
                </div>
            </div>

        @else
            <div class="bg-white p-8 rounded-3xl border border-red-100 text-center space-y-2">
                <div class="text-3xl">📦</div>
                <p class="text-sm font-semibold text-slate-700">{{ $error ?: 'No order found.' }}</p>
                <p class="text-xs text-slate-400">Please double-check your Order ID or phone number and try again.</p>
            </div>
        @endif
    @endif

</div>
@endsection
