@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16 text-center space-y-6" x-data x-init="$store.cart.clear()">

    <div class="w-20 h-20 rounded-full bg-brand-green-100 text-brand-green-600 flex items-center justify-center mx-auto shadow-soft">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
    </div>

    <div class="space-y-2">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Order Placed Successfully!</h1>
        <p class="text-sm text-slate-600">
            Thank you for shopping with Mama Bazar. Your order has been placed and is being prepared for dispatch.
        </p>
    </div>

    @if($orderId)
        <div class="p-4 rounded-2xl bg-white border border-brand-green-200 inline-block shadow-soft">
            <span class="text-xs text-slate-500 uppercase tracking-wider font-semibold block">Order Reference ID</span>
            <span class="text-xl font-black text-brand-green-700 tracking-wider">{{ $orderId }}</span>
        </div>
    @endif

    <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('track', ['order_id' => $orderId]) }}" class="px-6 py-2.5 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold shadow-md transition">
            Track Your Order
        </a>
        <a href="{{ route('home') }}" class="px-6 py-2.5 rounded-full border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold transition">
            Return to Homepage
        </a>
    </div>

</div>
@endsection
