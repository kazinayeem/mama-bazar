@extends('layouts.app')

@php
    $pmPayload = $paymentMethods->map(function ($m) {
        return [
            'id' => $m->id,
            'code' => $m->code,
            'name' => $m->name,
            'type' => $m->type,
            'config' => $m->config_array,
            'icon' => $m->icon,
        ];
    })->values();
    $defaultPm = $paymentMethods->first();
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" x-data="checkoutPage({
    shippingCharge: {{ $shippingMethods->first() ? (float) $shippingMethods->first()->charge : 60 }},
    selectedShippingId: {{ $shippingMethods->first() ? $shippingMethods->first()->id : 1 }},
    paymentMethods: @js($pmPayload),
    selectedPaymentCode: @js($defaultPm?->code ?? ''),
})">

    <div class="pb-4 border-b border-brand-green-100">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Checkout</h1>
        <p class="text-xs text-slate-500 mt-1">Please provide your delivery address and choose a payment method.</p>
    </div>

    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-semibold text-red-700">{{ session('error') }}</div>
    @endif

    @if($checkoutNotice)
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-3"
             style="background-color: {{ $checkoutNotice->background_color ?? '#FFF7ED' }}; color: {{ $checkoutNotice->text_color ?? '#9A3412' }}">
            <span class="text-base">📢</span>
            <span>{{ $checkoutNotice->text }}</span>
        </div>
    @endif

    <form action="{{ route('checkout.process') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8" @submit="prepareSubmit">
        @csrf
        <input type="hidden" name="items_json" :value="itemsPayload">

        <template x-for="(item, index) in $store.cart.items" :key="item.key">
            <div>
                <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.id">
                <input type="hidden" :name="'items[' + index + '][variant_id]'" :value="item.variantId">
                <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                <input type="hidden" :name="'items[' + index + '][price]'" :value="item.price">
            </div>
        </template>

        <div class="lg:col-span-2 space-y-6">

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

            <div class="bg-white p-6 rounded-3xl border border-brand-green-100 shadow-soft space-y-4">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-brand-green-500 text-white text-xs flex items-center justify-center font-bold">2</span>
                    Shipping Method
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @forelse($shippingMethods as $method)
                        <label class="cursor-pointer border rounded-2xl p-4 flex items-start gap-3 transition" :class="selectedShippingId === {{ $method->id }} ? 'border-brand-green-500 bg-brand-green-50/40' : 'border-slate-200'">
                            <input type="radio" name="shipping_method_id" value="{{ $method->id }}" @change="selectedShippingId = {{ $method->id }}; shippingCharge = {{ (float) $method->charge }}" :checked="selectedShippingId === {{ $method->id }}" class="mt-0.5 text-brand-green-600">
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

            <div class="bg-white p-6 rounded-3xl border border-brand-green-100 shadow-soft space-y-4">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-brand-green-500 text-white text-xs flex items-center justify-center font-bold">3</span>
                    Payment Method
                </h2>

                <template x-if="paymentMethods.length === 0">
                    <p class="text-sm text-slate-500">কোনো পেমেন্ট পদ্ধতি উপলব্ধ নেই। / No payment methods available.</p>
                </template>

                <template x-if="paymentMethods.length > 0 && selectedMethod">
                    <div class="space-y-4">
                        <div class="relative">
                            <select
                                name="payment_method"
                                x-model="selectedPaymentCode"
                                aria-label="Payment method"
                                class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-10 text-sm font-medium text-slate-900 shadow-soft outline-none transition focus:border-brand-green-500 focus:ring-2 focus:ring-brand-green-100"
                            >
                                <template x-for="method in paymentMethods" :key="method.id">
                                    <option :value="method.code" x-text="method.name"></option>
                                </template>
                            </select>
                            <span class="pointer-events-none absolute inset-y-0 right-3.5 flex items-center text-brand-green-600">⌄</span>
                        </div>

                        <template x-if="selectedMethod.config?.instructions">
                            <div class="rounded-xl border border-brand-green-200 bg-brand-green-50/70 p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.15em] text-brand-green-700">নির্দেশনা / Instructions</p>
                                <p class="mt-2 whitespace-pre-line text-sm text-slate-700" x-text="selectedMethod.config.instructions"></p>
                            </div>
                        </template>

                        <template x-if="isCOD">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-sm font-medium text-slate-700">ডেলিভারি পাওয়ার সময় ক্যাশ পেমেন্ট করতে হবে। / Pay in cash when your order is delivered.</p>
                            </div>
                        </template>

                        <template x-if="selectedMethod.type === 'mobile_banking' && selectedMethod.config?.merchantNumber">
                            <div class="space-y-4 rounded-xl bg-slate-50 p-5">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-slate-500">পেমেন্ট নম্বর / Payment Number</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-3">
                                        <span class="text-xl font-bold text-slate-900" x-text="selectedMethod.config.merchantNumber"></span>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-green-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-green-700"
                                            @click="copyNumber(selectedMethod.config.merchantNumber)"
                                        >
                                            <span x-text="copiedNumber === selectedMethod.config.merchantNumber ? 'কপি হয়েছে! / Copied!' : 'কপি / Copy'"></span>
                                        </button>
                                    </div>
                                    <template x-if="selectedMethod.config.merchantName">
                                        <p class="mt-2 text-sm text-slate-600">অ্যাকাউন্টের নাম: / Account Name: <span x-text="selectedMethod.config.merchantName"></span></p>
                                    </template>
                                </div>
                                <template x-if="selectedMethod.config?.qrCode">
                                    <img :src="selectedMethod.config.qrCode" alt="QR Code" class="h-36 w-36 rounded-xl border border-slate-200 object-contain bg-white">
                                </template>
                                <p class="text-sm text-slate-700">১. উপরের <span x-text="selectedMethod.name"></span> নম্বরে পেমেন্ট করুন।<br>২. পেমেন্ট সম্পন্ন করার পর Transaction ID দিন।</p>
                            </div>
                        </template>

                        <template x-if="selectedMethod.type === 'bank'">
                            <div class="space-y-2 rounded-xl bg-slate-50 p-5">
                                <p class="text-xs font-bold uppercase tracking-[0.15em] text-slate-500">ব্যাংক তথ্য / Bank Information</p>
                                <template x-if="selectedMethod.config?.bankName">
                                    <p class="text-sm text-slate-700">Bank Name: <strong x-text="selectedMethod.config.bankName"></strong></p>
                                </template>
                                <template x-if="selectedMethod.config?.accountName">
                                    <p class="text-sm text-slate-700">Account Name: <strong x-text="selectedMethod.config.accountName"></strong></p>
                                </template>
                                <template x-if="selectedMethod.config?.accountNumber">
                                    <p class="text-sm text-slate-700">Account Number: <strong x-text="selectedMethod.config.accountNumber"></strong></p>
                                </template>
                                <template x-if="selectedMethod.config?.branch">
                                    <p class="text-sm text-slate-700">Branch: <strong x-text="selectedMethod.config.branch"></strong></p>
                                </template>
                            </div>
                        </template>

                        <template x-if="requiresVerification">
                            <div class="space-y-4 rounded-xl border border-brand-green-200 bg-brand-green-50/70 p-5">
                                <div>
                                    <p class="text-sm font-bold text-brand-green-700">পেমেন্ট যাচাই / Payment Verification</p>
                                    <p class="mt-1 text-xs text-slate-500">পেমেন্ট করার পর নিচের তথ্য দিন। / After completing the payment, provide the following details.</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">যে নম্বর/অ্যাকাউন্ট থেকে টাকা পাঠিয়েছেন / Sender Account</label>
                                    <input type="text" name="sender_number" x-model="senderNumber" :required="requiresVerification" inputmode="tel" placeholder="01XXXXXXXXX"
                                        class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Transaction ID / ট্রানজেকশন আইডি</label>
                                    <input type="text" name="transaction_id" x-model="transactionId" :required="requiresVerification" placeholder="Enter your Transaction ID"
                                        class="w-full text-xs rounded-xl border border-slate-200 p-2.5 focus:border-brand-green-500 focus:outline-none">
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

        </div>

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
                    <template x-if="selectedMethod?.config?.extraFeePercent">
                        <p class="text-[11px] text-slate-400">Note: payment gateway fees may apply.</p>
                    </template>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-between text-base font-extrabold">
                    <span class="text-slate-800">Total:</span>
                    <span class="text-brand-orange-600" x-text="'৳' + ($store.cart.subtotal + shippingCharge).toFixed(0)">৳0</span>
                </div>

                <button type="submit" :disabled="$store.cart.items.length === 0 || !selectedPaymentCode" class="w-full py-3 px-4 rounded-full bg-brand-orange-500 hover:bg-brand-orange-600 disabled:opacity-50 text-white text-center text-sm font-bold shadow-md transition">
                    Confirm Order &rarr;
                </button>
            </div>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
function checkoutPage(opts) {
    return {
        shippingCharge: opts.shippingCharge,
        selectedShippingId: opts.selectedShippingId,
        paymentMethods: opts.paymentMethods || [],
        selectedPaymentCode: opts.selectedPaymentCode || (opts.paymentMethods?.[0]?.code ?? ''),
        itemsPayload: '[]',
        senderNumber: '',
        transactionId: '',
        copiedNumber: '',

        get selectedMethod() {
            return this.paymentMethods.find((m) => m.code === this.selectedPaymentCode) || null;
        },

        get isCOD() {
            return (this.selectedMethod?.code || '').toLowerCase() === 'cod';
        },

        get requiresVerification() {
            return Boolean(this.selectedMethod && !this.isCOD);
        },

        init() {
            this.itemsPayload = JSON.stringify(this.$store.cart.items);
            this.$watch('$store.cart.items', (val) => {
                this.itemsPayload = JSON.stringify(val);
            });
            this.$watch('selectedPaymentCode', () => {
                this.senderNumber = '';
                this.transactionId = '';
                this.copiedNumber = '';
            });
        },

        copyNumber(value) {
            if (!value) return;
            navigator.clipboard?.writeText(value).then(() => {
                this.copiedNumber = value;
                setTimeout(() => { if (this.copiedNumber === value) this.copiedNumber = ''; }, 2000);
            }).catch(() => {});
        },

        prepareSubmit(e) {
            if (this.$store.cart.items.length === 0) {
                e.preventDefault();
                return;
            }
            if (!this.selectedPaymentCode) {
                e.preventDefault();
                return;
            }
            if (this.requiresVerification && (!this.senderNumber.trim() || !this.transactionId.trim())) {
                e.preventDefault();
                alert('Please enter sender number and Transaction ID.');
            }
        },
    };
}
</script>
@endpush
