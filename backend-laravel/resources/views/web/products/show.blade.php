@extends('layouts.app')

@php
    $title = ($product['title'] ?? 'Product') . ' | Mama Bazar';
    $images = is_array($product['images'] ?? null) ? $product['images'] : [];
    $variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];
    $optionGroups = is_array($product['optionGroups'] ?? null) ? $product['optionGroups'] : [];
    $specs = is_array($product['specs'] ?? null) ? $product['specs'] : [];
    $features = is_array($product['features'] ?? null) ? $product['features'] : [];
    $tags = is_array($product['tags'] ?? null) ? $product['tags'] : [];
    $reviewsList = isset($reviews) ? $reviews : collect();
    $isLoggedIn = auth()->check();

    $pdpPayload = [
        'id' => (int) ($product['id'] ?? 0),
        'title' => $product['title'] ?? '',
        'slug' => $product['slug'] ?? '',
        'price' => (float) ($product['price'] ?? 0),
        'salePrice' => isset($product['salePrice']) ? (float) $product['salePrice'] : null,
        'discount' => (float) ($product['discount'] ?? 0),
        'stock' => (int) ($product['stock'] ?? 0),
        'sku' => $product['sku'] ?? null,
        'images' => $images,
        'unlimitedStock' => (bool) ($product['unlimitedStock'] ?? false),
        'backorder' => (bool) ($product['backorder'] ?? false),
        'lowStockAlert' => (int) ($product['lowStockAlert'] ?? 5),
        'minOrder' => max(1, (int) ($product['minOrder'] ?? 1)),
        'maxOrder' => isset($product['maxOrder']) ? (int) $product['maxOrder'] : null,
        'variants' => $variants,
        'optionGroups' => $optionGroups,
        'colorOptions' => $product['colorOptions'] ?? [],
        'checkoutUrl' => route('checkout'),
    ];
@endphp

@section('content')
<div
    class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
    x-data="productDetail(@js($pdpPayload))"
    x-cloak
>
    {{-- Breadcrumb --}}
    <nav aria-label="Breadcrumb" class="mb-8 flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-600">
        <a class="transition hover:text-brand-green-600" href="{{ route('home') }}">Home</a>
        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @if(!empty($product['category']))
            <a class="transition hover:text-brand-green-600" href="{{ route('shop', ['category' => $product['category']['slug']]) }}">{{ $product['category']['name'] }}</a>
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @endif
        @if(!empty($product['subCategory']))
            <a class="transition hover:text-brand-green-600" href="{{ route('shop', ['category' => $product['subCategory']['slug']]) }}">{{ $product['subCategory']['name'] }}</a>
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @endif
        @if(!empty($product['childCategory']))
            <a class="transition hover:text-brand-green-600" href="{{ route('shop', ['category' => $product['childCategory']['slug']]) }}">{{ $product['childCategory']['name'] }}</a>
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @endif
        <span class="text-slate-700 truncate max-w-[12rem] sm:max-w-xs">{{ \Illuminate\Support\Str::limit($product['title'] ?? '', 40) }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-14">
        {{-- Gallery --}}
        <div class="lg:col-span-6">
            <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-[#F8F8F8]">
                <button
                    type="button"
                    class="group relative block w-full cursor-zoom-in focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-green-500 focus-visible:ring-inset"
                    @click="galleryOpen = true"
                    aria-label="Open product image"
                >
                    <img
                        :src="currentImage"
                        alt="{{ $product['title'] }} - Mama Bazar"
                        class="aspect-square w-full object-contain"
                        loading="eager"
                    >
                    <span class="pointer-events-none absolute bottom-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 opacity-0 shadow-sm transition group-hover:opacity-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    </span>
                </button>
            </div>

            <template x-if="galleryImages.length > 1">
                <div class="mt-4 grid grid-cols-4 gap-3">
                    <template x-for="(img, idx) in galleryImages" :key="img + '-' + idx">
                        <button
                            type="button"
                            class="overflow-hidden rounded-xl transition"
                            :class="activeImageIndex === idx && !variantImage ? 'ring-2 ring-brand-green-500 ring-offset-2' : 'opacity-70 hover:opacity-100'"
                            @click="selectGalleryImage(idx)"
                            :aria-label="'View image ' + (idx + 1)"
                        >
                            <img :src="img" :alt="'{{ addslashes($product['title'] ?? '') }} ' + (idx + 1)" class="aspect-square w-full object-cover" loading="lazy">
                        </button>
                    </template>
                </div>
            </template>

            @if(!empty($product['videoUrl']))
                <div class="mt-4">
                    <a
                        href="{{ $product['videoUrl'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-brand-green-500 hover:text-brand-green-600"
                    >
                        <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        Watch Video
                    </a>
                </div>
            @endif
        </div>

        {{-- Buy box --}}
        <div class="flex flex-col lg:col-span-6">
            <div class="flex flex-wrap items-center gap-2">
                @if(!empty($product['brandInfo']['name']))
                    <a href="{{ route('shop', ['brand' => $product['brandInfo']['slug'] ?? '']) }}" class="rounded-full bg-brand-green-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-brand-green-700 hover:bg-brand-green-100">
                        {{ $product['brandInfo']['name'] }}
                    </a>
                @elseif(!empty($product['brand']))
                    <span class="rounded-full bg-brand-green-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-brand-green-700">{{ $product['brand'] }}</span>
                @endif
                @if(!empty($product['isNewArrival']))
                    <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-[11px] font-bold text-emerald-600">New Arrival</span>
                @endif
                @if(!empty($product['isBestSeller']))
                    <span class="rounded-full bg-amber-500/10 px-3 py-1 text-[11px] font-bold text-amber-600">Best Seller</span>
                @endif
                @if(!empty($product['isLimitedEdition']))
                    <span class="rounded-full bg-violet-500/10 px-3 py-1 text-[11px] font-bold text-violet-600">Limited Edition</span>
                @endif
                @if(!empty($product['isOfficial']))
                    <span class="rounded-full bg-blue-500/10 px-3 py-1 text-[11px] font-bold text-blue-600">Official</span>
                @endif
                <template x-if="showDiscount">
                    <span class="inline-flex items-center gap-1 rounded-full bg-brand-orange-50 px-3 py-1 text-[11px] font-bold text-brand-orange-600">
                        Save <span x-text="discountPercent + '%'"></span>
                    </span>
                </template>
            </div>

            <h1 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-4xl">
                {{ $product['title'] }}
            </h1>

            <div class="mt-4 flex items-center gap-3">
                <div class="flex items-center gap-0.5" aria-label="Rating {{ number_format((float)($product['rating'] ?? 0), 1) }}">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="h-4 w-4 {{ $i <= round((float)($product['rating'] ?? 0)) ? 'text-brand-orange-500' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endfor
                </div>
                <span class="text-sm font-semibold text-slate-700">
                    {{ !empty($product['rating']) ? number_format((float)$product['rating'], 1) : 'No rating' }}
                </span>
                <span class="text-sm text-slate-600">({{ (int)($product['reviewCount'] ?? 0) }} reviews)</span>
            </div>

            <div class="mt-6 flex flex-wrap items-end gap-3">
                <template x-if="showSelectOptionsPrice">
                    <p class="text-2xl font-bold text-slate-600">Select options to see price</p>
                </template>
                <template x-if="!showSelectOptionsPrice">
                    <div class="flex flex-wrap items-end gap-3">
                        <p class="text-4xl font-black tracking-tight text-slate-900" x-text="formatPrice(finalPrice)"></p>
                        <template x-if="showOriginalPrice && displayPrice">
                            <p class="pb-1 text-lg text-slate-500 line-through" x-text="formatPrice(displayPrice)"></p>
                        </template>
                    </div>
                </template>
                <template x-if="activeVariant && activeVariant.sku">
                    <span class="pb-1 text-xs text-slate-500">SKU: <span x-text="activeVariant.sku"></span></span>
                </template>
            </div>

            @if(!empty($product['shortDescription']) || !empty($product['description']))
                <p class="mt-4 text-[15px] leading-8 text-slate-600">
                    {{ $product['shortDescription'] ?? \Illuminate\Support\Str::limit(strip_tags($product['description'] ?? ''), 220) }}
                </p>
            @endif

            {{-- Generic option groups (Storage / Color / RAM / Size / Strap / …) --}}
            <template x-for="group in optionGroups" :key="group.key">
                <div class="mt-7">
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        <span x-text="group.label"></span>:
                        <span class="text-slate-900" x-text="selected[group.key] || 'Select'"></span>
                    </p>

                    <div class="flex flex-wrap gap-2" x-show="group.type === 'color'">
                        <template x-for="opt in group.values" :key="group.key + '-' + opt.name">
                            <button
                                type="button"
                                class="flex h-11 w-11 items-center justify-center rounded-full border-2 transition"
                                :class="selected[group.key] === opt.name
                                    ? 'border-brand-green-500 ring-2 ring-brand-green-200'
                                    : (isOptionAvailable(group.key, opt.name) ? 'border-slate-200 hover:border-slate-300' : 'border-slate-100 opacity-40 cursor-not-allowed')"
                                :style="opt.value ? { backgroundColor: opt.value } : {}"
                                :title="opt.name"
                                :aria-label="'Select ' + group.label + ' ' + opt.name"
                                :disabled="!isOptionAvailable(group.key, opt.name)"
                                @click="selectOption(group.key, opt.name)"
                            >
                                <span x-show="!opt.value" class="text-[9px] font-bold text-slate-500" x-text="opt.name.slice(0, 3)"></span>
                            </button>
                        </template>
                    </div>

                    <div class="flex flex-wrap gap-2" x-show="group.type !== 'color'">
                        <template x-for="opt in group.values" :key="group.key + '-' + opt.name">
                            <button
                                type="button"
                                class="flex h-11 min-w-[3rem] items-center justify-center rounded-lg border px-4 transition"
                                :class="selected[group.key] === opt.name
                                    ? 'border-brand-green-500 bg-brand-green-50 text-brand-green-700 ring-1 ring-brand-green-200'
                                    : (!isOptionAvailable(group.key, opt.name)
                                        ? 'border-slate-100 text-slate-300 cursor-not-allowed line-through'
                                        : (isOptionOos(group.key, opt.name)
                                            ? 'border-amber-200 text-amber-600 bg-amber-50'
                                            : 'border-slate-200 text-slate-700 hover:border-slate-300'))"
                                :disabled="!isOptionAvailable(group.key, opt.name)"
                                :aria-label="'Select ' + group.label + ' ' + opt.name"
                                @click="selectOption(group.key, opt.name)"
                            >
                                <span class="text-sm font-bold" x-text="opt.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="needsOptions && allOptionsSelected && variantOutOfStock">
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-700">
                    This variant is currently out of stock
                </div>
            </template>

            <div class="mt-7 flex items-center gap-5">
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Quantity</p>
                    <div class="flex items-center gap-1 rounded-full border border-slate-200 px-2 py-1.5">
                        <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-600 transition hover:bg-slate-100" :disabled="qty <= minQty" @click="qty = Math.max(minQty, qty - 1)" aria-label="Decrease quantity">−</button>
                        <span class="w-10 text-center font-bold" x-text="qty"></span>
                        <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-600 transition hover:bg-slate-100" :disabled="outOfStock || variantOutOfStock || qty >= maxQty" @click="qty = Math.min(maxQty, qty + 1)" aria-label="Increase quantity">+</button>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Availability</p>
                    <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-bold" :class="stockLabel.className" x-text="stockLabel.text"></span>
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-3 md:flex-row">
                <button
                    type="button"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-full border-2 border-brand-orange-400 bg-white px-6 py-4 text-sm font-bold text-brand-green-600 transition hover:bg-brand-orange-50 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="disableAddToCart"
                    @click="addToCart(false)"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Add to Cart
                </button>
                <button
                    type="button"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-brand-orange-500 px-6 py-4 text-sm font-bold text-white transition hover:bg-brand-orange-600 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="disableAddToCart"
                    @click="addToCart(true)"
                >
                    Buy Now
                </button>
            </div>

            <div class="mt-4 flex gap-3">
                <button
                    type="button"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-full border px-5 py-3 text-sm font-bold transition"
                    :class="$store.cart.hasWishlist(product.id) ? 'border-brand-orange-300 bg-brand-orange-50 text-brand-orange-700' : 'border-slate-200 text-slate-700 hover:border-brand-orange-300'"
                    @click="$store.cart.toggleWishlist(product.id)"
                >
                    <svg class="h-4 w-4" :class="$store.cart.hasWishlist(product.id) && 'fill-brand-orange-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <span x-text="$store.cart.hasWishlist(product.id) ? 'Saved' : 'Wishlist'"></span>
                </button>
                <button
                    type="button"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-full border px-5 py-3 text-sm font-bold transition"
                    :class="$store.cart.hasCompare(product.id) ? 'border-brand-green-400 bg-brand-green-50 text-brand-green-700' : 'border-slate-200 text-slate-700 hover:border-brand-green-400'"
                    @click="$store.cart.toggleCompare(product.id)"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <span x-text="$store.cart.hasCompare(product.id) ? 'Comparing' : 'Compare'"></span>
                </button>
            </div>

            <div class="mt-6 rounded-[18px] border border-slate-100 bg-slate-50/50 p-4 text-sm">
                @if(!empty($product['warranty']))
                    <div class="flex items-center justify-between border-b border-slate-100 py-2.5">
                        <span class="font-semibold text-slate-500">Warranty</span>
                        <span class="font-bold text-slate-900">{{ $product['warranty'] }}</span>
                    </div>
                @endif
                @if(!empty($product['countryOfOrigin']))
                    <div class="flex items-center justify-between border-b border-slate-100 py-2.5">
                        <span class="font-semibold text-slate-500">Country of Origin</span>
                        <span class="font-bold text-slate-900">{{ $product['countryOfOrigin'] }}</span>
                    </div>
                @endif
                <template x-if="(!activeVariant || !activeVariant.sku) && product.sku">
                    <div class="flex items-center justify-between border-b border-slate-100 py-2.5 last:border-0">
                        <span class="font-semibold text-slate-500">SKU</span>
                        <span class="font-bold text-slate-900" x-text="product.sku"></span>
                    </div>
                </template>
                @if(!empty($product['brandInfo']['name']) || !empty($product['brand']))
                    <div class="flex items-center justify-between border-b border-slate-100 py-2.5">
                        <span class="font-semibold text-slate-500">Brand</span>
                        <span class="font-bold text-slate-900">{{ $product['brandInfo']['name'] ?? $product['brand'] }}</span>
                    </div>
                @endif
                @if(!empty($product['category']))
                    <div class="flex items-center justify-between border-b border-slate-100 py-2.5">
                        <span class="font-semibold text-slate-500">Category</span>
                        <a href="{{ route('shop', ['category' => $product['category']['slug']]) }}" class="font-bold text-brand-green-700 hover:underline">{{ $product['category']['name'] }}</a>
                    </div>
                @endif
                @if(!empty($product['collection']))
                    <div class="flex items-center justify-between py-2.5">
                        <span class="font-semibold text-slate-500">Collection</span>
                        <span class="font-bold text-slate-900">{{ $product['collection']['name'] }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Description / Specs / Features / Tags --}}
    <section class="mt-16">
        <div class="rounded-[18px] border border-slate-100 bg-white p-6 shadow-soft sm:p-8">
            @if(!empty($product['description']))
                <div class="mb-8">
                    <h2 class="mb-4 text-xl font-extrabold text-slate-900">Description</h2>
                    <div class="product-description product-description-table-wrap">
                        {!! \App\Services\HtmlSanitizer::forDisplay($product['description']) !!}
                    </div>
                </div>
            @endif

            @if(count($specs) > 0)
                <div class="mb-8">
                    <h2 class="mb-4 text-xl font-extrabold text-slate-900">Specifications</h2>
                    <div class="overflow-hidden rounded-xl border border-slate-100">
                        @foreach($specs as $i => $spec)
                            <div class="flex items-center {{ $i % 2 === 0 ? 'bg-slate-50' : 'bg-white' }}">
                                <span class="w-1/3 px-4 py-3 text-sm font-semibold text-slate-500">{{ $spec['label'] }}</span>
                                <span class="w-2/3 px-4 py-3 text-sm font-bold text-slate-900">{{ $spec['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(count($features) > 0)
                <div class="mb-8">
                    <h2 class="mb-4 text-xl font-extrabold text-slate-900">Features</h2>
                    <ul class="space-y-2">
                        @foreach($features as $feature)
                            <li class="flex items-start gap-2 text-sm text-slate-600">
                                <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-green-500"></span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(count($tags) > 0)
                <div>
                    <h2 class="mb-3 text-xl font-extrabold text-slate-900">Tags</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach($tags as $tag)
                            <a href="{{ route('shop', ['q' => $tag]) }}" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 hover:border-brand-green-300 hover:text-brand-green-700">{{ $tag }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if(!empty($product['returnPolicy']))
        <section class="mt-6">
            <div class="rounded-[18px] border border-slate-100 bg-white p-6 shadow-soft">
                <h2 class="mb-3 flex items-center gap-2 text-xl font-extrabold text-slate-900">Return Policy</h2>
                <p class="text-sm leading-7 text-slate-600">{{ $product['returnPolicy'] }}</p>
            </div>
        </section>
    @endif

    {{-- Reviews --}}
    <section class="mt-16" id="reviews">
        <div class="mb-8">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-brand-green-600">Customer feedback</p>
            <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">
                Reviews ({{ (int)($product['reviewCount'] ?? $reviewsList->count()) }})
            </h2>
        </div>

        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-3">
            <div>
                <h3 class="mb-4 text-lg font-extrabold text-slate-900">Write a review</h3>
                @if($isLoggedIn)
                    <form method="POST" action="{{ route('products.review', $product['slug']) }}" class="rounded-[18px] border border-slate-100 bg-white p-5 shadow-soft" x-data="{ rating: {{ (int) old('rating', 0) }} }">
                        @csrf
                        <input type="hidden" name="rating" :value="rating">
                        <div class="mb-4 flex items-center gap-1">
                            @for($value = 1; $value <= 5; $value++)
                                <button type="button" class="transition hover:scale-110" @click="rating = {{ $value }}" aria-label="Rate {{ $value }} stars">
                                    <svg class="h-5 w-5" :class="rating >= {{ $value }} ? 'text-brand-orange-500 fill-brand-orange-500' : 'text-slate-300'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </button>
                            @endfor
                        </div>
                        @error('rating')
                            <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <input
                            name="title"
                            type="text"
                            value="{{ old('title') }}"
                            placeholder="Review title (optional)"
                            class="mb-3 w-full rounded-full border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-brand-green-500"
                        >
                        <textarea
                            name="comment"
                            required
                            maxlength="5000"
                            rows="4"
                            placeholder="Share your experience with this product..."
                            class="mb-3 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-green-500"
                        >{{ old('comment') }}</textarea>
                        @error('comment')
                            <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="w-full rounded-full bg-brand-green-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-green-700">
                            Submit Review
                        </button>
                    </form>
                @else
                    <div class="rounded-[18px] border border-slate-100 bg-white p-6 text-center shadow-soft">
                        <p class="text-sm text-slate-500">Please log in to write a review.</p>
                        <a href="{{ route('login') }}" class="mt-4 inline-block rounded-full bg-brand-green-600 px-6 py-2.5 text-sm font-bold text-white">Login</a>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-2 space-y-4">
                @forelse($reviewsList as $review)
                    <article class="rounded-[18px] border border-slate-100 bg-white p-5 shadow-soft">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-green-50 text-sm font-black text-brand-green-700">
                                    {{ strtoupper(substr($review->customer_name ?: 'A', 0, 1)) }}
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $review->customer_name ?: 'Anonymous' }}</p>
                                    <p class="text-xs text-slate-500">{{ optional($review->created_at)->format('F j, Y') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="h-3.5 w-3.5 {{ $i <= (int)$review->rating ? 'text-brand-orange-500' : 'text-slate-300' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </div>
                        </div>
                        @if(!empty($review->title))
                            <h4 class="mt-4 text-sm font-extrabold text-slate-900">{{ $review->title }}</h4>
                        @endif
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $review->comment }}</p>
                    </article>
                @empty
                    <div class="flex flex-col items-center justify-center rounded-[18px] border border-dashed border-slate-200 bg-white py-16 text-center">
                        <p class="font-extrabold text-lg text-slate-900">No reviews yet</p>
                        <p class="mt-2 text-sm text-slate-500">Be the first to share your experience with this product.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Related --}}
    @if(!empty($relatedProducts) && count($relatedProducts) > 0)
        <section class="mt-20">
            <div class="mb-8 flex items-end justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-brand-green-600">You may also like</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Related Products</h2>
                </div>
                @if(!empty($product['category']['slug']))
                    <a class="text-sm font-bold text-brand-green-600 hover:underline" href="{{ route('shop', ['category' => $product['category']['slug']]) }}">View more</a>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4 sm:gap-5">
                @foreach(array_slice($relatedProducts, 0, 4) as $index => $rel)
                    <x-product-card :product="$rel" :index="$index" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Lightbox --}}
    <div
        x-show="galleryOpen"
        x-transition.opacity
        class="fixed inset-0 z-[400] flex flex-col bg-slate-950/95 backdrop-blur-sm"
        @keydown.escape.window="galleryOpen = false"
        @click="galleryOpen = false"
        role="dialog"
        aria-modal="true"
        style="display: none;"
    >
        <div class="flex shrink-0 items-center justify-end p-4">
            <button type="button" class="flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white" @click.stop="galleryOpen = false" aria-label="Close image viewer">×</button>
        </div>
        <div class="flex min-h-0 flex-1 items-center justify-center px-4 pb-4 sm:px-16" @click.stop>
            <img :src="currentImage" alt="{{ $product['title'] }}" class="max-h-full max-w-full object-contain">
        </div>
    </div>
</div>

@endsection
