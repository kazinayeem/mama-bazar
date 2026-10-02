@extends('layouts.admin', ['headerTitle' => 'SEO Optimization & Audit'])

@section('content')
<div class="admin-page space-y-6" x-data="{ currentTab: '{{ $tab ?? 'products' }}' }">
    <x-admin.page-header title="SEO Optimization & Catalog Audit" subtitle="Monitor search engine visibility, structured data, canonicals, and sitemap health">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.seo.export-csv') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export CSV
                </a>
                <a href="{{ route('admin.seo.export-pdf') }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print / PDF
                </a>
                <form action="{{ route('admin.seo.refresh-sitemap') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh Sitemap
                    </button>
                </form>
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs font-semibold text-sky-800">
            {{ session('info') }}
        </div>
    @endif

    {{-- Audit Score & KPI Overview Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Health score card --}}
        <div class="admin-surface p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Catalog Health</span>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold {{ $audit['health_score'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($audit['health_score'] >= 50 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                    {{ $audit['health_score'] }}%
                </span>
            </div>
            <div class="mt-3">
                <div class="text-3xl font-extrabold text-slate-900">{{ $audit['health_score'] }}<span class="text-lg text-slate-400">/100</span></div>
                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full transition-all duration-500 {{ $audit['health_score'] >= 80 ? 'bg-emerald-500' : ($audit['health_score'] >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $audit['health_score'] }}%"></div>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-slate-500">Audited: {{ $audit['last_audited_at'] }}</p>
        </div>

        {{-- Missing Titles --}}
        <div class="admin-surface p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Missing SEO Titles</span>
                <span class="rounded-lg bg-rose-50 p-2 text-rose-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-black text-slate-900">{{ number_format($audit['products_missing_title']) }}</div>
                <p class="mt-1 text-xs text-slate-500">of {{ number_format($audit['products_total']) }} active products (use fallback)</p>
            </div>
            <a href="{{ route('admin.seo.index', ['filter' => 'missing_title', 'tab' => 'products']) }}" class="mt-2 text-xs font-bold text-brand-green-600 hover:text-brand-green-700">Filter Products →</a>
        </div>

        {{-- Missing Descriptions --}}
        <div class="admin-surface p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Missing Meta Descriptions</span>
                <span class="rounded-lg bg-amber-50 p-2 text-amber-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-black text-slate-900">{{ number_format($audit['products_missing_description']) }}</div>
                <p class="mt-1 text-xs text-slate-500">Need custom meta description</p>
            </div>
            <a href="{{ route('admin.seo.index', ['filter' => 'missing_description', 'tab' => 'products']) }}" class="mt-2 text-xs font-bold text-brand-green-600 hover:text-brand-green-700">Filter Products →</a>
        </div>

        {{-- Missing Images & Category issues --}}
        <div class="admin-surface p-4 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Category & Image Gaps</span>
                <span class="rounded-lg bg-sky-50 p-2 text-sky-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-black text-slate-900">{{ number_format($audit['products_missing_images'] + $audit['categories_missing_description']) }}</div>
                <p class="mt-1 text-xs text-slate-500">{{ $audit['products_missing_images'] }} products no img, {{ $audit['categories_missing_description'] }} cats no desc</p>
            </div>
            <a href="{{ route('admin.seo.index', ['filter' => 'missing_image', 'tab' => 'products']) }}" class="mt-2 text-xs font-bold text-brand-green-600 hover:text-brand-green-700">Inspect Issues →</a>
        </div>
    </div>

    {{-- Tabs Bar --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6">
            <button type="button" @click="currentTab = 'products'" :class="currentTab === 'products' ? 'border-brand-green-600 text-brand-green-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'" class="whitespace-nowrap border-b-2 py-3 px-1 text-xs font-bold transition">
                Products Audit ({{ $products->total() }})
            </button>
            <button type="button" @click="currentTab = 'categories'" :class="currentTab === 'categories' ? 'border-brand-green-600 text-brand-green-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'" class="whitespace-nowrap border-b-2 py-3 px-1 text-xs font-bold transition">
                Categories & Brands Audit
            </button>
            <button type="button" @click="currentTab = 'routes'" :class="currentTab === 'routes' ? 'border-brand-green-600 text-brand-green-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'" class="whitespace-nowrap border-b-2 py-3 px-1 text-xs font-bold transition">
                Homepage & Shop Route SEO
            </button>
            <button type="button" @click="currentTab = 'sitemaps'" :class="currentTab === 'sitemaps' ? 'border-brand-green-600 text-brand-green-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'" class="whitespace-nowrap border-b-2 py-3 px-1 text-xs font-bold transition">
                Technical SEO & Sitemaps
            </button>
        </nav>
    </div>

    {{-- TAB 1: Products Audit Table --}}
    <div x-show="currentTab === 'products'" class="space-y-4">
        {{-- Filters & Bulk Generation Box --}}
        <div class="admin-surface p-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <form method="GET" action="{{ route('admin.seo.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="products">
                <input type="text" name="q" value="{{ $search }}" placeholder="Search by title or SKU..." class="admin-control text-xs w-48 sm:w-64">
                <select name="filter" onchange="this.form.submit()" class="admin-control text-xs">
                    <option value="all" @selected($filter === 'all')>All Products</option>
                    <option value="all_issues" @selected($filter === 'all_issues')>Any SEO Issue</option>
                    <option value="missing_title" @selected($filter === 'missing_title')>Missing SEO Title</option>
                    <option value="missing_description" @selected($filter === 'missing_description')>Missing Description</option>
                    <option value="missing_image" @selected($filter === 'missing_image')>Missing Image</option>
                    <option value="duplicate_title" @selected($filter === 'duplicate_title')>Duplicate Titles</option>
                </select>
                <button type="submit" class="admin-button admin-button-secondary text-xs">Filter</button>
            </form>

            <form action="{{ route('admin.seo.generate-drafts') }}" method="POST" class="flex items-center gap-2">
                @csrf
                <input type="hidden" name="type" value="missing_only">
                <input type="hidden" name="apply" value="1">
                <button type="submit" onclick="return confirm('Generate dynamic SEO titles and meta descriptions for up to 50 products currently missing them? Existing manual SEO will NOT be overwritten.')" class="inline-flex items-center gap-1.5 rounded-lg border border-brand-green-300 bg-brand-green-50 px-3 py-1.5 text-xs font-bold text-brand-green-800 hover:bg-brand-green-100">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Auto-Draft Missing Meta
                </button>
            </form>
        </div>

        {{-- Products Table --}}
        <div class="admin-table-wrap">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-200 bg-slate-50 font-bold uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Category / Brand</th>
                        <th class="px-4 py-3">SEO Title Status</th>
                        <th class="px-4 py-3">Meta Description Status</th>
                        <th class="px-4 py-3">Audit Flags</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        @php
                            $images = is_array($product->images) ? $product->images : json_decode($product->images ?? '[]', true);
                            $hasImg = !empty($images[0]);
                            $hasCustomTitle = !empty($product->seo_title);
                            $hasCustomDesc = !empty($product->seo_description);
                        @endphp
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                        @if($hasImg)
                                            <img src="{{ $images[0] }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-[10px] font-bold text-rose-500 bg-rose-50">No img</div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 max-w-xs">
                                        <p class="truncate font-semibold text-slate-900">{{ $product->title }}</p>
                                        <p class="font-mono text-[11px] text-slate-400">SKU: {{ $product->sku ?? 'None' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-slate-800">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                                <p class="text-slate-400 text-[11px]">{{ $product->brandRel?->name ?? $product->brand ?? 'No Brand' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if($hasCustomTitle)
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Custom</span>
                                    <p class="mt-0.5 truncate text-[11px] text-slate-500 max-w-[12rem]">{{ $product->seo_title }}</p>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Auto-Generated</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($hasCustomDesc)
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Custom</span>
                                    <p class="mt-0.5 truncate text-[11px] text-slate-500 max-w-[12rem]">{{ $product->seo_description }}</p>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Fallback Description</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @if(!$hasImg)
                                        <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-800">Missing Image</span>
                                    @endif
                                    @if(!$hasCustomTitle)
                                        <span class="rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-medium text-slate-700">Default Title</span>
                                    @endif
                                    @if(!$hasCustomDesc)
                                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-800">Default Meta</span>
                                    @endif
                                    @if($hasImg && $hasCustomTitle && $hasCustomDesc)
                                        <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">Optimal</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="text-slate-400 hover:text-slate-600" title="View Frontend Page">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                    <a href="{{ route('admin.products.edit', $product->id) }}" class="rounded bg-brand-green-50 px-2.5 py-1 text-xs font-bold text-brand-green-700 hover:bg-brand-green-100">
                                        Edit SEO
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">No products found matching filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($products->hasPages())
                <div class="border-t border-slate-200 p-4">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 2: Categories & Brands Audit --}}
    <div x-show="currentTab === 'categories'" class="space-y-4">
        <div class="admin-table-wrap">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-200 bg-slate-50 font-bold uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Slug / URL</th>
                        <th class="px-4 py-3">SEO Title</th>
                        <th class="px-4 py-3">SEO Description</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($categories as $category)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $category->name }}</td>
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-400">/shop?category={{ $category->slug }}</td>
                            <td class="px-4 py-3">
                                @if($category->seo_title)
                                    <span class="text-slate-700 font-medium">{{ $category->seo_title }}</span>
                                @else
                                    <span class="text-slate-400 italic">Default: {{ $category->name }} - Buy Online at Mama Bazar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($category->seo_description)
                                    <span class="text-slate-700 truncate max-w-xs block">{{ $category->seo_description }}</span>
                                @else
                                    <span class="text-slate-400 italic">Auto-generated from catalog</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.categories.index') }}" class="rounded bg-brand-green-50 px-2.5 py-1 text-xs font-bold text-brand-green-700 hover:bg-brand-green-100">
                                    Edit in Categories
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($categories->hasPages())
                <div class="border-t border-slate-200 p-4">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 3: Route-Level Custom SEO (Home & Shop) --}}
    <div x-show="currentTab === 'routes'" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Homepage SEO form --}}
            <div class="admin-surface p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Homepage SEO Overrides</h3>
                <form action="{{ route('admin.seo.update-route') }}" method="POST" class="space-y-4" x-data="{ title: '{{ addslashes($homeSeo?->seo_title ?? '') }}', desc: '{{ addslashes($homeSeo?->seo_description ?? '') }}' }">
                    @csrf
                    <input type="hidden" name="route_name" value="home">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">SEO Title (Recommended: 50-60 chars)</label>
                        <input type="text" name="seo_title" x-model="title" placeholder="Mama Bazar - Online Grocery & Lifestyle Essentials" class="admin-control w-full text-xs">
                        <p class="mt-1 text-[11px] text-slate-400" x-text="title.length + ' characters'"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Meta Description (Recommended: 140-160 chars)</label>
                        <textarea name="seo_description" x-model="desc" rows="3" placeholder="Shop fresh grocery and lifestyle essentials at Mama Bazar..." class="admin-control w-full text-xs"></textarea>
                        <p class="mt-1 text-[11px] text-slate-400" x-text="desc.length + ' characters'"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Canonical URL</label>
                        <input type="url" name="canonical_url" value="{{ $homeSeo?->canonical_url }}" placeholder="{{ url('/') }}" class="admin-control w-full text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Robots Directive</label>
                        <select name="robots" class="admin-control w-full text-xs bg-white">
                            <option value="index, follow" @selected(($homeSeo?->robots ?? 'index, follow') === 'index, follow')>index, follow (Recommended)</option>
                            <option value="noindex, follow" @selected(($homeSeo?->robots ?? '') === 'noindex, follow')>noindex, follow</option>
                        </select>
                    </div>

                    {{-- Live Google Search Snippet Preview --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-1">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Google Search Preview</p>
                        <p class="text-xs text-emerald-700 font-mono truncate">{{ url('/') }}</p>
                        <p class="text-sm font-semibold text-blue-700 truncate" x-text="title || 'Mama Bazar - Online Grocery & Lifestyle Essentials in Bangladesh'"></p>
                        <p class="text-xs text-slate-600 line-clamp-2" x-text="desc || 'Your trusted daily online grocery, lifestyle and essentials store. Quality products delivered across Bangladesh.'"></p>
                    </div>

                    <button type="submit" class="admin-button admin-button-primary text-xs w-full">Save Homepage SEO</button>
                </form>
            </div>

            {{-- Shop Route SEO form --}}
            <div class="admin-surface p-6 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Main Shop Page SEO Overrides</h3>
                <form action="{{ route('admin.seo.update-route') }}" method="POST" class="space-y-4" x-data="{ title: '{{ addslashes($shopSeo?->seo_title ?? '') }}', desc: '{{ addslashes($shopSeo?->seo_description ?? '') }}' }">
                    @csrf
                    <input type="hidden" name="route_name" value="shop">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">SEO Title</label>
                        <input type="text" name="seo_title" x-model="title" placeholder="Shop All Products | Mama Bazar" class="admin-control w-full text-xs">
                        <p class="mt-1 text-[11px] text-slate-400" x-text="title.length + ' characters'"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Meta Description</label>
                        <textarea name="seo_description" x-model="desc" rows="3" placeholder="Browse all products at Mama Bazar..." class="admin-control w-full text-xs"></textarea>
                        <p class="mt-1 text-[11px] text-slate-400" x-text="desc.length + ' characters'"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Canonical URL</label>
                        <input type="url" name="canonical_url" value="{{ $shopSeo?->canonical_url }}" placeholder="{{ route('shop') }}" class="admin-control w-full text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Robots Directive</label>
                        <select name="robots" class="admin-control w-full text-xs bg-white">
                            <option value="index, follow" @selected(($shopSeo?->robots ?? 'index, follow') === 'index, follow')>index, follow (Recommended)</option>
                            <option value="noindex, follow" @selected(($shopSeo?->robots ?? '') === 'noindex, follow')>noindex, follow</option>
                        </select>
                    </div>

                    {{-- Live Google Search Snippet Preview --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-1">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Google Search Preview</p>
                        <p class="text-xs text-emerald-700 font-mono truncate">{{ route('shop') }}</p>
                        <p class="text-sm font-semibold text-blue-700 truncate" x-text="title || 'Shop All Products | Mama Bazar'"></p>
                        <p class="text-xs text-slate-600 line-clamp-2" x-text="desc || 'Browse all products, daily groceries, and lifestyle essentials at Mama Bazar.'"></p>
                    </div>

                    <button type="submit" class="admin-button admin-button-primary text-xs w-full">Save Shop Page SEO</button>
                </form>
            </div>
        </div>
    </div>

    {{-- TAB 4: Technical SEO & Sitemaps --}}
    <div x-show="currentTab === 'sitemaps'" class="space-y-6">
        <div class="admin-surface p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Dynamic XML Sitemaps</h3>
                    <p class="text-xs text-slate-500">Search engines crawl these files to index products, categories, brands, and public pages.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Active & Cached
                </span>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-slate-900 text-xs">Primary Sitemap Index</p>
                        <p class="text-[11px] font-mono text-slate-400">/sitemap.xml</p>
                    </div>
                    <a href="{{ url('/sitemap.xml') }}" target="_blank" class="rounded bg-brand-green-50 px-2.5 py-1 text-xs font-bold text-brand-green-700 hover:bg-brand-green-100">
                        View XML ↗
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 p-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-slate-900 text-xs">Products Sitemap</p>
                        <p class="text-[11px] font-mono text-slate-400">/sitemap-products.xml</p>
                    </div>
                    <a href="{{ url('/sitemap-products.xml') }}" target="_blank" class="rounded bg-brand-green-50 px-2.5 py-1 text-xs font-bold text-brand-green-700 hover:bg-brand-green-100">
                        View XML ↗
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 p-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-slate-900 text-xs">Categories Sitemap</p>
                        <p class="text-[11px] font-mono text-slate-400">/sitemap-categories.xml</p>
                    </div>
                    <a href="{{ url('/sitemap-categories.xml') }}" target="_blank" class="rounded bg-brand-green-50 px-2.5 py-1 text-xs font-bold text-brand-green-700 hover:bg-brand-green-100">
                        View XML ↗
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 p-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-slate-900 text-xs">Brands Sitemap</p>
                        <p class="text-[11px] font-mono text-slate-400">/sitemap-brands.xml</p>
                    </div>
                    <a href="{{ url('/sitemap-brands.xml') }}" target="_blank" class="rounded bg-brand-green-50 px-2.5 py-1 text-xs font-bold text-brand-green-700 hover:bg-brand-green-100">
                        View XML ↗
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 p-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-slate-900 text-xs">Pages & Home Sitemap</p>
                        <p class="text-[11px] font-mono text-slate-400">/sitemap-pages.xml</p>
                    </div>
                    <a href="{{ url('/sitemap-pages.xml') }}" target="_blank" class="rounded bg-brand-green-50 px-2.5 py-1 text-xs font-bold text-brand-green-700 hover:bg-brand-green-100">
                        View XML ↗
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 p-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-slate-900 text-xs">Robots Directives</p>
                        <p class="text-[11px] font-mono text-slate-400">/robots.txt</p>
                    </div>
                    <a href="{{ url('/robots.txt') }}" target="_blank" class="rounded bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-200">
                        View Robots ↗
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
