@extends('layouts.admin', [
    'title' => 'Products',
    'headerTitle' => 'Catalog Management'
])

@section('content')
<div class="mx-auto w-full max-w-[1400px] space-y-4" x-data="productList({
    total: {{ $total }},
    page: {{ $page }},
    totalPages: {{ $totalPages }},
    products: {{ Js::from($products) }},
    csrfToken: '{{ csrf_token() }}',
    routes: {
        export: '{{ route('admin.products.export') }}',
        import: '{{ route('admin.products.import') }}',
        bulk: '{{ route('admin.products.bulk') }}',
        create: '{{ route('admin.products.create') }}',
    }
})">

    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Products</h1>
            <p class="text-sm text-slate-500">{{ number_format($total) }} products · manage your catalog</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <input
                type="file"
                accept=".csv"
                class="hidden"
                x-ref="fileInput"
                @change="handleImport($event.target.files[0])"
            />
            <button
                type="button"
                @click="handleExport()"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-xs transition"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </button>
            <button
                type="button"
                @click="$refs.fileInput.click()"
                :disabled="importing"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-xs transition disabled:opacity-50"
            >
                <svg class="w-4 h-4 text-slate-500" :class="{ 'animate-spin': importing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span x-text="importing ? 'Importing…' : 'Import CSV'">Import CSV</span>
            </button>
            <a
                href="{{ route('admin.products.create') }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-white bg-brand-green-500 rounded-full hover:bg-brand-green-600 shadow-sm transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Product
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    @php
        $hasActiveFilters = request()->hasAny(['search', 'category', 'brand', 'supplier', 'vendor', 'collection', 'stock', 'productStatus', 'label', 'minPrice', 'maxPrice', 'dateFrom', 'dateTo'])
            || (request('sort') && request('sort') !== 'newest');
    @endphp
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-xs" x-data="{ filtersOpen: {{ $hasActiveFilters ? 'true' : 'false' }} }">
        <form method="GET" action="{{ route('admin.products.index') }}" id="filtersForm" class="space-y-3">
            <!-- Row 1: Search & Sort -->
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by name, SKU, barcode, brand…"
                        class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-brand-green-100 focus:border-brand-green-500 bg-white"
                        @keydown.enter.prevent="$el.form.submit()"
                    />
                </div>
                <div class="w-full lg:w-48 shrink-0">
                    <select
                        name="sort"
                        onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white focus:outline-hidden focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900"
                    >
                        <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Newest</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="stock_asc" {{ request('sort') === 'stock_asc' ? 'selected' : '' }}>Stock: Low to High</option>
                        <option value="stock_desc" {{ request('sort') === 'stock_desc' ? 'selected' : '' }}>Stock: High to Low</option>
                        <option value="title_asc" {{ request('sort') === 'title_asc' ? 'selected' : '' }}>Alphabetical (A–Z)</option>
                    </select>
                </div>
            </div>

            <button
                type="button"
                @click="filtersOpen = !filtersOpen"
                class="flex w-full items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-semibold text-slate-700 md:hidden"
            >
                <span>More filters{{ $hasActiveFilters ? ' (active)' : '' }}</span>
                <svg class="h-4 w-4 text-slate-400 transition-transform" :class="filtersOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <!-- Row 2: Dropdowns & Range -->
            <div class="flex flex-wrap items-center gap-2" :class="filtersOpen ? 'flex' : 'hidden md:flex'">
                <!-- Category Select -->
                <select
                    name="category"
                    onchange="this.form.submit()"
                    class="w-full sm:w-40 px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->slug ?: $cat->id }}" {{ request('category') == ($cat->slug ?: $cat->id) ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                <!-- Brand Select -->
                <select
                    name="brand"
                    onchange="this.form.submit()"
                    class="w-full sm:w-36 px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">All Brands</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->slug ?: $b->id }}" {{ request('brand') == ($b->slug ?: $b->id) ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>

                <!-- Supplier Select -->
                <select
                    name="supplier"
                    onchange="this.form.submit()"
                    class="w-full sm:w-36 px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->slug ?: $s->id }}" {{ request('supplier') == ($s->slug ?: $s->id) ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>

                <!-- Vendor Select -->
                <select
                    name="vendor"
                    onchange="this.form.submit()"
                    class="w-full sm:w-32 px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">All Vendors</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->slug ?: $v->id }}" {{ request('vendor') == ($v->slug ?: $v->id) ? 'selected' : '' }}>
                            {{ $v->name }}
                        </option>
                    @endforeach
                </select>

                <!-- Collection Select -->
                <select
                    name="collection"
                    onchange="this.form.submit()"
                    class="w-full sm:w-36 px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">All Collections</option>
                    @foreach($collections as $col)
                        <option value="{{ $col->slug ?: $col->id }}" {{ request('collection') == ($col->slug ?: $col->id) ? 'selected' : '' }}>
                            {{ $col->name }}
                        </option>
                    @endforeach
                </select>

                <!-- Stock Filter -->
                <select
                    name="stock"
                    onchange="this.form.submit()"
                    class="w-full sm:w-32 px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">Stock: All</option>
                    <option value="in_stock" {{ request('stock') === 'in_stock' ? 'selected' : '' }}>In stock</option>
                    <option value="low_stock" {{ request('stock') === 'low_stock' ? 'selected' : '' }}>Low stock</option>
                    <option value="out_of_stock" {{ request('stock') === 'out_of_stock' ? 'selected' : '' }}>Out of stock</option>
                    <option value="on_backorder" {{ request('stock') === 'on_backorder' ? 'selected' : '' }}>On backorder</option>
                </select>

                <!-- Product Status Filter -->
                <select
                    name="productStatus"
                    onchange="this.form.submit()"
                    class="w-full sm:w-32 px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">Status: All</option>
                    <option value="draft" {{ request('productStatus') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ request('productStatus') === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="hidden" {{ request('productStatus') === 'hidden' ? 'selected' : '' }}>Hidden</option>
                    <option value="archived" {{ request('productStatus') === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>

                <!-- Label Filter -->
                <select
                    name="label"
                    onchange="this.form.submit()"
                    class="w-full sm:w-36 px-2.5 py-1.5 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">Label: All</option>
                    <option value="featured" {{ request('label') === 'featured' ? 'selected' : '' }}>Featured</option>
                    <option value="trending" {{ request('label') === 'trending' ? 'selected' : '' }}>Trending</option>
                    <option value="flash_sale" {{ request('label') === 'flash_sale' ? 'selected' : '' }}>Flash sale</option>
                    <option value="new_arrival" {{ request('label') === 'new_arrival' ? 'selected' : '' }}>New arrival</option>
                    <option value="best_seller" {{ request('label') === 'best_seller' ? 'selected' : '' }}>Best seller</option>
                    <option value="hot_deal" {{ request('label') === 'hot_deal' ? 'selected' : '' }}>Hot deal</option>
                </select>

                <!-- Price Range -->
                <div class="flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-2 py-0.5">
                    <input
                        type="number"
                        name="minPrice"
                        placeholder="Min ৳"
                        value="{{ request('minPrice') }}"
                        class="h-7 w-20 border-0 px-1 text-xs focus:outline-hidden"
                        @keydown.enter.prevent="$el.form.submit()"
                    />
                    <span class="text-slate-400 text-xs">–</span>
                    <input
                        type="number"
                        name="maxPrice"
                        placeholder="Max ৳"
                        value="{{ request('maxPrice') }}"
                        class="h-7 w-20 border-0 px-1 text-xs focus:outline-hidden"
                        @keydown.enter.prevent="$el.form.submit()"
                    />
                </div>

                <!-- Date Range -->
                <div class="flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2 py-0.5">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <input
                        type="date"
                        name="dateFrom"
                        value="{{ request('dateFrom') }}"
                        class="h-7 w-28 border-0 px-1 text-[11px] focus:outline-hidden"
                        onchange="this.form.submit()"
                    />
                    <span class="text-slate-400 text-xs">–</span>
                    <input
                        type="date"
                        name="dateTo"
                        value="{{ request('dateTo') }}"
                        class="h-7 w-28 border-0 px-1 text-[11px] focus:outline-hidden"
                        onchange="this.form.submit()"
                    />
                </div>

                @if(request()->hasAny(['search', 'category', 'brand', 'supplier', 'vendor', 'collection', 'stock', 'productStatus', 'label', 'minPrice', 'maxPrice', 'dateFrom', 'dateTo', 'sort']))
                    <a
                        href="{{ route('admin.products.index') }}"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bulk Action Toolbar -->
    <div
        x-show="selected.length > 0"
        x-cloak
        class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-slate-100 p-3 shadow-xs"
    >
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-800 text-white">
            <span x-text="selected.length"></span>&nbsp;selected
        </span>
        <button
            type="button"
            @click="executeBulk('publish')"
            :disabled="bulkBusy"
            class="px-2.5 py-1 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition disabled:opacity-50"
        >
            Publish
        </button>
        <button
            type="button"
            @click="executeBulk('draft')"
            :disabled="bulkBusy"
            class="px-2.5 py-1 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-xs transition disabled:opacity-50"
        >
            Move to Draft
        </button>
        <button
            type="button"
            @click="executeBulk('hide')"
            :disabled="bulkBusy"
            class="px-2.5 py-1 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-xs transition disabled:opacity-50"
        >
            Hide
        </button>
        <button
            type="button"
            @click="executeBulk('archive')"
            :disabled="bulkBusy"
            class="px-2.5 py-1 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-xs transition disabled:opacity-50"
        >
            Archive
        </button>
        <button
            type="button"
            @click="bulkDeleteOpen = true"
            :disabled="bulkBusy"
            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-xs transition disabled:opacity-50"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Delete
        </button>
        <button
            type="button"
            @click="selected = []"
            class="ml-auto text-xs font-medium text-slate-500 hover:text-slate-800"
        >
            Clear
        </button>
    </div>

    <!-- Product list: mobile cards + desktop table -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        {{-- Mobile: select all --}}
        @if(count($products) > 0)
            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50 px-4 py-2.5 md:hidden">
                <input
                    type="checkbox"
                    :checked="allSelected"
                    @change="toggleSelectAll($event.target.checked)"
                    class="rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500 h-4 w-4"
                />
                <span class="text-xs font-semibold text-slate-600">Select all on this page</span>
            </div>
        @endif

        {{-- Mobile cards --}}
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse($products as $product)
                @php
                    $isArchived = ($product['productStatus'] ?? '') === 'archived';
                    $status = $product['productStatus'] ?? ($product['status'] ?? 'draft');
                    $variantsCount = isset($product['variants']) ? count($product['variants']) : 0;
                    $stock = (int) ($product['stock'] ?? 0);
                    $lowStockAlert = (int) ($product['lowStockAlert'] ?? 10);
                    $firstImage = !empty($product['images'][0]) ? $product['images'][0] : null;
                    $statusBadge = match($status) {
                        'published', 'active' => 'bg-brand-green-50 text-brand-green-700 border-brand-green-200',
                        'draft' => 'bg-slate-100 text-slate-600 border-slate-200',
                        'hidden' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'archived' => 'bg-slate-200 text-slate-600 border-slate-300',
                        default => 'bg-slate-100 text-slate-600 border-slate-200',
                    };
                @endphp
                <div class="p-4 {{ $isArchived ? 'opacity-50' : '' }}">
                    <div class="flex gap-3">
                        <input
                            type="checkbox"
                            :value="{{ $product['id'] }}"
                            x-model="selected"
                            class="mt-1 rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500 h-4 w-4 shrink-0"
                        />
                        @if($firstImage)
                            <img src="{{ $firstImage }}" alt="{{ $product['title'] }}" class="h-14 w-14 rounded-lg border border-slate-200 object-cover bg-slate-50 shrink-0" loading="lazy" />
                        @else
                            <div class="flex h-14 w-14 items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-slate-400 shrink-0">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('admin.products.show', $product['id']) }}" class="font-semibold text-slate-900 hover:text-brand-green-700 transition line-clamp-2">
                                    {{ $product['title'] }}
                                </a>
                                <div class="relative shrink-0 text-left" x-data="{ open: false }">
                                    <button type="button" @click="open = !open" @click.outside="open = false" class="p-1.5 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak class="absolute right-0 z-20 mt-1 w-48 rounded-xl border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-black/5">
                                        <a href="{{ route('admin.products.show', $product['id']) }}" class="flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">View</a>
                                        <a href="{{ route('admin.products.edit', $product['id']) }}" class="flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">Edit</a>
                                        <button type="button" @click="open = false; duplicateProduct({{ $product['id'] }})" class="flex w-full items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 text-left">Duplicate</button>
                                        @if(!empty($product['slug']))
                                            <a href="{{ url('/products/' . $product['slug']) }}" target="_blank" class="flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">View on storefront</a>
                                        @endif
                                        <button type="button" @click="open = false; confirmDelete({{ $product['id'] }}, '{{ addslashes($product['title']) }}')" class="flex w-full items-center gap-2 px-3 py-2 text-xs text-red-600 hover:bg-red-50 text-left">Delete</button>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border capitalize {{ $statusBadge }}">{{ $status }}</span>
                                <p class="text-sm font-bold text-slate-900">৳{{ number_format((float)($product['price'] ?? 0), 2) }}</p>
                                @if((float)($product['discount'] ?? 0) > 0)
                                    <span class="text-[10px] text-red-600 font-semibold">-{{ $product['discount'] }}%</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-slate-400">SKU</dt>
                            <dd class="font-mono text-slate-600">{{ $product['sku'] ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-slate-400">Stock</dt>
                            <dd>
                                @if($stock <= 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">0</span>
                                @elseif($stock <= $lowStockAlert)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">{{ $variantsCount > 0 ? "{$stock} (total)" : $stock }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-brand-green-50 text-brand-green-700 border border-brand-green-200">{{ $variantsCount > 0 ? "{$stock} (total)" : $stock }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="font-semibold uppercase tracking-wide text-slate-400">Brand · Category</dt>
                            <dd class="text-slate-700 truncate">{{ $product['brandInfo']['name'] ?? ($product['brand'] ?: '—') }} · {{ $product['category']['name'] ?? '—' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                        <div class="flex flex-wrap gap-1">
                            @if(!empty($product['isFeatured']))
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-brand-green-50 text-brand-green-700 border border-brand-green-200">Featured</span>
                            @endif
                            @if(!empty($product['isTrending']))
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200">Trending</span>
                            @endif
                            @if(!empty($product['isFlashSale']))
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Flash</span>
                            @endif
                            @if(!empty($product['isHotDeal']))
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-orange-50 text-orange-700 border border-orange-200">Hot Deal</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2" x-data="{ featured: {{ !empty($product['isFeatured']) ? 'true' : 'false' }}, loading: false }">
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Featured</span>
                            <button
                                type="button"
                                @click="
                                    loading = true;
                                    fetch('{{ route('admin.products.toggle-featured', $product['id']) }}', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                                        body: JSON.stringify({ featured: !featured })
                                    })
                                    .then(r => r.json())
                                    .then(d => { featured = d.isFeatured; })
                                    .finally(() => { loading = false; })
                                "
                                :disabled="loading"
                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden"
                                :class="featured ? 'bg-brand-green-600' : 'bg-slate-300'"
                                role="switch"
                                :aria-checked="featured"
                            >
                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out" :class="featured ? 'translate-x-4' : 'translate-x-0'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-14 text-center px-4">
                    <svg class="mx-auto mb-2 h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <p class="text-sm font-semibold text-slate-800">No products found</p>
                    <p class="mt-1 text-xs text-slate-500">Try adjusting your search or filters.</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="w-10 px-3 py-3">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                @change="toggleSelectAll($event.target.checked)"
                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-4 w-4"
                            />
                        </th>
                        <th class="w-14 px-3 py-3">Image</th>
                        <th class="min-w-56 px-3 py-3">Product Name</th>
                        <th class="w-28 px-3 py-3">SKU</th>
                        <th class="w-32 px-3 py-3">Brand</th>
                        <th class="w-36 px-3 py-3">Category</th>
                        <th class="w-28 px-3 py-3 text-right">Price</th>
                        <th class="w-28 px-3 py-3">Type</th>
                        <th class="w-24 px-3 py-3 text-center">Stock</th>
                        <th class="w-28 px-3 py-3">Status</th>
                        <th class="w-20 px-3 py-3 text-center">Featured</th>
                        <th class="w-28 px-3 py-3">Created</th>
                        <th class="w-16 px-3 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        @php
                            $isArchived = ($product['productStatus'] ?? '') === 'archived';
                            $status = $product['productStatus'] ?? ($product['status'] ?? 'draft');
                            $variantsCount = isset($product['variants']) ? count($product['variants']) : 0;
                            $stock = (int) ($product['stock'] ?? 0);
                            $lowStockAlert = (int) ($product['lowStockAlert'] ?? 10);
                            $firstImage = !empty($product['images'][0]) ? $product['images'][0] : null;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition {{ $isArchived ? 'opacity-50' : '' }}">
                            <!-- Checkbox -->
                            <td class="px-3 py-3">
                                <input
                                    type="checkbox"
                                    :value="{{ $product['id'] }}"
                                    x-model="selected"
                                    class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-4 w-4"
                                />
                            </td>

                            <!-- Image -->
                            <td class="px-3 py-3">
                                @if($firstImage)
                                    <img
                                        src="{{ $firstImage }}"
                                        alt="{{ $product['title'] }}"
                                        class="h-11 w-11 rounded-lg border border-slate-200 object-cover bg-slate-50 shrink-0"
                                        loading="lazy"
                                    />
                                @else
                                    <div class="flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-slate-400 shrink-0">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                @endif
                            </td>

                            <!-- Title + Badges -->
                            <td class="px-3 py-3">
                                <a
                                    href="{{ route('admin.products.show', $product['id']) }}"
                                    class="font-semibold text-slate-900 hover:text-emerald-700 transition line-clamp-2 block max-w-xs"
                                >
                                    {{ $product['title'] }}
                                </a>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if(!empty($product['isFeatured']))
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-200">Featured</span>
                                    @endif
                                    @if(!empty($product['isTrending']))
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200">Trending</span>
                                    @endif
                                    @if(!empty($product['isFlashSale']))
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Flash</span>
                                    @endif
                                    @if(!empty($product['isHotDeal']))
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-orange-50 text-orange-700 border border-orange-200">Hot Deal</span>
                                    @endif
                                </div>
                            </td>

                            <!-- SKU -->
                            <td class="px-3 py-3 font-mono text-[11px] text-slate-500">
                                {{ $product['sku'] ?: '—' }}
                            </td>

                            <!-- Brand -->
                            <td class="px-3 py-3 text-slate-700">
                                {{ $product['brandInfo']['name'] ?? ($product['brand'] ?: '—') }}
                            </td>

                            <!-- Category -->
                            <td class="px-3 py-3 text-slate-700">
                                {{ $product['category']['name'] ?? '—' }}
                            </td>

                            <!-- Price -->
                            <td class="px-3 py-3 text-right">
                                <p class="font-bold text-slate-900">৳{{ number_format((float)($product['price'] ?? 0), 2) }}</p>
                                @if((float)($product['discount'] ?? 0) > 0)
                                    <p class="text-[10px] text-red-600 font-semibold">-{{ $product['discount'] }}%</p>
                                @endif
                            </td>

                            <!-- Type -->
                            <td class="px-3 py-3">
                                @if($variantsCount > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        {{ $variantsCount }} Variant{{ $variantsCount !== 1 ? 's' : '' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-50 text-slate-600 border border-slate-200">
                                        Simple
                                    </span>
                                @endif
                            </td>

                            <!-- Stock -->
                            <td class="px-3 py-3 text-center">
                                @if($stock <= 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">
                                        0
                                    </span>
                                @elseif($stock <= $lowStockAlert)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        {{ $variantsCount > 0 ? "{$stock} (total)" : $stock }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $variantsCount > 0 ? "{$stock} (total)" : $stock }}
                                    </span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-3 py-3">
                                @php
                                    $statusBadge = match($status) {
                                        'published', 'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'draft' => 'bg-slate-100 text-slate-600 border-slate-200',
                                        'hidden' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'archived' => 'bg-slate-200 text-slate-600 border-slate-300',
                                        default => 'bg-slate-100 text-slate-600 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border capitalize {{ $statusBadge }}">
                                    {{ $status }}
                                </span>
                            </td>

                            <!-- Featured Switch -->
                            <td class="px-3 py-3 text-center" x-data="{ featured: {{ !empty($product['isFeatured']) ? 'true' : 'false' }}, loading: false }">
                                <button
                                    type="button"
                                    @click="
                                        loading = true;
                                        fetch('{{ route('admin.products.toggle-featured', $product['id']) }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': csrfToken,
                                                'Accept': 'application/json'
                                            },
                                            body: JSON.stringify({ featured: !featured })
                                        })
                                        .then(r => r.json())
                                        .then(d => { featured = d.isFeatured; })
                                        .finally(() => { loading = false; })
                                    "
                                    :disabled="loading"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden"
                                    :class="featured ? 'bg-slate-900' : 'bg-slate-300'"
                                    role="switch"
                                    :aria-checked="featured"
                                >
                                    <span
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                        :class="featured ? 'translate-x-4' : 'translate-x-0'"
                                    ></span>
                                </button>
                            </td>

                            <!-- Created -->
                            <td class="px-3 py-3 text-slate-500 whitespace-nowrap">
                                {{ !empty($product['createdAt']) ? \Carbon\Carbon::parse($product['createdAt'])->format('M d, Y') : '—' }}
                            </td>

                            <!-- Actions Menu -->
                            <td class="px-3 py-3 text-right" x-data="{ open: false }">
                                <div class="relative inline-block text-left">
                                    <button
                                        type="button"
                                        @click="open = !open"
                                        @click.outside="open = false"
                                        class="p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                                    </button>

                                    <div
                                        x-show="open"
                                        x-cloak
                                        class="absolute right-0 z-20 mt-1 w-48 rounded-xl border border-slate-200 bg-white py-1 shadow-lg ring-1 ring-black/5"
                                    >
                                        <a
                                            href="{{ route('admin.products.show', $product['id']) }}"
                                            class="flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50"
                                        >
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            View
                                        </a>
                                        <a
                                            href="{{ route('admin.products.edit', $product['id']) }}"
                                            class="flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50"
                                        >
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit
                                        </a>
                                        <button
                                            type="button"
                                            @click="open = false; duplicateProduct({{ $product['id'] }})"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 text-left"
                                        >
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            Duplicate
                                        </button>
                                        <div class="my-1 border-t border-slate-100"></div>
                                        @if(!empty($product['slug']))
                                            <a
                                                href="{{ url('/products/' . $product['slug']) }}"
                                                target="_blank"
                                                class="flex items-center gap-2 px-3 py-2 text-xs text-slate-700 hover:bg-slate-50"
                                            >
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                View on storefront
                                            </a>
                                            <div class="my-1 border-t border-slate-100"></div>
                                        @endif
                                        <button
                                            type="button"
                                            @click="open = false; confirmDelete({{ $product['id'] }}, '{{ addslashes($product['title']) }}')"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-xs text-red-600 hover:bg-red-50 text-left"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="py-14 text-center">
                                <svg class="mx-auto mb-2 h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <p class="text-sm font-semibold text-slate-800">No products found</p>
                                <p class="mt-1 text-xs text-slate-500">Try adjusting your search or filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="flex flex-col items-center justify-between gap-3 sm:flex-row pt-2">
        <p class="text-xs text-slate-500">
            Page {{ $page }} of {{ max(1, $totalPages) }} · {{ number_format($total) }} products
        </p>

        @if($totalPages > 1)
            <div class="flex items-center gap-1.5">
                <a
                    href="{{ $page > 1 ? request()->fullUrlWithQuery(['page' => $page - 1]) : '#' }}"
                    class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-300 bg-white {{ $page <= 1 ? 'opacity-40 pointer-events-none' : 'hover:bg-slate-50 text-slate-700' }}"
                >
                    Previous
                </a>

                @php
                    $startPage = max(1, min($page - 3, $totalPages - 6));
                    $endPage = min($totalPages, $startPage + 6);
                @endphp

                @for($p = $startPage; $p <= $endPage; $p++)
                    <a
                        href="{{ request()->fullUrlWithQuery(['page' => $p]) }}"
                        class="h-8 w-8 flex items-center justify-center text-xs font-semibold rounded-lg {{ $p == $page ? 'bg-slate-900 text-white' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}"
                    >
                        {{ $p }}
                    </a>
                @endfor

                <a
                    href="{{ $page < $totalPages ? request()->fullUrlWithQuery(['page' => $page + 1]) : '#' }}"
                    class="px-2.5 py-1 text-xs font-semibold rounded-lg border border-slate-300 bg-white {{ $page >= $totalPages ? 'opacity-40 pointer-events-none' : 'hover:bg-slate-50 text-slate-700' }}"
                >
                    Next
                </a>
            </div>
        @endif
    </div>

    <!-- Single Delete Modal -->
    <div
        x-show="deleteTarget !== null"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    >
        <div
            @click.outside="deleteTarget = null"
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-4"
        >
            <h3 class="text-base font-bold text-slate-900">Delete product?</h3>
            <p class="text-xs text-slate-500">
                This will permanently delete "<span class="font-semibold text-slate-800" x-text="deleteTarget?.title"></span>". This action cannot be undone.
            </p>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button
                    type="button"
                    @click="deleteTarget = null"
                    class="px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg"
                >
                    Cancel
                </button>
                <form :action="'/admin/products/' + deleteTarget?.id" method="POST">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="px-3.5 py-1.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm"
                    >
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Modal -->
    <div
        x-show="bulkDeleteOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    >
        <div
            @click.outside="bulkDeleteOpen = false"
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-4"
        >
            <h3 class="text-base font-bold text-slate-900">Delete <span x-text="selected.length"></span> product(s)?</h3>
            <p class="text-xs text-slate-500">
                This will permanently delete all selected products. This action cannot be undone.
            </p>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button
                    type="button"
                    @click="bulkDeleteOpen = false"
                    class="px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="executeBulk('delete')"
                    class="px-3.5 py-1.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm"
                >
                    Delete
                </button>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('productList', (config) => ({
        selected: [],
        deleteTarget: null,
        bulkDeleteOpen: false,
        importing: false,
        bulkBusy: false,
        products: config.products,
        csrfToken: config.csrfToken,
        routes: config.routes,

        get allSelected() {
            return this.products.length > 0 && this.selected.length === this.products.length;
        },

        toggleSelectAll(checked) {
            if (checked) {
                this.selected = this.products.map(p => p.id);
            } else {
                this.selected = [];
            }
        },

        confirmDelete(id, title) {
            this.deleteTarget = { id, title };
        },

        duplicateProduct(id) {
            fetch(`/admin/products/${id}/duplicate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(res => {
                window.location.reload();
            })
            .catch(err => alert('Failed to duplicate product'));
        },

        executeBulk(action) {
            if (this.selected.length === 0) return;
            this.bulkBusy = true;
            fetch(this.routes.bulk, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    ids: this.selected
                })
            })
            .then(r => r.json())
            .then(res => {
                this.bulkDeleteOpen = false;
                window.location.reload();
            })
            .catch(err => {
                alert('Bulk action failed');
                this.bulkBusy = false;
            });
        },

        handleExport() {
            const form = document.getElementById('filtersForm');
            const params = new URLSearchParams(new FormData(form)).toString();
            window.location.href = `${this.routes.export}?${params}`;
        },

        handleImport(file) {
            if (!file) return;
            this.importing = true;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', this.csrfToken);

            fetch(this.routes.import, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(res => {
                alert(`Successfully imported ${res.imported || 0} product(s).`);
                window.location.reload();
            })
            .catch(err => {
                alert('Import failed');
            })
            .finally(() => {
                this.importing = false;
            });
        }
    }));
});
</script>
@endsection
