@extends('layouts.app')

@section('title', $seoTitle ?? 'Shop')

@php
    $shopUrl = function (array $overrides = [], array $remove = []) {
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
        unset($query['q']);
        return route('shop', $query);
    };

    $priceMinBound = (int) floor((float) ($priceBounds->min_p ?? 0));
    $priceMaxBound = (int) ceil((float) ($priceBounds->max_p ?? 50000));
    $quickRanges = array_values(array_filter([
        ['label' => 'Under ৳1,000', 'min' => null, 'max' => 1000],
        ['label' => '৳1,000 – ৳5,000', 'min' => 1000, 'max' => 5000],
        ['label' => '৳5,000 – ৳10,000', 'min' => 5000, 'max' => 10000],
        ['label' => '৳10,000+', 'min' => 10000, 'max' => null],
    ], function ($r) use ($priceMinBound, $priceMaxBound) {
        $lo = $r['min'] ?? $priceMinBound;
        $hi = $r['max'] ?? $priceMaxBound;
        return $lo <= $priceMaxBound && $hi >= $priceMinBound;
    }));

    $totalPages = (int) ($pagination['totalPages'] ?? 1);
    $currentPage = (int) ($pagination['page'] ?? 1);
    $total = (int) ($pagination['total'] ?? 0);

    $pageWindow = [];
    if ($totalPages <= 7) {
        $pageWindow = range(1, max(1, $totalPages));
    } else {
        $pageWindow = [1];
        $start = max(2, $currentPage - 1);
        $end = min($totalPages - 1, $currentPage + 1);
        if ($start > 2) {
            $pageWindow[] = '...';
        }
        for ($i = $start; $i <= $end; $i++) {
            $pageWindow[] = $i;
        }
        if ($end < $totalPages - 1) {
            $pageWindow[] = '...';
        }
        $pageWindow[] = $totalPages;
    }

    $subcategories = $selectedCategory ? $selectedCategory->children : collect();
@endphp

@section('content')
<div
    class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
    x-data="shopPage({ suggestUrl: @js(route('shop.suggest')), initialSearch: @js($search) })"
>
    <div class="mb-6 flex flex-col gap-4 border-b border-brand-green-100 pb-6 lg:mb-8 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <nav class="mb-2 flex flex-wrap items-center gap-1.5 text-xs text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-brand-green-600">Home</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('shop') }}" class="hover:text-brand-green-600">Shop</a>
                @if($selectedCategory)
                    <span aria-hidden="true">/</span>
                    <span class="font-semibold text-brand-green-700">{{ $selectedCategory->name }}</span>
                @endif
            </nav>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-brand-green-600">Mama Bazar</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
                @if($search)
                    Results for “{{ $search }}”
                @elseif($selectedCategory)
                    {{ $selectedCategory->name }}
                @else
                    Shop All Products
                @endif
            </h1>
            <p class="mt-1.5 text-sm text-slate-500">
                Showing {{ number_format($from) }}–{{ number_format($to) }} of {{ number_format($total) }} products
                @if($selectedBrand)
                    from <span class="font-semibold text-slate-700">{{ $selectedBrand->name }}</span>
                @endif
            </p>
        </div>

        <div class="relative w-full max-w-md lg:w-96">
            <form method="GET" action="{{ route('shop') }}" class="relative" @submit="closeSuggest()">
                @foreach(request()->except(['search', 'q', 'page']) as $key => $val)
                    @if(is_scalar($val))
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endif
                @endforeach
                <label for="shop-search" class="sr-only">Search products</label>
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-slate-400" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input
                    id="shop-search"
                    type="search"
                    name="search"
                    x-model="search"
                    @input.debounce.250ms="fetchSuggest()"
                    @focus="fetchSuggest()"
                    @keydown.escape.prevent="closeSuggest()"
                    placeholder="Search products, brands, categories…"
                    autocomplete="off"
                    class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-sm font-medium text-slate-800 shadow-soft outline-none transition focus:border-brand-green-500 focus:ring-2 focus:ring-brand-green-100"
                >
                <button type="button" x-show="search.length" x-cloak @click="clearSearch()"
                        class="absolute inset-y-0 right-2.5 my-auto flex h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Clear search">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </form>

            <div x-show="suggestOpen && (suggest.products.length || suggest.brands.length || suggest.categories.length || suggestLoading)"
                 x-cloak @click.outside="closeSuggest()"
                 class="absolute left-0 right-0 z-40 mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lift"
                 role="listbox">
                <div x-show="suggestLoading" class="px-4 py-3 text-xs font-semibold text-slate-400">Searching…</div>
                <template x-if="!suggestLoading && suggest.products.length">
                    <div>
                        <p class="bg-slate-50 px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">Products</p>
                        <template x-for="(item, idx) in suggest.products" :key="'p'+idx">
                            <a :href="item.url" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-brand-green-50 hover:text-brand-green-700" x-text="item.title"></a>
                        </template>
                    </div>
                </template>
                <template x-if="!suggestLoading && suggest.brands.length">
                    <div>
                        <p class="bg-slate-50 px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">Brands</p>
                        <template x-for="(item, idx) in suggest.brands" :key="'b'+idx">
                            <a :href="item.url" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-brand-green-50 hover:text-brand-green-700" x-text="item.title"></a>
                        </template>
                    </div>
                </template>
                <template x-if="!suggestLoading && suggest.categories.length">
                    <div>
                        <p class="bg-slate-50 px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">Categories</p>
                        <template x-for="(item, idx) in suggest.categories" :key="'c'+idx">
                            <a :href="item.url" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-brand-green-50 hover:text-brand-green-700" x-text="item.title"></a>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
        <aside class="hidden w-[270px] shrink-0 lg:block xl:w-[280px]">
            <div class="sticky top-28 max-h-[calc(100vh-8rem)] space-y-4 overflow-y-auto pr-1">
                @include('web.products.partials.filters', compact('shopUrl', 'subcategories', 'quickRanges') + ['compact' => false])
            </div>
        </aside>

        <section class="min-w-0 flex-1">
            <div class="mb-4 flex flex-wrap items-center gap-2 sm:gap-3">
                <button type="button" @click="filtersOpen = true"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-soft lg:hidden">
                    <svg class="h-4 w-4 text-brand-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 12h12M10 20h4"/></svg>
                    Filters
                    @if(count($activeFilters))
                        <span class="rounded-full bg-brand-green-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ count($activeFilters) }}</span>
                    @endif
                </button>

                <div class="relative ml-auto">
                    <label for="shop-sort" class="sr-only">Sort products</label>
                    <select id="shop-sort"
                            class="w-full appearance-none rounded-full border border-slate-200 bg-white py-2.5 pl-4 pr-10 text-sm font-semibold text-slate-700 outline-none focus:border-brand-green-500 sm:w-auto"
                            onchange="window.location.href=this.value">
                        @foreach([
                            'newest' => 'Newest',
                            'oldest' => 'Oldest',
                            'price_asc' => 'Price: Low to High',
                            'price_desc' => 'Price: High to Low',
                            'title_asc' => 'Name: A–Z',
                            'title_desc' => 'Name: Z–A',
                            'popular' => 'Popular',
                            'discount' => 'Discount',
                        ] as $value => $label)
                            <option value="{{ $shopUrl(['sort' => $value]) }}" @selected($currentSort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>

                <div class="hidden items-center rounded-full border border-slate-200 bg-white p-1 sm:flex" role="group" aria-label="View mode">
                    <a href="{{ $shopUrl(['view' => 'grid']) }}"
                       class="flex h-9 w-9 items-center justify-center rounded-full {{ $viewMode === 'grid' ? 'bg-brand-green-600 text-white' : 'text-slate-500 hover:bg-slate-50' }}"
                       aria-label="Grid view" @if($viewMode === 'grid') aria-current="true" @endif>
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                    </a>
                    <a href="{{ $shopUrl(['view' => 'list']) }}"
                       class="flex h-9 w-9 items-center justify-center rounded-full {{ $viewMode === 'list' ? 'bg-brand-green-600 text-white' : 'text-slate-500 hover:bg-slate-50' }}"
                       aria-label="List view">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/></svg>
                    </a>
                </div>
            </div>

            @if(count($activeFilters))
                <div class="mb-5 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Filters:</span>
                    @foreach($activeFilters as $chip)
                        <a href="{{ $shopUrl([], array_keys($chip['remove'])) }}"
                           class="inline-flex items-center gap-1.5 rounded-full border border-brand-green-200 bg-brand-green-50 px-3 py-1.5 text-xs font-semibold text-brand-green-700 transition hover:bg-brand-green-100">
                            {{ $chip['label'] }}
                            <span aria-hidden="true">×</span>
                        </a>
                    @endforeach
                    <a href="{{ route('shop') }}" class="text-xs font-bold text-slate-500 hover:text-brand-orange-600 hover:underline">Clear All</a>
                </div>
            @endif

            @if($products->isEmpty())
                <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-200 bg-white px-6 py-20 text-center shadow-soft">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-green-50 text-brand-green-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <h2 class="mt-4 text-xl font-extrabold text-slate-900">No products found</h2>
                    <p class="mt-2 max-w-sm text-sm text-slate-500">Try removing some filters or changing your search.</p>
                    <a href="{{ route('shop') }}" class="mt-5 inline-flex rounded-full bg-brand-green-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-green-700">Clear All Filters</a>
                </div>
            @elseif($viewMode === 'list')
                <div class="space-y-3">
                    @foreach($products as $product)
                        @php
                            $imgs = is_array($product->images) ? $product->images : (json_decode($product->images ?? '[]', true) ?: []);
                            $img = $imgs[0] ?? '/brandlogo.png';
                            $price = (float) $product->price;
                            $sale = (float) ($product->sale_price ?? 0);
                            $eff = $sale > 0 && $sale < $price ? $sale : $price;
                            $out = (int) $product->stock <= 0 && !($product->unlimited_stock ?? false);
                        @endphp
                        <article class="flex flex-col gap-4 rounded-2xl border border-brand-green-100 bg-white p-3 shadow-soft sm:flex-row sm:items-center sm:p-4">
                            <a href="{{ route('products.show', $product->slug) }}" class="mx-auto block h-32 w-32 shrink-0 sm:mx-0 sm:h-28 sm:w-28">
                                <img src="{{ $img }}" alt="{{ $product->title }}" loading="lazy" class="h-full w-full object-contain">
                            </a>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-brand-green-600">{{ $product->brandRel->name ?? $product->brand ?? 'Mama Bazar' }}</p>
                                <a href="{{ route('products.show', $product->slug) }}" class="mt-0.5 block text-sm font-bold text-slate-900 hover:text-brand-green-600 sm:text-base">{{ $product->title }}</a>
                                @if($product->short_description)
                                    <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ Str::limit(strip_tags($product->short_description), 120) }}</p>
                                @endif
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <span class="text-lg font-extrabold text-slate-900">৳{{ number_format($eff, 0) }}</span>
                                    @if($sale > 0 && $sale < $price)
                                        <span class="text-sm text-slate-400 line-through">৳{{ number_format($price, 0) }}</span>
                                    @endif
                                    <button type="button"
                                            @click="$store.cart.addItem({ id: {{ $product->id }}, title: {{ json_encode($product->title) }}, slug: {{ json_encode($product->slug) }}, price: {{ $eff }}, image: {{ json_encode($img) }}, quantity: 1 })"
                                            @disabled($out)
                                            class="ml-auto rounded-full bg-brand-orange-500 px-4 py-2 text-xs font-bold text-white transition hover:bg-brand-orange-600 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">
                                        {{ $out ? 'Out of Stock' : 'Add to Cart' }}
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="store-product-grid">
                    @foreach($products as $index => $product)
                        <x-product-card :product="$product" :index="$index" />
                    @endforeach
                </div>
            @endif

            @if($totalPages > 1)
                <nav class="mt-10 flex items-center justify-center gap-1.5 sm:gap-2" aria-label="Pagination">
                    <a href="{{ $currentPage <= 1 ? '#' : $shopUrl(['page' => $currentPage - 1]) }}"
                       class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-brand-green-500 hover:text-brand-green-600 {{ $currentPage <= 1 ? 'pointer-events-none opacity-40' : '' }}"
                       aria-label="Previous page">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <div class="hidden items-center gap-1.5 sm:flex">
                        @foreach($pageWindow as $p)
                            @if($p === '...')
                                <span class="px-1 text-slate-400">…</span>
                            @else
                                <a href="{{ $shopUrl(['page' => $p]) }}"
                                   class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold transition {{ $p == $currentPage ? 'bg-brand-green-600 text-white' : 'border border-slate-200 text-slate-600 hover:border-brand-green-500 hover:text-brand-green-600' }}"
                                   @if($p == $currentPage) aria-current="page" @endif>{{ $p }}</a>
                            @endif
                        @endforeach
                    </div>
                    <span class="px-3 text-sm font-semibold text-slate-600 sm:hidden">{{ $currentPage }} / {{ $totalPages }}</span>
                    <a href="{{ $currentPage >= $totalPages ? '#' : $shopUrl(['page' => $currentPage + 1]) }}"
                       class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-brand-green-500 hover:text-brand-green-600 {{ $currentPage >= $totalPages ? 'pointer-events-none opacity-40' : '' }}"
                       aria-label="Next page">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </nav>
            @endif
        </section>
    </div>

    {{-- Mobile filter drawer --}}
    <div x-show="filtersOpen" x-cloak class="fixed inset-0 z-[200] lg:hidden" role="dialog" aria-modal="true" aria-label="Filters">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="filtersOpen = false"></div>
        <div class="absolute inset-y-0 left-0 flex w-[90%] max-w-sm flex-col bg-slate-50 shadow-lift"
             x-transition:enter="transition transform duration-200 ease-out"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition transform duration-150 ease-in"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">
            <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
                <h2 class="text-lg font-extrabold text-slate-900">Filters</h2>
                <button type="button" @click="filtersOpen = false" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600" aria-label="Close filters">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 space-y-4 overflow-y-auto p-4">
                @include('web.products.partials.filters', compact('shopUrl', 'subcategories', 'quickRanges') + ['compact' => true])
            </div>
            <div class="flex gap-2 border-t border-slate-200 bg-white p-4">
                <a href="{{ route('shop') }}" class="flex-1 rounded-full border border-slate-200 py-2.5 text-center text-sm font-bold text-slate-600">Clear</a>
                <button type="button" @click="filtersOpen = false" class="flex-1 rounded-full bg-brand-green-600 py-2.5 text-sm font-bold text-white">Done</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function shopPage({ suggestUrl, initialSearch }) {
    return {
        filtersOpen: false,
        search: initialSearch || '',
        suggestOpen: false,
        suggestLoading: false,
        suggest: { products: [], brands: [], categories: [] },
        init() {
            this.\$watch('filtersOpen', (open) => {
                document.body.style.overflow = open ? 'hidden' : '';
            });
        },
        closeSuggest() { this.suggestOpen = false; },
        clearSearch() {
            this.search = '';
            this.closeSuggest();
            const url = new URL(window.location.href);
            url.searchParams.delete('search');
            url.searchParams.delete('q');
            url.searchParams.delete('page');
            window.location.href = url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '');
        },
        async fetchSuggest() {
            if (!this.search || this.search.length < 2) {
                this.suggest = { products: [], brands: [], categories: [] };
                this.suggestOpen = false;
                return;
            }
            this.suggestLoading = true;
            this.suggestOpen = true;
            try {
                const res = await fetch(suggestUrl + '?q=' + encodeURIComponent(this.search), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.suggest = { products: data.products || [], brands: data.brands || [], categories: data.categories || [] };
            } catch (e) {
                this.suggest = { products: [], brands: [], categories: [] };
            } finally {
                this.suggestLoading = false;
            }
        },
    }
}
</script>
@endpush
@endsection
