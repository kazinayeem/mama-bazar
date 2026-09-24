@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" x-data="{
    shippingCharge: {{ $shippingMethods->first() ? $shippingMethods->first()->charge : 60 }},
    selectedShippingId: {{ $shippingMethods->first() ? $shippingMethods->first()->id : 1 }},
    selectedPaymentMethod: 'cod',
    itemsPayload: JSON.stringify($store.cart.items),

    init() {
        this.$watch('$store.cart.items', (val) => {
            this.itemsPayload = JSON.stringify(val);
        });
    }
}">

    <div class="pb-4 border-b border-brand-green-100">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Checkout</h1>
        <p class="text-xs text-slate-500 mt-1">Please provide your delivery address and choose a payment method.</p>
    </div>

    @if($checkoutNotice)
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-3">
            <span class="text-base">📢</span>
            <span>{{ $checkoutNotice->text }}</span>
        </div>
    @endif

    <form action="{{ route('checkout.process') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @csrf
        <input type="hidden" name="items_json" :value="itemsPayload">

        <!-- Hidden input for items so form submission passes validation -->
        <template x-for="(item, index) in $store.cart.items" :key="item.key">
            <div>
                <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.id">
                <input type="hidden" :name="'items[' + index + '][variant_id]'" :value="item.variantId">
                <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                <input type="hidden" :name="'items[' + index + '][price]'" :value="item.price">
            </div>
        </template>

        <!-- Customer & Shipping Information -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Address Card -->
            <div class="bg-white p-6 rounded-3xl border border-brand-green-100 shadow-soft space-y-4">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-brand-green-500 text-white text-xs flex items-center justify-center font-bold">1</span>
                    Delivery Information
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Full Name *</label>
                        <input type="text" name="customer_name" required value="{{ old('customer_name', auth()->user() ? auth()->user()->name : '') }}" placeholder="e.g. Mohammad Ali" 
                            class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Phone Number *</label>
                        <input type="text" name="phone" required value="{{ old('phone', auth()->user() ? auth()->user()->phone : '') }}" placeholder="e.g. 01712345678" 
                            class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Alternative Phone (Optional)</label>
                        <input type="text" name="alternative_phone" value="{{ old('alternative_phone') }}" placeholder="Alternative phone" 
                            class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">District / City *</label>
                        <input type="text" name="district" required value="{{ old('district', 'Dhaka') }}" placeholder="e.g. Dhaka" 
                            class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Full Delivery Address *</label>
                        <textarea name="address" required rows="2" placeholder="House, Road, Area, Ward..." 
                            class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">{{ old('address') }}</textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Order Notes (Optional)</label>
                        <input type="text" name="order_note" value="{{ old('order_note') }}" placeholder="Special instructions for delivery" 
                            class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Shipping Methods -->
            <div class="bg-white p-6 rounded-3xl border border-brand-green-100 shadow-soft space-y-4">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-brand-green-500 text-white text-xs flex items-center justify-center font-bold">2</span>
                    Shipping Method
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @forelse($shippingMethods as $method)
                        <label class="cursor-pointer border rounded-2xl p-4 flex items-start gap-3 transition" :class="selectedShippingId === {{ $method->id }} ? 'border-brand-green-500 bg-brand-green-50/40' : 'border-slate-200'">
                            <input type="radio" name="shipping_method_id" value="{{ $method->id }}" @change="selectedShippingId = {{ $method->id }}; shippingCharge = {{ $method->charge }}" :checked="selectedShippingId === {{ $method->id }}" class="mt-0.5 text-brand-green-600">
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-800">{{ $method->name }}</span>
                                    <span class="text-xs font-extrabold text-brand-green-700">৳{{ number_format($method->charge, 0) }}</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $method->estimated_delivery ?: 'Fast delivery' }}</p>
                            </div>
                        </label>
                    @empty
                        <input type="hidden" name="shipping_method_id" value="1">
                        <div class="text-xs text-slate-500">Standard Delivery (৳60)</div>
                    @endforelse
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="bg-white p-6 rounded-3xl border border-brand-green-100 shadow-soft space-y-4">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-brand-green-500 text-white text-xs flex items-center justify-center font-bold">3</span>
                    Payment Method
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="cursor-pointer border rounded-2xl p-4 flex items-center gap-3 transition" :class="selectedPaymentMethod === 'cod' ? 'border-brand-green-500 bg-brand-green-50/40' : 'border-slate-200'">
                        <input type="radio" name="payment_method" value="cod" @change="selectedPaymentMethod = 'cod'" checked class="text-brand-green-600">
                        <div>
                            <span class="text-xs font-bold text-slate-800">Cash on Delivery</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Pay in cash when order arrives</p>
                        </div>
                    </label>

                    @foreach($paymentMethods->where('code', '!=', 'cod') as $pm)
                        <label class="cursor-pointer border rounded-2xl p-4 flex items-center gap-3 transition" :class="selectedPaymentMethod === '{{ $pm->code }}' ? 'border-brand-green-500 bg-brand-green-50/40' : 'border-slate-200'">
                            <input type="radio" name="payment_method" value="{{ $pm->code }}" @change="selectedPaymentMethod = '{{ $pm->code }}'" class="text-brand-green-600">
                            <div>
                                <span class="text-xs font-bold text-slate-800">{{ $pm->name }}</span>
                                <p class="text-[11px] text-slate-500 mt-0.5">Manual Mobile Banking</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- Order Summary & Confirmation -->
        <div class="space-y-4">
            <div class="bg-white p-6 rounded-3xl border border-brand-green-100 shadow-soft space-y-4">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Your Order</h2>

                <div class="divide-y divide-slate-100 max-h-60 overflow-y-auto pr-1">
                    <template x-for="item in $store.cart.items" :key="item.key">
                        <div class="py-2.5 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2 truncate pr-2">
                                <span class="font-bold text-slate-800" x-text="item.quantity + 'x'"></span>
                                <span class="text-slate-600 truncate" x-text="item.title"></span>
                            </div>
                            <span class="font-extrabold text-slate-800 shrink-0" x-text="'৳' + (item.price * item.quantity).toFixed(0)"></span>
                        </div>
                    </template>
                </div>

                <div class="space-y-2 pt-3 border-t border-slate-100 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Items Subtotal:</span>
                        <span class="font-bold text-slate-800" x-text="'৳' + $store.cart.subtotal.toFixed(0)">৳0</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Shipping Delivery:</span>
                        <span class="font-bold text-slate-800" x-text="'৳' + shippingCharge">৳60</span>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-between text-base font-extrabold">
                    <span class="text-slate-800">Total:</span>
                    <span class="text-brand-orange-600" x-text="'৳' + ($store.cart.subtotal + shippingCharge).toFixed(0)">৳0</span>
                </div>

                <button type="submit" :disabled="$store.cart.items.length === 0" class="w-full py-3 px-4 rounded-full bg-brand-orange-500 hover:bg-brand-orange-600 disabled:opacity-50 text-white text-center text-sm font-bold shadow-md transition">
                    Confirm Order &rarr;
                </button>
            </div>
        </div>

    </form>

</div>
@endsection
