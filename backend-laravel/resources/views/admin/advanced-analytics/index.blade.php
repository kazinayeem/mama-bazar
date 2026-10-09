@extends('layouts.admin', [
    'headerTitle' => 'Advanced Analytics & Inventory Intelligence',
    'title' => 'Advanced Analytics'
])

@section('content')
<div class="admin-page" x-data="advancedAnalyticsDashboard()">

    {{-- 1. Header with Title & Export Actions --}}
    <x-admin.page-header
        title="Advanced Analytics & Inventory Intelligence"
        subtitle="Real-time stock valuation, sales performance, profit margins, and inventory health">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                {{-- Quick Export CSV --}}
                <a href="{{ route('admin.advanced-analytics.export.csv', request()->query()) }}"
                   class="inline-flex h-9 items-center gap-2 rounded-[6px] border border-[var(--admin-border)] bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[var(--admin-ring)]">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Export CSV</span>
                </a>

                {{-- Prominent Export PDF Report Button --}}
                <button type="button"
                        @click="pdfModalOpen = true"
                        class="inline-flex h-9 items-center gap-2 rounded-[6px] bg-[#0f4d2c] px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-[#0a3820] focus:outline-none focus:ring-2 focus:ring-[var(--admin-ring)]">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span>Export PDF Report</span>
                </button>
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- 2. Global Filter Toolbar --}}
    <div class="admin-surface p-4">
        <form method="GET" action="{{ route('admin.advanced-analytics.index') }}" id="analyticsFilterForm">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                {{-- Date Preset --}}
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Date Range</label>
                    <select name="preset"
                            class="admin-control mt-1 w-full text-xs font-medium"
                            x-model="preset"
                            @change="if(preset !== 'custom' && preset !== 'month_year') $el.form.submit()">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_quarter">This Quarter</option>
                        <option value="this_year">This Year</option>
                        <option value="month_year">Specific Month/Year</option>
                        <option value="custom">Custom Date Range</option>
                    </select>
                </div>

                {{-- Category Filter --}}
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Category</label>
                    <select name="category_id" class="admin-control mt-1 w-full text-xs" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected($filters['category_id'] == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Brand Filter --}}
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Brand</label>
                    <select name="brand_id" class="admin-control mt-1 w-full text-xs" onchange="this.form.submit()">
                        <option value="">All Brands</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected($filters['brand_id'] == $brand->id)>
                                {{ $brand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Stock Status Filter --}}
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Stock Status</label>
                    <select name="stock_status" class="admin-control mt-1 w-full text-xs" onchange="this.form.submit()">
                        <option value="all" @selected($filters['stock_status'] === 'all')>All Inventory</option>
                        <option value="in_stock" @selected($filters['stock_status'] === 'in_stock')>In Stock (>10 units)</option>
                        <option value="low_stock" @selected($filters['stock_status'] === 'low_stock')>Low Stock (≤10 units)</option>
                        <option value="out_of_stock" @selected($filters['stock_status'] === 'out_of_stock')>Out of Stock (0 units)</option>
                        <option value="overstock" @selected($filters['stock_status'] === 'overstock')>Overstocked (≥100 units)</option>
                    </select>
                </div>

                {{-- Product Status --}}
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Catalog Status</label>
                    <select name="product_status" class="admin-control mt-1 w-full text-xs" onchange="this.form.submit()">
                        <option value="all" @selected($filters['product_status'] === 'all')>All Statuses</option>
                        <option value="published" @selected($filters['product_status'] === 'published')>Published</option>
                        <option value="draft" @selected($filters['product_status'] === 'draft')>Draft</option>
                        <option value="archived" @selected($filters['product_status'] === 'archived')>Archived</option>
                    </select>
                </div>

                {{-- Sort By --}}
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Sort By</label>
                    <select name="sort_by" class="admin-control mt-1 w-full text-xs" onchange="this.form.submit()">
                        <option value="revenue_desc" @selected($filters['sort_by'] === 'revenue_desc')>Highest Revenue</option>
                        <option value="units_desc" @selected($filters['sort_by'] === 'units_desc')>Most Units Sold</option>
                        <option value="stock_desc" @selected($filters['sort_by'] === 'stock_desc')>Stock: High to Low</option>
                        <option value="stock_asc" @selected($filters['sort_by'] === 'stock_asc')>Stock: Low to High</option>
                        <option value="retail_val_desc" @selected($filters['sort_by'] === 'retail_val_desc')>Retail Value</option>
                        <option value="price_desc" @selected($filters['sort_by'] === 'price_desc')>Price: High to Low</option>
                        <option value="price_asc" @selected($filters['sort_by'] === 'price_asc')>Price: Low to High</option>
                        <option value="title_asc" @selected($filters['sort_by'] === 'title_asc')>Product Name</option>
                    </select>
                </div>
            </div>

            {{-- Custom Range / Month-Year Collapsible Section --}}
            <div x-show="preset === 'custom' || preset === 'month_year'" x-cloak class="mt-3 grid grid-cols-1 gap-3 border-t border-slate-100 pt-3 sm:grid-cols-2 lg:grid-cols-4">
                <template x-if="preset === 'custom'">
                    <div class="col-span-full grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Start Date</label>
                            <input type="date" name="start_date" value="{{ $filters['start_date']->format('Y-m-d') }}" class="admin-control mt-1 w-full text-xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">End Date</label>
                            <input type="date" name="end_date" value="{{ $filters['end_date']->format('Y-m-d') }}" class="admin-control mt-1 w-full text-xs">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex h-[40px] w-full items-center justify-center rounded-[6px] bg-[#0f4d2c] px-4 text-xs font-semibold text-white shadow-sm hover:bg-[#0a3820]">
                                Apply Custom Dates
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="preset === 'month_year'">
                    <div class="col-span-full grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Month</label>
                            <select name="month" class="admin-control mt-1 w-full text-xs">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @selected($filters['month'] == $m)>
                                        {{ \Carbon\Carbon::create(2026, $m, 1)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Year</label>
                            <select name="year" class="admin-control mt-1 w-full text-xs">
                                @for($y = 2024; $y <= 2028; $y++)
                                    <option value="{{ $y }}" @selected($filters['year'] == $y)>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="inline-flex h-[40px] w-full items-center justify-center rounded-[6px] bg-[#0f4d2c] px-4 text-xs font-semibold text-white shadow-sm hover:bg-[#0a3820]">
                                Load Month Analytics
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Search Bar & Filter Actions --}}
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                <div class="relative min-w-[260px] flex-1 max-w-md">
                    <svg class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           name="search"
                           value="{{ $filters['search'] }}"
                           placeholder="Search product name, SKU, or barcode..."
                           class="admin-control w-full pl-9 text-xs">
                </div>

                <div class="flex items-center gap-2">
                    <select name="per_page" class="admin-control text-xs" onchange="this.form.submit()">
                        <option value="15" @selected($filters['per_page'] == 15)>15 rows</option>
                        <option value="25" @selected($filters['per_page'] == 25)>25 rows</option>
                        <option value="50" @selected($filters['per_page'] == 50)>50 rows</option>
                        <option value="100" @selected($filters['per_page'] == 100)>100 rows</option>
                    </select>

                    <button type="submit" class="inline-flex h-[40px] items-center gap-1.5 rounded-[6px] bg-slate-900 px-4 text-xs font-semibold text-white hover:bg-slate-800">
                        <span>Apply</span>
                    </button>

                    <a href="{{ route('admin.advanced-analytics.index') }}" class="inline-flex h-[40px] items-center rounded-[6px] border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        {{-- Active Filters Badges Bar --}}
        @php
            $hasActiveFilters = $filters['preset'] !== '30d' || !empty($filters['category_id']) || !empty($filters['brand_id']) || !empty($filters['search']) || $filters['stock_status'] !== 'all' || $filters['product_status'] !== 'all';
        @endphp
        @if($hasActiveFilters)
            <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-slate-100 pt-2.5 text-xs">
                <span class="font-semibold text-slate-400">Active Filters:</span>
                @if($filters['preset'] !== '30d')
                    <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                        Period: {{ $filters['start_date']->format('d M') }} — {{ $filters['end_date']->format('d M') }}
                    </span>
                @endif
                @if(!empty($filters['category_id']))
                    @php $activeCategory = $categories->firstWhere('id', $filters['category_id']); @endphp
                    <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-800">
                        Category: {{ $activeCategory?->name ?? $filters['category_id'] }}
                    </span>
                @endif
                @if(!empty($filters['brand_id']))
                    @php $activeBrand = $brands->firstWhere('id', $filters['brand_id']); @endphp
                    <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-800">
                        Brand: {{ $activeBrand?->name ?? $filters['brand_id'] }}
                    </span>
                @endif
                @if($filters['stock_status'] !== 'all')
                    <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800">
                        Stock: {{ ucfirst(str_replace('_', ' ', $filters['stock_status'])) }}
                    </span>
                @endif
                @if($filters['product_status'] !== 'all')
                    <span class="inline-flex items-center gap-1 rounded bg-purple-50 px-2 py-0.5 text-[11px] font-medium text-purple-800">
                        Status: {{ ucfirst($filters['product_status']) }}
                    </span>
                @endif
                @if(!empty($filters['search']))
                    <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                        Query: "{{ $filters['search'] }}"
                    </span>
                @endif
                <a href="{{ route('admin.advanced-analytics.index') }}" class="ml-1 text-[11px] text-red-600 underline hover:text-red-800">Clear all</a>
            </div>
        @endif
    </div>

    {{-- 3. Executive KPI Cards --}}
    <div>
        {{-- Section Subheading --}}
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                1. Inventory Intelligence & Valuation (Snapshot: {{ $inventoryKpis['snapshot_date'] }})
            </h2>
            <span class="text-[11px] text-slate-400">Values calculated from active product catalog</span>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Total Active Products & Variants --}}
            <div class="admin-surface relative overflow-hidden p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Active Products</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($inventoryKpis['active_products']) }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-2 text-emerald-700">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
                <div class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                    <span>{{ number_format($inventoryKpis['total_products']) }} total in catalog</span>
                    <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                    <span class="font-medium text-slate-600">{{ number_format($inventoryKpis['active_variants']) }} active variants</span>
                </div>
            </div>

            {{-- Total Units in Stock & Health --}}
            <div class="admin-surface relative overflow-hidden p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Units in Stock</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($inventoryKpis['total_units_in_stock']) }}</p>
                    </div>
                    <div class="rounded-lg bg-blue-50 p-2 text-blue-700">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                </div>
                <div class="mt-2 flex items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1 font-semibold text-amber-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> {{ $inventoryKpis['low_stock_count'] }} Low
                    </span>
                    <span class="inline-flex items-center gap-1 font-semibold text-rose-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> {{ $inventoryKpis['out_of_stock_count'] }} Out
                    </span>
                    <span class="inline-flex items-center gap-1 text-slate-500">
                        {{ $inventoryKpis['overstocked_count'] }} Overstock
                    </span>
                </div>
            </div>

            {{-- Potential Retail Valuation --}}
            <div class="admin-surface relative overflow-hidden p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Potential Retail Valuation</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">৳{{ number_format($inventoryKpis['retail_valuation'], 0) }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-2 text-[#0f4d2c]">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-2 text-xs text-slate-500">
                    <span>Retail value across all present inventory units</span>
                </div>
            </div>

            {{-- Inventory Cost Valuation & Margin --}}
            @if($financialAccess->canViewCostValuation)
            <div class="admin-surface relative overflow-hidden p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Valuation at Cost</p>
                        @if($inventoryKpis['cost_valuation'] !== null)
                            <p class="mt-1 text-2xl font-bold text-slate-900">৳{{ number_format($inventoryKpis['cost_valuation'], 0) }}</p>
                        @else
                            <p class="mt-1 text-lg font-bold text-slate-400">Cost data unavailable</p>
                        @endif
                    </div>
                    <div class="rounded-lg bg-amber-50 p-2 text-amber-700">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div class="mt-2 flex items-center justify-between text-xs">
                    @if($inventoryKpis['cost_data_status'] === 'unavailable')
                        <span class="text-slate-400">Requires cost_price on products</span>
                    @elseif($inventoryKpis['cost_data_status'] === 'partial')
                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">Partial: {{ $inventoryKpis['cost_coverage_pct'] }}% products</span>
                    @else
                        <span class="text-emerald-700 font-semibold">100% cost verified</span>
                    @endif

                    @if($inventoryKpis['unrealized_margin'] !== null)
                        <span class="font-bold text-emerald-700">Margin: {{ $inventoryKpis['unrealized_margin'] }}%</span>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Sales & Profit Performance KPIs --}}
        <div class="mt-4 mb-2 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">
                2. Sales, Revenue & Profit Performance ({{ $filters['start_date']->format('d M') }} — {{ $filters['end_date']->format('d M, Y') }})
            </h2>
            <span class="text-[11px] text-slate-400">Comparing with previous equivalent period</span>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Gross Sales --}}
            <div class="admin-surface p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Gross Sales</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">৳{{ number_format($salesKpis['gross_sales'], 0) }}</p>
                    </div>
                    @if($salesKpis['gross_sales_growth'] !== null)
                        <span class="inline-flex items-center gap-0.5 rounded px-2 py-0.5 text-xs font-bold {{ $salesKpis['gross_sales_growth'] >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                            {{ $salesKpis['gross_sales_growth'] > 0 ? '+' : '' }}{{ $salesKpis['gross_sales_growth'] }}%
                        </span>
                    @endif
                </div>
                <div class="mt-2 text-xs text-slate-500">
                    <span>{{ number_format($salesKpis['all_orders_count']) }} total orders placed in period</span>
                </div>
            </div>

            {{-- Net Sales --}}
            <div class="admin-surface p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Net Sales</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-700">৳{{ number_format($salesKpis['net_sales'], 0) }}</p>
                    </div>
                    @if($salesKpis['sales_growth'] !== null)
                        <span class="inline-flex items-center gap-0.5 rounded px-2 py-0.5 text-xs font-bold {{ $salesKpis['sales_growth'] >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                            {{ $salesKpis['sales_growth'] > 0 ? '+' : '' }}{{ $salesKpis['sales_growth'] }}%
                        </span>
                    @endif
                </div>
                <div class="mt-2 text-xs text-slate-500">
                    <span>Excludes cancelled (৳{{ number_format($salesKpis['cancelled_sales'], 0) }}) & refunds</span>
                </div>
            </div>

            {{-- Orders & Units Sold --}}
            <div class="admin-surface p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Orders & Units Sold</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">
                            {{ number_format($salesKpis['orders_count']) }} <span class="text-sm font-normal text-slate-400">orders</span> / {{ number_format($salesKpis['units_sold']) }} <span class="text-sm font-normal text-slate-400">units</span>
                        </p>
                    </div>
                    @if($salesKpis['orders_growth'] !== null)
                        <span class="inline-flex items-center gap-0.5 rounded px-2 py-0.5 text-xs font-bold {{ $salesKpis['orders_growth'] >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                            {{ $salesKpis['orders_growth'] > 0 ? '+' : '' }}{{ $salesKpis['orders_growth'] }}%
                        </span>
                    @endif
                </div>
                <div class="mt-2 text-xs text-slate-500">
                    <span>AOV: <strong class="text-slate-800">৳{{ number_format($salesKpis['avg_order_value'], 0) }}</strong></span>
                </div>
            </div>

            {{-- Gross Profit & Gross Margin --}}
            @if($financialAccess->canViewProfitMargin)
            <div class="admin-surface p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Gross Profit & Margin</p>
                        @if($salesKpis['gross_profit'] !== null)
                            <p class="mt-1 text-2xl font-bold text-emerald-700">
                                ৳{{ number_format($salesKpis['gross_profit'], 0) }}
                                <span class="text-sm font-semibold text-slate-500">({{ $salesKpis['gross_margin_pct'] }}%)</span>
                            </p>
                        @else
                            <p class="mt-1 text-lg font-bold text-slate-400">Cost data unavailable</p>
                        @endif
                    </div>
                    <div class="rounded-lg bg-slate-50 p-2 text-slate-500">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                </div>
                <div class="mt-2 text-xs text-slate-500">
                    @if($salesKpis['profit_status'] === 'unavailable')
                        <span>Set product cost price to calculate profit</span>
                    @elseif($salesKpis['profit_status'] === 'partial')
                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">
                            Based on {{ $salesKpis['items_with_cost'] }} of {{ $salesKpis['total_items_sold_count'] }} sold items
                        </span>
                    @else
                        <span class="text-emerald-700 font-semibold">100% of sold items verified</span>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- 4. Interactive Analytics Charts --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- Chart A: Sales & Revenue Trends (2 Cols) --}}
        <div class="admin-surface p-5 xl:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-bold text-slate-900">Sales & Revenue Trends</h3>
                    <p class="text-xs text-slate-500">Daily gross and net revenue performance across selected date range</p>
                </div>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5 text-slate-600">
                        <span class="h-2.5 w-2.5 rounded-sm bg-[#176B3A]"></span> Net Sales
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-slate-600">
                        <span class="h-2.5 w-2.5 rounded-sm bg-[#94A3B8]"></span> Gross Sales
                    </span>
                </div>
            </div>

            {{-- Interactive SVG / Bar Trend --}}
            <div class="mt-4">
                @if(count($chartsData['sales_trend']) > 0)
                    @php
                        $maxRev = max(1, collect($chartsData['sales_trend'])->max('gross_sales'));
                    @endphp
                    <div class="flex h-56 items-end gap-1 overflow-x-auto pt-6 pb-2" style="scrollbar-width: thin;">
                        @foreach($chartsData['sales_trend'] as $point)
                            @php
                                $grossHeight = max(2, min(100, ($point['gross_sales'] / $maxRev) * 100));
                                $netHeight = max(2, min(100, ($point['net_sales'] / $maxRev) * 100));
                            @endphp
                            <div class="group relative flex flex-1 min-w-[28px] flex-col items-center justify-end h-full">
                                {{-- Hover Tooltip --}}
                                <div class="pointer-events-none absolute -top-14 z-20 hidden w-36 rounded-md bg-slate-900 p-2 text-center text-[10px] text-white shadow-lg group-hover:block">
                                    <div class="font-bold">{{ $point['label'] }}</div>
                                    <div class="text-emerald-400">Net: ৳{{ number_format($point['net_sales'], 0) }}</div>
                                    <div class="text-slate-300">Gross: ৳{{ number_format($point['gross_sales'], 0) }}</div>
                                    <div class="text-slate-400">{{ $point['orders'] }} orders ({{ $point['units'] }} pcs)</div>
                                </div>

                                {{-- Dual Bars --}}
                                <div class="flex items-end gap-0.5 w-full justify-center h-full">
                                    <div class="w-2.5 rounded-t bg-slate-300 transition-all hover:bg-slate-400"
                                         style="height: {{ $grossHeight }}%"></div>
                                    <div class="w-2.5 rounded-t bg-[#176B3A] transition-all hover:bg-[#0f4d2c]"
                                         style="height: {{ $netHeight }}%"></div>
                                </div>

                                <span class="mt-2 block truncate text-[9px] text-slate-400 group-hover:text-slate-700">
                                    {{ $point['label'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex h-48 items-center justify-center text-xs text-slate-400">
                        No sales activity recorded in this period.
                    </div>
                @endif
            </div>
        </div>

        {{-- Chart B: Stock Distribution by Status (1 Col) --}}
        <div class="admin-surface p-5">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-900">Stock Status Distribution</h3>
                <p class="text-xs text-slate-500">Breakdown of products by inventory availability</p>
            </div>

            @php $dist = $chartsData['stock_distribution']; @endphp
            <div class="mt-4 space-y-3.5">
                {{-- In Stock --}}
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-slate-700">In Stock (>10 units)</span>
                        <span class="font-bold text-emerald-700">{{ $dist['in_stock'] }} ({{ $dist['in_stock_pct'] }}%)</span>
                    </div>
                    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-emerald-600" style="width: {{ $dist['in_stock_pct'] }}%"></div>
                    </div>
                </div>

                {{-- Low Stock --}}
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-amber-700">Low Stock (≤10 units)</span>
                        <span class="font-bold text-amber-800">{{ $dist['low_stock'] }} ({{ $dist['low_stock_pct'] }}%)</span>
                    </div>
                    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-amber-500" style="width: {{ $dist['low_stock_pct'] }}%"></div>
                    </div>
                </div>

                {{-- Out of Stock --}}
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-rose-700">Out of Stock (0 units)</span>
                        <span class="font-bold text-rose-800">{{ $dist['out_of_stock'] }} ({{ $dist['out_of_stock_pct'] }}%)</span>
                    </div>
                    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-rose-600" style="width: {{ $dist['out_of_stock_pct'] }}%"></div>
                    </div>
                </div>

                {{-- Overstocked --}}
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-medium text-indigo-700">Overstocked (≥100 units)</span>
                        <span class="font-bold text-indigo-800">{{ $dist['overstock'] }} ({{ $dist['overstock_pct'] }}%)</span>
                    </div>
                    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-indigo-600" style="width: {{ $dist['overstock_pct'] }}%"></div>
                    </div>
                </div>

                <div class="rounded-lg bg-slate-50 p-3 mt-4 text-[11px] text-slate-600">
                    <p class="font-semibold text-slate-900">Slow-Moving Inventory Exposure:</p>
                    <p class="mt-0.5">৳{{ number_format($inventoryKpis['slow_moving_valuation'], 0) }} tied in {{ $inventoryKpis['slow_moving_count'] }} products with zero sales in period.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row 2: Category Valuation & Pricing Distribution --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        {{-- Category Stock & Valuation --}}
        <div class="admin-surface p-5">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-900">Stock & Retail Valuation by Category</h3>
                <p class="text-xs text-slate-500">Total units and potential retail valuation across top categories</p>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($chartsData['category_inventory'] as $cat)
                    @php
                        $maxCatVal = max(1, collect($chartsData['category_inventory'])->max('retail_value'));
                        $w = min(100, ($cat['retail_value'] / $maxCatVal) * 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-800">{{ $cat['category'] }}</span>
                            <span class="text-slate-500">
                                <strong>{{ number_format($cat['stock']) }}</strong> units | <strong class="text-slate-900">৳{{ number_format($cat['retail_value'], 0) }}</strong>
                            </span>
                        </div>
                        <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-[#176B3A]" style="width: {{ $w }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-6 text-center">No category inventory recorded.</p>
                @endforelse
            </div>
        </div>

        {{-- Pricing & Discount Analysis --}}
        <div class="admin-surface p-5">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-900">Pricing & Discount Analysis</h3>
                <p class="text-xs text-slate-500">Distribution of products by selling price and discount brackets</p>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                {{-- Price Brackets --}}
                <div>
                    <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Price Tiers</h4>
                    <div class="mt-2 space-y-2">
                        @foreach($chartsData['pricing_analysis']['price_tiers'] as $tier => $cnt)
                            <div class="flex items-center justify-between rounded bg-slate-50 px-2.5 py-1.5 text-xs">
                                <span class="font-medium text-slate-700">{{ $tier }}</span>
                                <span class="font-bold text-slate-900">{{ $cnt }} items</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Discount Brackets --}}
                <div>
                    <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Discount Levels</h4>
                    <div class="mt-2 space-y-2">
                        @foreach($chartsData['pricing_analysis']['discount_tiers'] as $disc => $cnt)
                            <div class="flex items-center justify-between rounded bg-slate-50 px-2.5 py-1.5 text-xs">
                                <span class="font-medium text-slate-700">{{ $disc }}</span>
                                <span class="font-bold text-slate-900">{{ $cnt }} items</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if(!empty($chartsData['pricing_analysis']['highest_discounts']))
                <div class="mt-4 border-t border-slate-100 pt-3">
                    <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Highest Discounted Products</h4>
                    <div class="mt-2 space-y-1.5">
                        @foreach($chartsData['pricing_analysis']['highest_discounts'] as $hd)
                            <div class="flex items-center justify-between text-xs">
                                <span class="truncate max-w-[220px] text-slate-700">{{ $hd['title'] }}</span>
                                <span class="rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">
                                    {{ $hd['discount_pct'] }}% OFF (৳{{ number_format($hd['sale_price'], 0) }})
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Product Highlights & Variant Insights --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3" x-data="{ perfTab: 'best_sellers' }">
        {{-- Product Performance Tabs --}}
        <div class="admin-surface p-5 lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-900">Product Performance Intelligence</h3>
                <div class="flex items-center gap-1 rounded-[6px] bg-slate-100 p-1 text-xs">
                    <button type="button" @click="perfTab = 'best_sellers'" :class="perfTab === 'best_sellers' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-600'" class="rounded px-2.5 py-1 transition">Top Sellers</button>
                    <button type="button" @click="perfTab = 'highest_revenue'" :class="perfTab === 'highest_revenue' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-600'" class="rounded px-2.5 py-1 transition">Highest Revenue</button>
                    <button type="button" @click="perfTab = 'slow_moving'" :class="perfTab === 'slow_moving' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-600'" class="rounded px-2.5 py-1 transition">Slow Moving</button>
                    <button type="button" @click="perfTab = 'low_demand'" :class="perfTab === 'low_demand' ? 'bg-white shadow text-slate-900 font-bold' : 'text-slate-600'" class="rounded px-2.5 py-1 transition">Low Stock Demand</button>
                </div>
            </div>

            <div class="mt-4">
                {{-- Best Sellers --}}
                <div x-show="perfTab === 'best_sellers'">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 uppercase text-[10px] border-b">
                                <th class="pb-2">Product</th>
                                <th class="pb-2 text-center">Stock</th>
                                <th class="pb-2 text-center">Units Sold</th>
                                <th class="pb-2 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($chartsData['product_performance']['best_sellers'] as $prod)
                                <tr>
                                    <td class="py-2.5 font-medium text-slate-800">{{ $prod->title }}</td>
                                    <td class="py-2.5 text-center text-slate-600">{{ $prod->stock }}</td>
                                    <td class="py-2.5 text-center font-bold text-emerald-700">{{ $prod->units_sold }}</td>
                                    <td class="py-2.5 text-right font-bold text-slate-900">৳{{ number_format($prod->revenue, 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-slate-400">No sales recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Highest Revenue --}}
                <div x-show="perfTab === 'highest_revenue'" x-cloak>
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 uppercase text-[10px] border-b">
                                <th class="pb-2">Product</th>
                                <th class="pb-2 text-center">Stock</th>
                                <th class="pb-2 text-center">Units</th>
                                <th class="pb-2 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($chartsData['product_performance']['highest_revenue'] as $prod)
                                <tr>
                                    <td class="py-2.5 font-medium text-slate-800">{{ $prod->title }}</td>
                                    <td class="py-2.5 text-center text-slate-600">{{ $prod->stock }}</td>
                                    <td class="py-2.5 text-center text-slate-600">{{ $prod->units_sold }}</td>
                                    <td class="py-2.5 text-right font-bold text-emerald-700">৳{{ number_format($prod->revenue, 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-slate-400">No revenue records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Slow Moving --}}
                <div x-show="perfTab === 'slow_moving'" x-cloak>
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 uppercase text-[10px] border-b">
                                <th class="pb-2">Product</th>
                                <th class="pb-2 text-center">Stock</th>
                                <th class="pb-2 text-right">Price</th>
                                <th class="pb-2 text-right">Capital Tied</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($chartsData['product_performance']['slow_moving'] as $prod)
                                <tr>
                                    <td class="py-2.5 font-medium text-slate-800">{{ $prod->title }}</td>
                                    <td class="py-2.5 text-center font-bold text-amber-600">{{ $prod->stock }}</td>
                                    <td class="py-2.5 text-right text-slate-600">৳{{ number_format($prod->sale_price ?: $prod->price, 0) }}</td>
                                    <td class="py-2.5 text-right font-bold text-slate-900">৳{{ number_format($prod->tied_capital, 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-slate-400">No slow moving inventory identified.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Low Stock Demand --}}
                <div x-show="perfTab === 'low_demand'" x-cloak>
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 uppercase text-[10px] border-b">
                                <th class="pb-2">Product</th>
                                <th class="pb-2 text-center">Current Stock</th>
                                <th class="pb-2 text-center">Units Sold</th>
                                <th class="pb-2 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($chartsData['product_performance']['low_stock_high_demand'] as $prod)
                                <tr>
                                    <td class="py-2.5 font-medium text-slate-800">{{ $prod->title }}</td>
                                    <td class="py-2.5 text-center font-bold text-rose-600">{{ $prod->stock }}</td>
                                    <td class="py-2.5 text-center font-bold text-emerald-700">{{ $prod->units_sold }}</td>
                                    <td class="py-2.5 text-right">
                                        <span class="rounded bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700">Reorder Alert</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-slate-400">No low stock items with high demand found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Variant Intelligence Overview --}}
        <div class="admin-surface p-5">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-900">Variant Inventory Health</h3>
                <p class="text-xs text-slate-500">SKU variant metrics across active catalog</p>
            </div>

            @php $vData = $chartsData['variant_analytics']; @endphp
            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between rounded bg-slate-50 p-2.5 text-xs">
                    <span class="text-slate-600 font-medium">Total Tracked Variants</span>
                    <span class="font-bold text-slate-900">{{ number_format($vData['total_variants']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded bg-emerald-50 p-2.5 text-xs">
                    <span class="text-emerald-800 font-medium">In Stock Variants</span>
                    <span class="font-bold text-emerald-900">{{ number_format($vData['in_stock']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded bg-rose-50 p-2.5 text-xs">
                    <span class="text-rose-800 font-medium">Out of Stock Variants</span>
                    <span class="font-bold text-rose-900">{{ number_format($vData['out_of_stock']) }}</span>
                </div>

                @if(!empty($vData['top_variants']) && count($vData['top_variants']) > 0)
                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Top Sold Variants</h4>
                        <div class="mt-2 space-y-2">
                            @foreach($vData['top_variants'] as $tVar)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="truncate max-w-[170px] text-slate-700">{{ $tVar->name }}</span>
                                    <span class="font-bold text-slate-900">{{ $tVar->units_sold }} pcs</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 5. Advanced Product and Stock Table --}}
    <div class="admin-surface overflow-hidden">
        {{-- Table Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4">
            <div>
                <h3 class="font-bold text-slate-900">Product & Stock Intelligence Table</h3>
                <p class="text-xs text-slate-500">Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} matching products</p>
            </div>

            <div class="flex items-center gap-2">
                {{-- Column Visibility Dropdown --}}
                <div class="relative" x-data="{ colOpen: false }" @click.outside="colOpen = false">
                    <button type="button" @click="colOpen = !colOpen" class="inline-flex h-8 items-center gap-1.5 rounded-[6px] border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                        <span>Columns</span>
                    </button>
                    <div x-show="colOpen" x-cloak class="absolute right-0 z-30 mt-1 w-48 rounded-md border border-slate-200 bg-white p-2 shadow-lg text-xs space-y-1">
                        <label class="flex items-center gap-2 px-2 py-1 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" x-model="cols.sku" class="rounded text-emerald-600"> <span>SKU</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" x-model="cols.category" class="rounded text-emerald-600"> <span>Category</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" x-model="cols.price" class="rounded text-emerald-600"> <span>Selling Price</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" x-model="cols.retail_val" class="rounded text-emerald-600"> <span>Retail Value</span>
                        </label>
                        <label class="flex items-center gap-2 px-2 py-1 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" x-model="cols.sales" class="rounded text-emerald-600"> <span>Period Sales</span>
                        </label>
                        @if($financialAccess->canViewProductProfit())
                        <label class="flex items-center gap-2 px-2 py-1 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" x-model="cols.cost" class="rounded text-emerald-600"> <span>Cost & Margin</span>
                        </label>
                        @endif
                        <label class="flex items-center gap-2 px-2 py-1 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" x-model="cols.last_sale" class="rounded text-emerald-600"> <span>Last Sale Date</span>
                        </label>
                    </div>
                </div>

                {{-- Export CSV current filter --}}
                <a href="{{ route('admin.advanced-analytics.export.csv', request()->query()) }}"
                   class="inline-flex h-8 items-center gap-1.5 rounded-[6px] border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        {{-- Table Element --}}
        <div class="overflow-x-auto" style="scrollbar-width: thin;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="w-12">Thumbnail</th>
                        <th>Product Name</th>
                        <th x-show="cols.sku">SKU</th>
                        <th x-show="cols.category">Category</th>
                        <th class="text-center">Stock</th>
                        <th>Stock Status</th>
                        <th x-show="cols.price" class="text-right">Price</th>
                        <th x-show="cols.retail_val" class="text-right">Retail Valuation</th>
                        <th x-show="cols.sales" class="text-center">Units Sold</th>
                        <th x-show="cols.sales" class="text-right">Revenue</th>
                        @if($financialAccess->canViewProductProfit())
                        <th x-show="cols.cost" class="text-right">Profit</th>
                        @endif
                        <th x-show="cols.last_sale">Last Sale</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        @php
                            $stock = (int) $p->stock;
                            $sellPrice = (float) ($p->sale_price ?: $p->price);
                            $regPrice = (float) $p->price;
                            $costPrice = (float) $p->cost_price;
                            $unitsSold = (int) $p->period_units_sold;
                            $revenue = (float) $p->period_revenue;
                            $retailVal = $stock * $sellPrice;
                            $profit = ($costPrice > 0) ? round($revenue - ($unitsSold * $costPrice), 0) : null;

                            // Badging
                            $badgeVariant = 'success';
                            $statusLabel = 'In Stock';
                            if ($stock <= 0) {
                                $badgeVariant = 'destructive';
                                $statusLabel = 'Out of Stock';
                            } elseif ($stock <= (int) ($p->low_stock_alert ?? 10)) {
                                $badgeVariant = 'warning';
                                $statusLabel = 'Low Stock';
                            } elseif ($stock >= 100) {
                                $badgeVariant = 'neutral';
                                $statusLabel = 'Overstocked';
                            }

                            // Images
                            $firstImg = null;
                            if (is_array($p->images) && count($p->images) > 0) {
                                $firstImg = $p->images[0];
                            }
                        @endphp
                        <tr>
                            {{-- Thumbnail --}}
                            <td>
                                <div class="h-10 w-10 overflow-hidden rounded-[6px] border border-slate-200 bg-slate-50 flex items-center justify-center">
                                    @if($firstImg)
                                        <img src="{{ $firstImg }}" alt="" class="h-full w-full object-cover" onerror="this.src='/brandlogo.png'">
                                    @else
                                        <svg class="text-slate-300" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    @endif
                                </div>
                            </td>

                            {{-- Product Name (Bangla + English support) --}}
                            <td class="max-w-[280px]">
                                <a href="{{ route('admin.products.edit', $p->id) }}" class="font-semibold text-slate-900 hover:text-[#0f4d2c] line-clamp-2">
                                    {{ $p->title }}
                                </a>
                                <div class="mt-0.5 flex items-center gap-2 text-[11px] text-slate-400">
                                    <span>ID: #{{ $p->id }}</span>
                                    @if($p->variants->isNotEmpty())
                                        <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.2 text-[10px] font-semibold text-slate-700">
                                            {{ $p->variants->count() }} Variants
                                        </span>
                                    @endif
                                    @if($regPrice > $sellPrice)
                                        <span class="text-rose-600 font-semibold">
                                            -{{ round((($regPrice - $sellPrice) / $regPrice) * 100) }}%
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- SKU --}}
                            <td x-show="cols.sku" class="font-mono text-xs text-slate-600">
                                {{ $p->sku ?: '—' }}
                            </td>

                            {{-- Category --}}
                            <td x-show="cols.category" class="text-xs text-slate-600">
                                {{ $p->category?->name ?? 'Uncategorized' }}
                            </td>

                            {{-- Stock --}}
                            <td class="text-center font-bold text-slate-900">
                                {{ number_format($stock) }}
                            </td>

                            {{-- Stock Status Badge --}}
                            <td>
                                <x-admin.badge :variant="$badgeVariant">
                                    {{ $statusLabel }}
                                </x-admin.badge>
                            </td>

                            {{-- Selling Price --}}
                            <td x-show="cols.price" class="text-right">
                                <span class="font-bold text-slate-900">৳{{ number_format($sellPrice, 0) }}</span>
                                @if($regPrice > $sellPrice)
                                    <div class="text-[10px] text-slate-400 line-through">৳{{ number_format($regPrice, 0) }}</div>
                                @endif
                            </td>

                            {{-- Retail Valuation --}}
                            <td x-show="cols.retail_val" class="text-right font-medium text-slate-800">
                                ৳{{ number_format($retailVal, 0) }}
                            </td>

                            {{-- Units Sold --}}
                            <td x-show="cols.sales" class="text-center">
                                @if($unitsSold > 0)
                                    <span class="font-bold text-emerald-700">{{ number_format($unitsSold) }}</span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>

                            {{-- Period Revenue --}}
                            <td x-show="cols.sales" class="text-right">
                                @if($revenue > 0)
                                    <span class="font-bold text-slate-900">৳{{ number_format($revenue, 0) }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Profit & Margin --}}
                            @if($financialAccess->canViewProductProfit())
                            <td x-show="cols.cost" class="text-right">
                                @if($profit !== null)
                                    <span class="{{ $profit >= 0 ? 'text-emerald-700' : 'text-rose-700' }} font-bold">
                                        ৳{{ number_format($profit, 0) }}
                                    </span>
                                @else
                                    <span class="text-[11px] text-slate-400" title="Cost price not specified on product">Unavailable</span>
                                @endif
                            </td>
                            @endif

                            {{-- Last Sale Date --}}
                            <td x-show="cols.last_sale" class="text-xs text-slate-500">
                                @if($p->last_sale_at)
                                    {{ \Carbon\Carbon::parse($p->last_sale_at)->format('d M, Y') }}
                                @else
                                    <span class="text-slate-400">No sales</span>
                                @endif
                            </td>

                            {{-- Actions (Drill-down) --}}
                            <td class="text-right">
                                <div class="inline-flex items-center gap-1">
                                    @if($p->variants->isNotEmpty())
                                        <button type="button"
                                                @click="openVariantDrilldown({{ $p->id }})"
                                                class="rounded-[4px] border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50"
                                                title="View Variant Details">
                                            Variants
                                        </button>
                                    @endif
                                    <a href="{{ route('admin.products.edit', $p->id) }}"
                                       class="rounded-[4px] p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                       title="Edit Product">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="py-12 text-center text-slate-400">
                                <div class="mx-auto max-w-sm">
                                    <svg class="mx-auto text-slate-300" width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                    <p class="mt-2 font-semibold text-slate-700">No matching products found</p>
                                    <p class="mt-1 text-xs text-slate-500">Try adjusting your filters, stock status, or search terms.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
            <div class="border-t border-slate-100 p-4">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    {{-- 6. Monthly & Historical Performance Analysis --}}
    <div class="admin-surface p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div>
                <h3 class="font-bold text-slate-900">Monthly & Historical Sales Analysis</h3>
                <p class="text-xs text-slate-500">12-Month performance breakdown for Year {{ $monthlyAnalysis['year'] }}</p>
            </div>
            <form method="GET" action="{{ route('admin.advanced-analytics.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="preset" value="month_year">
                <select name="year" class="admin-control text-xs" onchange="this.form.submit()">
                    @for($y = 2024; $y <= 2028; $y++)
                        <option value="{{ $y }}" @selected($monthlyAnalysis['year'] == $y)>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>

        <div class="mt-4 overflow-x-auto" style="scrollbar-width: thin;">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 uppercase text-[10px] border-b">
                        <th class="pb-2">Month</th>
                        <th class="pb-2 text-right">Gross Sales</th>
                        <th class="pb-2 text-right">Net Sales</th>
                        <th class="pb-2 text-center">Orders</th>
                        <th class="pb-2 text-center">Units Sold</th>
                        <th class="pb-2 text-right">MoM Growth</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($monthlyAnalysis['months'] as $mo)
                        <tr class="{{ $mo['is_current'] ? 'bg-emerald-50/40 font-semibold' : '' }}">
                            <td class="py-2.5">
                                {{ $mo['month_name'] }}
                                @if($mo['is_current'])
                                    <span class="ml-1 text-[10px] font-bold text-emerald-700">(Current)</span>
                                @endif
                            </td>
                            <td class="py-2.5 text-right text-slate-600">৳{{ number_format($mo['gross_sales'], 0) }}</td>
                            <td class="py-2.5 text-right font-bold text-slate-900">৳{{ number_format($mo['net_sales'], 0) }}</td>
                            <td class="py-2.5 text-center text-slate-700">{{ number_format($mo['orders_count']) }}</td>
                            <td class="py-2.5 text-center text-slate-700">{{ number_format($mo['units_sold']) }}</td>
                            <td class="py-2.5 text-right">
                                @if($mo['mom_growth'] !== null)
                                    <span class="{{ $mo['mom_growth'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }} font-bold">
                                        {{ $mo['mom_growth'] > 0 ? '+' : '' }}{{ $mo['mom_growth'] }}%
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- 7. PDF Report Generator Modal --}}
    <div x-show="pdfModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         x-transition.opacity>
        <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl"
             @click.outside="pdfModalOpen = false">
            <div class="flex items-start justify-between border-b pb-3">
                <div>
                    <h3 class="font-bold text-slate-900">Generate PDF Analytics Report</h3>
                    <p class="text-xs text-slate-500">Configure report contents, layout, and filters</p>
                </div>
                <button type="button" @click="pdfModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.advanced-analytics.export.pdf') }}" class="mt-4 space-y-4" @submit="isGeneratingPdf = true">
                @csrf
                {{-- Pass through current filters --}}
                <input type="hidden" name="preset" value="{{ $filters['preset'] }}">
                <input type="hidden" name="start_date" value="{{ $filters['start_date']->format('Y-m-d') }}">
                <input type="hidden" name="end_date" value="{{ $filters['end_date']->format('Y-m-d') }}">
                <input type="hidden" name="category_id" value="{{ $filters['category_id'] }}">
                <input type="hidden" name="brand_id" value="{{ $filters['brand_id'] }}">
                <input type="hidden" name="stock_status" value="{{ $filters['stock_status'] }}">
                <input type="hidden" name="product_status" value="{{ $filters['product_status'] }}">
                <input type="hidden" name="search" value="{{ $filters['search'] }}">

                {{-- Report Type --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Report Type</label>
                    <select name="report_type" class="admin-control mt-1 w-full text-xs">
                        <option value="complete">Complete Advanced Analytics Report</option>
                        <option value="executive">Executive Summary Report</option>
                        <option value="inventory">Inventory & Stock Valuation Report</option>
                        <option value="sales">Sales & Revenue Report</option>
                        <option value="pricing">Product Pricing & Discount Report</option>
                        @if($financialAccess->forExport()->canViewProfitMargin)
                        <option value="profitability">Profitability & Margin Report</option>
                        @endif
                        <option value="reorder">Low Stock & Reorder Alert Report</option>
                        <option value="variants">Product & Variant Stock Report</option>
                    </select>
                </div>

                {{-- Page Orientation --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Page Orientation</label>
                    <div class="mt-1 flex gap-3 text-xs">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="orientation" value="landscape" checked class="text-emerald-600">
                            <span>Landscape (Recommended for wide tables)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="orientation" value="portrait" class="text-emerald-600">
                            <span>Portrait</span>
                        </label>
                    </div>
                </div>

                {{-- Filter Summary Box in Modal --}}
                <div class="rounded-md bg-slate-50 p-3 text-xs text-slate-600">
                    <p class="font-semibold text-slate-800">Applied Filter Context:</p>
                    <p class="mt-1">Date Range: {{ $filters['start_date']->format('d M, Y') }} — {{ $filters['end_date']->format('d M, Y') }}</p>
                    @if($filters['category_id'])<p>Category ID: {{ $filters['category_id'] }}</p>@endif
                    @if($filters['stock_status'] !== 'all')<p>Stock Status: {{ ucfirst($filters['stock_status']) }}</p>@endif
                    <p class="text-[11px] text-slate-400 mt-1">Full Bengali text support (Hind Siliguri Unicode font) included.</p>
                </div>

                <div class="flex items-center justify-end gap-2 border-t pt-4">
                    <button type="button" @click="pdfModalOpen = false" class="rounded-[6px] border px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="submit"
                            :disabled="isGeneratingPdf"
                            class="inline-flex items-center gap-2 rounded-[6px] bg-[#0f4d2c] px-4 py-2 text-xs font-semibold text-white hover:bg-[#0a3820] disabled:opacity-50">
                        <svg x-show="isGeneratingPdf" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span x-text="isGeneratingPdf ? 'Generating PDF...' : 'Download PDF'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 8. Variant Drill-down Modal --}}
    <div x-show="variantModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         x-transition.opacity>
        <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl"
             @click.outside="variantModalOpen = false">
            <div class="flex items-start justify-between border-b pb-3">
                <div>
                    <h3 class="font-bold text-slate-900" x-text="selectedProduct?.title || 'Product Variants'"></h3>
                    <p class="text-xs text-slate-500">Live variant stock, price, and attributes breakdown</p>
                </div>
                <button type="button" @click="variantModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="mt-4">
                <div x-show="loadingVariants" class="py-8 text-center text-xs text-slate-400">
                    Loading variant data...
                </div>
                <div x-show="!loadingVariants">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 uppercase text-[10px] border-b">
                                <th class="pb-2">Variant</th>
                                <th class="pb-2">SKU</th>
                                <th class="pb-2 text-center">Stock</th>
                                <th class="pb-2 text-right">Price</th>
                                <th class="pb-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="v in activeVariants" :key="v.id">
                                <tr>
                                    <td class="py-2.5 font-medium text-slate-800" x-text="v.name"></td>
                                    <td class="py-2.5 font-mono text-slate-600" x-text="v.sku"></td>
                                    <td class="py-2.5 text-center font-bold" :class="v.stock <= 0 ? 'text-rose-600' : 'text-slate-900'" x-text="v.stock"></td>
                                    <td class="py-2.5 text-right font-bold text-slate-900" x-text="'৳' + Number(v.price).toLocaleString()"></td>
                                    <td class="py-2.5 text-center">
                                        <span class="rounded px-2 py-0.5 text-[10px] font-bold"
                                              :class="v.stock > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                                              x-text="v.stock > 0 ? 'In Stock' : 'Out of Stock'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 border-t pt-3 flex justify-end">
                <button type="button" @click="variantModalOpen = false" class="rounded-[6px] border px-4 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function advancedAnalyticsDashboard() {
    return {
        preset: '{{ $filters['preset'] }}',
        pdfModalOpen: false,
        isGeneratingPdf: false,
        variantModalOpen: false,
        loadingVariants: false,
        selectedProduct: null,
        activeVariants: [],
        cols: {
            sku: true,
            category: true,
            price: true,
            retail_val: true,
            sales: true,
            cost: true,
            last_sale: false,
        },
        openVariantDrilldown(productId) {
            this.variantModalOpen = true;
            this.loadingVariants = true;
            fetch(`/admin/advanced-analytics/products/${productId}/variants`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                this.selectedProduct = data.product;
                this.activeVariants = data.variants;
                this.loadingVariants = false;
            })
            .catch(() => {
                this.loadingVariants = false;
            });
        }
    };
}
</script>
@endsection
