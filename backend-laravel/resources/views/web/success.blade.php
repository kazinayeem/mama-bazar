@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10 sm:py-16 text-center space-y-6" x-data x-init="$store.cart.clear()">

    <div class="w-20 h-20 rounded-full bg-brand-green-100 text-brand-green-600 flex items-center justify-center mx-auto shadow-soft">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
    </div>

    <div class="space-y-2">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Order Placed Successfully!</h1>
        <p class="text-sm text-slate-600">
            Thank you for shopping with Mama Bazar. Your order has been placed and is being prepared for dispatch.
        </p>
    </div>

    @if($orderId)
        <div class="p-4 rounded-2xl bg-white border border-brand-green-200 inline-block shadow-soft">
            <span class="text-xs text-slate-500 uppercase tracking-wider font-semibold block">Order Reference ID</span>
            <span class="text-xl font-black text-brand-green-700 tracking-wider">{{ $orderId }}</span>
            @if(!empty($order))
                <span class="mt-1 block text-xs text-slate-500">Total: <strong class="text-slate-800">৳{{ number_format($order->total_price, 0) }}</strong> ·
                    {{ strtoupper($order->payment_method) }} · {{ ucfirst(str_replace('_',' ',$order->payment_status)) }}</span>
            @endif
        </div>
    @endif

    <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('track', array_filter(['order_id' => $orderId])) }}" class="px-6 py-3 rounded-full bg-brand-green-600 hover:bg-brand-green-700 text-white text-xs font-bold shadow-md transition min-h-[44px] inline-flex items-center">
            Track Your Order
        </a>
        <a href="{{ route('home') }}" class="px-6 py-3 rounded-full border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold transition min-h-[44px] inline-flex items-center">
            Continue Shopping
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script>
// Purchase event — fires once per order (refresh-safe via sessionStorage dedup).
// Browser pixels fire only with tracking consent; server CAPI likewise respects consent.
(function () {
    try {
        @if(!empty($order))
        var key = 'mb_purchase_{{ $order->order_id }}';
        if (sessionStorage.getItem(key)) return;
        sessionStorage.setItem(key, '1');
        var value = {{ (float) $order->total_price }};
        var contentIds = @js($order->items->pluck('product_id')->map(fn ($v) => (string) $v)->values());
        if (window.mbTrack) {
            window.mbTrack('purchase', {
                transaction_id: @js($order->order_id),
                value: value,
                currency: 'BDT',
                payment_status: @js($order->payment_status),
                content_ids: contentIds,
                content_type: 'product',
            });
        } else {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({
                event: 'purchase',
                transaction_id: @js($order->order_id),
                value: value,
                currency: 'BDT',
            });
        }
        // Server-side CAPI delivery with the same stable event ID (dedup with browser pixel).
        // Only when the visitor granted tracking consent — otherwise skip silently.
        if (window.mbConsent && !window.mbConsent.granted()) return;
        fetch('/api/analytics/purchase', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                value: value,
                currency: 'BDT',
                contentIds: contentIds,
                eventId: @js($order->fb_event_id ?: ('order-' . $order->order_id)),
                orderId: @js($order->order_id),
            }),
        }).catch(function () {});
        @endif
    } catch (e) {}
})();
</script>
@endpush
