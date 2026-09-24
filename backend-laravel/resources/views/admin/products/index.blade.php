@extends('layouts.admin', [
    'title' => 'Products',
    'headerTitle' => 'Products',
])

@php
    $adminUrl = function (array $overrides = [], array $remove = []) {
        $query = request()->query();
        foreach ($remove as $key) {
            unset($query[$key]);
        }
        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '' || $value === false) {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }
        if (! array_key_exists('page', $overrides)) {
            unset($query['page']);
        }
        unset($query['view']);
        return route('admin.products.index', $query);
    };

    $chips = [];
    if (request('search')) {
        $chips[] = ['keys' => ['search'], 'label' => 'Search: '.request('search')];
    }
    if (request('category')) {
        $cat = $categories->first(fn ($c) => ($c->slug ?: (string) $c->id) == request('category'));
        $chips[] = ['keys' => ['category'], 'label' => $cat?->name ?? request('category')];
    }
    if (request('brand')) {
        $b = $brands->first(fn ($x) => ($x->slug ?: (string) $x->id) == request('brand'));
        $chips[] = ['keys' => ['brand'], 'label' => $b?->name ?? request('brand')];
    }
    if (request('supplier')) {
        $s = $suppliers->first(fn ($x) => ($x->slug ?: (string) $x->id) == request('supplier'));
        $chips[] = ['keys' => ['supplier'], 'label' => 'Supplier: '.($s?->name ?? request('supplier'))];
    }
    if (request('vendor')) {
        $v = $vendors->first(fn ($x) => ($x->slug ?: (string) $x->id) == request('vendor'));
        $chips[] = ['keys' => ['vendor'], 'label' => 'Vendor: '.($v?->name ?? request('vendor'))];
    }
    if (request('collection')) {
        $col = $collections->first(fn ($x) => ($x->slug ?: (string) $x->id) == request('collection'));
        $chips[] = ['keys' => ['collection'], 'label' => $col?->name ?? request('collection')];
    }
    if (request('stock')) {
        $chips[] = ['keys' => ['stock'], 'label' => str_replace('_', ' ', ucfirst(request('stock')))];
    }
    if (request('productStatus')) {
        $chips[] = ['keys' => ['productStatus'], 'label' => 'Status: '.ucfirst(request('productStatus'))];
    }
    if (request('label')) {
        $chips[] = ['keys' => ['label'], 'label' => 'Label: '.str_replace('_', ' ', request('label'))];
    }
    if (request()->filled('minPrice') || request()->filled('maxPrice')) {
        $chips[] = ['keys' => ['minPrice', 'maxPrice'], 'label' => '৳'.(request('minPrice') ?: '0').'–৳'.(request('maxPrice') ?: '∞')];
    }
    if (request('dateFrom') || request('dateTo')) {
        $chips[] = ['keys' => ['dateFrom', 'dateTo'], 'label' => 'Date: '.(request('dateFrom') ?: '…').' → '.(request('dateTo') ?: '…')];
    }

    $from = $total === 0 ? 0 : (($page - 1) * ($pagination['limit'] ?? 20)) + 1;
    $to = min($page * ($pagination['limit'] ?? 20), $total);
    $startPage = max(1, min($page - 2, max(1, $totalPages - 4)));
    $endPage = min($totalPages, $startPage + 4);
@endphp

@section('content')
<style>[x-cloak]{display:none!important}</style>
<div
    class="admin-page"
    x-data="productList({
        products: {{ Js::from(collect($products)->map(fn ($p) => ['id' => $p['id']])->values()) }},
        csrfToken: '{{ csrf_token() }}',
        routes: {
            export: '{{ route('admin.products.export') }}',
            import: '{{ route('admin.products.import') }}',
            bulk: '{{ route('admin.products.bulk') }}',
            create: '{{ route('admin.products.create') }}',
        }
    })"
>
    <x-admin.page-header title="Products" :subtitle="number_format($total).' products · Manage your catalog'">
        <x-slot:actions>
            <input type="file" accept=".csv" class="hidden" x-ref="fileInput" @change="handleImport($event.target.files[0])" />
            <button type="button" @click="handleExport()"
                    class="hidden sm:inline-flex h-10 items-center gap-1.5 rounded-[6px] border border-[var(--admin-border)] bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-[var(--admin-muted)]">
                Export CSV
            </button>
            <button type="button" @click="$refs.fileInput.click()" :disabled="importing"
                    class="hidden sm:inline-flex h-10 items-center gap-1.5 rounded-[6px] border border-[var(--admin-border)] bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-[var(--admin-muted)] disabled:opacity-50">
                <span x-text="importing ? 'Importing…' : 'Import CSV'"></span>
            </button>
            <div class="relative sm:hidden" x-data="{ open: false }">
                <button type="button" @click="open = !open" @click.outside="open = false"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-[6px] border border-[var(--admin-border)] bg-white text-slate-600" aria-label="More actions">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01"/></svg>
                </button>
                <div x-show="open" x-cloak class="absolute right-0 z-30 mt-1 w-40 overflow-hidden rounded-[8px] border border-[var(--admin-border)] bg-white py-1 shadow-panel">
                    <button type="button" @click="open=false; handleExport()" class="block w-full px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Export CSV</button>
                    <button type="button" @click="open=false; $refs.fileInput.click()" class="block w-full px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Import CSV</button>
                </div>
            </div>
            <a href="{{ route('admin.products.create') }}"
               class="inline-flex h-10 items-center gap-1.5 rounded-[6px] bg-brand-green-500 px-3.5 text-sm font-medium text-white hover:bg-brand-green-600">
                Add Product
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('admin.products.index') }}" id="filtersForm" class="space-y-3">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
            <div class="relative min-w-0 flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="search" value="{{ request('search') }}"
                       placeholder="Search products, SKU, brand…"
                       class="w-full admin-control w-full pl-9 pr-9 text-sm"
                       @keydown.enter.prevent="$el.form.submit()" />
                @if(request('search'))
                    <a href="{{ $adminUrl([], ['search']) }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-full p-1 text-slate-400 hover:bg-slate-100" aria-label="Clear search">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select name="category" onchange="this.form.submit()" class="admin-control text-xs font-semibold">
                    <option value="">Category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->slug ?: $cat->id }}" @selected(request('category') == ($cat->slug ?: $cat->id))>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <select name="brand" onchange="this.form.submit()" class="admin-control text-xs font-semibold">
                    <option value="">Brand</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->slug ?: $b->id }}" @selected(request('brand') == ($b->slug ?: $b->id))>{{ $b->name }}</option>
                    @endforeach
                </select>
                <select name="productStatus" onchange="this.form.submit()" class="admin-control text-xs font-semibold">
                    <option value="">Status</option>
                    <option value="published" @selected(request('productStatus') === 'published')>Published</option>
                    <option value="draft" @selected(request('productStatus') === 'draft')>Draft</option>
                    <option value="hidden" @selected(request('productStatus') === 'hidden')>Hidden</option>
                    <option value="archived" @selected(request('productStatus') === 'archived')>Archived</option>
                </select>
                <select name="stock" onchange="this.form.submit()" class="hidden sm:block admin-control text-xs font-semibold">
                    <option value="">Stock</option>
                    <option value="in_stock" @selected(request('stock') === 'in_stock')>In stock</option>
                    <option value="low_stock" @selected(request('stock') === 'low_stock')>Low stock</option>
                    <option value="out_of_stock" @selected(request('stock') === 'out_of_stock')>Out of stock</option>
                    <option value="on_backorder" @selected(request('stock') === 'on_backorder')>On backorder</option>
                </select>
                <select name="sort" onchange="this.form.submit()" class="admin-control text-xs font-semibold">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                    <option value="title_asc" @selected(request('sort') === 'title_asc')>Name A–Z</option>
                    <option value="title_desc" @selected(request('sort') === 'title_desc')>Name Z–A</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>Price ↑</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>Price ↓</option>
                    <option value="stock_asc" @selected(request('sort') === 'stock_asc')>Stock ↑</option>
                    <option value="stock_desc" @selected(request('sort') === 'stock_desc')>Stock ↓</option>
                </select>

                <button type="button" @click="moreFilters = true"
                        class="inline-flex items-center gap-1.5 admin-control text-xs font-semibold hover:bg-slate-50">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 12h12M10 20h4"/></svg>
                    More
                </button>

                {{-- View toggle --}}
                <div class="ml-auto flex items-center rounded-xl border border-slate-200 bg-white p-0.5" role="group" aria-label="View mode">
                    <button type="button" @click="setView('table')"
                            class="flex h-8 w-8 items-center justify-center rounded-lg {{ $listView === 'table' ? 'bg-brand-green-600 text-white' : 'text-slate-500 hover:bg-slate-50' }}"
                            aria-label="Table view" aria-pressed="{{ $listView === 'table' ? 'true' : 'false' }}">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 5h18v2H3V5zm0 6h18v2H3v-2zm0 6h18v2H3v-2z"/></svg>
                    </button>
                    <button type="button" @click="setView('cards')"
                            class="flex h-8 w-8 items-center justify-center rounded-lg {{ $listView === 'cards' ? 'bg-brand-green-600 text-white' : 'text-slate-500 hover:bg-slate-50' }}"
                            aria-label="Card view" aria-pressed="{{ $listView === 'cards' ? 'true' : 'false' }}">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Hidden fields kept for drawer submit --}}
        <input type="hidden" name="view" value="{{ $listView }}">
        <input type="hidden" name="supplier" value="{{ request('supplier') }}" id="filter-supplier">
        <input type="hidden" name="vendor" value="{{ request('vendor') }}" id="filter-vendor">
        <input type="hidden" name="collection" value="{{ request('collection') }}" id="filter-collection">
        <input type="hidden" name="label" value="{{ request('label') }}" id="filter-label">
        <input type="hidden" name="minPrice" value="{{ request('minPrice') }}" id="filter-minPrice">
        <input type="hidden" name="maxPrice" value="{{ request('maxPrice') }}" id="filter-maxPrice">
        <input type="hidden" name="dateFrom" value="{{ request('dateFrom') }}" id="filter-dateFrom">
        <input type="hidden" name="dateTo" value="{{ request('dateTo') }}" id="filter-dateTo">
    </form>

    {{-- Active chips --}}
    @if(count($chips))
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Filters:</span>
            @foreach($chips as $chip)
                <a href="{{ $adminUrl([], $chip['keys']) }}"
                   class="inline-flex items-center gap-1 rounded-full border border-brand-green-200 bg-brand-green-50 px-2.5 py-1 text-[11px] font-semibold text-brand-green-700 hover:bg-brand-green-100">
                    {{ $chip['label'] }} <span aria-hidden="true">×</span>
                </a>
            @endforeach
            <a href="{{ route('admin.products.index', ['view' => $listView]) }}" class="text-[11px] font-bold text-slate-500 hover:text-brand-orange-600 hover:underline">Clear all</a>
        </div>
    @endif

    {{-- Bulk bar --}}
    <div x-show="selected.length > 0" x-cloak
         class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-slate-900 px-3 py-2.5 text-white shadow-sm">
        <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-bold"><span x-text="selected.length"></span> selected</span>
        <button type="button" @click="executeBulk('publish')" :disabled="bulkBusy" class="rounded-lg bg-emerald-500 px-2.5 py-1 text-xs font-semibold hover:bg-emerald-400 disabled:opacity-50">Activate</button>
        <button type="button" @click="executeBulk('draft')" :disabled="bulkBusy" class="rounded-lg bg-white/10 px-2.5 py-1 text-xs font-semibold hover:bg-white/20 disabled:opacity-50">Deactivate</button>
        <button type="button" @click="executeBulk('hide')" :disabled="bulkBusy" class="rounded-lg bg-white/10 px-2.5 py-1 text-xs font-semibold hover:bg-white/20 disabled:opacity-50">Hide</button>
        <button type="button" @click="executeBulk('archive')" :disabled="bulkBusy" class="rounded-lg bg-white/10 px-2.5 py-1 text-xs font-semibold hover:bg-white/20 disabled:opacity-50">Archive</button>
        <button type="button" @click="bulkDeleteOpen = true" :disabled="bulkBusy" class="rounded-lg bg-red-500 px-2.5 py-1 text-xs font-semibold hover:bg-red-400 disabled:opacity-50">Delete</button>
        <button type="button" @click="selected = []" class="ml-auto text-xs font-medium text-white/70 hover:text-white">Clear</button>
    </div>

    {{-- ONE primary list: table XOR cards (server-rendered from cookie / view toggle) --}}
    @if($listView === 'cards')
        <div class="space-y-2" data-products-view="cards">
            @if(count($products) > 0)
                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2">
                    <input type="checkbox" :checked="allSelected" @change="toggleSelectAll($event.target.checked)"
                           class="h-4 w-4 rounded border-slate-300 text-brand-green-600" aria-label="Select all">
                    <span class="text-xs font-semibold text-slate-600">Select all on this page</span>
                </div>
            @endif
            @forelse($products as $product)
                @include('admin.products.partials.row-card', ['product' => $product])
            @empty
                <div class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-16 text-center">
                    @if(count($chips))
                        <p class="text-sm font-semibold text-slate-800">No products found</p>
                        <a href="{{ route('admin.products.index') }}" class="mt-3 inline-flex rounded-full bg-brand-green-600 px-4 py-2 text-xs font-bold text-white">Clear filters</a>
                    @else
                        <p class="text-sm font-semibold text-slate-800">No products yet</p>
                        <a href="{{ route('admin.products.create') }}" class="mt-3 inline-flex rounded-full bg-brand-green-600 px-4 py-2 text-xs font-bold text-white">Add Product</a>
                    @endif
                </div>
            @endforelse
        </div>
    @else
        <div class="admin-table-wrap" data-products-view="table">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] border-collapse text-left text-xs">
                    <thead class="sticky top-0 z-10">
                        <tr class="border-b border-slate-200 bg-slate-50/95 text-[10px] font-semibold uppercase tracking-wider text-slate-500 backdrop-blur">
                            <th class="w-10 px-3 py-2.5">
                                <input type="checkbox" :checked="allSelected" @change="toggleSelectAll($event.target.checked)"
                                       class="h-4 w-4 rounded border-slate-300 text-brand-green-600 focus:ring-brand-green-500" aria-label="Select all">
                            </th>
                            <th class="px-3 py-2.5">Product</th>
                            <th class="w-28 px-3 py-2.5 text-right">Price</th>
                            <th class="w-24 px-3 py-2.5 text-center">Variants</th>
                            <th class="w-24 px-3 py-2.5 text-center">Stock</th>
                            <th class="w-24 px-3 py-2.5">Status</th>
                            <th class="w-20 px-3 py-2.5 text-center">Featured</th>
                            <th class="w-24 px-3 py-2.5">Created</th>
                            <th class="w-12 px-3 py-2.5 text-right"> </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($products as $product)
                            @include('admin.products.partials.row-table', ['product' => $product])
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-16 text-center">
                                    <svg class="mx-auto mb-2 h-8 w-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    @if(count($chips))
                                        <p class="text-sm font-semibold text-slate-800">No products found</p>
                                        <p class="mt-1 text-xs text-slate-500">Try changing your filters or search.</p>
                                        <a href="{{ route('admin.products.index') }}" class="mt-3 inline-flex rounded-full bg-brand-green-600 px-4 py-2 text-xs font-bold text-white">Clear filters</a>
                                    @else
                                        <p class="text-sm font-semibold text-slate-800">No products yet</p>
                                        <a href="{{ route('admin.products.create') }}" class="mt-3 inline-flex rounded-full bg-brand-green-600 px-4 py-2 text-xs font-bold text-white">Add Product</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Pagination --}}
    <div class="flex flex-col items-center justify-between gap-3 pt-1 sm:flex-row">
        <p class="text-xs text-slate-500">Showing {{ number_format($from) }}–{{ number_format($to) }} of {{ number_format($total) }}</p>
        @if($totalPages > 1)
            <div class="flex items-center gap-1">
                <a href="{{ $page > 1 ? $adminUrl(['page' => $page - 1]) : '#' }}"
                   class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold {{ $page <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-slate-50' }}">Previous</a>
                <div class="hidden items-center gap-1 sm:flex">
                    @for($p = $startPage; $p <= $endPage; $p++)
                        <a href="{{ $adminUrl(['page' => $p]) }}"
                           class="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-semibold {{ $p == $page ? 'bg-brand-green-600 text-white' : 'border border-slate-200 bg-white hover:bg-slate-50' }}">{{ $p }}</a>
                    @endfor
                </div>
                <span class="px-2 text-xs font-semibold text-slate-600 sm:hidden">{{ $page }}/{{ $totalPages }}</span>
                <a href="{{ $page < $totalPages ? $adminUrl(['page' => $page + 1]) : '#' }}"
                   class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold {{ $page >= $totalPages ? 'pointer-events-none opacity-40' : 'hover:bg-slate-50' }}">Next</a>
            </div>
        @endif
    </div>

    {{-- More Filters Drawer --}}
    <div x-show="moreFilters" x-cloak class="fixed inset-0 z-[220]" role="dialog" aria-modal="true" aria-label="More filters">
        <div class="absolute inset-0 bg-slate-950/40" @click="moreFilters = false"></div>
        <div class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:inset-y-0 sm:left-auto sm:right-0 sm:max-h-none sm:w-full sm:max-w-md sm:rounded-none sm:border-l sm:border-slate-200"
             x-show="moreFilters"
             x-transition:enter="transition transform duration-200 ease-out"
             x-transition:enter-start="translate-y-full sm:translate-y-0 sm:translate-x-full"
             x-transition:enter-end="translate-y-0 sm:translate-x-0"
             x-transition:leave="transition transform duration-150 ease-in"
             x-transition:leave-start="translate-y-0 sm:translate-x-0"
             x-transition:leave-end="translate-y-full sm:translate-y-0 sm:translate-x-full">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">More filters</h2>
                <button type="button" @click="moreFilters = false" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100" aria-label="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4 p-4" x-data="{
                supplier: @js(request('supplier')),
                vendor: @js(request('vendor')),
                collection: @js(request('collection')),
                label: @js(request('label')),
                minPrice: @js(request('minPrice')),
                maxPrice: @js(request('maxPrice')),
                dateFrom: @js(request('dateFrom')),
                dateTo: @js(request('dateTo')),
                stock: @js(request('stock')),
            }">
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Supplier</label>
                    <select x-model="supplier" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All suppliers</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->slug ?: $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Vendor</label>
                    <select x-model="vendor" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All vendors</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->slug ?: $v->id }}">{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Collection</label>
                    <select x-model="collection" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All collections</option>
                        @foreach($collections as $col)
                            <option value="{{ $col->slug ?: $col->id }}">{{ $col->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Label</label>
                    <select x-model="label" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All labels</option>
                        <option value="featured">Featured</option>
                        <option value="trending">Trending</option>
                        <option value="flash_sale">Flash sale</option>
                        <option value="new_arrival">New arrival</option>
                        <option value="best_seller">Best seller</option>
                        <option value="hot_deal">Hot deal</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Price range</label>
                    <div class="flex gap-2">
                        <input type="number" x-model="minPrice" placeholder="Min ৳" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <input type="number" x-model="maxPrice" placeholder="Max ৳" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Created date</label>
                    <div class="flex gap-2">
                        <input type="date" x-model="dateFrom" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        <input type="date" x-model="dateTo" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">Stock</label>
                    <select x-model="stock" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm sm:hidden">
                        <option value="">All</option>
                        <option value="in_stock">In stock</option>
                        <option value="low_stock">Low stock</option>
                        <option value="out_of_stock">Out of stock</option>
                        <option value="on_backorder">On backorder</option>
                    </select>
                    <p class="hidden text-xs text-slate-400 sm:block">Use the Stock dropdown in the toolbar.</p>
                </div>
                <div class="flex gap-2 border-t border-slate-100 pt-4">
                    <a href="{{ route('admin.products.index', ['view' => $listView]) }}" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-center text-xs font-bold text-slate-600">Clear</a>
                    <button type="button"
                            @click="
                                document.getElementById('filter-supplier').value = supplier || '';
                                document.getElementById('filter-vendor').value = vendor || '';
                                document.getElementById('filter-collection').value = collection || '';
                                document.getElementById('filter-label').value = label || '';
                                document.getElementById('filter-minPrice').value = minPrice || '';
                                document.getElementById('filter-maxPrice').value = maxPrice || '';
                                document.getElementById('filter-dateFrom').value = dateFrom || '';
                                document.getElementById('filter-dateTo').value = dateTo || '';
                                const stockSel = document.querySelector('#filtersForm select[name=stock]');
                                if (stockSel && stock) stockSel.value = stock;
                                else if (!stockSel && stock) {
                                    let h = document.getElementById('filter-stock-mobile');
                                    if (!h) {
                                        h = document.createElement('input');
                                        h.type = 'hidden';
                                        h.name = 'stock';
                                        h.id = 'filter-stock-mobile';
                                        document.getElementById('filtersForm').appendChild(h);
                                    }
                                    h.value = stock;
                                }
                                document.getElementById('filtersForm').submit();
                            "
                            class="flex-1 rounded-xl bg-brand-green-600 py-2.5 text-xs font-bold text-white hover:bg-brand-green-700">
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete modal --}}
    <div x-show="deleteTarget !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="deleteTarget = null" class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-2xl">
            <h3 class="text-base font-bold text-slate-900">Delete product?</h3>
            <p class="text-xs text-slate-500">This will permanently delete “<span class="font-semibold text-slate-800" x-text="deleteTarget?.title"></span>”. This cannot be undone.</p>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="deleteTarget = null" class="rounded-lg border border-slate-200 px-3.5 py-1.5 text-xs font-semibold text-slate-700">Cancel</button>
                <form :action="'/admin/products/' + deleteTarget?.id" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-600 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-red-700">Delete</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Bulk delete modal --}}
    <div x-show="bulkDeleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.outside="bulkDeleteOpen = false" class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-2xl">
            <h3 class="text-base font-bold text-slate-900">Delete <span x-text="selected.length"></span> product(s)?</h3>
            <p class="text-xs text-slate-500">This will permanently delete all selected products.</p>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="bulkDeleteOpen = false" class="rounded-lg border border-slate-200 px-3.5 py-1.5 text-xs font-semibold">Cancel</button>
                <button type="button" @click="executeBulk('delete')" class="rounded-lg bg-red-600 px-3.5 py-1.5 text-xs font-bold text-white">Delete</button>
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
        moreFilters: false,
        importing: false,
        bulkBusy: false,
        products: config.products,
        csrfToken: config.csrfToken,
        routes: config.routes,
        viewMode: config.listView || 'table',

        init() {
            // Prefer cards on narrow screens when no explicit view is in the URL.
            const params = new URLSearchParams(window.location.search);
            if (!params.has('view') && window.matchMedia('(max-width: 767px)').matches) {
                params.set('view', 'cards');
                window.location.replace(`${window.location.pathname}?${params.toString()}`);
                return;
            }
            this.$watch('moreFilters', (open) => {
                document.body.style.overflow = open ? 'hidden' : '';
            });
        },

        setView(mode) {
            if (mode !== 'table' && mode !== 'cards') return;
            try { localStorage.setItem('mamabazar:admin_products_view', mode); } catch (e) {}
            const params = new URLSearchParams(window.location.search);
            params.set('view', mode);
            window.location.href = `${window.location.pathname}?${params.toString()}`;
        },

        get allSelected() {
            return this.products.length > 0 && this.selected.length === this.products.length;
        },

        toggleSelectAll(checked) {
            this.selected = checked ? this.products.map(p => p.id) : [];
        },

        confirmDelete(id, title) {
            this.deleteTarget = { id, title };
        },

        duplicateProduct(id) {
            fetch(`/admin/products/${id}/duplicate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
            }).then(r => r.json()).then(() => window.location.reload())
              .catch(() => alert('Failed to duplicate product'));
        },

        executeBulk(action) {
            if (this.selected.length === 0) return;
            this.bulkBusy = true;
            fetch(this.routes.bulk, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ action, ids: this.selected })
            }).then(r => r.json()).then(() => { this.bulkDeleteOpen = false; window.location.reload(); })
              .catch(() => { alert('Bulk action failed'); this.bulkBusy = false; });
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
            fetch(this.routes.import, { method: 'POST', body: formData, headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(res => { alert(`Successfully imported ${res.imported || 0} product(s).`); window.location.reload(); })
                .catch(() => alert('Import failed'))
                .finally(() => { this.importing = false; });
        }
    }));
});
</script>
@endsection
