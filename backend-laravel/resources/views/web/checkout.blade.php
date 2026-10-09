@extends('layouts.app')

@php
    // Public-safe payment payload: only fields the customer needs to see.
    $publicKeys = ['instructions','merchantNumber','merchantName','qrCode','bankName','accountName','accountNumber','branch','routingNumber','minAmount','maxAmount'];
    $pmPayload = $paymentMethods->map(function ($m) use ($publicKeys) {
        $cfg = $m->config_array;
        return [
            'id' => $m->id,
            'code' => $m->code,
            'name' => $m->name,
            'type' => $m->type,
            'icon' => $m->icon,
            'config' => array_intersect_key($cfg, array_flip($publicKeys)),
        ];
    })->values();
    $shipPayload = $shippingMethods->map(function ($m) {
        return [
            'id' => $m->id,
            'name' => $m->name,
            'charge' => (float) $m->charge,
            'estimated' => $m->estimated_delivery,
            'description' => $m->description,
            'areas' => $m->applicable_areas,
            'areasList' => $m->areas_array,
            'freeMin' => $m->free_shipping_min_amount !== null ? (float) $m->free_shipping_min_amount : null,
            'codAvailable' => (bool) $m->cod_available,
        ];
    })->values();
    $defaultShip = $shippingMethods->first();
    $defaultPm = $paymentMethods->first();
    $districts = ['Dhaka','Gazipur','Narayanganj','Narsingdi','Munshiganj','Manikganj','Chattogram',"Cox's Bazar",'Cumilla','Sylhet','Khulna','Rajshahi','Rangpur','Barishal','Mymensingh','Tangail','Faridpur','Bogura','Dinajpur','Jashore','Kushtia','Pabna','Noakhali','Feni','Other'];
    $defaultDistrict = old('district', $checkoutSettings['default_district'] ?? 'Dhaka');
    $minOrder = (float) ($checkoutSettings['min_order_amount'] ?? 0);
    $globalFree = $checkoutSettings['free_shipping_threshold'] ?? null;
    $taxRate = (float) ($taxRate ?? 0);
@endphp

@section('content')
<div class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-8" x-data="checkoutPage({
    shippingMethods: @js($shipPayload),
    paymentMethods: @js($pmPayload),
    selectedShippingId: {{ old('shipping_method_id', $defaultShip?->id ?? 'null') }},
    selectedPaymentCode: @js(old('payment_method', $defaultPm?->code ?? '')),
    district: @js($defaultDistrict),
    globalFreeThreshold: @js($globalFree !== null && $globalFree !== '' ? (float) $globalFree : null),
    taxRate: {{ $taxRate }},
    minOrder: {{ $minOrder }},
    couponValidateUrl: @js(route('checkout.coupon')),
    trackUrl: @js(route('checkout.track')),
    initialCoupon: @js(old('coupon_code', '')),
    initialName: @js(old('customer_name', auth()->user()?->name ?? '')),
    initialPhone: @js(old('phone', auth()->user()?->phone ?? '')),
    initialAddress: @js(old('address', '')),
    initialAlt: @js(old('alternative_phone', '')),
})" x-init="init()">

    {{-- Progress indicator --}}
    <nav aria-label="Checkout progress" class="mb-5">
        <ol class="flex items-center gap-1.5 text-[11px] font-bold sm:gap-2 sm:text-xs">
            <li class="flex items-center gap-1.5 text-brand-green-700">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-green-600 text-[10px] text-white">✓</span>
                <a href="{{ route('cart') }}" class="hover:underline">Cart</a>
            </li>
            <li aria-hidden="true" class="h-px w-6 bg-brand-green-300 sm:w-10"></li>
            <li class="flex items-center gap-1.5 text-brand-green-700" aria-current="step">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-green-600 text-[10px] text-white">2</span>
                Details &amp; Payment
            </li>
            <li aria-hidden="true" class="h-px w-6 bg-slate-200 sm:w-10"></li>
            <li class="flex items-center gap-1.5 text-slate-400">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-[10px] text-slate-500">3</span>
                Confirmation
            </li>
        </ol>
        <h1 class="mt-3 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Checkout</h1>
        <p class="mt-0.5 text-xs text-slate-500">Delivery details, shipping &amp; payment — all on one page.</p>
    </nav>

    @if(session('error'))
        <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-semibold text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Configurable delivery notices (compact) --}}
    @if($checkoutNotices->isNotEmpty())
        <div class="mb-4 space-y-2">
            @foreach($checkoutNotices as $notice)
                <div class="flex items-start gap-2.5 rounded-xl border px-3.5 py-2.5 text-xs leading-5"
                     style="background-color: {{ $notice->background_color ?? '#FFF7ED' }}; border-color: #f1e3d3; color: {{ $notice->text_color ?? '#9A3412' }}">
                    <span aria-hidden="true" class="mt-0.5 shrink-0">📢</span>
                    <span>{{ $notice->text }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <form id="checkoutForm" action="{{ route('checkout.process') }}" method="POST" @submit.prevent="submitForm($event)" novalidate>
        @csrf
        <input type="hidden" name="checkout_session_id" :value="checkoutSessionId">
        <input type="hidden" name="order_key" :value="orderKey">
        <input type="hidden" name="coupon_code" :value="coupon.code">
        <input type="hidden" name="shipping_method_id" :value="selectedShippingId">
        <input type="hidden" name="utm_source" :value="attribution.utm_source">
        <input type="hidden" name="utm_medium" :value="attribution.utm_medium">
        <input type="hidden" name="utm_campaign" :value="attribution.utm_campaign">
        <input type="hidden" name="utm_content" :value="attribution.utm_content">
        <input type="hidden" name="utm_term" :value="attribution.utm_term">

        <template x-for="(item, index) in $store.cart.items" :key="item.key">
            <span>
                <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.id">
                <input type="hidden" :name="'items[' + index + '][variant_id]'" :value="item.variantId">
                <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                <input type="hidden" :name="'items[' + index + '][price]'" :value="item.price">
            </span>
        </template>

        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-3">

            {{-- LEFT (65%): delivery + shipping + payment --}}
            <div class="min-w-0 space-y-5 lg:col-span-2">

                {{-- Saved addresses --}}
                @if($savedAddresses->isNotEmpty())
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-label="Saved addresses">
                    <h2 class="text-sm font-bold text-slate-900">Saved addresses</h2>
                    <div class="mt-2.5 flex flex-wrap gap-2">
                        @foreach($savedAddresses as $a)
                        <button type="button" @click="fillSaved($event)"
                            data-addr="{{ json_encode(['name' => $a->recipient_name, 'phone' => $a->phone, 'district' => $a->district, 'address' => $a->address]) }}"
                            class="rounded-full border border-brand-green-200 bg-brand-green-50 px-3 py-1.5 text-xs font-semibold text-brand-green-700 transition hover:bg-brand-green-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-green-500">
                            {{ $a->recipient_name ?: 'Address' }} · {{ \Illuminate\Support\Str::limit($a->address ?? '', 24) }}
                        </button>
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- 1. Delivery information --}}
                <section class="rounded-2xl border bg-white p-4 shadow-sm sm:p-5"
                         :class="deliveryComplete ? 'border-brand-green-300' : 'border-slate-200'"
                         aria-labelledby="delivery-heading">
                    <h2 id="delivery-heading" class="flex items-center gap-2 text-sm font-bold text-slate-900 sm:text-base">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                              :class="deliveryComplete ? 'bg-brand-green-600 text-white' : 'bg-brand-green-500 text-white'">1</span>
                        Delivery Information
                        <span x-show="deliveryComplete" class="ml-auto rounded-full bg-brand-green-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-brand-green-700">Complete</span>
                    </h2>

                    <div class="mt-4 grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                        <div>
                            <label for="f_name" class="mb-1 block text-xs font-semibold text-slate-700">Full Name <span class="text-red-500">*</span></label>
                            <input id="f_name" type="text" name="customer_name" required autocomplete="name" maxlength="100"
                                x-model="form.name" @input="touch.delivery = true"
                                value="{{ old('customer_name', auth()->user()?->name ?? '') }}"
                                placeholder="e.g. Mohammad Ali"
                                class="w-full rounded-xl border p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green-100 @error('customer_name') border-red-400 @else border-slate-200 focus:border-brand-green-500 @enderror">
                            @error('customer_name')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                            <p x-show="touch.delivery && !form.name.trim()" class="mt-1 text-[11px] font-medium text-red-600">Full name is required.</p>
                        </div>
                        <div>
                            <label for="f_phone" class="mb-1 block text-xs font-semibold text-slate-700">Phone Number <span class="text-red-500">*</span></label>
                            <input id="f_phone" type="tel" name="phone" required autocomplete="tel" inputmode="numeric" maxlength="14"
                                x-model="form.phone" @input="touch.delivery = true"
                                value="{{ old('phone', auth()->user()?->phone ?? '') }}"
                                placeholder="e.g. 01712345678"
                                class="w-full rounded-xl border p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green-100 @error('phone') border-red-400 @else border-slate-200 focus:border-brand-green-500 @enderror">
                            @error('phone')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                            <p x-show="touch.delivery && form.phone && !validBD(form.phone)" class="mt-1 text-[11px] font-medium text-red-600">Enter a valid BD number (01XXXXXXXXX).</p>
                        </div>
                        <div>
                            <label for="f_alt" class="mb-1 block text-xs font-semibold text-slate-700">Alternative Phone <span class="font-normal text-slate-400">(optional)</span></label>
                            <input id="f_alt" type="tel" name="alternative_phone" autocomplete="tel" inputmode="numeric" maxlength="14"
                                x-model="form.altPhone" value="{{ old('alternative_phone') }}" placeholder="01XXXXXXXXX"
                                class="w-full rounded-xl border border-slate-200 p-2.5 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
                            @error('alternative_phone')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="f_district" class="mb-1 block text-xs font-semibold text-slate-700">District / City <span class="text-red-500">*</span></label>
                            <select id="f_district" name="district" required x-model="district" @change="onDistrictChange"
                                class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100 @error('district') border-red-400 @enderror">
                                @foreach($districts as $d)
                                    <option value="{{ $d }}" @selected($defaultDistrict === $d)>{{ $d }}</option>
                                @endforeach
                            </select>
                            @error('district')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="f_addr" class="mb-1 block text-xs font-semibold text-slate-700">Full Delivery Address <span class="text-red-500">*</span></label>
                            <textarea id="f_addr" name="address" required rows="2" autocomplete="street-address" maxlength="500"
                                x-model="form.address" @input="touch.delivery = true"
                                placeholder="House, Road, Area, Ward…" class="w-full rounded-xl border p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-green-100 @error('address') border-red-400 @else border-slate-200 focus:border-brand-green-500 @enderror">{{ old('address') }}</textarea>
                            @error('address')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="f_email" class="mb-1 block text-xs font-semibold text-slate-700">Email <span class="font-normal text-slate-400">(optional)</span></label>
                            <input id="f_email" type="email" name="email" autocomplete="email" maxlength="255" value="{{ old('email') }}"
                                placeholder="you@example.com"
                                class="w-full rounded-xl border border-slate-200 p-2.5 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
                            @error('email')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="f_delivery" class="mb-1 block text-xs font-semibold text-slate-700">Delivery Instructions <span class="font-normal text-slate-400">(optional)</span></label>
                            <input id="f_delivery" type="text" name="delivery_instructions" maxlength="1000" value="{{ old('delivery_instructions') }}"
                                placeholder="e.g. Call on arrival, 3rd floor" autocomplete="off"
                                class="w-full rounded-xl border border-slate-200 p-2.5 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="f_note" class="mb-1 block text-xs font-semibold text-slate-700">Order Notes <span class="font-normal text-slate-400">(optional)</span></label>
                            <input id="f_note" type="text" name="order_note" maxlength="1000" value="{{ old('order_note') }}"
                                placeholder="Anything else we should know" autocomplete="off"
                                class="w-full rounded-xl border border-slate-200 p-2.5 text-sm focus:border-brand-green-500 focus:outline-none focus:ring-2 focus:ring-brand-green-100">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-start gap-2 text-xs text-slate-600">
                                <input type="checkbox" name="marketing_consent" value="1" class="mt-0.5 h-4 w-4 accent-green-700">
                                <span>Send me occasional offers and new arrivals via SMS / email. You can opt out anytime. (Order updates are always sent.)</span>
                            </label>
                        </div>
                    </div>
                </section>

                {{-- 2. Shipping method --}}
                <section class="rounded-2xl border bg-white p-4 shadow-sm sm:p-5"
                         :class="selectedShipping ? 'border-brand-green-300' : 'border-slate-200'"
                         aria-labelledby="shipping-heading">
                    <h2 id="shipping-heading" class="flex items-center gap-2 text-sm font-bold text-slate-900 sm:text-base">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-green-500 text-xs font-bold text-white">2</span>
                        Shipping Method
                        <span class="ml-auto text-[11px] font-medium text-slate-400" x-text="district"></span>
                    </h2>

                    <div x-show="availableShipping.length === 0" class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-xs font-medium text-amber-800">
                        No delivery method is available for <span class="font-bold" x-text="district"></span>. Please try another district or contact support.
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-2.5 sm:grid-cols-2" role="radiogroup" aria-label="Shipping methods">
                        <template x-for="m in availableShipping" :key="m.id">
                            <label class="cursor-pointer rounded-2xl border p-3.5 transition focus-within:ring-2 focus-within:ring-brand-green-200"
                                :class="selectedShippingId === m.id ? 'border-brand-green-600 bg-brand-green-50/60 ring-1 ring-brand-green-500' : 'border-slate-200 hover:border-brand-green-300'">
                                <span class="flex items-start gap-2.5">
                                    <input type="radio" name="shipping_method_id_radio" :value="m.id" :checked="selectedShippingId === m.id"
                                        @change="selectedShippingId = m.id" class="mt-1 h-4 w-4 shrink-0 accent-green-700">
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-center justify-between gap-2">
                                            <span class="truncate text-[13px] font-bold text-slate-800" x-text="m.name"></span>
                                            <span class="shrink-0 text-[13px] font-extrabold" :class="effectiveShipping(m) === 0 ? 'text-brand-orange-600' : 'text-brand-green-700'"
                                                x-text="effectiveShipping(m) === 0 ? 'FREE' : '৳' + m.charge.toFixed(0)"></span>
                                        </span>
                                        <span class="mt-0.5 block text-[11px] text-slate-500" x-text="m.estimated || m.description || 'Fast delivery'"></span>
                                        <template x-if="m.freeMin">
                                            <span class="mt-1 inline-block rounded-full bg-brand-orange-50 px-2 py-0.5 text-[10px] font-bold text-brand-orange-600" x-text="'Free above ৳' + m.freeMin.toFixed(0)"></span>
                                        </template>
                                    </span>
                                </span>
                            </label>
                        </template>
                    </div>
                    @error('shipping_method_id')<p class="mt-2 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                </section>

                {{-- 3. Payment method --}}
                <section class="rounded-2xl border bg-white p-4 shadow-sm sm:p-5"
                         :class="selectedMethod ? 'border-brand-green-300' : 'border-slate-200'"
                         aria-labelledby="payment-heading">
                    <h2 id="payment-heading" class="flex items-center gap-2 text-sm font-bold text-slate-900 sm:text-base">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-green-500 text-xs font-bold text-white">3</span>
                        Payment Method
                    </h2>

                    <template x-if="visiblePayments.length === 0">
                        <p class="mt-3 text-xs text-slate-500">No payment methods available right now. Please contact support.</p>
                    </template>

                    <div class="mt-3 grid grid-cols-1 gap-2.5 sm:grid-cols-2" role="radiogroup" aria-label="Payment methods">
                        <template x-for="m in visiblePayments" :key="m.id">
                            <label class="cursor-pointer rounded-2xl border p-3.5 transition focus-within:ring-2 focus-within:ring-brand-green-200"
                                :class="selectedPaymentCode === m.code ? 'border-brand-green-600 bg-brand-green-50/60 ring-1 ring-brand-green-500' : 'border-slate-200 hover:border-brand-green-300'">
                                <span class="flex items-center gap-2.5">
                                    <input type="radio" name="payment_method" :value="m.code" x-model="selectedPaymentCode" class="h-4 w-4 shrink-0 accent-green-700">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm" x-text="m.icon || '৳'"></span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[13px] font-bold text-slate-800" x-text="m.name"></span>
                                        <span class="block text-[11px] text-slate-400" x-text="m.code === 'cod' ? 'Pay on delivery' : (m.type === 'online' ? 'Online gateway' : 'Manual verification')"></span>
                                    </span>
                                </span>
                            </label>
                        </template>
                    </div>

                    {{-- Instructions / verification --}}
                    <template x-if="selectedMethod?.config?.instructions">
                        <div class="mt-3 rounded-xl border border-brand-green-200 bg-brand-green-50/70 p-3.5">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-brand-green-700">Instructions</p>
                            <p class="mt-1 whitespace-pre-line text-[13px] text-slate-700" x-text="selectedMethod.config.instructions"></p>
                        </div>
                    </template>
                    <template x-if="isCOD">
                        <div class="mt-3 rounded-xl bg-slate-50 p-3.5 text-[13px] text-slate-700">
                            Pay in cash when your order is delivered. Please keep the exact amount ready if possible.
                        </div>
                    </template>
                    <template x-if="selectedMethod && selectedMethod.type === 'mobile_banking' && selectedMethod.config?.merchantNumber">
                        <div class="mt-3 rounded-xl bg-slate-50 p-4">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Payment Number</p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-2.5">
                                <span class="text-lg font-extrabold tracking-wide text-slate-900" x-text="selectedMethod.config.merchantNumber"></span>
                                <button type="button" @click="copyNumber(selectedMethod.config.merchantNumber)"
                                    class="rounded-lg bg-brand-green-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-green-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-green-500"
                                    x-text="copied ? 'Copied!' : 'Copy'"></button>
                            </div>
                            <template x-if="selectedMethod.config.merchantName">
                                <p class="mt-1.5 text-xs text-slate-600">Account: <span class="font-semibold" x-text="selectedMethod.config.merchantName"></span></p>
                            </template>
                        </div>
                    </template>
                    <template x-if="selectedMethod && selectedMethod.type === 'bank'">
                        <div class="mt-3 space-y-1 rounded-xl bg-slate-50 p-4 text-[13px] text-slate-700">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Bank Information</p>
                            <template x-if="selectedMethod.config?.bankName"><p>Bank: <strong x-text="selectedMethod.config.bankName"></strong></p></template>
                            <template x-if="selectedMethod.config?.accountName"><p>Account: <strong x-text="selectedMethod.config.accountName"></strong></p></template>
                            <template x-if="selectedMethod.config?.accountNumber"><p>Number: <strong x-text="selectedMethod.config.accountNumber"></strong></p></template>
                            <template x-if="selectedMethod.config?.branch"><p>Branch: <strong x-text="selectedMethod.config.branch"></strong></p></template>
                        </div>
                    </template>
                    <template x-if="requiresVerification">
                        <div class="mt-3 space-y-3 rounded-xl border border-brand-green-200 bg-brand-green-50/60 p-4">
                            <p class="text-[13px] font-bold text-brand-green-800">Payment Verification <span class="font-normal text-slate-500">— after paying, enter details below</span></p>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600"><span x-text="isMobileBanking ? 'Sender Mobile Number' : 'Sender Account Number'"></span> <span class="text-red-500">*</span></label>
                                <input type="tel" name="sender_number" x-model="senderNumber" inputmode="numeric" :placeholder="isMobileBanking ? '01XXXXXXXXX' : 'Account number you paid from'"
                                    class="w-full rounded-xl border bg-white p-2.5 text-sm focus:border-brand-green-500 focus:outline-none @error('sender_number') border-red-400 @else border-slate-200 @enderror">
                                @error('sender_number')<p class="mt-1 text-[11px] font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Transaction ID <span class="text-red-500">*</span></label>
                                <input type="text" name="transaction_id" x-model="transactionId" placeholder="Enter Transaction ID" autocomplete="off"
                                    class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-sm focus:border-brand-green-500 focus:outline-none">
                            </div>
                        </div>
                    </template>
                </section>
            </div>

            {{-- RIGHT (35%): sticky order summary --}}
            <aside class="min-w-0 lg:sticky lg:top-24" aria-label="Order summary">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="border-b border-slate-100 pb-3 text-sm font-bold text-slate-900 sm:text-base">Your Order</h2>

                    <template x-if="$store.cart.items.length === 0">
                        <div class="py-8 text-center">
                            <p class="text-3xl">🛒</p>
                            <p class="mt-2 text-sm font-semibold text-slate-700">Your cart is empty</p>
                            <p class="mt-0.5 text-xs text-slate-400">Add some products to continue.</p>
                            <a href="{{ route('shop') }}" class="mt-3 inline-block rounded-full bg-brand-green-600 px-5 py-2 text-xs font-bold text-white transition hover:bg-brand-green-700">Browse Shop</a>
                        </div>
                    </template>

                    <template x-if="$store.cart.items.length > 0">
                        <div>
                            <ul class="mt-1 max-h-64 divide-y divide-slate-100 overflow-y-auto pr-1">
                                <template x-for="item in $store.cart.items" :key="item.key">
                                    <li class="flex items-center gap-2.5 py-2.5">
                                        <span class="relative shrink-0">
                                            <img :src="item.image || '/brandlogo.png'" :alt="item.title" loading="lazy"
                                                class="h-11 w-11 rounded-xl border border-slate-100 object-cover">
                                            <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-green-600 px-1 text-[10px] font-bold text-white" x-text="item.quantity"></span>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-xs font-semibold text-slate-800" x-text="item.title"></span>
                                            <span class="block text-[11px] text-slate-400" x-text="'৳' + Number(item.price).toFixed(0) + ' each'"></span>
                                        </span>
                                        <span class="shrink-0 text-xs font-extrabold text-slate-800" x-text="'৳' + (item.price * item.quantity).toFixed(0)"></span>
                                    </li>
                                </template>
                            </ul>

                            {{-- Coupon --}}
                            <div class="mt-3 border-t border-slate-100 pt-3">
                                <template x-if="!coupon.code">
                                    <div class="flex gap-2">
                                        <label for="coupon_input" class="sr-only">Coupon code</label>
                                        <input id="coupon_input" type="text" x-model="couponInput" placeholder="Coupon code" autocomplete="off"
                                            class="h-10 min-w-0 flex-1 rounded-xl border border-slate-200 px-3 text-xs font-mono uppercase focus:border-brand-green-500 focus:outline-none">
                                        <button type="button" @click="applyCoupon()" :disabled="coupon.loading || !couponInput.trim()"
                                            class="h-10 shrink-0 rounded-xl border border-brand-green-600 px-4 text-xs font-bold text-brand-green-700 transition hover:bg-brand-green-50 disabled:opacity-50">
                                            <span x-text="coupon.loading ? '…' : 'Apply'"></span>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="coupon.code">
                                    <div class="flex items-center justify-between rounded-xl bg-brand-green-50 px-3 py-2">
                                        <p class="text-xs font-bold text-brand-green-700"><span x-text="coupon.code"></span> <span class="font-medium" x-text="'(−৳' + coupon.discount.toFixed(0) + ')'"></span></p>
                                        <button type="button" @click="removeCoupon()" class="text-xs font-semibold text-red-500 hover:text-red-700">Remove</button>
                                    </div>
                                </template>
                                <template x-if="coupon.message">
                                    <p class="mt-1.5 text-[11px] font-medium" :class="coupon.code ? 'text-brand-green-700' : 'text-red-600'" x-text="coupon.message"></p>
                                </template>
                            </div>

                            {{-- Breakdown --}}
                            <dl class="mt-3 space-y-1.5 border-t border-slate-100 pt-3 text-xs">
                                <div class="flex justify-between text-slate-600"><dt>Items Subtotal:</dt><dd class="font-bold text-slate-800" x-text="'৳' + subtotal.toFixed(0)"></dd></div>
                                <template x-if="coupon.discount > 0">
                                    <div class="flex justify-between text-brand-green-700"><dt>Coupon Discount:</dt><dd class="font-bold" x-text="'−৳' + coupon.discount.toFixed(0)"></dd></div>
                                </template>
                                <div class="flex justify-between text-slate-600"><dt>Delivery Charge:</dt>
                                    <dd class="font-bold" :class="shippingCharge === 0 ? 'text-brand-orange-600' : 'text-slate-800'" x-text="shippingCharge === 0 ? 'FREE' : '৳' + shippingCharge.toFixed(0)"></dd>
                                </div>
                                <template x-if="taxAmount > 0">
                                    <div class="flex justify-between text-slate-600"><dt>VAT / Tax:</dt><dd class="font-bold text-slate-800" x-text="'৳' + taxAmount.toFixed(0)"></dd></div>
                                </template>
                            </dl>

                            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
                                <span class="text-sm font-extrabold text-slate-900">Total:</span>
                                <span class="text-lg font-extrabold text-brand-orange-600" x-text="'৳' + total.toFixed(0)"></span>
                            </div>
                            <p class="mt-1 text-right text-[11px] text-slate-400">Final amount verified on server before order.</p>

                            <p x-show="formError" x-text="formError" class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-[11px] font-semibold text-red-600"></p>

                            <button type="submit" :disabled="submitting || $store.cart.items.length === 0 || !selectedShippingId || !selectedPaymentCode"
                                class="mt-3 flex w-full items-center justify-center gap-2 rounded-full bg-brand-orange-500 px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-brand-orange-600 disabled:cursor-not-allowed disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange-300">
                                <svg x-show="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                <span x-text="submitting ? 'Placing Order…' : 'Confirm Order →'"></span>
                            </button>
                            <p class="mt-2 text-center text-[11px] text-slate-400">🔒 Secure checkout · Cash on Delivery available</p>
                        </div>
                    </template>
                </div>
            </aside>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function checkoutPage(opts) {
    const BD_RE = /^(?:\+?880|0)1[3-9]\d{8}$/;
    const norm = (s) => (s || '').toString().trim().toLowerCase();
    return {
        shippingMethods: opts.shippingMethods || [],
        paymentMethods: opts.paymentMethods || [],
        selectedShippingId: opts.selectedShippingId ?? null,
        selectedPaymentCode: opts.selectedPaymentCode || '',
        district: opts.district || 'Dhaka',
        globalFreeThreshold: opts.globalFreeThreshold ?? null,
        taxRate: parseFloat(opts.taxRate || 0),
        minOrder: parseFloat(opts.minOrder || 0),
        couponValidateUrl: opts.couponValidateUrl,
        trackUrl: opts.trackUrl || '/checkout/track',
        checkoutSessionId: '',
        lastSavedStateHash: '',
        lastSavedPercent: 0,
        lastSaveTime: 0,
        saveDebounceTimer: null,
        debounceDelayMs: 3500,
        minSaveIntervalMs: 8000,
        form: { name: opts.initialName || '', phone: opts.initialPhone || '', altPhone: opts.initialAlt || '', address: opts.initialAddress || '' },
        touch: { delivery: false },
        senderNumber: @js(old('sender_number', '')),
        transactionId: @js(old('transaction_id', '')),
        coupon: { code: opts.initialCoupon || '', discount: 0, message: '', loading: false },
        couponInput: '',
        copied: false,
        submitting: false,
        formError: '',
        orderKey: (crypto.randomUUID ? crypto.randomUUID() : String(Date.now())),
        attribution: { utm_source: '', utm_medium: '', utm_campaign: '', utm_content: '', utm_term: '' },

        init() {
            this.checkoutSessionId = this.getCheckoutSessionId();
            this.captureAttribution();
            this.syncDomInputs();
            if (this.coupon.code) {
                this.couponInput = this.coupon.code;
                this.applyCoupon(true);
            }
            this.ensureValidShipping();

            // Setup watches for smart autosave
            this.$watch('district', () => { this.ensureValidShipping(); this.queueAutosave(); });
            this.$watch('form.name', () => this.queueAutosave());
            this.$watch('form.phone', () => this.queueAutosave());
            this.$watch('form.address', () => this.queueAutosave());
            this.$watch('form.altPhone', () => this.queueAutosave());
            this.$watch('$store.cart.subtotal', () => this.revalidateCoupon());
            this.$watch('selectedShippingId', () => this.queueAutosave(true));
            this.$watch('selectedPaymentCode', () => {
                this.senderNumber = '';
                this.transactionId = '';
                this.queueAutosave(true);
            });

            // Initial session tracking beacon (opened milestone)
            setTimeout(() => {
                this.saveProgress('opened');
            }, 800);
        },

        normalizePhone(v) {
            return (v || '').replace(/[০-৯]/g, (d) => String('০১২৩৪৫৬৭৮৯'.indexOf(d))).replace(/[\s\-().]+/g, '');
        },
        validBD(v) { return BD_RE.test(this.normalizePhone(v)); },

        captureAttribution() {
            try {
                const params = new URLSearchParams(window.location.search);
                const stored = JSON.parse(sessionStorage.getItem('mb_attribution') || '{}');
                ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'].forEach((k) => {
                    const v = params.get(k);
                    if (v) stored[k] = v;
                });
                if (!stored.landing && document.referrer === '') stored.landing = window.location.href;
                sessionStorage.setItem('mb_attribution', JSON.stringify(stored));
                this.attribution = {
                    utm_source: stored.utm_source || '', utm_medium: stored.utm_medium || '',
                    utm_campaign: stored.utm_campaign || '', utm_content: stored.utm_content || '',
                    utm_term: stored.utm_term || '',
                };
            } catch (e) { /* attribution is best-effort only */ }
        },

        get availableShipping() {
            const d = norm(this.district);
            return this.shippingMethods.filter((m) => {
                const areas = m.areasList || [];
                if (!areas.length) return true;
                if (areas.includes(d)) return true;
                const isDhaka = ['dhaka', 'dhaka city', 'inside dhaka'].includes(d);
                if (isDhaka && areas.some((a) => ['inside_dhaka', 'inside dhaka', 'dhaka'].includes(a))) return true;
                if (!isDhaka && areas.some((a) => ['outside_dhaka', 'outside dhaka', 'outside'].includes(a))) return true;
                return areas.some((a) => a && (d.includes(a) || a.includes(d)));
            });
        },

        get selectedShipping() {
            return this.shippingMethods.find((m) => m.id === this.selectedShippingId) || null;
        },

        get visiblePayments() {
            if (!this.selectedShipping || this.selectedShipping.codAvailable) return this.paymentMethods;
            return this.paymentMethods.filter((m) => m.code !== 'cod');
        },

        get selectedMethod() {
            return this.paymentMethods.find((m) => m.code === this.selectedPaymentCode) || null;
        },
        get isCOD() { return (this.selectedMethod?.code || '').toLowerCase() === 'cod'; },
        get requiresVerification() { return Boolean(this.selectedMethod && !this.isCOD); },
        get isMobileBanking() { return this.selectedMethod?.type === 'mobile_banking'; },

        get subtotal() { return this.$store.cart.subtotal || 0; },

        effectiveShipping(m) {
            if (!m) return 0;
            const base = this.subtotal - this.coupon.discount;
            const threshold = m.freeMin ?? this.globalFreeThreshold;
            if (threshold !== null && threshold !== '' && base >= parseFloat(threshold)) return 0;
            return parseFloat(m.charge || 0);
        },
        get shippingCharge() { return this.effectiveShipping(this.selectedShipping); },
        get taxAmount() {
            if (!this.taxRate) return 0;
            return Math.max(0, Math.round((this.subtotal - this.coupon.discount) * (this.taxRate / 100)));
        },
        get total() { return Math.max(0, this.subtotal - this.coupon.discount + this.shippingCharge + this.taxAmount); },

        get deliveryComplete() {
            return this.form.name.trim().length >= 3 && this.validBD(this.form.phone) && this.form.address.trim().length >= 8 && !!this.district;
        },

        ensureValidShipping() {
            const avail = this.availableShipping;
            if (!avail.some((m) => m.id === this.selectedShippingId)) {
                this.selectedShippingId = avail.length ? avail[0].id : null;
            }
            if (this.selectedShipping && !this.selectedShipping.codAvailable && this.selectedPaymentCode === 'cod') {
                const fallback = this.visiblePayments[0];
                this.selectedPaymentCode = fallback ? fallback.code : '';
            }
        },
        onDistrictChange() { this.touch.delivery = true; this.ensureValidShipping(); },

        fillSaved(event) {
            const raw = event?.currentTarget?.dataset?.addr || '{}';
            let d = {};
            try { d = JSON.parse(raw); } catch (e) { d = {}; }
            if (d.name) this.form.name = d.name;
            if (d.phone) this.form.phone = d.phone;
            if (d.district) { this.district = d.district; }
            if (d.address) this.form.address = d.address;
            this.touch.delivery = true;
            this.syncDomInputs();
            this.ensureValidShipping();
        },
        syncDomInputs() {
            const map = { f_name: this.form.name, f_phone: this.form.phone, f_addr: this.form.address, f_alt: this.form.altPhone };
            Object.entries(map).forEach(([id, v]) => { const el = document.getElementById(id); if (el) el.value = v; });
            const ds = document.getElementById('f_district');
            if (ds && ![...ds.options].some((o) => o.value === this.district)) {
                const opt = document.createElement('option'); opt.value = this.district; opt.textContent = this.district; ds.appendChild(opt);
            }
            if (ds) ds.value = this.district;
        },

        copyNumber(value) {
            if (!value) return;
            navigator.clipboard?.writeText(value).then(() => {
                this.copied = true;
                setTimeout(() => (this.copied = false), 2000);
            }).catch(() => {});
        },

        async applyCoupon(silent = false) {
            const code = (this.couponInput || '').trim();
            if (!code) return;
            this.coupon.loading = true;
            this.coupon.message = '';
            try {
                const res = await fetch(this.couponValidateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value },
                    body: JSON.stringify({ code, subtotal: this.subtotal }),
                });
                const json = await res.json();
                if (json.success) {
                    this.coupon.code = json.code;
                    this.coupon.discount = parseFloat(json.discount || 0);
                    this.coupon.message = json.message;
                } else {
                    this.coupon.code = '';
                    this.coupon.discount = 0;
                    this.coupon.message = json.message || 'Invalid coupon.';
                    if (!silent) this.couponInput = '';
                }
            } catch (e) {
                this.coupon.message = 'Could not validate coupon. Try again.';
            } finally {
                this.coupon.loading = false;
            }
        },
        removeCoupon() { this.coupon.code = ''; this.coupon.discount = 0; this.coupon.message = ''; this.couponInput = ''; },
        async revalidateCoupon() {
            if (!this.coupon.code) return;
            this.couponInput = this.coupon.code;
            await this.applyCoupon(true);
        },

        getCheckoutSessionId() {
            try {
                let sid = sessionStorage.getItem('mb_checkout_sid');
                if (!sid) {
                    sid = 'cs_' + (crypto.randomUUID ? crypto.randomUUID().replace(/-/g, '') : (Date.now().toString(36) + Math.random().toString(36).substring(2)));
                    sessionStorage.setItem('mb_checkout_sid', sid);
                }
                return sid;
            } catch (e) {
                return 'cs_' + Date.now().toString(36);
            }
        },

        getDeviceCategory() {
            const ua = navigator.userAgent || '';
            if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(ua)) return 'tablet';
            if (/Mobile|iP(hone|od)|Android|BlackBerry|IEMobile|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/i.test(ua)) return 'mobile';
            return 'desktop';
        },

        getCompletedFields() {
            const fields = [];
            if ((this.form.name || '').trim().length >= 2) fields.push('customer_name');
            if (this.validBD(this.form.phone) || (this.form.phone || '').trim().length >= 8) fields.push('phone');
            if ((this.district || '').trim().length > 0) fields.push('district');
            if ((this.form.address || '').trim().length >= 5) fields.push('address');
            if (this.selectedShippingId) fields.push('shipping_method');
            if (this.selectedPaymentCode) fields.push('payment_method');
            if ((this.form.altPhone || '').trim().length > 0) fields.push('alternative_phone');
            return fields;
        },

        calculateProgress() {
            const fields = this.getCompletedFields();
            let percent = 10;
            let step = 'opened';
            let milestone = 'opened';

            const hasName = fields.includes('customer_name');
            const hasPhone = fields.includes('phone');
            const hasAddress = fields.includes('address');
            const hasDistrict = fields.includes('district');
            const hasShipping = fields.includes('shipping_method');
            const hasPayment = fields.includes('payment_method');

            if (hasName || hasPhone) {
                percent = Math.max(percent, 25);
                step = 'contact_started';
                milestone = 'started_entering_info';
            }
            if (hasName && hasPhone) {
                percent = Math.max(percent, 50);
                step = 'contact_completed';
                milestone = 'progress_50';
            }
            if (hasName && hasPhone && hasAddress && hasDistrict) {
                percent = Math.max(percent, 75);
                step = 'shipping_started';
                milestone = 'progress_75';
            }
            if (hasName && hasPhone && hasAddress && hasDistrict && hasShipping) {
                percent = Math.max(percent, 85);
                step = 'shipping_completed';
                milestone = 'shipping_completed';
            }
            if (hasName && hasPhone && hasAddress && hasDistrict && hasShipping && hasPayment) {
                percent = Math.max(percent, 95);
                step = 'payment_selected';
                milestone = 'payment_selected';
            }

            return { percent, step, milestone, fields };
        },

        queueAutosave(immediate = false) {
            if (this.saveDebounceTimer) {
                clearTimeout(this.saveDebounceTimer);
                this.saveDebounceTimer = null;
            }

            const delay = immediate ? 600 : this.debounceDelayMs;
            this.saveDebounceTimer = setTimeout(() => {
                this.saveProgress();
            }, delay);
        },

        saveProgress(customMilestone = null) {
            const calc = this.calculateProgress();
            const milestone = customMilestone || calc.milestone;
            const cartItems = (this.$store?.cart?.items || []);
            const cartCount = cartItems.reduce((acc, i) => acc + (parseInt(i.quantity) || 1), 0);
            const cartTotal = this.total;

            const shipName = this.selectedShipping ? this.selectedShipping.name : null;
            const pmCode = this.selectedPaymentCode || null;

            // Generate state signature to prevent duplicate requests when state has not changed
            const stateSig = `${calc.percent}|${calc.step}|${calc.fields.sort().join(',')}|${shipName}|${pmCode}|${cartCount}|${Math.round(cartTotal)}`;
            if (stateSig === this.lastSavedStateHash && !customMilestone) {
                return;
            }

            // Enforce minimum interval throttle (8s), unless milestone changed meaningfully
            const now = Date.now();
            if (!customMilestone && (now - this.lastSaveTime < this.minSaveIntervalMs) && (calc.percent === this.lastSavedPercent)) {
                return;
            }

            this.lastSavedStateHash = stateSig;
            this.lastSavedPercent = calc.percent;
            this.lastSaveTime = now;

            const payload = {
                checkout_session_id: this.checkoutSessionId,
                progress_percent: calc.percent,
                current_step: calc.step,
                milestone: milestone,
                completed_fields: calc.fields,
                cart_item_count: cartCount,
                cart_total: cartTotal,
                selected_shipping_method: shipName,
                selected_payment_method: pmCode,
                district: this.district,
                device_type: this.getDeviceCategory(),
            };

            const tokenEl = document.querySelector('input[name=_token]');
            const csrfToken = tokenEl ? tokenEl.value : '';

            try {
                fetch(this.trackUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                    keepalive: true,
                }).catch(() => {});
            } catch (e) {
                // Non-blocking, fails gracefully
            }
        },

        submitForm(event) {
            this.formError = '';
            this.touch.delivery = true;
            if (this.$store.cart.items.length === 0) { this.formError = 'Your cart is empty.'; return; }
            if (!this.form.name.trim()) { this.formError = 'Please enter your full name.'; return; }
            if (!this.validBD(this.form.phone)) { this.formError = 'Please enter a valid BD phone number.'; return; }
            if (!this.district) { this.formError = 'Please select your district.'; return; }
            if (!this.form.address.trim()) { this.formError = 'Please enter your delivery address.'; return; }
            if (!this.selectedShippingId) { this.formError = 'Please select a delivery method available for your area.'; return; }
            if (!this.selectedPaymentCode) { this.formError = 'Please select a payment method.'; return; }
            if (this.requiresVerification && (!this.senderNumber.trim() || !this.transactionId.trim())) {
                this.formError = 'Please enter sender number and Transaction ID for this payment method.';
                return;
            }
            if (this.requiresVerification && this.isMobileBanking && !this.validBD(this.senderNumber)) {
                this.formError = 'Sender number must be the ' + (this.selectedMethod.name || 'mobile banking') + ' number you paid from (e.g. 01712345678).';
                return;
            }
            if (this.minOrder && this.subtotal < this.minOrder) {
                this.formError = 'Minimum order amount is ৳' + this.minOrder.toFixed(0) + '.';
                return;
            }
            // Send non-blocking submitted milestone
            this.saveProgress('submitted');
            this.syncDomInputs();
            this.submitting = true;
            event.target.submit();
        },
    };
}
</script>
@endpush
