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
    $subscriberRows = $subscribers->map(fn ($s) => [
        'id' => $s->id,
        'email' => $s->email,
        'source' => $s->source,
        'status' => $s->status ?? 'subscribed',
        'subscribedAt' => $s->subscribed_at?->toIso8601String(),
    ])->values();
@endphp

<div
    x-data="homepageBuilder({
        initialConfig: @json($config),
        categories: @json($categories),
        subscribers: @json($subscriberRows),
        sectionMeta: @json($sectionMeta),
        sectionOrder: @json($sectionOrder),
        iconOptions: @json($iconOptions),
        saveUrl: @json(route('admin.homepage.save')),
        resetUrl: @json(route('admin.homepage.reset')),
        csrf: @json(csrf_token()),
    })"
    x-init="init()"
    class="space-y-4"
>
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="admin-page-title">Homepage Builder</h1>
            <p class="text-sm text-slate-500">Design the storefront homepage — sections, hero slides, and content.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/" target="_blank" rel="noreferrer" class="hidden sm:inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                View Storefront
            </a>
            <button type="button" @click="resetOpen = true" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Reset
            </button>
            <button type="button" @click="publish()" :disabled="saving || !dirty" class="inline-flex items-center gap-2 inline-flex h-10 items-center justify-center rounded-[6px] bg-brand-green-500 px-3.5 text-sm font-medium text-white hover:bg-brand-green-600 disabled:cursor-not-allowed disabled:opacity-50">
                <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                <span x-text="dirty ? 'Publish Changes' : 'Published'"></span>
            </button>
        </div>
    </div>

    <div x-show="dirty" x-cloak class="rounded-md border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-800">
        You have unpublished changes — press “Publish Changes” to apply them to the storefront.
    </div>

    {{-- Tabs --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex flex-wrap gap-1">
            <button type="button" @click="setTab('layout')" :class="tab === 'layout' ? 'border-brand-green-500 text-brand-green-700' : 'border-transparent text-slate-500 hover:text-slate-700'" class="border-b-2 px-4 py-2.5 text-sm font-semibold transition">Layout</button>
            <button type="button" @click="setTab('hero')" :class="tab === 'hero' ? 'border-brand-green-500 text-brand-green-700' : 'border-transparent text-slate-500 hover:text-slate-700'" class="inline-flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-semibold transition">
                Hero Slides
                <span x-show="config.heroSlides.length > 0" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600" x-text="config.heroSlides.length"></span>
            </button>
            <button type="button" @click="setTab('content')" :class="tab === 'content' ? 'border-brand-green-500 text-brand-green-700' : 'border-transparent text-slate-500 hover:text-slate-700'" class="border-b-2 px-4 py-2.5 text-sm font-semibold transition">Content</button>
            <button type="button" @click="setTab('subscribers')" :class="tab === 'subscribers' ? 'border-brand-green-500 text-brand-green-700' : 'border-transparent text-slate-500 hover:text-slate-700'" class="inline-flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-semibold transition">
                Subscribers
                <span x-show="subscribers.length > 0" class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600" x-text="subscribers.length"></span>
            </button>
        </nav>
    </div>

    {{-- Layout --}}
    <div x-show="tab === 'layout'" x-cloak class="space-y-3 pt-4">
        <p class="text-sm text-slate-500">Drag to reorder, toggle to show or hide. Empty sections are automatically hidden on the storefront.</p>
        <div x-ref="sectionList" class="space-y-2">
            <template x-for="(section, index) in knownSections()" :key="section.id">
                <div class="admin-surface" :data-section-id="section.id">
                    <div class="flex flex-wrap items-center gap-3 p-3">
                        <button type="button" class="drag-handle cursor-grab touch-none text-slate-400 hover:text-slate-600 active:cursor-grabbing" aria-label="Drag to reorder">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M8 9h8M8 15h8"/></svg>
                        </button>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-green-50 text-xs font-bold text-brand-green-700" x-text="(sectionMeta[section.type]?.label || section.type).slice(0,2).toUpperCase()"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold text-slate-900" x-text="(section.title || '').trim() || sectionMeta[section.type]?.label || section.type"></p>
                                <span class="hidden rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-500 sm:inline" x-text="'#' + (index + 1)"></span>
                            </div>
                            <p class="truncate text-xs text-slate-500" x-text="sectionMeta[section.type]?.description || ''"></p>
                        </div>
                        <span x-show="!section.enabled" class="text-xs text-slate-400">Hidden</span>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" class="peer sr-only" :checked="section.enabled" @change="updateSection(section.id, { enabled: $event.target.checked })">
                            <span class="h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-brand-green-500 peer-checked:after:translate-x-full"></span>
                        </label>
                        <button x-show="section.type === 'category_products'" type="button" @click="removeSection(section.id)" class="rounded-lg p-2 text-red-600 hover:bg-red-50" title="Remove section">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                        <button type="button" @click="toggleSectionOpen(section.id)" class="rounded-lg p-2 hover:bg-slate-50">
                            <svg class="h-4 w-4 transition" :class="openSections[section.id] && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                    <div x-show="openSections[section.id]" class="border-t border-slate-100 px-4 pb-4 pt-3">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div x-show="section.type === 'category_products'">
                                <label class="text-xs font-bold text-slate-600">Category</label>
                                <select class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2 text-xs" :value="section.categoryId ? String(section.categoryId) : 'none'" @change="onCategoryPick(section.id, $event.target.value)">
                                    <option value="none">Select a category…</option>
                                    <template x-for="cat in categories" :key="cat.id">
                                        <option :value="String(cat.id)" x-text="cat.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-600">Title</label>
                                <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" :placeholder="sectionMeta[section.type]?.label || ''" :value="section.title || ''" @input="updateSection(section.id, { title: $event.target.value })">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-600">Subtitle</label>
                                <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" placeholder="Optional" :value="section.subtitle || ''" @input="updateSection(section.id, { subtitle: $event.target.value })">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-600">Eyebrow label</label>
                                <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" placeholder="Optional" :value="section.eyebrow || ''" @input="updateSection(section.id, { eyebrow: $event.target.value })">
                            </div>
                            <div x-show="sectionCanLimit(section.type)">
                                <label class="text-xs font-bold text-slate-600">Max items</label>
                                <input type="number" min="1" max="24" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" :value="section.limit || 12" @input="updateSection(section.id, { limit: clampLimit($event.target.value) })">
                            </div>
                            <div x-show="section.type === 'categories'">
                                <label class="text-xs font-bold text-slate-600">Columns</label>
                                <select class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2 text-xs" :value="String(section.columns || 4)" @change="updateSection(section.id, { columns: Number($event.target.value) })">
                                    <option value="4">4 columns</option>
                                    <option value="5">5 columns</option>
                                    <option value="6">6 columns</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-600">Background</label>
                                <select class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2 text-xs" :value="section.background || 'default'" @change="updateSection(section.id, { background: $event.target.value })">
                                    <option value="default">White</option>
                                    <option value="muted">Soft grey</option>
                                    <option value="dark">Dark</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-600">CTA text</label>
                                <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" placeholder="View all" :value="section.ctaText || ''" @input="updateSection(section.id, { ctaText: $event.target.value })">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-600">CTA link</label>
                                <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" placeholder="/shop" :value="section.ctaUrl || ''" @input="updateSection(section.id, { ctaUrl: $event.target.value })">
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <template x-for="section in unknownSections()" :key="section.id">
            <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50/50 p-3 text-xs text-amber-900">
                Unknown section type: <span class="font-bold" x-text="section.type"></span> (<span x-text="section.id"></span>)
            </div>
        </template>
        <button type="button" @click="addCategoryProducts()" class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-white py-3 text-sm font-medium text-slate-700 hover:border-brand-green-400 hover:text-brand-green-700">
            + Add Category Products section
        </button>
    </div>

    {{-- Hero slides --}}
    <div x-show="tab === 'hero'" x-cloak class="space-y-3 pt-4">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-slate-500"><span x-text="config.heroSlides.length"></span> slides · reorder via arrows · first slide shows first</p>
            <button type="button" @click="openSlideCreate()" class="rounded-full bg-brand-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-green-600">+ Add Slide</button>
        </div>
        <div x-show="config.heroSlides.length === 0" class="rounded-xl border bg-white py-14 text-center">
            <p class="text-sm text-slate-500">No slides yet. Add your first hero slide — or leave empty to hide the carousel.</p>
        </div>
        <div class="space-y-2">
            <template x-for="(slide, index) in config.heroSlides" :key="slide.id">
                <div class="rounded-xl border border-slate-200 bg-white p-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-1">
                            <button type="button" :disabled="index === 0" @click="moveSlide(index, -1)" class="rounded-lg p-2 hover:bg-slate-50 disabled:opacity-30">↑</button>
                            <button type="button" :disabled="index === config.heroSlides.length - 1" @click="moveSlide(index, 1)" class="rounded-lg p-2 hover:bg-slate-50 disabled:opacity-30">↓</button>
                        </div>
                        <div class="relative h-16 w-28 shrink-0 overflow-hidden rounded-md border bg-slate-100 sm:h-14 sm:w-40">
                            <img x-show="slide.desktopImage" :src="slide.desktopImage" alt="" class="h-full w-full object-cover">
                            <div x-show="!slide.desktopImage" class="flex h-full items-center justify-center text-xs text-slate-400">No image</div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate font-semibold text-slate-900" x-text="(slide.title || '').trim() || (slide.badge || '').trim() || ('Slide ' + (index + 1))"></p>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" :class="slide.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" x-text="slide.status"></span>
                            </div>
                            <p class="truncate text-xs text-slate-500" x-text="slide.subtitle || slide.description || (slide.primaryButtonText ? ('CTA: ' + slide.primaryButtonText) : 'No caption')"></p>
                        </div>
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                            <input type="checkbox" class="rounded text-brand-green-600" :checked="slide.status === 'active'" @change="updateSlideField(slide.id, { status: $event.target.checked ? 'active' : 'inactive' })">
                            Live
                        </label>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="duplicateSlide(slide)" class="rounded-lg p-2 text-xs hover:bg-slate-50" title="Duplicate">Copy</button>
                            <button type="button" @click="openSlideEdit(slide)" class="rounded-lg p-2 text-xs hover:bg-slate-50" title="Edit">Edit</button>
                            <button type="button" @click="deleteSlideTarget = slide" class="rounded-lg p-2 text-xs text-red-600 hover:bg-red-50" title="Delete">Delete</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Content --}}
    <div x-show="tab === 'content'" x-cloak class="grid gap-4 pt-4">
        <div class="admin-surface p-4">
            <h3 class="text-base font-bold text-slate-900">Announcement Bar</h3>
            <div class="mt-3 space-y-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" class="rounded text-brand-green-600" :checked="config.announcement.enabled" @change="config.announcement.enabled = $event.target.checked">
                    Show announcement bar on top of the site
                </label>
                <input type="text" class="w-full rounded-xl border border-slate-200 p-2.5 text-sm" placeholder="e.g. Free delivery on orders over ৳2,000" x-model="config.announcement.text">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-bold text-slate-600">Background color</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input type="color" class="h-8 w-10 cursor-pointer rounded border" x-model="config.announcement.backgroundColor">
                            <input type="text" class="h-8 flex-1 rounded-xl border border-slate-200 px-2 text-xs" x-model="config.announcement.backgroundColor">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-600">Text color</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input type="color" class="h-8 w-10 cursor-pointer rounded border" x-model="config.announcement.textColor">
                            <input type="text" class="h-8 flex-1 rounded-xl border border-slate-200 px-2 text-xs" x-model="config.announcement.textColor">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-surface p-4">
            <h3 class="text-base font-bold text-slate-900">Trust Strip</h3>
            <p class="text-xs text-slate-500">Icon perks shown right below the hero.</p>
            <div class="mt-3 space-y-2">
            <template x-for="(item, idx) in config.trustStrip" :key="'trust-' + idx">
                <div class="mb-2 flex flex-col gap-2 rounded-md border border-slate-200 p-3 sm:flex-row">
                    <div class="w-full sm:w-36">
                        <label class="text-xs font-bold text-slate-600">Icon</label>
                        <select class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2 text-xs" :value="item.icon || 'none'" @change="setContentItem('trustStrip', idx, { icon: $event.target.value === 'none' ? undefined : $event.target.value })">
                            <option value="none">No icon</option>
                            <template x-for="name in iconOptions" :key="name"><option :value="name" x-text="name"></option></template>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="text-xs font-bold text-slate-600">Title</label>
                        <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" placeholder="e.g. Fast delivery" :value="item.title" @input="setContentItem('trustStrip', idx, { title: $event.target.value })">
                    </div>
                    <div class="flex-1">
                        <label class="text-xs font-bold text-slate-600">Text</label>
                        <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" placeholder="Supporting text" :value="item.text || ''" @input="setContentItem('trustStrip', idx, { text: $event.target.value })">
                    </div>
                    <button type="button" @click="removeContentItem('trustStrip', idx)" class="self-end rounded-lg p-2 text-red-600 hover:bg-red-50">×</button>
                </div>
            </template>
            </div>
            <button type="button" @click="addContentItem('trustStrip')" class="mt-2 rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold hover:bg-slate-50">+ Add item</button>
        </div>

        <div class="admin-surface p-4">
            <h3 class="text-base font-bold text-slate-900">Why Choose Us</h3>
            <p class="text-xs text-slate-500">Value proposition cards near the bottom of the homepage.</p>
            <template x-for="(item, idx) in config.whyChooseUs" :key="'why-' + idx">
                <div class="mb-2 flex flex-col gap-2 rounded-md border border-slate-200 p-3 sm:flex-row">
                    <div class="w-full sm:w-36">
                        <label class="text-xs font-bold text-slate-600">Icon</label>
                        <select class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2 text-xs" :value="item.icon || 'none'" @change="setContentItem('whyChooseUs', idx, { icon: $event.target.value === 'none' ? undefined : $event.target.value })">
                            <option value="none">No icon</option>
                            <template x-for="name in iconOptions" :key="name"><option :value="name" x-text="name"></option></template>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="text-xs font-bold text-slate-600">Title</label>
                        <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" placeholder="e.g. Authentic products" :value="item.title" @input="setContentItem('whyChooseUs', idx, { title: $event.target.value })">
                    </div>
                    <div class="flex-1">
                        <label class="text-xs font-bold text-slate-600">Text</label>
                        <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" :value="item.text || ''" @input="setContentItem('whyChooseUs', idx, { text: $event.target.value })">
                    </div>
                    <button type="button" @click="removeContentItem('whyChooseUs', idx)" class="self-end rounded-lg p-2 text-red-600 hover:bg-red-50">×</button>
                </div>
            </template>
            <button type="button" @click="addContentItem('whyChooseUs')" class="mt-2 rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold hover:bg-slate-50">+ Add item</button>
        </div>

        <div class="admin-surface p-4">
            <h3 class="text-base font-bold text-slate-900">Flash Sale Window</h3>
            <p class="text-xs text-slate-500">Optional date range that powers the Flash Deals countdown. Leave empty for a daily sale ending at midnight.</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <label class="flex items-center gap-2 text-sm sm:col-span-3">
                    <input type="checkbox" class="rounded text-brand-green-600" :checked="config.flashSaleWindow.enabled" @change="config.flashSaleWindow.enabled = $event.target.checked">
                    Enable scheduled flash sale window
                </label>
                <div>
                    <label class="text-xs font-bold text-slate-600">Starts</label>
                    <input type="datetime-local" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2 text-xs" :value="flashStartLocal()" @input="config.flashSaleWindow.start = $event.target.value ? $event.target.value : null">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-600">Ends</label>
                    <input type="datetime-local" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2 text-xs" :value="flashEndLocal()" @input="config.flashSaleWindow.end = $event.target.value ? $event.target.value : null">
                </div>
            </div>
        </div>

        <div class="admin-surface p-4">
            <h3 class="text-base font-bold text-slate-900">Newsletter Section</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input type="checkbox" class="rounded text-brand-green-600" :checked="config.newsletter.enabled" @change="config.newsletter.enabled = $event.target.checked">
                    Show newsletter signup block
                </label>
                <div>
                    <label class="text-xs font-bold text-slate-600">Title</label>
                    <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" x-model="config.newsletter.title">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-600">Button text</label>
                    <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" x-model="config.newsletter.buttonText">
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-bold text-slate-600">Subtitle</label>
                    <input type="text" class="mt-1 h-9 w-full rounded-xl border border-slate-200 px-2.5 text-xs" x-model="config.newsletter.subtitle">
                </div>
            </div>
        </div>

        <div class="admin-surface p-4">
            <h3 class="text-base font-bold text-slate-900">Popular Searches</h3>
            <p class="text-xs text-slate-500">Suggested chips shown in the search bar overlay.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <template x-for="term in config.popularSearches" :key="term">
                    <span class="inline-flex items-center gap-1.5 rounded-full border bg-slate-100 px-3 py-1 text-xs font-semibold">
                        <span x-text="term"></span>
                        <button type="button" @click="removePopularSearch(term)" class="text-slate-500 hover:text-slate-800">×</button>
                    </span>
                </template>
            </div>
            <button type="button" @click="addPopularSearch()" class="mt-3 rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold hover:bg-slate-50">+ Add search term</button>
        </div>
    </div>

    {{-- Subscribers --}}
    <div x-show="tab === 'subscribers'" x-cloak class="pt-4">
        <div x-show="subscribers.length === 0" class="rounded-xl border bg-white py-14 text-center">
            <p class="mt-3 text-sm text-slate-500">No subscribers yet. Emails collected from the homepage newsletter form appear here.</p>
        </div>
        <div x-show="subscribers.length > 0" class="admin-table-wrap">
            <div class="divide-y divide-slate-100">
                <template x-for="sub in subscribers" :key="sub.id">
                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900" x-text="sub.email"></p>
                            <p class="text-xs text-slate-500">
                                <span x-text="formatDate(sub.subscribedAt)"></span> · via <span x-text="sub.source || 'homepage'"></span>
                            </p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="sub.status === 'subscribed' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" x-text="sub.status"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Reset dialog --}}
    <div x-show="resetOpen" x-cloak class="fixed inset-0 z-[300] flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="resetOpen = false">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" @click.outside="resetOpen = false">
            <h3 class="text-lg font-bold text-slate-900">Reset homepage to defaults?</h3>
            <p class="mt-2 text-sm text-slate-500">This restores the default section layout and content. Your changes stay in the editor until you press Publish.</p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="resetOpen = false" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium">Cancel</button>
                <button type="button" @click="confirmReset()" class="rounded-full bg-brand-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-green-600">Reset</button>
            </div>
        </div>
    </div>

    {{-- Hero slide editor --}}
    <div x-show="slideEditing" x-cloak class="fixed inset-0 z-[300] flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="slideEditing = null">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-bold text-slate-900" x-text="slideIsNew ? 'Add Hero Slide' : 'Edit Hero Slide'"></h3>
            <div x-show="slideEditing" class="mt-4 space-y-4">
                    <div class="rounded-md border border-slate-200 p-3">
                        <p class="mb-3 text-sm font-semibold">Responsive Images (URL)</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <template x-for="field in ['desktopImage','tabletImage','mobileImage']" :key="field">
                                <div>
                                    <label class="text-[10px] font-bold uppercase text-slate-500" x-text="field === 'desktopImage' ? 'Desktop (16:6)' : (field === 'tabletImage' ? 'Tablet' : 'Mobile (4:5)')"></label>
                                    <input type="url" class="mt-1 w-full rounded-xl border border-slate-200 px-2 py-1.5 text-xs" :placeholder="field === 'desktopImage' ? 'Required' : 'Optional'" x-model="slideEditing[field]">
                                </div>
                            </template>
                        </div>
                        <p x-show="!slideEditing.desktopImage" class="mt-2 text-xs text-red-600">Desktop image is required</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div><label class="text-xs font-bold">Badge</label><input type="text" class="mt-1 w-full rounded-xl border px-2.5 py-2 text-xs" x-model="slideEditing.badge"></div>
                        <div><label class="text-xs font-bold">Title</label><input type="text" maxlength="120" class="mt-1 w-full rounded-xl border px-2.5 py-2 text-xs" x-model="slideEditing.title"></div>
                        <div><label class="text-xs font-bold">Subtitle</label><input type="text" class="mt-1 w-full rounded-xl border px-2.5 py-2 text-xs" x-model="slideEditing.subtitle"></div>
                        <div><label class="text-xs font-bold">Description</label><input type="text" class="mt-1 w-full rounded-xl border px-2.5 py-2 text-xs" x-model="slideEditing.description"></div>
                        <div><label class="text-xs font-bold">Primary button text</label><input type="text" class="mt-1 w-full rounded-xl border px-2.5 py-2 text-xs" x-model="slideEditing.primaryButtonText"></div>
                        <div><label class="text-xs font-bold">Primary button link</label><input type="text" class="mt-1 w-full rounded-xl border px-2.5 py-2 text-xs" x-model="slideEditing.primaryButtonUrl"></div>
                        <div><label class="text-xs font-bold">Secondary button text</label><input type="text" class="mt-1 w-full rounded-xl border px-2.5 py-2 text-xs" x-model="slideEditing.secondaryButtonText"></div>
                        <div><label class="text-xs font-bold">Secondary button link</label><input type="text" class="mt-1 w-full rounded-xl border px-2.5 py-2 text-xs" x-model="slideEditing.secondaryButtonUrl"></div>
                        <div>
                            <label class="text-xs font-bold">Text alignment</label>
                            <select class="mt-1 w-full rounded-xl border px-2 py-2 text-xs" x-model="slideEditing.alignment">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold">Fallback background color</label>
                            <input type="color" class="mt-1 h-9 w-full rounded-xl border" x-model="slideEditing.backgroundColor">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-end gap-6">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" class="rounded text-brand-green-600" :checked="slideEditing.overlay !== false" @change="slideEditing.overlay = $event.target.checked">
                            Dark overlay
                        </label>
                        <div x-show="slideEditing.overlay !== false">
                            <label class="text-xs font-bold">Overlay strength · <span x-text="Math.round((slideEditing.overlayOpacity ?? 0.55) * 100)"></span>%</label>
                            <input type="range" min="0" max="1" step="0.05" class="mt-1 block w-56" x-model.number="slideEditing.overlayOpacity">
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" class="rounded text-brand-green-600" :checked="slideEditing.status === 'active'" @change="slideEditing.status = $event.target.checked ? 'active' : 'inactive'">
                            Active
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2 border-t border-slate-100 pt-4">
                <button type="button" @click="slideEditing = null" class="rounded-full border border-slate-200 px-4 py-2 text-sm">Cancel</button>
                <button type="button" @click="saveSlide()" class="rounded-full bg-brand-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-green-600">Save</button>
            </div>
        </div>
    </div>

    {{-- Delete slide confirm --}}
    <div x-show="deleteSlideTarget" x-cloak class="fixed inset-0 z-[301] flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
            <h3 class="font-bold text-slate-900">Delete slide?</h3>
            <p class="mt-2 text-sm text-slate-500">This slide will be removed from the homepage carousel.</p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="deleteSlideTarget = null" class="rounded-full border px-4 py-2 text-sm">Cancel</button>
                <button type="button" @click="confirmDeleteSlide()" class="rounded-full bg-red-600 px-4 py-2 text-sm font-medium text-white">Delete</button>
            </div>
        </div>
    </div>

    <form x-ref="publishForm" method="POST" :action="saveUrl" class="hidden">
        @csrf
        <input type="hidden" name="config_json" :value="JSON.stringify(config)">
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('homepageBuilder', (opts) => ({
        config: structuredClone(opts.initialConfig),
        categories: opts.categories || [],
        subscribers: opts.subscribers || [],
        sectionMeta: opts.sectionMeta || {},
        sectionOrder: opts.sectionOrder || [],
        iconOptions: opts.iconOptions || [],
        saveUrl: opts.saveUrl,
        resetUrl: opts.resetUrl,
        csrf: opts.csrf,
        tab: 'layout',
        dirty: false,
        saving: false,
        publishedBaseline: '',
        resetOpen: false,
        openSections: {},
        sortable: null,
        slideEditing: null,
        slideIsNew: false,
        deleteSlideTarget: null,

        init() {
            this.publishedBaseline = JSON.stringify(this.config);
            this.$watch('config', () => this.checkDirty(), { deep: true });
            this.$watch('tab', (v) => {
                if (v === 'layout') this.$nextTick(() => this.initSortable());
            });
            this.$nextTick(() => this.initSortable());
        },

        checkDirty() {
            this.dirty = JSON.stringify(this.config) !== this.publishedBaseline;
        },

        setTab(name) {
            this.tab = name;
        },

        isKnownType(type) {
            return this.sectionOrder.includes(type);
        },

        knownSections() {
            return this.config.sections.filter((s) => this.isKnownType(s.type));
        },

        unknownSections() {
            return this.config.sections.filter((s) => !this.isKnownType(s.type));
        },

        sectionCanLimit(type) {
            return !['hero', 'trust_strip', 'promo_banner', 'why_choose_us', 'newsletter'].includes(type);
        },

        updateSection(id, patch) {
            this.config.sections = this.config.sections.map((s) => (s.id === id ? { ...s, ...patch } : s));
        },

        toggleSectionOpen(id) {
            this.openSections[id] = !this.openSections[id];
        },

        onCategoryPick(sectionId, value) {
            if (value === 'none') {
                this.updateSection(sectionId, { categoryId: null, categorySlug: null });
                return;
            }
            const cat = this.categories.find((c) => String(c.id) === value);
            if (!cat) return;
            const section = this.config.sections.find((s) => s.id === sectionId);
            const patch = { categoryId: cat.id, categorySlug: cat.slug };
            if (section && !(section.title || '').trim()) patch.title = cat.name;
            this.updateSection(sectionId, patch);
        },

        clampLimit(v) {
            const n = Number(v) || 12;
            return Math.max(1, Math.min(24, n));
        },

        addCategoryProducts() {
            const id = 'category_products_' + Date.now();
            this.config.sections = [...this.config.sections, { id, type: 'category_products', enabled: true, title: '', limit: 6 }];
        },

        removeSection(id) {
            this.config.sections = this.config.sections.filter((s) => s.id !== id);
        },

        initSortable() {
            const el = this.$refs.sectionList;
            if (!el || typeof Sortable === 'undefined') return;
            if (this.sortable) {
                this.sortable.destroy();
                this.sortable = null;
            }
            this.sortable = Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                draggable: '[data-section-id]',
                onEnd: (evt) => {
                    if (evt.oldIndex === evt.newIndex) return;
                    const known = this.knownSections();
                    const [moved] = known.splice(evt.oldIndex, 1);
                    known.splice(evt.newIndex, 0, moved);
                    const unknown = this.unknownSections();
                    this.config.sections = [...known, ...unknown];
                },
            });
        },

        publish() {
            if (!this.dirty || this.saving) return;
            this.saving = true;
            this.$refs.publishForm.submit();
        },

        async confirmReset() {
            this.resetOpen = false;
            try {
                const res = await fetch(this.resetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                });
                if (!res.ok) throw new Error('Reset failed');
                const cfg = await res.json();
                this.config = cfg;
                this.dirty = true;
            } catch (e) {
                alert(e.message || 'Reset failed');
            }
        },

        slideUid() {
            return 'slide-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 7);
        },

        blankSlide() {
            return {
                id: this.slideUid(),
                desktopImage: '',
                status: 'active',
                priority: 0,
                alignment: 'left',
                overlay: true,
                overlayOpacity: 0.55,
                backgroundColor: '#0f172a',
            };
        },

        openSlideCreate() {
            this.slideIsNew = true;
            this.slideEditing = this.blankSlide();
        },

        openSlideEdit(slide) {
            this.slideIsNew = false;
            this.slideEditing = structuredClone(slide);
        },

        isValidButtonUrl(url) {
            if (!url || !String(url).trim()) return true;
            const value = String(url).trim();
            if (value.startsWith('/') || value.startsWith('#')) return true;
            try {
                const parsed = new URL(value);
                return ['http:', 'https:', 'tel:', 'mailto:'].includes(parsed.protocol);
            } catch {
                return false;
            }
        },

        saveSlide() {
            if (!this.slideEditing) return;
            if (!this.slideEditing.desktopImage) {
                alert('A desktop image is required');
                return;
            }
            if ((this.slideEditing.title || '').length > 120) {
                alert('Title must be 120 characters or fewer');
                return;
            }
            if (!this.isValidButtonUrl(this.slideEditing.primaryButtonUrl)) {
                alert('Primary button link must be a valid URL or internal path');
                return;
            }
            if (!this.isValidButtonUrl(this.slideEditing.secondaryButtonUrl)) {
                alert('Secondary button link must be a valid URL or internal path');
                return;
            }
            if (this.slideIsNew) {
                const slide = { ...this.slideEditing, priority: this.config.heroSlides.length + 1 };
                this.config.heroSlides = [...this.config.heroSlides, slide];
            } else {
                this.config.heroSlides = this.config.heroSlides.map((s) => (s.id === this.slideEditing.id ? { ...this.slideEditing } : s));
            }
            this.slideEditing = null;
        },

        updateSlideField(id, patch) {
            this.config.heroSlides = this.config.heroSlides.map((s) => (s.id === id ? { ...s, ...patch } : s));
        },

        moveSlide(index, dir) {
            const target = index + dir;
            if (target < 0 || target >= this.config.heroSlides.length) return;
            const next = [...this.config.heroSlides];
            const [item] = next.splice(index, 1);
            next.splice(target, 0, item);
            this.config.heroSlides = next.map((s, i) => ({ ...s, priority: next.length - i }));
        },

        duplicateSlide(slide) {
            const copy = {
                ...structuredClone(slide),
                id: this.slideUid(),
                title: slide.title ? slide.title + ' (Copy)' : undefined,
                priority: this.config.heroSlides.length + 1,
            };
            this.config.heroSlides = [...this.config.heroSlides, copy];
        },

        confirmDeleteSlide() {
            if (!this.deleteSlideTarget) return;
            this.config.heroSlides = this.config.heroSlides.filter((s) => s.id !== this.deleteSlideTarget.id);
            this.deleteSlideTarget = null;
        },

        setContentItem(key, idx, patch) {
            this.config[key] = this.config[key].map((it, i) => (i === idx ? { ...it, ...patch } : it));
        },

        addContentItem(key) {
            const icon = this.iconOptions[0] || undefined;
            this.config[key] = [...(this.config[key] || []), { icon, title: '', text: '' }];
        },

        removeContentItem(key, idx) {
            this.config[key] = this.config[key].filter((_, i) => i !== idx);
        },

        flashStartLocal() {
            const s = this.config.flashSaleWindow?.start;
            return s ? String(s).slice(0, 16) : '';
        },

        flashEndLocal() {
            const s = this.config.flashSaleWindow?.end;
            return s ? String(s).slice(0, 16) : '';
        },

        addPopularSearch() {
            const term = window.prompt('Enter a popular search term');
            if (!term || !term.trim()) return;
            const t = term.trim();
            if (this.config.popularSearches.includes(t)) return;
            this.config.popularSearches = [...this.config.popularSearches, t].slice(0, 12);
        },

        removePopularSearch(term) {
            this.config.popularSearches = this.config.popularSearches.filter((t) => t !== term);
        },

        formatDate(iso) {
            if (!iso) return '—';
            try {
                return new Date(iso).toLocaleDateString();
            } catch {
                return iso;
            }
        },
    }));
});
</script>
@endpush
