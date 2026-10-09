@extends('layouts.app')

@php
    $copy = [
        'review' => ['Payment received — under review', 'Your payment was received and is being reviewed by our team. We will confirm your order shortly.', 'bg-amber-100 text-amber-600'],
        'awaiting' => ['Confirming your payment…', 'We have not received confirmation from the payment gateway yet. If you completed the payment, refresh this page in a minute. Otherwise you can try again.', 'bg-sky-100 text-sky-600'],
        'closed' => ['Order closed', 'This order can no longer be paid online. Please contact us if you need help.', 'bg-slate-100 text-slate-500'],
        'unpaid' => ['Payment not completed', 'Your order is saved, but the online payment was not completed. You have not been charged for this attempt. You can try the payment again.', 'bg-red-100 text-red-600'],
    ];
    [$heading, $message, $iconClass] = $copy[$state];
@endphp

@section('content')
<div class="mx-auto max-w-2xl space-y-6 px-4 py-10 text-center sm:py-16" x-data x-init="$store.cart.clear()">
    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full shadow-soft {{ $iconClass }}">
        @if($state === 'unpaid' || $state === 'closed')
            <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        @else
            <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        @endif
    </div>

    <div class="space-y-2">
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $heading }}</h1>
        <p class="text-sm text-slate-600">{{ $message }}</p>
    </div>

    @if(session('error'))
        <p role="alert" class="mx-auto max-w-md rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</p>
    @endif

    <div class="inline-block rounded-2xl border border-slate-200 bg-white p-4 shadow-soft">
        <span class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Order Reference ID</span>
        <span class="text-xl font-black tracking-wider text-slate-800">{{ $order->order_id }}</span>
        <span class="mt-1 block text-xs text-slate-500">Amount: <strong class="text-slate-800">৳{{ number_format((float) $order->total_price, 2) }}</strong></span>
    </div>

    <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
        @if(in_array($state, ['unpaid', 'awaiting'], true))
            <form method="POST" action="{{ route('payment.sslcommerz.retry') }}" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                <input type="hidden" name="orderId" value="{{ $order->order_id }}">
                <input type="hidden" name="token" value="{{ $token }}">
                <button type="submit" :disabled="busy" class="inline-flex min-h-[44px] items-center rounded-full bg-brand-orange-500 px-6 py-3 text-xs font-bold text-white shadow-md transition hover:bg-brand-orange-600 disabled:opacity-60">
                    <span x-text="busy ? 'Redirecting…' : 'Pay Now'">Pay Now</span>
                </button>
            </form>
        @endif
        @if($state === 'awaiting')
            <a href="{{ request()->fullUrl() }}" class="inline-flex min-h-[44px] items-center rounded-full border border-slate-300 px-6 py-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50">Refresh Status</a>
        @endif
        <a href="{{ route('track', ['order_id' => $order->order_id]) }}" class="inline-flex min-h-[44px] items-center rounded-full border border-slate-300 px-6 py-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50">Track Order</a>
        <a href="{{ route('home') }}" class="inline-flex min-h-[44px] items-center rounded-full border border-slate-300 px-6 py-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50">Continue Shopping</a>
    </div>
</div>
@endsection
