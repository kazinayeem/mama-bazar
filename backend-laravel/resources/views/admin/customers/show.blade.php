@extends('layouts.admin', ['headerTitle' => 'Customer 360° Profile'])

@section('content')
<div class="admin-page space-y-6">

    {{-- Top Navigation & Actions --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-brand-green-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Customers Directory
        </a>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="document.getElementById('edit-customer-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                Edit Customer
            </button>

            <form action="{{ route('admin.customers.toggle', $customer->id) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold shadow-sm transition {{ $customer->status === 'active' ? 'border-red-300 bg-white text-red-700 hover:bg-red-50' : 'border-emerald-300 bg-white text-emerald-700 hover:bg-emerald-50' }}">
                    {{ $customer->status === 'active' ? 'Deactivate Account' : 'Activate Account' }}
                </button>
            </form>

            <div class="relative inline-block text-left" x-data="{ open: false }">
                <button @click="open = !open" type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-green-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-green-800 focus:outline-none">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Dossier
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 z-20 mt-1 w-44 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5">
                    <a href="{{ route('admin.customers.export', ['id' => $customer->id, 'format' => 'pdf']) }}" class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                        <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        PDF Profile Report
                    </a>
                    <a href="{{ route('admin.customers.export', ['id' => $customer->id, 'format' => 'csv']) }}" class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        CSV Order History
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Customer Profile Header Card --}}
    <div class="admin-surface relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-start gap-4">
                @php $initial = strtoupper(substr($customer->name ?? '?', 0, 1)); @endphp
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-green-600 to-brand-green-800 text-2xl font-bold text-white shadow-md shadow-brand-green-600/20">
                    {{ $initial }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold text-slate-900">{{ $customer->name }}</h1>
                        <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-600">ID: #{{ $customer->id }}</span>
                        <x-admin.badge :variant="$customer->status === 'active' ? 'success' : 'destructive'">
                            {{ ucfirst($customer->status) }}
                        </x-admin.badge>
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                            Registered Customer
                        </span>
                    </div>

                    <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-slate-600">
                        <div class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span class="font-semibold text-slate-800">{{ $customer->phone }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>{{ $customer->email ?: 'No email on record' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Joined {{ $customer->created_at ? $customer->created_at->format('M d, Y') : '—' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Last login: {{ $customer->last_login_at ? $customer->last_login_at->diffForHumans() : 'Never' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick action links --}}
            <div class="flex items-center gap-2 border-t border-slate-100 pt-3 md:border-0 md:pt-0">
                <a href="#compose-email-section" onclick="switchCustomerTab('communication')" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Send Email
                </a>
                <a href="#notes-tab" onclick="switchCustomerTab('notes')" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Add Note
                </a>
            </div>
        </div>
    </div>

    {{-- Overview Statistics Cards --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Orders</p>
            <p class="mt-1 text-2xl font-black text-slate-900">{{ $metrics['totalOrders'] }}</p>
            <p class="mt-1 text-[11px] text-slate-400">All-time count</p>
        </div>

        <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Spending</p>
            <p class="mt-1 text-2xl font-black text-brand-green-700">৳{{ number_format($metrics['totalSpent'], 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Valid purchases</p>
        </div>

        <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Average Order</p>
            <p class="mt-1 text-2xl font-black text-slate-800">৳{{ number_format($metrics['aov'], 0) }}</p>
            <p class="mt-1 text-[11px] text-slate-400">AOV per order</p>
        </div>

        <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-600">Pending</p>
            <p class="mt-1 text-2xl font-black text-amber-700">{{ $metrics['pendingOrders'] }}</p>
            <p class="mt-1 text-[11px] text-slate-400">In fulfillment</p>
        </div>

        <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600">Delivered</p>
            <p class="mt-1 text-2xl font-black text-emerald-700">{{ $metrics['completedOrders'] }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Completed</p>
        </div>

        <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-red-600">Cancelled</p>
            <p class="mt-1 text-2xl font-black text-red-700">{{ $metrics['cancelledOrders'] }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Returned/Void</p>
        </div>
    </div>

    {{-- Tabs Navigation Bar --}}
    <div class="border-b border-slate-200 bg-white px-2 rounded-t-xl shadow-sm">
        <nav class="-mb-px flex flex-wrap gap-2 sm:gap-6" aria-label="Customer 360 Tabs" id="customer-tabs-nav">
            @php
                $tabs = [
                    'overview' => ['label' => 'Overview', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                    'orders' => ['label' => 'Order History ('.$metrics['totalOrders'].')', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                    'payments' => ['label' => 'Payments ('.$payments->total().')', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                    'addresses' => ['label' => 'Address Book ('.$savedAddresses->count().')', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z'],
                    'activity' => ['label' => 'Activity Timeline', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'analytics' => ['label' => 'Analytics', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    'communication' => ['label' => 'Communication', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                    'notes' => ['label' => 'Notes & Actions ('.$notes->count().')', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ];
            @endphp

            @foreach($tabs as $key => $tab)
                <button type="button" onclick="switchCustomerTab('{{ $key }}')" id="tab-btn-{{ $key }}" class="tab-trigger inline-flex items-center gap-2 border-b-2 py-3 px-1 text-xs font-semibold transition {{ ($activeTab === $key || (empty($activeTab) && $key === 'overview')) ? 'border-brand-green-700 text-brand-green-800' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"/></svg>
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- TAB 1: OVERVIEW --}}
    <div id="tab-content-overview" class="tab-panel space-y-6 {{ ($activeTab === 'overview' || empty($activeTab)) ? '' : 'hidden' }}">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left 2 cols: Account Summary & Recent Orders --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Account Summary Card --}}
                <div class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Account Information</h3>
                    <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2 text-xs">
                        <div>
                            <dt class="text-slate-400 font-medium">Customer Full Name</dt>
                            <dd class="mt-0.5 font-bold text-slate-900">{{ $customer->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Phone Number</dt>
                            <dd class="mt-0.5 font-semibold text-slate-800">{{ $customer->phone }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Email Address</dt>
                            <dd class="mt-0.5 font-semibold text-slate-800">{{ $customer->email ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Email Verification</dt>
                            <dd class="mt-0.5">
                                @if($customer->email_verified_at)
                                    <span class="inline-flex items-center text-emerald-700 font-semibold gap-1">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Verified ({{ $customer->email_verified_at->format('M d, Y') }})
                                    </span>
                                @else
                                    <span class="text-slate-400 font-medium">Unverified</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Shipping Area Preference</dt>
                            <dd class="mt-0.5 font-medium text-slate-800">{{ $customer->shipping_area ? ucwords(str_replace('_', ' ', $customer->shipping_area)) : 'Default / Not set' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 font-medium">Default Address</dt>
                            <dd class="mt-0.5 font-medium text-slate-800">{{ $customer->shipping_address ?: 'No address specified' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Recent Orders Snapshot --}}
                <div class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-bold text-slate-900">Recent Purchases</h3>
                        <button type="button" onclick="switchCustomerTab('orders')" class="text-xs font-semibold text-brand-green-700 hover:underline">
                            View All {{ $metrics['totalOrders'] }} Orders &rarr;
                        </button>
                    </div>

                    @if($paginatedOrders->isEmpty())
                        <div class="py-8 text-center text-xs text-slate-500">
                            No orders placed yet.
                        </div>
                    @else
                        <div class="divide-y divide-slate-100 mt-2">
                            @foreach($paginatedOrders->take(5) as $ord)
                                <div class="py-3 flex items-center justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.orders.show', $ord->id) }}" class="font-bold text-xs text-slate-900 hover:text-brand-green-700 hover:underline">
                                                #{{ $ord->order_id }}
                                            </a>
                                            <span class="text-[11px] text-slate-400">&bull; {{ $ord->created_at ? $ord->created_at->format('M d, Y') : '—' }}</span>
                                            <x-admin.badge :variant="$ord->status === 'delivered' ? 'success' : ($ord->status === 'cancelled' ? 'destructive' : 'warning')">
                                                {{ ucfirst($ord->status) }}
                                            </x-admin.badge>
                                        </div>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">
                                            @if($ord->items->count())
                                                {{ $ord->items->pluck('product_title')->implode(', ') }}
                                            @else
                                                1 order item
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-bold text-slate-900">৳{{ number_format((float) $ord->total_price, 2) }}</div>
                                        <div class="text-[11px] text-slate-400 uppercase font-medium">{{ $ord->payment_method ?: 'COD' }} ({{ $ord->payment_status ?: 'pending' }})</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right 1 col: Notes & Primary Address Preview --}}
            <div class="space-y-6">
                {{-- Quick Add Note Card --}}
                <div class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Internal Staff Notes</h3>
                    <form action="{{ route('admin.customers.notes.store', $customer->id) }}" method="POST" class="mt-3 space-y-2">
                        @csrf
                        <textarea name="note" rows="3" required placeholder="Add an internal note about this customer..." class="w-full rounded-md border border-slate-300 p-2 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none"></textarea>
                        <button type="submit" class="w-full rounded-md bg-slate-800 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-slate-900">
                            Save Internal Note
                        </button>
                    </form>

                    <div class="mt-4 space-y-2 max-h-56 overflow-y-auto divide-y divide-slate-100">
                        @forelse($notes->take(3) as $n)
                            <div class="pt-2 text-xs">
                                <div class="flex items-center justify-between text-[11px] text-slate-400">
                                    <span class="font-bold text-slate-700">{{ $n->admin?->name ?? 'Staff Admin' }}</span>
                                    <span>{{ $n->created_at ? $n->created_at->diffForHumans() : '—' }}</span>
                                </div>
                                <p class="mt-1 text-slate-600 bg-slate-50 p-2 rounded">{{ $n->note }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic">No notes recorded yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Primary Saved Address --}}
                <div class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-bold text-slate-900">Primary Address</h3>
                        <button type="button" onclick="switchCustomerTab('addresses')" class="text-xs font-semibold text-brand-green-700 hover:underline">
                            Manage Addresses
                        </button>
                    </div>

                    @php $defaultAddr = $savedAddresses->firstWhere('is_default', true) ?? $savedAddresses->first(); @endphp
                    @if($defaultAddr)
                        <div class="mt-3 text-xs space-y-1">
                            <p class="font-bold text-slate-900">{{ $defaultAddr->recipient_name }}</p>
                            <p class="text-slate-600">{{ $defaultAddr->phone }}</p>
                            <p class="text-slate-700">{{ $defaultAddr->formatted_address }}</p>
                            <span class="inline-block mt-2 rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 uppercase">Default Address</span>
                        </div>
                    @else
                        <p class="mt-3 text-xs text-slate-400 italic">No saved addresses in address book.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 2: ORDER HISTORY --}}
    <div id="tab-content-orders" class="tab-panel space-y-4 {{ $activeTab === 'orders' ? '' : 'hidden' }}">
        {{-- Orders Filter Bar --}}
        <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.customers.show', $customer->id) }}" class="space-y-3">
                <input type="hidden" name="tab" value="orders">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Search Order</label>
                        <input type="text" name="order_search" value="{{ request('order_search') }}" placeholder="Order #, Invoice #..." class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Order Status</label>
                        <select name="order_status" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('order_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ request('order_status') === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="delivered" {{ request('order_status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="completed" {{ request('order_status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('order_status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="returned" {{ request('order_status') === 'returned' ? 'selected' : '' }}>Returned</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Payment Status</label>
                        <select name="order_payment_status" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                            <option value="">All Payments</option>
                            <option value="pending" {{ request('order_payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="paid" {{ request('order_payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="partial" {{ request('order_payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="failed" {{ request('order_payment_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="refunded" {{ request('order_payment_status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Date From</label>
                        <input type="date" name="order_date_from" value="{{ request('order_date_from') }}" class="mt-1 block w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 rounded-md bg-brand-green-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-green-800">
                            Filter Orders
                        </button>
                        @if(request()->anyFilled(['order_search', 'order_status', 'order_payment_status', 'order_date_from', 'order_date_to']))
                            <a href="{{ route('admin.customers.show', ['id' => $customer->id, 'tab' => 'orders']) }}" class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs font-medium text-slate-600">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Order History Table --}}
        <div class="admin-table-wrap">
            @if($paginatedOrders->isEmpty())
                <x-admin.empty-state title="No orders found" description="No orders match the selected filters for this customer." />
            @else
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="min-width: 120px;">Order #</th>
                                <th style="min-width: 105px;">Date</th>
                                <th style="min-width: 220px;">Items Purchased</th>
                                <th class="text-center" style="min-width: 60px;">Qty</th>
                                <th class="text-right" style="min-width: 90px;">Subtotal</th>
                                <th class="text-right" style="min-width: 80px;">Delivery</th>
                                <th class="text-right" style="min-width: 80px;">Discount</th>
                                <th class="text-right" style="min-width: 100px;">Grand Total</th>
                                <th style="min-width: 110px;">Payment</th>
                                <th style="min-width: 100px;">Order Status</th>
                                <th class="text-right" style="min-width: 90px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paginatedOrders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="font-bold text-slate-900 hover:text-brand-green-700 hover:underline">
                                            #{{ $order->order_id }}
                                        </a>
                                        @if($order->invoice_number)
                                            <div class="text-[11px] font-mono text-slate-400">{{ $order->invoice_number }}</div>
                                        @endif
                                    </td>
                                    <td class="text-xs text-slate-600">
                                        {{ $order->created_at ? $order->created_at->format('M d, Y') : '—' }}
                                        <div class="text-[10px] text-slate-400">{{ $order->created_at ? $order->created_at->format('h:i A') : '' }}</div>
                                    </td>
                                    <td>
                                        <div class="space-y-1">
                                            @foreach($order->items->take(2) as $item)
                                                <div class="text-xs text-slate-800 flex items-center justify-between">
                                                    <span class="truncate max-w-[170px]">{{ $item->product_title }}</span>
                                                    <span class="text-slate-400 font-mono">x{{ $item->quantity }}</span>
                                                </div>
                                            @endforeach
                                            @if($order->items->count() > 2)
                                                <div class="text-[11px] font-semibold text-brand-green-700">+{{ $order->items->count() - 2 }} more item(s)</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center font-bold text-slate-700">
                                        {{ $order->items->sum('quantity') }}
                                    </td>
                                    <td class="text-right text-xs text-slate-700">
                                        ৳{{ number_format((float) $order->subtotal, 2) }}
                                    </td>
                                    <td class="text-right text-xs text-slate-500">
                                        ৳{{ number_format((float) $order->shipping_cost, 2) }}
                                    </td>
                                    <td class="text-right text-xs text-amber-600">
                                        {{ $order->discount > 0 ? '-৳'.number_format((float) $order->discount, 2) : '—' }}
                                    </td>
                                    <td class="text-right font-black text-slate-900">
                                        ৳{{ number_format((float) $order->total_price, 2) }}
                                    </td>
                                    <td>
                                        <div class="text-xs font-semibold text-slate-800 uppercase">{{ $order->payment_method ?: 'COD' }}</div>
                                        <x-admin.badge :variant="$order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'failed' ? 'destructive' : 'warning')">
                                            {{ ucfirst($order->payment_status ?: 'pending') }}
                                        </x-admin.badge>
                                    </td>
                                    <td>
                                        <x-admin.badge :variant="$order->status === 'delivered' || $order->status === 'completed' ? 'success' : ($order->status === 'cancelled' || $order->status === 'returned' ? 'destructive' : 'warning')">
                                            {{ ucfirst($order->status) }}
                                        </x-admin.badge>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200">
                    <x-admin.pagination :paginator="$paginatedOrders" />
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 3: PAYMENTS HISTORY --}}
    <div id="tab-content-payments" class="tab-panel space-y-4 {{ $activeTab === 'payments' ? '' : 'hidden' }}">
        <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.customers.show', $customer->id) }}" class="space-y-3">
                <input type="hidden" name="tab" value="payments">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Search Transaction</label>
                        <input type="text" name="pay_search" value="{{ request('pay_search') }}" placeholder="Txn ID, Order #, Phone..." class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Payment Status</label>
                        <select name="pay_status" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                            <option value="">All Statuses</option>
                            <option value="paid" {{ request('pay_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="pending" {{ request('pay_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="failed" {{ request('pay_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="refunded" {{ request('pay_status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600">Date From</label>
                        <input type="date" name="pay_date_from" value="{{ request('pay_date_from') }}" class="mt-1 block w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 rounded-md bg-brand-green-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-green-800">
                            Filter Payments
                        </button>
                        @if(request()->anyFilled(['pay_search', 'pay_status', 'pay_date_from', 'pay_date_to']))
                            <a href="{{ route('admin.customers.show', ['id' => $customer->id, 'tab' => 'payments']) }}" class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs font-medium text-slate-600">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="admin-table-wrap">
            @if($payments->isEmpty())
                <x-admin.empty-state title="No payment records found" description="No payment transactions match the filter criteria." />
            @else
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Transaction / Reference</th>
                                <th>Related Order</th>
                                <th>Method</th>
                                <th class="text-right">Amount (৳)</th>
                                <th>Payment Status</th>
                                <th>Payment Date</th>
                                <th>Sender / Notes</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $pay)
                                <tr>
                                    <td>
                                        <span class="font-mono text-xs font-bold text-slate-900">
                                            {{ $pay->transaction_id ?: 'TXN-'.$pay->order_id }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $pay->id) }}" class="font-semibold text-xs text-brand-green-700 hover:underline">
                                            #{{ $pay->order_id }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold uppercase text-slate-700">
                                            {{ $pay->payment_method ?: 'Cash on Delivery' }}
                                        </span>
                                    </td>
                                    <td class="text-right font-black text-slate-900">
                                        ৳{{ number_format((float) ($pay->amount_sent > 0 ? $pay->amount_sent : $pay->total_price), 2) }}
                                    </td>
                                    <td>
                                        <x-admin.badge :variant="$pay->payment_status === 'paid' ? 'success' : ($pay->payment_status === 'failed' ? 'destructive' : 'warning')">
                                            {{ ucfirst($pay->payment_status ?: 'pending') }}
                                        </x-admin.badge>
                                    </td>
                                    <td class="text-xs text-slate-600">
                                        {{ $pay->payment_date ? $pay->payment_date->format('M d, Y h:i A') : ($pay->created_at ? $pay->created_at->format('M d, Y') : '—') }}
                                    </td>
                                    <td class="text-xs text-slate-500">
                                        @if($pay->sender_number)
                                            <div><span class="font-medium text-slate-700">Sender:</span> {{ $pay->sender_number }}</div>
                                        @endif
                                        @if($pay->payment_instructions)
                                            <div class="truncate max-w-[150px]">{{ $pay->payment_instructions }}</div>
                                        @endif
                                        @if(!$pay->sender_number && !$pay->payment_instructions)
                                            —
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.orders.show', $pay->id) }}" class="text-xs font-semibold text-brand-green-700 hover:underline">
                                            Inspect Order
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200">
                    <x-admin.pagination :paginator="$payments" />
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 4: ADDRESS BOOK --}}
    <div id="tab-content-addresses" class="tab-panel space-y-6 {{ $activeTab === 'addresses' ? '' : 'hidden' }}">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Customer Saved Addresses</h3>
            <button type="button" onclick="document.getElementById('add-address-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-green-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-green-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Address
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($savedAddresses as $addr)
                <div class="admin-surface relative rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="font-bold text-sm text-slate-900">{{ $addr->recipient_name }}</span>
                            <p class="text-xs font-semibold text-slate-700 mt-0.5">{{ $addr->phone }}</p>
                        </div>
                        @if($addr->is_default)
                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-800">Default</span>
                        @endif
                    </div>

                    <div class="text-xs text-slate-600 space-y-1">
                        <p class="font-medium text-slate-800">{{ $addr->address }}</p>
                        @if($addr->apartment)
                            <p class="text-slate-500">Apt / Suite: {{ $addr->apartment }}</p>
                        @endif
                        <p class="text-slate-500">
                            {{ implode(', ', array_filter([$addr->area, $addr->upazila, $addr->district, $addr->division])) }}
                        </p>
                        @if($addr->postal_code)
                            <p class="text-slate-400">Postal Code: {{ $addr->postal_code }}</p>
                        @endif
                        <p class="text-[11px] text-slate-400">Shipping Zone: {{ ucwords(str_replace('_', ' ', $addr->shipping_area)) }}</p>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
                        <div class="flex items-center gap-2">
                            @if(!$addr->is_default)
                                <form action="{{ route('admin.customers.addresses.default', ['id' => $customer->id, 'addressId' => $addr->id]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-brand-green-700 hover:underline">
                                        Set Default
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <form action="{{ route('admin.customers.addresses.destroy', ['id' => $customer->id, 'addressId' => $addr->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this address?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <x-admin.empty-state title="No saved addresses" description="This customer does not have any saved addresses in their address book." />
                </div>
            @endforelse
        </div>

        {{-- Historical Shipping Addresses --}}
        @if($historicalOrderAddresses->isNotEmpty())
            <div class="mt-8">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Historical Shipping Addresses from Past Orders</h4>
                <p class="text-xs text-slate-400 mb-3">Addresses used during previous checkouts that may not be in the saved address book.</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($historicalOrderAddresses as $ordAddr)
                        <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50/70 p-3 text-xs">
                            <p class="font-semibold text-slate-800">{{ $ordAddr->customer_name ?: $customer->name }} ({{ $ordAddr->phone }})</p>
                            <p class="text-slate-600 mt-1">{{ $ordAddr->address }}</p>
                            <p class="text-slate-400 text-[11px]">{{ implode(', ', array_filter([$ordAddr->area, $ordAddr->upazila, $ordAddr->district, $ordAddr->division])) }}</p>
                            <div class="mt-2 text-[10px] text-slate-400">Used in Order #{{ $ordAddr->order_id }} ({{ $ordAddr->created_at ? $ordAddr->created_at->format('M Y') : '' }})</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- TAB 5: ACTIVITY TIMELINE --}}
    <div id="tab-content-activity" class="tab-panel space-y-4 {{ $activeTab === 'activity' ? '' : 'hidden' }}">
        <div class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Security & Account Event Timeline</h3>

            @if($activityLogs->isEmpty())
                <div class="py-12 text-center text-xs text-slate-400">
                    No activity logs recorded for this customer yet. Future events (orders, logins, updates, notes, status changes) will appear here in real time.
                </div>
            @else
                <div class="relative pl-6 mt-4 space-y-6 before:absolute before:bottom-0 before:top-2 before:left-2.5 before:w-0.5 before:bg-slate-200">
                    @foreach($activityLogs as $act)
                        <div class="relative flex items-start gap-4 text-xs">
                            <div class="absolute -left-6 top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-brand-green-100 text-brand-green-800 ring-4 ring-white">
                                <span class="h-2 w-2 rounded-full bg-brand-green-600"></span>
                            </div>
                            <div class="min-w-0 flex-1 bg-slate-50/70 rounded-lg p-3 border border-slate-100">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-slate-900">{{ $act->human_action ?: ucwords(str_replace(['.', '_'], ' ', $act->event_name)) }}</span>
                                    <span class="text-[11px] text-slate-400 font-mono" title="{{ $act->occurred_at }}">{{ $act->occurred_at ? $act->occurred_at->diffForHumans() : '—' }}</span>
                                </div>
                                <p class="mt-1 text-slate-600">{{ $act->description }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-400">
                                    <span>Actor: <strong class="text-slate-600">{{ $act->actor_name ?: ($act->actor_type ?? 'System') }}</strong></span>
                                    @if($act->ip_address)
                                        <span>IP: <span class="font-mono text-slate-600">{{ $act->ip_address }}</span></span>
                                    @endif
                                    <span>Status: <strong class="{{ $act->status === 'success' ? 'text-emerald-700' : 'text-red-700' }}">{{ ucfirst($act->status ?? 'success') }}</strong></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 border-t border-slate-200 mt-4">
                    <x-admin.pagination :paginator="$activityLogs" />
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 6: CUSTOMER ANALYTICS --}}
    <div id="tab-content-analytics" class="tab-panel space-y-6 {{ $activeTab === 'analytics' ? '' : 'hidden' }}">
        {{-- High Level Analytics KPIs --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="text-xs font-semibold uppercase text-slate-400">Customer Lifetime Value</span>
                <p class="mt-1 text-2xl font-black text-brand-green-700">৳{{ number_format($analytics['customerLifetimeValue'], 2) }}</p>
                <p class="mt-1 text-[11px] text-slate-400">Net completed revenue</p>
            </div>
            <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="text-xs font-semibold uppercase text-slate-400">Average Order Value</span>
                <p class="mt-1 text-2xl font-black text-slate-900">৳{{ number_format($metrics['aov'], 2) }}</p>
                <p class="mt-1 text-[11px] text-slate-400">Per valid transaction</p>
            </div>
            <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="text-xs font-semibold uppercase text-slate-400">Purchase Cadence</span>
                <p class="mt-1 text-base font-bold text-slate-800">{{ $analytics['orderFrequency'] }}</p>
                <p class="mt-1 text-[11px] text-slate-400">Order frequency pace</p>
            </div>
            <div class="admin-surface rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <span class="text-xs font-semibold uppercase text-slate-400">First / Latest Order</span>
                <p class="mt-1 text-xs font-bold text-slate-800">
                    {{ $metrics['firstOrderDate'] ? $metrics['firstOrderDate']->format('M d, Y') : 'None' }}
                </p>
                <p class="mt-0.5 text-xs font-bold text-slate-800">
                    Latest: {{ $metrics['lastOrderDate'] ? $metrics['lastOrderDate']->format('M d, Y') : 'None' }}
                </p>
            </div>
        </div>

        {{-- 12-Month Spending & Order Trend Visualization --}}
        <div class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">12-Month Order & Spending Progression</h3>
            <div class="mt-4 grid grid-cols-6 sm:grid-cols-12 gap-2 text-center text-xs">
                @php $maxSpend = max(1, max($analytics['monthlySpendData'])); @endphp
                @foreach($analytics['monthlyLabels'] as $idx => $label)
                    @php
                        $ordersCount = $analytics['monthlyOrdersData'][$idx] ?? 0;
                        $spendAmount = $analytics['monthlySpendData'][$idx] ?? 0;
                        $heightPercent = round(($spendAmount / $maxSpend) * 100);
                    @endphp
                    <div class="flex flex-col items-center justify-end h-40 group">
                        <div class="w-full flex items-end justify-center h-28 bg-slate-50 rounded-t pb-1">
                            <div class="w-3/4 rounded-t bg-brand-green-600 transition-all group-hover:bg-brand-green-700" style="height: {{ max(4, $heightPercent) }}%;" title="Orders: {{ $ordersCount }} | Spent: ৳{{ number_format($spendAmount, 0) }}"></div>
                        </div>
                        <div class="mt-2 text-[10px] font-bold text-slate-800">৳{{ $spendAmount > 999 ? round($spendAmount/1000, 1).'k' : round($spendAmount) }}</div>
                        <div class="text-[9px] font-semibold text-slate-400 truncate w-full">{{ substr($label, 0, 3) }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top Purchased Products & Categories --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Frequently Purchased Products</h3>
                @if($analytics['topProducts']->isEmpty())
                    <p class="py-6 text-center text-xs text-slate-400">No product purchases recorded yet.</p>
                @else
                    <div class="divide-y divide-slate-100 mt-2">
                        @foreach($analytics['topProducts'] as $prod)
                            <div class="py-2.5 flex items-center justify-between text-xs">
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-slate-900 truncate">{{ $prod->product_title }}</p>
                                    <p class="text-[11px] text-slate-400">Category: {{ $prod->product?->category?->name ?? 'General' }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block rounded bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-800">{{ $prod->total_quantity }} units</span>
                                    <p class="mt-0.5 font-bold text-slate-900">৳{{ number_format((float) $prod->total_spent, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Category Affinity Breakdown</h3>
                @if($analytics['topCategories']->isEmpty())
                    <p class="py-6 text-center text-xs text-slate-400">No categories recorded.</p>
                @else
                    <div class="divide-y divide-slate-100 mt-2">
                        @foreach($analytics['topCategories'] as $cat => $units)
                            <div class="py-2.5 flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-800">{{ $cat }}</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 font-bold text-slate-700">{{ $units }} items bought</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- TAB 7: COMMUNICATION --}}
    <div id="tab-content-communication" class="tab-panel space-y-6 {{ $activeTab === 'communication' ? '' : 'hidden' }}">
        {{-- Compose Email Form --}}
        <div id="compose-email-section" class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Compose Direct Message to Customer</h3>

            @if(empty($customer->email))
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">
                    <strong>Notice:</strong> This customer does not have an email address specified. Please update their profile with a valid email to enable direct customer communications.
                </div>
            @else
                <form action="{{ route('admin.customers.email.send', $customer->id) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700">Subject</label>
                            <input type="text" name="subject" value="{{ old('subject') }}" required placeholder="e.g. Important update regarding your recent Mama Bazar order..." class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Message Type</label>
                            <select name="email_type" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                                <option value="transactional">Transactional</option>
                                <option value="notification">Operational Notification</option>
                                <option value="order">Order Update</option>
                                <option value="campaign">Marketing / Promotion</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Message Content</label>
                        <textarea name="message" rows="5" required placeholder="Type the customer message here. It will be sent via configured SMTP and logged..." class="mt-1 block w-full rounded-md border border-slate-300 p-3 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">{{ old('message') }}</textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">Recipient: <strong>{{ $customer->email }}</strong> ({{ $customer->name }})</span>
                        <button type="submit" class="rounded-lg bg-brand-green-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-brand-green-800">
                            Dispatch Email
                        </button>
                    </div>
                </form>
            @endif
        </div>

        {{-- Email Delivery History Table --}}
        <div class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Email Transmission & Delivery Logs</h3>

            @if($emailLogs->isEmpty())
                <p class="py-8 text-center text-xs text-slate-400">No emails have been sent to this customer address yet.</p>
            @else
                <div class="overflow-x-auto mt-3">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Sent Date</th>
                                <th>Attempts</th>
                                <th>Details / Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($emailLogs as $log)
                                <tr>
                                    <td class="font-bold text-slate-900 text-xs">{{ $log->subject }}</td>
                                    <td><span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] uppercase font-semibold text-slate-700">{{ $log->email_type }}</span></td>
                                    <td>
                                        <x-admin.badge :variant="in_array($log->status, ['sent', 'delivered']) ? 'success' : ($log->status === 'failed' ? 'destructive' : 'warning')">
                                            {{ ucfirst($log->status) }}
                                        </x-admin.badge>
                                    </td>
                                    <td class="text-xs text-slate-600">{{ $log->created_at ? $log->created_at->format('M d, Y h:i A') : '—' }}</td>
                                    <td class="text-xs text-center font-mono">{{ $log->attempts }}</td>
                                    <td class="text-xs text-slate-500 truncate max-w-xs">{{ $log->error_message ?: 'Delivered without errors' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-200">
                    <x-admin.pagination :paginator="$emailLogs" />
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 8: INTERNAL NOTES & ADMIN ACTIONS --}}
    <div id="tab-content-notes" class="tab-panel space-y-6 {{ $activeTab === 'notes' ? '' : 'hidden' }}">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Internal Staff Notes --}}
            <div class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Internal Administrator Notes</h3>
                    <span class="text-xs text-slate-400">Visible strictly to authorized staff</span>
                </div>

                <form action="{{ route('admin.customers.notes.store', $customer->id) }}" method="POST" class="space-y-2">
                    @csrf
                    <textarea name="note" rows="3" required placeholder="Write a private note regarding customer preferences, payment verifications, or history..." class="w-full rounded-md border border-slate-300 p-2.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none"></textarea>
                    <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-slate-900">
                        Add Internal Note
                    </button>
                </form>

                <div class="divide-y divide-slate-100 mt-4 max-h-96 overflow-y-auto">
                    @forelse($notes as $note)
                        <div class="py-3 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $note->admin?->name ?? 'Staff Admin' }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] text-slate-400">{{ $note->created_at ? $note->created_at->format('M d, Y h:i A') : '—' }}</span>
                                    <form action="{{ route('admin.customers.notes.destroy', ['id' => $customer->id, 'noteId' => $note->id]) }}" method="POST" onsubmit="return confirm('Delete this internal note?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <p class="text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100 whitespace-pre-line">{{ $note->note }}</p>
                        </div>
                    @empty
                        <p class="py-6 text-center text-xs text-slate-400 italic">No staff notes added yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Administrative Actions Audit Log --}}
            <div class="admin-surface rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Administrative Action Trail</h3>
                    <span class="text-xs text-slate-400">Actions taken on this customer</span>
                </div>

                @if($adminActions->isEmpty())
                    <p class="py-8 text-center text-xs text-slate-400 italic">No admin actions recorded yet.</p>
                @else
                    <div class="divide-y divide-slate-100 text-xs">
                        @foreach($adminActions as $act)
                            <div class="py-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800">{{ $act->human_action }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $act->occurred_at ? $act->occurred_at->format('M d, Y h:i A') : '—' }}</span>
                                </div>
                                <p class="text-slate-600 mt-0.5">{{ $act->description }}</p>
                                <span class="text-[10px] text-slate-400 font-mono">By {{ $act->actor_name ?: 'Administrator' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- MODAL: Edit Customer Profile --}}
<div id="edit-customer-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 hidden">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <h3 class="text-base font-bold text-slate-900">Edit Customer Information</h3>
            <button type="button" onclick="document.getElementById('edit-customer-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST" class="mt-4 space-y-3">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-700">Full Name</label>
                <input type="text" name="name" value="{{ old('name', $customer->name) }}" required class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" required class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Shipping Zone</label>
                    <input type="text" name="shipping_area" value="{{ old('shipping_area', $customer->shipping_area) }}" placeholder="e.g. inside_dhaka" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Account Status</label>
                    <select name="status" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                        <option value="active" {{ $customer->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $customer->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700">Shipping Address</label>
                <textarea name="shipping_address" rows="2" class="mt-1 block w-full rounded-md border border-slate-300 p-2 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-3">
                <button type="button" onclick="document.getElementById('edit-customer-modal').classList.add('hidden')" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="rounded-md bg-brand-green-700 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-green-800">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: Add Customer Address --}}
<div id="add-address-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 hidden">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <h3 class="text-base font-bold text-slate-900">Add Address to Customer Profile</h3>
            <button type="button" onclick="document.getElementById('add-address-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('admin.customers.addresses.store', $customer->id) }}" method="POST" class="mt-4 space-y-3">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Recipient Name</label>
                    <input type="text" name="recipient_name" value="{{ old('recipient_name', $customer->name) }}" required class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" required class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Division</label>
                    <input type="text" name="division" placeholder="e.g. Dhaka" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">District</label>
                    <input type="text" name="district" placeholder="e.g. Dhaka" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Area / City</label>
                    <input type="text" name="area" placeholder="e.g. Dhanmondi" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Shipping Zone</label>
                    <select name="shipping_area" required class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                        <option value="inside_dhaka">Inside Dhaka</option>
                        <option value="outside_dhaka">Outside Dhaka</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700">Street Address</label>
                <textarea name="address" rows="2" required placeholder="House, Road, Block..." class="mt-1 block w-full rounded-md border border-slate-300 p-2 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none"></textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="add_is_default" name="is_default" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500">
                <label for="add_is_default" class="text-xs text-slate-700 font-medium">Set as default shipping address</label>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-3">
                <button type="button" onclick="document.getElementById('add-address-modal').classList.add('hidden')" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="rounded-md bg-brand-green-700 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-green-800">
                    Add Address
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchCustomerTab(tabKey) {
        document.querySelectorAll('.tab-panel').forEach(function(el) {
            el.classList.add('hidden');
        });
        document.querySelectorAll('.tab-trigger').forEach(function(btn) {
            btn.classList.remove('border-brand-green-700', 'text-brand-green-800');
            btn.classList.add('border-transparent', 'text-slate-500');
        });

        var targetPanel = document.getElementById('tab-content-' + tabKey);
        var targetBtn = document.getElementById('tab-btn-' + tabKey);
        if (targetPanel) {
            targetPanel.classList.remove('hidden');
        }
        if (targetBtn) {
            targetBtn.classList.remove('border-transparent', 'text-slate-500');
            targetBtn.classList.add('border-brand-green-700', 'text-brand-green-800');
        }

        // Update URL hash without reload
        if (history.pushState) {
            var url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.pushState({}, '', url);
        }
    }
</script>
@endsection
