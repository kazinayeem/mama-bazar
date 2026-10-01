@extends('layouts.admin', ['headerTitle' => 'Homepage Builder'])

@section('content')
@php
    $sectionMeta = [
        'hero' => ['label' => 'Hero Carousel', 'description' => 'Full-width rotating banner slides'],
        'trust_strip' => ['label' => 'Trust Strip', 'description' => 'Icon perks — delivery, warranty, support'],
        'categories' => ['label' => 'Categories', 'description' => 'Shop-by-category image grid'],
        'category_products' => ['label' => 'Category Products', 'description' => 'Products picked automatically from a selected category'],
        'promo_banner' => ['label' => 'Promo Banner', 'description' => 'Single promo banner from Banners manager'],
        'flash_deals' => ['label' => 'Flash Deals', 'description' => 'Discounted products with live countdown'],
        'featured' => ['label' => 'Featured Products', 'description' => 'Products marked as featured'],
        'best_sellers' => ['label' => 'Best Sellers', 'description' => 'Top products by real order volume'],
        'brands' => ['label' => 'Brands', 'description' => 'Scrolling logo marquee of active brands'],
        'collections' => ['label' => 'Collections', 'description' => 'Curated collection banner tiles'],
        'trending' => ['label' => 'Trending', 'description' => 'Products marked as trending'],
        'new_arrivals' => ['label' => 'New Arrivals', 'description' => 'Most recently added products'],
        'limited_edition' => ['label' => 'Limited Edition', 'description' => 'Products marked as limited edition'],
        'official' => ['label' => 'Official Products', 'description' => 'Products marked as official'],
        'hot_deals' => ['label' => 'Hot Deals', 'description' => 'Products marked as hot deals'],
        'emi_available' => ['label' => 'EMI Available', 'description' => 'Products available for EMI payment'],
        'recommendations' => ['label' => 'Recommended for You', 'description' => 'Personalized picks for signed-in shoppers'],
        'why_choose_us' => ['label' => 'Why Choose Us', 'description' => 'Value proposition icon cards'],
        'reviews' => ['label' => 'Customer Reviews', 'description' => 'Approved reviews carousel'],
        'newsletter' => ['label' => 'Newsletter', 'description' => 'Email capture CTA block'],
    ];
    $sectionOrder = [
        'hero', 'trust_strip', 'categories', 'category_products', 'new_arrivals', 'promo_banner', 'featured', 'brands',
        'collections', 'flash_deals', 'best_sellers', 'trending', 'limited_edition', 'official', 'hot_deals',
        'emi_available', 'recommendations', 'why_choose_us', 'reviews', 'newsletter',
    ];
    $iconOptions = ['Truck', 'ShieldCheck', 'BadgeCheck', 'RefreshCcw', 'Headphones', 'CreditCard', 'Wallet', 'Lock', 'Boxes', 'Package', 'Sparkles', 'ThumbsUp', 'HeartHandshake', 'Zap'];
    $subscriberRows = collect($subscribers)->map(function ($s) {
        if (is_array($s)) {
            return $s;
        }
        return [
            'id' => $s->id,
            'email' => $s->email,
            'source' => $s->source,
            'status' => $s->status ?? 'subscribed',
            'subscribedAt' => $s->subscribed_at?->toIso8601String(),
        ];
    })->values();

    // Boot payload MUST NOT be inlined into an HTML attribute — JSON double-quotes break x-data="...".
    $builderBoot = [
        'initialConfig' => $config,
        'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])->values(),
        'subscribers' => $subscriberRows,
        'sectionMeta' => $sectionMeta,
        'sectionOrder' => $sectionOrder,
        'iconOptions' => $iconOptions,
        'saveUrl' => route('admin.homepage.save'),
        'resetUrl' => route('admin.homepage.reset'),
        'mediaListUrl' => route('admin.media.picker'),
        'mediaUploadUrl' => route('admin.media.picker.upload'),
        'csrf' => csrf_token(),
    ];
@endphp

{{-- Safe JSON boot (not inside an HTML attribute — JSON quotes break x-data="...") --}}
<script type="application/json" id="homepage-builder-boot">@json($builderBoot)</script>

{{-- Load Sortable + homepageBuilder factory BEFORE Alpine evaluates x-data --}}
<x-admin.homepage.builder-script />

<div
    x-data="homepageBuilder(JSON.parse(document.getElementById('homepage-builder-boot').textContent))"
    x-init="init()"
    class="admin-page space-y-4"
>
    @if(session('success'))
        <div class="rounded-[8px] border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-[8px] border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="admin-page-title">Homepage Builder</h1>
                <span class="inline-flex items-center rounded-full bg-brand-green-50 px-2.5 py-0.5 text-xs font-semibold text-brand-green-700 border border-brand-green-200/70">Live Editor</span>
            </div>
            <p class="admin-page-subtitle">Design the storefront homepage — configure hero carousel slides, arrange sections, and manage marketing content.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ url('/') }}" target="_blank" rel="noreferrer" class="inline-flex h-9 items-center gap-1.5 rounded-[6px] border border-[var(--admin-border)] bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition">
                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>View Storefront</span>
            </a>
            <button type="button" @click="resetOpen = true" class="inline-flex h-9 items-center gap-1.5 rounded-[6px] border border-[var(--admin-border)] bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900 transition">
                <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.033 8.033 0 01-15.357-2m15.357 2H15"/></svg>
                <span>Reset Defaults</span>
            </button>
            <button type="button" @click="publish()" :disabled="saving || !dirty" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-[6px] bg-brand-green-500 px-4 text-xs font-semibold text-white shadow-sm hover:bg-brand-green-600 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50 transition">
                <span x-show="saving" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                <svg x-show="!saving && dirty" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <svg x-show="!saving && !dirty" class="h-3.5 w-3.5 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="dirty ? 'Publish Changes' : 'Published'"></span>
            </button>
        </div>
    </div>

    <div x-show="dirty" x-cloak class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-[8px] border border-amber-300 bg-amber-50 px-4 py-2.5 text-xs font-medium text-amber-900 shadow-sm">
        <div class="flex items-center gap-2">
            <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>You have unpublished changes. Press <strong>“Publish Changes”</strong> to apply them to your storefront homepage.</span>
        </div>
        <button type="button" @click="publish()" :disabled="saving" class="self-start sm:self-auto shrink-0 rounded-[5px] bg-amber-600 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-amber-700 transition">
            Publish Now
        </button>
    </div>

    {{-- Tabs: Layout | Hero | Content | Subscribers --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-1 overflow-x-auto pb-0.5" role="tablist">
            <button
                type="button"
                role="tab"
                @click="setTab('layout')"
                :aria-selected="tab === 'layout'"
                :class="tab === 'layout' ? 'border-brand-green-600 text-brand-green-800 bg-brand-green-50/40' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50/70'"
                class="inline-flex items-center gap-2 border-b-2 rounded-t-[6px] px-3.5 py-2.5 text-xs font-semibold transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                <span>Layout</span>
            </button>

            <button
                type="button"
                role="tab"
                @click="setTab('hero')"
                :aria-selected="tab === 'hero'"
                :class="tab === 'hero' ? 'border-brand-green-600 text-brand-green-800 bg-brand-green-50/40' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50/70'"
                class="inline-flex items-center gap-2 border-b-2 rounded-t-[6px] px-3.5 py-2.5 text-xs font-semibold transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Hero Slides</span>
                <span
                    x-show="config.heroSlides.length > 0"
                    :class="tab === 'hero' ? 'bg-brand-green-100 text-brand-green-800' : 'bg-slate-100 text-slate-600'"
                    class="rounded-full px-2 py-0.5 text-[10px] font-bold transition"
                    x-text="config.heroSlides.length"
                ></span>
            </button>

            <button
                type="button"
                role="tab"
                @click="setTab('content')"
                :aria-selected="tab === 'content'"
                :class="tab === 'content' ? 'border-brand-green-600 text-brand-green-800 bg-brand-green-50/40' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50/70'"
                class="inline-flex items-center gap-2 border-b-2 rounded-t-[6px] px-3.5 py-2.5 text-xs font-semibold transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>Content</span>
            </button>

            <button
                type="button"
                role="tab"
                @click="setTab('subscribers')"
                :aria-selected="tab === 'subscribers'"
                :class="tab === 'subscribers' ? 'border-brand-green-600 text-brand-green-800 bg-brand-green-50/40' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50/70'"
                class="inline-flex items-center gap-2 border-b-2 rounded-t-[6px] px-3.5 py-2.5 text-xs font-semibold transition"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Subscribers</span>
                <span
                    x-show="subscribers.length > 0"
                    :class="tab === 'subscribers' ? 'bg-brand-green-100 text-brand-green-800' : 'bg-slate-100 text-slate-600'"
                    class="rounded-full px-2 py-0.5 text-[10px] font-bold transition"
                    x-text="subscribers.length"
                ></span>
            </button>
        </nav>
    </div>

    {{-- Do NOT x-cloak the default Layout tab — if Alpine fails, at least show a fallback notice --}}
    <div x-show="tab === 'layout'">
        <x-admin.homepage.layout-tab />
    </div>
    <div x-show="tab === 'hero'" x-cloak>
        <x-admin.homepage.hero-tab />
    </div>
    <div x-show="tab === 'content'" x-cloak>
        <x-admin.homepage.content-tab />
    </div>
    <div x-show="tab === 'subscribers'" x-cloak>
        <x-admin.homepage.subscribers-tab />
    </div>

    {{-- Reset dialog --}}
    <div x-show="resetOpen" x-cloak class="fixed inset-0 z-[300] flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="if(resetOpen) resetOpen = false">
        <div class="w-full max-w-md rounded-[10px] bg-white p-6 shadow-xl" @click.outside="resetOpen = false">
            <h3 class="text-lg font-bold text-slate-900">Reset homepage to defaults?</h3>
            <p class="mt-2 text-sm text-slate-500">This restores the default section layout and content. Your changes stay in the editor until you press Publish.</p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="resetOpen = false" class="rounded-[6px] border border-slate-200 px-4 py-2 text-sm font-medium">Cancel</button>
                <button type="button" @click="confirmReset()" class="rounded-[6px] bg-brand-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-green-600">Reset</button>
            </div>
        </div>
    </div>

    <x-admin.homepage.media-picker />

    <form x-ref="publishForm" method="POST" :action="saveUrl" class="hidden">
        @csrf
        <input type="hidden" name="config_json" :value="JSON.stringify(config)">
    </form>
</div>
@endsection
