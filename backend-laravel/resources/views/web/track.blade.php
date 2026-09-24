@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    <div class="text-center space-y-2">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Track Your Order</h1>
        <p class="text-xs text-slate-500">Enter your Order ID (e.g. GHB-123456) or your phone number to check current delivery status.</p>
    </div>

    <!-- Search Form -->
    <div class="bg-white p-6 rounded-3xl border border-brand-green-100 shadow-soft">
        <form action="{{ route('track') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Order ID</label>
                <input type="text" name="order_id" value="{{ request('order_id') }}" placeholder="GHB-XXXXXX" 
                    class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none uppercase">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Or Phone Number</label>
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

    <!-- Search Results -->
    @if($searched)
        @if($order)
            <div class="bg-white p-6 sm:p-8 rounded-3xl border border-brand-green-100 shadow-soft space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <span class="text-xs text-slate-400 font-semibold uppercase">Order Details</span>
                        <h2 class="text-lg font-black text-brand-green-700">{{ $order->order_id }}</h2>
                        <span class="text-xs text-slate-500">{{ $order->created_at->format('M d, Y - h:i A') }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-slate-400 font-semibold block">Total Amount</span>
                        <span class="text-lg font-extrabold text-slate-900">৳{{ number_format($order->total_price, 0) }}</span>
                    </div>
                </div>

                <!-- Status Progress -->
                @php
                    $steps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                    $currentIdx = array_search(strtolower($order->status), $steps);
                    if ($currentIdx === false) $currentIdx = 0;
                @endphp

                <div class="py-4">
                    <div class="grid grid-cols-5 text-center text-xs">
                        @foreach($steps as $idx => $step)
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs mb-2 {{ $idx <= $currentIdx ? 'bg-brand-green-500 text-white' : 'bg-slate-100 text-slate-400' }}">
                                    {{ $idx + 1 }}
                                </div>
                                <span class="capitalize {{ $idx <= $currentIdx ? 'font-bold text-slate-800' : 'text-slate-400' }}">
                                    {{ $step }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Order Items list -->
                <div class="pt-4 border-t border-slate-100 space-y-3">
                    <h3 class="text-xs font-bold uppercase text-slate-700">Ordered Items</h3>
                    <div class="divide-y divide-slate-100">
                        @foreach($order->items as $item)
                            <div class="py-2.5 flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-slate-800">{{ $item->quantity }}x</span>
                                    <span class="text-slate-700 ml-1">{{ $item->product ? $item->product->title : 'Product' }}</span>
                                </div>
                                <span class="font-semibold text-slate-900">৳{{ number_format($item->price * $item->quantity, 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs space-y-1">
                    <p><span class="font-semibold text-slate-500">Recipient:</span> {{ $order->customer_name }} ({{ $order->phone }})</p>
                    <p><span class="font-semibold text-slate-500">Address:</span> {{ $order->address }}, {{ $order->district }}</p>
                    <p><span class="font-semibold text-slate-500">Payment:</span> {{ strtoupper($order->payment_method) }} ({{ ucfirst($order->payment_status) }})</p>
                </div>
            </div>
        @else
            <div class="bg-white p-8 rounded-3xl border border-red-100 text-center space-y-2">
                <span class="text-2xl">⚠️</span>
                <p class="text-sm font-semibold text-slate-700">{{ $error ?: 'No order found.' }}</p>
                <p class="text-xs text-slate-400">Please double check your order number or phone number and try again.</p>
            </div>
        @endif
    @endif

</div>
@endsection
