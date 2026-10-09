@extends('layouts.admin', ['headerTitle' => 'Order Management'])

@section('content')
@php
    $advancedKeys = [
        'payment_status', 'payment_method', 'customer_type', 'customer_name', 'email', 'phone',
        'min_amount', 'max_amount', 'shipping_method', 'delivery_area', 'order_id', 'invoice_number',
        'product_search', 'sku', 'failed_emails', 'missing_info', 'attention_required',
        'start_date', 'end_date', 'month', 'year'
    ];
    $hasAdvancedActive = false;
    $advancedCount = 0;
    foreach ($advancedKeys as $ak) {
        if (!empty($params[$ak])) {
            $hasAdvancedActive = true;
            $advancedCount++;
        }
    }
    $currentPreset = $params['preset'] ?? '';
    $currentSort = $params['sort'] ?? 'newest';
    $currentDateRange = $params['date_range'] ?? '';
    $currentStatus = $params['status'] ?? '';
@endphp

<div class="admin-page space-y-5" x-data="{
    advancedOpen: {{ $hasAdvancedActive ? 'true' : 'false' }},
    datePreset: '{{ $currentDateRange }}'
}">

    {{-- Page Header --}}
    <x-admin.page-header title="Orders" :subtitle="$orders->total().' orders'.($summaryStats['is_filtered'] ? ' (filtered from overall store data)' : '')" />

    {{-- Summary Cards --}}
    <div class="space-y-2">
        <div class="flex items-center justify-between text-xs text-slate-500">
            <span class="font-semibold uppercase tracking-wider text-[11px]">
                {{ $summaryStats['is_filtered'] ? 'Summary for current filtered orders' : 'Overall Store Summary' }}
            </span>
            <span class="text-[11px] text-slate-400">
                Completed Revenue excludes unpaid, cancelled, and refunded orders
            </span>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-7">
            <x-admin.metric-card
                label="Total Orders"
                :value="number_format($summaryStats['total'])"
                hint="{{ $summaryStats['is_filtered'] ? 'Matching filters' : 'All time' }}" />

            <x-admin.metric-card
                label="Pending"
                :value="number_format($summaryStats['pending'])"
                tone="warning"
                hint="Awaiting processing" />

            <x-admin.metric-card
                label="Paid"
                :value="number_format($summaryStats['paid'])"
                tone="success"
                hint="Verified or successful" />

            <x-admin.metric-card
                label="Unpaid"
                :value="number_format($summaryStats['unpaid'])"
                tone="danger"
                hint="Pending payment / COD" />

            <x-admin.metric-card
                label="Delivered"
                :value="number_format($summaryStats['delivered'])"
                tone="success"
                hint="Completed orders" />

            <x-admin.metric-card
                label="Cancelled / Ref."
                :value="number_format($summaryStats['cancelled'])"
                tone="danger"
                hint="Returned or cancelled" />

            <x-admin.metric-card
                label="Completed Revenue"
                :value="'৳'.number_format($summaryStats['completed_revenue'], 0)"
                tone="accent"
                hint="Paid & delivered orders" />
        </div>
    </div>

    {{-- Quick Filter Presets --}}
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1 shrink-0">Presets:</span>
        @php
            $presets = [
                '' => 'All Orders',
                'today' => "Today's Orders",
                'pending' => 'Pending',
                'unpaid' => 'Unpaid',
                'ready_to_ship' => 'Ready to Ship',
                'delivered' => 'Delivered',
                'cancelled' => 'Cancelled / Refunded',
                'failed_emails' => 'Failed Emails',
            ];
        @endphp
        @foreach($presets as $pkey => $plabel)
            @php
                $isActivePreset = ($currentPreset === $pkey) || ($pkey === '' && empty($currentPreset) && !$summaryStats['is_filtered']);
                $presetParams = $pkey ? array_merge($params, ['preset' => $pkey, 'page' => 1]) : ['sort' => $currentSort];
                if ($pkey === '') {
                    unset($presetParams['preset']);
                }
            @endphp
            <a href="{{ route('admin.orders.index', $presetParams) }}"
               class="shrink-0 px-3 py-1.5 rounded-full font-semibold transition border {{ $isActivePreset ? 'bg-brand-green-700 text-white border-brand-green-700 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900' }}">
                {{ $plabel }}
            </a>
        @endforeach
    </div>

    {{-- Filter Toolbar Form --}}
    <form method="GET" action="{{ route('admin.orders.index') }}" class="space-y-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm" id="orderFilterForm">
        {{-- Primary Toolbar: Universal Search, Quick Status, Quick Date, Sort, Advanced Toggle --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            {{-- Universal Search (Col 4) --}}
            <div class="lg:col-span-4">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ $params['search'] ?? '' }}"
                           placeholder="Search Order ID, customer, phone, email, SKU..."
                           class="admin-control w-full pl-9 text-xs" />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            {{-- Status Filter (Col 2) --}}
            <div class="lg:col-span-2">
                <select name="status" class="admin-control w-full text-xs">
                    <option value="">All Statuses</option>
                    @foreach(\App\Services\OrderFilterService::VALID_STATUSES as $stKey => $stLabel)
                        <option value="{{ $stKey }}" @selected($currentStatus === $stKey)>{{ $stLabel }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range Preset (Col 2) --}}
            <div class="lg:col-span-2">
                <select name="date_range" x-model="datePreset" class="admin-control w-full text-xs">
                    <option value="">All Time</option>
                    @foreach(\App\Services\OrderFilterService::DATE_PRESETS as $dKey => $dLabel)
                        <option value="{{ $dKey }}" @selected($currentDateRange === $dKey)>{{ $dLabel }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Sorting (Col 2) --}}
            <div class="lg:col-span-2">
                <select name="sort" class="admin-control w-full text-xs">
                    @foreach(\App\Services\OrderFilterService::SORTS as $sKey => $sLabel)
                        <option value="{{ $sKey }}" @selected($currentSort === $sKey)>{{ $sLabel }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Actions: Advanced Toggle, Apply, Reset (Col 2) --}}
            <div class="lg:col-span-2 flex items-center gap-2">
                <button type="button"
                        @click="advancedOpen = !advancedOpen"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border text-xs font-bold transition {{ $advancedCount > 0 ? 'border-brand-green-300 bg-brand-green-50 text-brand-green-800' : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100' }}">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    <span>Filters</span>
                    @if($advancedCount > 0)
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-brand-green-700 text-white text-[10px] font-bold">
                            {{ $advancedCount }}
                        </span>
                    @endif
                </button>

                <x-admin.button type="submit" size="sm" class="px-4">Apply</x-admin.button>
                @if($summaryStats['is_filtered'] || !empty($params['search']) || !empty($params['sort']) && $params['sort'] !== 'newest')
                    <a href="{{ route('admin.orders.index', ['clear' => 1]) }}"
                       class="px-2 py-2 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                       title="Clear all filters">
                        ✕
                    </a>
                @endif
            </div>
        </div>

        {{-- Custom Date / Month Inputs (Shows dynamically when custom or month_year selected) --}}
        <div x-show="datePreset === 'custom' || datePreset === 'month_year'" x-cloak class="pt-3 border-t border-slate-100 flex flex-wrap items-center gap-3">
            <template x-if="datePreset === 'custom'">
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Start Date</label>
                        <input type="date" name="start_date" value="{{ $params['start_date'] ?? '' }}" class="admin-control text-xs" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">End Date</label>
                        <input type="date" name="end_date" value="{{ $params['end_date'] ?? '' }}" class="admin-control text-xs" />
                    </div>
                </div>
            </template>
            <template x-if="datePreset === 'month_year'">
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Month</label>
                        <select name="month" class="admin-control text-xs">
                            <option value="">Select Month</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected(($params['month'] ?? '') == $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Year</label>
                        <input type="number" name="year" value="{{ $params['year'] ?? date('Y') }}" min="2020" max="2030" class="admin-control text-xs w-24" />
                    </div>
                </div>
            </template>
        </div>

        {{-- Expandable Advanced Filters Panel --}}
        <div x-show="advancedOpen" x-cloak class="pt-4 mt-2 border-t border-slate-200/80 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                {{-- Col 1: Payment --}}
                <div class="space-y-2.5">
                    <h4 class="font-bold uppercase tracking-wider text-[11px] text-slate-500">Payment</h4>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Payment Status</label>
                        <select name="payment_status" class="admin-control w-full text-xs">
                            <option value="">All Payment Statuses</option>
                            @foreach(\App\Services\OrderFilterService::VALID_PAYMENT_STATUSES as $pKey => $pLabel)
                                <option value="{{ $pKey }}" @selected(($params['payment_status'] ?? '') === $pKey)>{{ $pLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Payment Method</label>
                        <select name="payment_method" class="admin-control w-full text-xs">
                            <option value="">All Payment Methods</option>
                            @foreach($paymentMethods as $pmKey => $pmLabel)
                                <option value="{{ $pmKey }}" @selected(($params['payment_method'] ?? '') === $pmKey)>{{ $pmLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Col 2: Customer --}}
                <div class="space-y-2.5">
                    <h4 class="font-bold uppercase tracking-wider text-[11px] text-slate-500">Customer</h4>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Customer Account</label>
                        <select name="customer_type" class="admin-control w-full text-xs">
                            <option value="">All (Registered & Guest)</option>
                            <option value="registered" @selected(($params['customer_type'] ?? '') === 'registered')>Registered Customer</option>
                            <option value="guest" @selected(($params['customer_type'] ?? '') === 'guest')>Guest Checkout</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Customer Phone</label>
                        <input type="text" name="phone" value="{{ $params['phone'] ?? '' }}" placeholder="Phone or alt phone" class="admin-control w-full text-xs" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Customer Email</label>
                        <input type="text" name="email" value="{{ $params['email'] ?? '' }}" placeholder="Email address" class="admin-control w-full text-xs" />
                    </div>
                </div>

                {{-- Col 3: Amount & Shipping --}}
                <div class="space-y-2.5">
                    <h4 class="font-bold uppercase tracking-wider text-[11px] text-slate-500">Amount & Shipping</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Min Price (৳)</label>
                            <input type="number" name="min_amount" value="{{ $params['min_amount'] ?? '' }}" placeholder="0" class="admin-control w-full text-xs" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Max Price (৳)</label>
                            <input type="number" name="max_amount" value="{{ $params['max_amount'] ?? '' }}" placeholder="10000" class="admin-control w-full text-xs" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Shipping Method</label>
                        <select name="shipping_method" class="admin-control w-full text-xs">
                            <option value="">All Shipping Methods</option>
                            @foreach($shippingMethods as $sm)
                                <option value="{{ $sm->id }}" @selected(($params['shipping_method'] ?? '') == $sm->id)>{{ $sm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Delivery District / Area</label>
                        <input type="text" name="delivery_area" value="{{ $params['delivery_area'] ?? '' }}" placeholder="e.g. Dhaka, Chittagong, Mirpur" class="admin-control w-full text-xs" />
                    </div>
                </div>

                {{-- Col 4: Items & Specific Flags --}}
                <div class="space-y-2.5">
                    <h4 class="font-bold uppercase tracking-wider text-[11px] text-slate-500">Products & Conditions</h4>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Contains Product or SKU</label>
                        <input type="text" name="product_search" value="{{ $params['product_search'] ?? '' }}" placeholder="Product title or SKU" class="admin-control w-full text-xs" />
                    </div>
                    <div class="pt-2 space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="failed_emails" value="1" @checked(!empty($params['failed_emails'])) class="rounded border-slate-300 text-brand-green-700 focus:ring-brand-green-600" />
                            <span>Failed email notifications</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="missing_info" value="1" @checked(!empty($params['missing_info'])) class="rounded border-slate-300 text-brand-green-700 focus:ring-brand-green-600" />
                            <span>Missing contact/address info</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="attention_required" value="1" @checked(!empty($params['attention_required'])) class="rounded border-slate-300 text-brand-green-700 focus:ring-brand-green-600" />
                            <span>Requires attention</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Advanced Filter Submit Actions --}}
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <a href="{{ route('admin.orders.index', ['clear' => 1]) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Clear All Filters
                </a>
                <x-admin.button type="submit" size="sm" class="px-5">
                    Apply Advanced Filters
                </x-admin.button>
            </div>
        </div>
    </form>

    {{-- Active Filter Chips Bar --}}
    @if(!empty($activeChips))
        <div class="flex flex-wrap items-center gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200/80 text-xs">
            <span class="font-bold text-slate-500 uppercase text-[10px] tracking-wider">Active Filters:</span>
            @foreach($activeChips as $chip)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <span>{{ $chip['label'] }}</span>
                    <a href="{{ $chip['remove_url'] }}" class="text-emerald-500 hover:text-emerald-800 font-bold ml-0.5" title="Remove filter">✕</a>
                </span>
            @endforeach
            <a href="{{ route('admin.orders.index', ['clear' => 1]) }}" class="text-xs font-bold text-rose-600 hover:text-rose-800 underline ml-2">
                Clear All
            </a>
            <span class="text-xs font-semibold text-slate-400 ml-auto">
                Found {{ $orders->total() }} matching orders
            </span>
        </div>
    @endif

    {{-- Orders List Table --}}
    <div class="admin-table-wrap">
        @if($orders->isEmpty())
            <div class="text-center py-12 px-4 bg-white rounded-xl border border-slate-200">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900 mb-1">No orders found</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">No orders match your active search terms or filter criteria. Try adjusting or clearing your filters.</p>
                <a href="{{ route('admin.orders.index', ['clear' => 1]) }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-brand-green-700 text-white text-xs font-bold hover:bg-brand-green-800 transition">
                    Reset All Filters
                </a>
            </div>
        @else
            {{-- Mobile Card View --}}
            <div class="md:hidden space-y-3">
                @foreach($orders as $ord)
                    @php
                        $statusVariant = match(true) {
                            $ord->status === 'delivered' => 'success',
                            in_array($ord->status, ['pending', 'processing', 'confirmed'], true) => 'warning',
                            in_array($ord->status, ['cancelled', 'refunded'], true) => 'destructive',
                            $ord->status === 'shipped' => 'secondary',
                            default => 'muted',
                        };
                        $orderDetailUrl = route('admin.orders.show', array_merge(['id' => $ord->id], request()->query()));
                    @endphp
                    <div class="admin-mobile-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ $orderDetailUrl }}" class="text-sm font-bold text-brand-green-700 hover:underline">{{ $ord->order_id }}</a>
                                <p class="mt-0.5 truncate text-sm font-semibold text-slate-900">{{ $ord->customer_name }}</p>
                                <p class="text-xs text-slate-500">{{ $ord->phone }}</p>
                            </div>
                            <x-admin.badge :variant="$statusVariant">{{ $ord->status }}</x-admin.badge>
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-slate-400">Items</dt>
                                <dd class="font-semibold text-slate-800">{{ $ord->items->count() }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-slate-400">Total</dt>
                                <dd class="font-bold text-slate-900">৳{{ number_format($ord->total_price, 0) }}</dd>
                            </div>
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-slate-400">Payment</dt>
                                <dd class="text-slate-700">
                                    <span class="uppercase font-semibold">{{ $ord->payment_method }}</span>
                                    <span class="{{ in_array($ord->payment_status, ['success', 'verified'], true) ? 'text-emerald-600' : 'text-amber-600' }}"> · {{ ucfirst(str_replace('_', ' ', $ord->payment_status)) }}</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-slate-400">Date</dt>
                                <dd class="text-slate-600">{{ $ord->created_at->format('M d, Y') }}</dd>
                            </div>
                        </dl>
                        <div class="mt-3 flex gap-2">
                            <x-admin.button :href="$orderDetailUrl" variant="outline" size="sm" class="flex-1">Details</x-admin.button>
                            <x-admin.button :href="route('admin.orders.invoice', $ord->id)" variant="outline" size="sm" class="flex-1" target="_blank">Invoice</x-admin.button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th class="admin-hide-sm">Payment</th>
                            <th>Status</th>
                            <th class="admin-hide-md">Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $ord)
                            @php
                                $statusVariant = match(true) {
                                    $ord->status === 'delivered' => 'success',
                                    in_array($ord->status, ['pending', 'processing', 'confirmed'], true) => 'warning',
                                    in_array($ord->status, ['cancelled', 'refunded'], true) => 'destructive',
                                    $ord->status === 'shipped' => 'secondary',
                                    default => 'muted',
                                };
                                $orderDetailUrl = route('admin.orders.show', array_merge(['id' => $ord->id], request()->query()));
                            @endphp
                            <tr>
                                <td class="font-bold text-brand-green-700">
                                    <a href="{{ $orderDetailUrl }}" class="hover:underline">{{ $ord->order_id }}</a>
                                </td>
                                <td>
                                    <span class="block font-semibold text-slate-900">{{ $ord->customer_name }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $ord->phone }}</span>
                                </td>
                                <td class="text-slate-600">{{ $ord->items->count() }}</td>
                                <td class="font-bold text-slate-900">৳{{ number_format($ord->total_price, 0) }}</td>
                                <td class="admin-hide-sm">
                                    <span class="block text-[10px] font-bold uppercase text-slate-700">{{ $ord->payment_method }}</span>
                                    <span class="text-[10px] font-semibold {{ in_array($ord->payment_status, ['success', 'verified'], true) ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst(str_replace('_', ' ', $ord->payment_status)) }}</span>
                                </td>
                                <td><x-admin.badge :variant="$statusVariant">{{ $ord->status }}</x-admin.badge></td>
                                <td class="admin-hide-md text-slate-500">{{ $ord->created_at->format('M d, Y') }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1" x-data="{ open: false }">
                                        <x-admin.button :href="$orderDetailUrl" variant="outline" size="sm">Details</x-admin.button>
                                        <x-admin.button :href="route('admin.orders.invoice', $ord->id)" variant="ghost" size="sm" target="_blank">Invoice</x-admin.button>
                                        <div class="relative">
                                            <button @click="open = !open" @click.away="open = false" class="rounded-[6px] px-2 py-1.5 text-xs font-bold text-slate-500 hover:bg-slate-100">▾</button>
                                            <div x-show="open" x-cloak class="absolute right-0 z-50 mt-1 w-44 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl py-1 text-left">
                                                <a href="{{ route('admin.orders.invoice.download', $ord->id) }}" class="block px-3 py-2 text-xs font-semibold hover:bg-slate-50">Download PDF</a>
                                                <a href="{{ route('admin.orders.packing-slip', $ord->id) }}" target="_blank" class="block px-3 py-2 text-xs font-semibold hover:bg-slate-50">Packing Slip</a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-admin.pagination :paginator="$orders" />
        @endif
    </div>
</div>
@endsection
