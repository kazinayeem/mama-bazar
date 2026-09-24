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
            <h1 class="admin-page-title">Homepage Builder</h1>
            <p class="text-sm text-slate-500">Design the storefront homepage — sections, hero slides, and content.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ url('/') }}" target="_blank" rel="noreferrer" class="hidden sm:inline-flex h-10 items-center gap-2 rounded-[6px] border border-[var(--admin-border)] bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                View Storefront
            </a>
            <button type="button" @click="resetOpen = true" class="inline-flex h-10 items-center gap-2 rounded-[6px] border border-[var(--admin-border)] bg-white px-3.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Reset
            </button>
            <button type="button" @click="publish()" :disabled="saving || !dirty" class="inline-flex h-10 items-center justify-center gap-2 rounded-[6px] bg-brand-green-500 px-3.5 text-sm font-medium text-white hover:bg-brand-green-600 disabled:cursor-not-allowed disabled:opacity-50">
                <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                <span x-text="dirty ? 'Publish Changes' : 'Published'"></span>
            </button>
        </div>
    </div>

    <div x-show="dirty" x-cloak class="rounded-md border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-800">
        You have unpublished changes — press “Publish Changes” to apply them to the storefront.
    </div>

    {{-- Tabs: Layout | Hero | Content | Subscribers (same as React AdminHomepagePage) --}}
    <div class="overflow-x-auto border-b border-slate-200">
        <nav class="-mb-px flex min-w-max gap-1" role="tablist">
            <button type="button" role="tab" @click="setTab('layout')" :aria-selected="tab === 'layout'" :class="tab === 'layout' ? 'border-brand-green-500 text-brand-green-700' : 'border-transparent text-slate-500 hover:text-slate-700'" class="border-b-2 px-4 py-2.5 text-sm font-semibold transition">Layout</button>
            <button type="button" role="tab" @click="setTab('hero')" :aria-selected="tab === 'hero'" :class="tab === 'hero' ? 'border-brand-green-500 text-brand-green-700' : 'border-transparent text-slate-500 hover:text-slate-700'" class="inline-flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-semibold transition">
                Hero Slides
                <span x-show="config.heroSlides.length > 0" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600" x-text="config.heroSlides.length"></span>
            </button>
            <button type="button" role="tab" @click="setTab('content')" :aria-selected="tab === 'content'" :class="tab === 'content' ? 'border-brand-green-500 text-brand-green-700' : 'border-transparent text-slate-500 hover:text-slate-700'" class="border-b-2 px-4 py-2.5 text-sm font-semibold transition">Content</button>
            <button type="button" role="tab" @click="setTab('subscribers')" :aria-selected="tab === 'subscribers'" :class="tab === 'subscribers' ? 'border-brand-green-500 text-brand-green-700' : 'border-transparent text-slate-500 hover:text-slate-700'" class="inline-flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-semibold transition">
                Subscribers
                <span x-show="subscribers.length > 0" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600" x-text="subscribers.length"></span>
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
