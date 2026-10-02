@extends('layouts.admin', ['headerTitle' => 'Customer Management'])

@section('content')
<div class="admin-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-admin.page-header title="Customer Directory & 360° Profiles" :subtitle="$customers->total().' customers matching filter ('.$totalCustomersCount.' total registered)'" />
        
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.customers.export-list', array_merge(request()->query(), ['format' => 'csv'])) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-green-500">
                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            <a href="{{ route('admin.customers.export-list', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-green-500">
                <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="admin-surface mt-4 p-4">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="space-y-3">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, phone, email, or ID..." class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none focus:ring-1 focus:ring-brand-green-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600">Account Status</label>
                    <select name="status" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Accounts</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Accounts</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600">Order Activity</label>
                    <select name="order_filter" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                        <option value="">All Customers</option>
                        <option value="with_orders" {{ request('order_filter') === 'with_orders' ? 'selected' : '' }}>Has Placed Orders</option>
                        <option value="no_orders" {{ request('order_filter') === 'no_orders' ? 'selected' : '' }}>No Orders Yet</option>
                        <option value="recent_orders" {{ request('order_filter') === 'recent_orders' ? 'selected' : '' }}>Recent Orders (Last 30 Days)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600">Sort By</label>
                    <select name="sort" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                        <option value="created_at_desc" {{ request('sort', 'created_at_desc') === 'created_at_desc' ? 'selected' : '' }}>Newest Registered</option>
                        <option value="created_at_asc" {{ request('sort') === 'created_at_asc' ? 'selected' : '' }}>Oldest Registered</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Customer Name (A-Z)</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Customer Name (Z-A)</option>
                        <option value="orders_desc" {{ request('sort') === 'orders_desc' ? 'selected' : '' }}>Order Count (Highest)</option>
                        <option value="orders_asc" {{ request('sort') === 'orders_asc' ? 'selected' : '' }}>Order Count (Lowest)</option>
                        <option value="spent_desc" {{ request('sort') === 'spent_desc' ? 'selected' : '' }}>Total Spending (Highest)</option>
                        <option value="spent_asc" {{ request('sort') === 'spent_asc' ? 'selected' : '' }}>Total Spending (Lowest)</option>
                        <option value="last_order_desc" {{ request('sort') === 'last_order_desc' ? 'selected' : '' }}>Recently Ordered</option>
                    </select>
                </div>
            </div>

            {{-- Secondary filter row: Dates and ranges --}}
            <div class="grid grid-cols-2 gap-3 pt-1 sm:grid-cols-4 lg:grid-cols-6">
                <div>
                    <label class="block text-xs font-medium text-slate-500">Joined From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500">Joined To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500">Min Orders</label>
                    <input type="number" min="0" name="min_orders" value="{{ request('min_orders') }}" placeholder="0" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500">Max Orders</label>
                    <input type="number" min="0" name="max_orders" value="{{ request('max_orders') }}" placeholder="Any" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500">Min Spend (৳)</label>
                    <input type="number" min="0" step="100" name="min_spend" value="{{ request('min_spend') }}" placeholder="0" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-1 text-xs shadow-sm focus:border-brand-green-500 focus:outline-none">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 rounded-md bg-brand-green-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-green-800 focus:outline-none">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'status', 'order_filter', 'sort', 'date_from', 'date_to', 'min_orders', 'max_orders', 'min_spend']))
                        <a href="{{ route('admin.customers.index') }}" class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Main Customer List --}}
    <div class="admin-table-wrap mt-4">
        @if($customers->isEmpty())
            <x-admin.empty-state title="No customers found" description="Try adjusting your search query, status, or date range filters." />
        @else
            {{-- Mobile Cards --}}
            <div class="divide-y divide-slate-200 md:hidden">
                @foreach($customers as $c)
                    @php
                        $initial = strtoupper(substr($c->name ?? '?', 0, 1));
                        $spent = (float) ($c->total_spent ?? 0);
                        $validCnt = (int) ($c->valid_orders_count ?? 0);
                        $aov = $validCnt > 0 ? ($spent / $validCnt) : 0;
                    @endphp
                    <div class="admin-mobile-card space-y-3 p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-green-100 text-base font-bold text-brand-green-800">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <a href="{{ route('admin.customers.show', $c->id) }}" class="text-sm font-bold text-slate-900 hover:text-brand-green-700 hover:underline">
                                            {{ $c->name }}
                                        </a>
                                        <span class="ml-1 text-[11px] font-mono text-slate-400">#{{ $c->id }}</span>
                                    </div>
                                    <x-admin.badge :variant="$c->status === 'active' ? 'success' : 'destructive'">
                                        {{ $c->status }}
                                    </x-admin.badge>
                                </div>
                                <p class="mt-0.5 text-xs font-medium text-slate-700">{{ $c->phone }}</p>
                                @if($c->email)
                                    <p class="truncate text-xs text-slate-500">{{ $c->email }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 rounded-lg bg-slate-50 p-2 text-center text-xs">
                            <div>
                                <span class="block text-[10px] uppercase font-semibold text-slate-400">Orders</span>
                                <span class="font-bold text-slate-900">{{ $c->orders_count }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-semibold text-slate-400">Total Spent</span>
                                <span class="font-bold text-brand-green-700">৳{{ number_format($spent, 0) }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-semibold text-slate-400">AOV</span>
                                <span class="font-medium text-slate-700">৳{{ number_format($aov, 0) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span>Joined {{ $c->created_at ? $c->created_at->format('M d, Y') : '—' }}</span>
                            <span>Last order: {{ $c->last_order_at ? \Carbon\Carbon::parse($c->last_order_at)->format('M d, Y') : 'None' }}</span>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <a href="{{ route('admin.customers.show', $c->id) }}" class="flex-1 rounded-md bg-brand-green-50 px-3 py-1.5 text-center text-xs font-semibold text-brand-green-800 hover:bg-brand-green-100">
                                View 360° Profile
                            </a>
                            <form action="{{ route('admin.customers.toggle', $c->id) }}" method="POST">
                                @csrf
                                <x-admin.button type="submit" variant="outline" size="sm">
                                    {{ $c->status === 'active' ? 'Deactivate' : 'Activate' }}
                                </x-admin.button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop Responsive Table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="min-width: 200px;">Customer</th>
                            <th style="min-width: 140px;">Contact</th>
                            <th class="text-center" style="min-width: 80px;">Orders</th>
                            <th class="text-right" style="min-width: 110px;">Total Spent</th>
                            <th class="text-right" style="min-width: 100px;">AOV</th>
                            <th style="min-width: 110px;">Last Order</th>
                            <th style="min-width: 105px;">Joined</th>
                            <th style="min-width: 115px;">Last Login</th>
                            <th style="min-width: 90px;">Status</th>
                            <th class="text-right" style="min-width: 160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $c)
                            @php
                                $initial = strtoupper(substr($c->name ?? '?', 0, 1));
                                $spent = (float) ($c->total_spent ?? 0);
                                $validCnt = (int) ($c->valid_orders_count ?? 0);
                                $aov = $validCnt > 0 ? ($spent / $validCnt) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-green-100 text-xs font-bold text-brand-green-800">
                                            {{ $initial }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.customers.show', $c->id) }}" class="font-bold text-slate-900 hover:text-brand-green-700 hover:underline">
                                                {{ $c->name }}
                                            </a>
                                            <div class="text-[11px] text-slate-400">ID: #{{ $c->id }} &bull; Registered</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-medium text-slate-800">{{ $c->phone }}</div>
                                    <div class="truncate text-xs text-slate-500">{{ $c->email ?: '—' }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-bold {{ $c->orders_count > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $c->orders_count }}
                                    </span>
                                </td>
                                <td class="text-right font-bold text-slate-900">
                                    ৳{{ number_format($spent, 2) }}
                                </td>
                                <td class="text-right text-xs font-semibold text-slate-600">
                                    ৳{{ number_format($aov, 2) }}
                                </td>
                                <td class="text-xs text-slate-600">
                                    @if($c->last_order_at)
                                        <span title="{{ $c->last_order_at }}">{{ \Carbon\Carbon::parse($c->last_order_at)->format('M d, Y') }}</span>
                                    @else
                                        <span class="text-slate-400">Never</span>
                                    @endif
                                </td>
                                <td class="text-xs text-slate-500">
                                    {{ $c->created_at ? $c->created_at->format('M d, Y') : '—' }}
                                </td>
                                <td class="text-xs text-slate-500">
                                    @if($c->last_login_at)
                                        <span title="{{ $c->last_login_at }}">{{ $c->last_login_at->diffForHumans() }}</span>
                                    @else
                                        <span class="text-slate-400">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <x-admin.badge :variant="$c->status === 'active' ? 'success' : 'destructive'">
                                        {{ ucfirst($c->status) }}
                                    </x-admin.badge>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.customers.show', $c->id) }}" class="inline-flex items-center gap-1 rounded-md border border-brand-green-600/30 bg-brand-green-50 px-2.5 py-1 text-xs font-semibold text-brand-green-800 transition hover:bg-brand-green-100 hover:text-brand-green-900">
                                            <span>360° View</span>
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                        <form action="{{ route('admin.customers.toggle', $c->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-md border border-slate-200 px-2 py-1 text-xs font-medium {{ $c->status === 'active' ? 'text-red-600 hover:bg-red-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                                {{ $c->status === 'active' ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-200">
                <x-admin.pagination :paginator="$customers" />
            </div>
        @endif
    </div>
</div>
@endsection
