@extends('layouts.app')

@php
    $title = ($product['title'] ?? 'Product') . ' | Mama Bazar';
    $images = is_array($product['images'] ?? null) ? $product['images'] : [];
    $variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];
    $optionGroups = is_array($product['optionGroups'] ?? null) ? $product['optionGroups'] : [];
    $specs = is_array($product['specs'] ?? null) ? $product['specs'] : [];
    $features = is_array($product['features'] ?? null) ? $product['features'] : [];
    $tags = \App\Services\ProductService::normalizeTags($product['tags'] ?? []);
    $reviewsList = isset($reviews) ? $reviews : collect();
    $isLoggedIn = auth()->check();
    $reviewSummary = $reviewSummary ?? ['average' => 0, 'count' => 0, 'breakdown' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];
    $userReview = $userReview ?? null;
    $canReview = $canReview ?? false;
    $isVerifiedBuyer = $isVerifiedBuyer ?? false;

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
    class="store-container py-6 sm:py-8"
    x-data="productDetail(@js($pdpPayload))"
    x-cloak
>
    {{-- ── Breadcrumb ── --}}
    <nav aria-label="Breadcrumb" class="mb-6 flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-500">
        <a class="transition hover:text-[#e2006a]" href="{{ route('home') }}">Home</a>
        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @if(!empty($product['category']))
            <a class="transition hover:text-[#e2006a]" href="{{ route('shop', ['category' => $product['category']['slug']]) }}">{{ $product['category']['name'] }}</a>
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @endif
        @if(!empty($product['subCategory']))
            <a class="transition hover:text-[#e2006a]" href="{{ route('shop', ['category' => $product['subCategory']['slug']]) }}">{{ $product['subCategory']['name'] }}</a>
            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        @endif
        <span class="text-slate-800 truncate max-w-xs">{{ $product['title'] ?? '' }}</span>
    </nav>

    {{-- ── Main Product Card (Gallery Left + Buy Box Right) ── --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-8 shadow-xs">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">

            {{-- ── Gallery (Left Column) ── --}}
            <div class="lg:col-span-5 flex flex-col gap-4">
                {{-- Main Image --}}
                <div class="relative aspect-square w-full rounded-2xl border border-slate-100 bg-[#fafafa] flex items-center justify-center p-6 overflow-hidden group">
                    <img
                        :src="currentImage"
                        alt="{{ $product['title'] }}"
                        class="max-h-full max-w-full object-contain transition-transform duration-300 group-hover:scale-105"
                        loading="eager"
                        fetchpriority="high"
                        width="600"
                        height="600"
                        onerror="this.onerror=null;this.src='/brandlogo.png';"
                    >

                    {{-- Zoom Button --}}
                    <button
                        type="button"
                        class="absolute bottom-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-sm transition hover:bg-white focus:outline-none"
                        @click="galleryOpen = true"
                        aria-label="Zoom image"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                    </button>
                </div>

                {{-- Thumbnails --}}
                <template x-if="galleryImages.length > 1">
                    <div class="flex gap-2.5 overflow-x-auto pb-1">
                        <template x-for="(img, idx) in galleryImages" :key="img + '-' + idx">
                            <button
                                type="button"
                                class="w-18 h-18 sm:w-20 sm:h-20 shrink-0 rounded-xl border-2 p-1 bg-white transition overflow-hidden"
                                :class="activeImageIndex === idx && !variantImage ? 'border-[#e2006a]' : 'border-slate-200 opacity-70 hover:opacity-100'"
                                @click="selectGalleryImage(idx)"
                                :aria-label="'View image ' + (idx + 1)"
                            >
                                <img :src="img" :alt="'Thumbnail ' + (idx + 1)" class="w-full h-full object-contain" loading="lazy" onerror="this.onerror=null;this.src='/brandlogo.png';">
                            </button>
                        </template>
                    </div>
                </template>
            </div>

            {{-- ── Buy Box (Right Column) ── --}}
            <div class="lg:col-span-7 flex flex-col gap-4">

                {{-- Top: Category / Brand Tag + Actions --}}
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-xs font-bold" style="color:#e2006a">
                        @if(!empty($product['category']['name']))
                            <span>{{ $product['category']['name'] }}</span>
                            <span>•</span>
                        @endif
                        <span>Brand: {{ $product['brandInfo']['name'] ?? $product['brand'] ?? 'Mama Bazar' }}</span>
                    </div>

                    {{-- Wishlist & Share buttons --}}
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            @click="$store.cart.toggleWishlist(product.id)"
                            aria-label="Toggle wishlist"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-pink-300 hover:text-[#e2006a]"
                            :style="$store.cart.hasWishlist(product.id) ? 'background:#e2006a; color:#fff; border-color:#e2006a' : ''"
                        >
                            <svg class="h-4 w-4" :class="$store.cart.hasWishlist(product.id) ? 'fill-current' : 'fill-none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </button>
                        <button
                            type="button"
                            onclick="if(navigator.share){navigator.share({title:document.title,url:window.location.href});}else{navigator.clipboard.writeText(window.location.href);alert('Link copied to clipboard!');}"
                            aria-label="Share product"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:border-pink-300 hover:text-[#e2006a]"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Product Title --}}
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                    {{ $product['title'] }}
                </h1>

                {{-- Short Description --}}
                @if(!empty($product['shortDescription']))
                    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed">
                        {{ $product['shortDescription'] }}
                    </p>
                @endif

                {{-- Rating & Reviews row --}}
                <div class="flex items-center gap-3 text-xs text-slate-500 pt-0.5">
                    <div class="flex items-center gap-1 text-amber-400">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="h-4 w-4 {{ $i <= round((float)($product['rating'] ?? 5)) ? 'fill-current' : 'text-slate-200 fill-current' }}" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                        <span class="font-bold text-slate-700 ml-1">({{ number_format((float)($product['rating'] ?? 5), 1) }})</span>
                    </div>
                    <span>•</span>
                    <a href="#reviews" class="hover:text-[#e2006a] transition font-medium">{{ (int)($product['reviewCount'] ?? 0) }} Reviews</a>
                    <span>•</span>
                    <span class="text-slate-400">54 sold</span>
                </div>

                {{-- Price Display --}}
                <div class="flex items-baseline gap-3 pt-2">
                    <template x-if="showSelectOptionsPrice">
                        <p class="text-xl font-bold text-slate-500">Select options to see price</p>
                    </template>
                    <template x-if="!showSelectOptionsPrice">
                        <div class="flex items-baseline gap-3">
                            <span class="text-3xl sm:text-4xl font-black" style="color:#e2006a" x-text="formatPrice(finalPrice)"></span>
                            <template x-if="showOriginalPrice && displayPrice">
                                <span class="text-lg text-slate-400 line-through font-medium" x-text="formatPrice(displayPrice)"></span>
                            </template>
                            <template x-if="showDiscount">
                                <span class="rounded px-2 py-0.5 text-xs font-bold text-white shadow-xs" style="background:#e2006a" x-text="'-' + discountPercent + '%'"></span>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="border-t border-slate-100 my-1"></div>

                {{-- Option Groups (Color / Size / etc.) --}}
                <template x-for="group in optionGroups" :key="group.key">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                            <span x-text="group.label + ':'"></span>
                            <span class="text-slate-900 font-extrabold" x-text="selected[group.key] || 'Select'"></span>
                        </div>

                        {{-- Color Swatches --}}
                        <div class="flex flex-wrap gap-2" x-show="group.type === 'color'">
                            <template x-for="opt in group.values" :key="group.key + '-' + opt.name">
                                <button
                                    type="button"
                                    class="h-8 w-8 rounded-full border-2 transition relative flex items-center justify-center"
                                    :class="selected[group.key] === opt.name
                                        ? 'ring-2 ring-offset-2'
                                        : (isOptionAvailable(group.key, opt.name) ? 'border-slate-300 hover:scale-105' : 'opacity-40 cursor-not-allowed')"
                                    :style="{
                                        backgroundColor: opt.value || '#ccc',
                                        borderColor: selected[group.key] === opt.name ? '#e2006a' : '#e2e8f0',
                                        '--tw-ring-color': '#e2006a'
                                    }"
                                    :title="opt.name"
                                    :disabled="!isOptionAvailable(group.key, opt.name)"
                                    @click="selectOption(group.key, opt.name)"
                                >
                                </button>
                            </template>
                        </div>

                        {{-- Size / Other option pills --}}
                        <div class="flex flex-wrap gap-2" x-show="group.type !== 'color'">
                            <template x-for="opt in group.values" :key="group.key + '-' + opt.name">
                                <button
                                    type="button"
                                    class="h-9 min-w-[42px] px-3.5 rounded-lg border text-xs font-bold transition flex items-center justify-center"
                                    :class="selected[group.key] === opt.name
                                        ? 'text-white shadow-xs'
                                        : (!isOptionAvailable(group.key, opt.name)
                                            ? 'border-slate-100 text-slate-300 cursor-not-allowed line-through'
                                            : 'border-slate-200 text-slate-700 hover:border-pink-300 hover:bg-pink-50')"
                                    :style="selected[group.key] === opt.name ? 'background:#e2006a; border-color:#e2006a' : ''"
                                    :disabled="!isOptionAvailable(group.key, opt.name)"
                                    @click="selectOption(group.key, opt.name)"
                                >
                                    <span x-text="opt.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Quantity Stepper & In-stock badge --}}
                <div class="flex items-center gap-4 pt-1">
                    <span class="text-xs font-bold text-slate-700">Quantity:</span>
                    <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-white">
                        <button type="button" class="w-8 h-8 flex items-center justify-center text-slate-600 hover:bg-slate-100 text-base font-bold" :disabled="qty <= minQty" @click="qty = Math.max(minQty, qty - 1)">−</button>
                        <span class="w-10 text-center text-xs font-bold" x-text="qty"></span>
                        <button type="button" class="w-8 h-8 flex items-center justify-center text-slate-600 hover:bg-slate-100 text-base font-bold" :disabled="outOfStock || variantOutOfStock || qty >= maxQty" @click="qty = Math.min(maxQty, qty + 1)">+</button>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-bold" :class="stockLabel.className" x-text="stockLabel.text"></span>
                </div>

                {{-- Action Buttons: Add to Cart (outline) + Buy Now (filled pink) --}}
                <div class="flex flex-col sm:flex-row gap-3 pt-3">
                    <button
                        type="button"
                        class="flex-1 py-3.5 px-6 rounded-xl border-2 font-extrabold text-xs sm:text-sm transition flex items-center justify-center gap-2 hover:bg-pink-50 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                        style="border-color:#e2006a; color:#e2006a"
                        :disabled="disableAddToCart"
                        @click="addToCart(false)"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span>Add to Cart</span>
                    </button>

                    <button
                        type="button"
                        class="flex-1 py-3.5 px-6 rounded-xl text-white font-extrabold text-xs sm:text-sm shadow-md transition hover:brightness-110 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 flex items-center justify-center gap-2"
                        style="background:#e2006a"
                        :disabled="disableAddToCart"
                        @click="addToCart(true)"
                    >
                        <span>Buy Now</span>
                    </button>
                </div>

                {{-- Seller / Store Box (matching reference image) --}}
                <div class="mt-3 rounded-xl border border-slate-100 bg-[#fffbfa] p-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full border border-pink-100 bg-white flex items-center justify-center p-1.5 shadow-xs shrink-0">
                            <img src="{{ $business['logo_url'] ?: '/brandlogo.png' }}" alt="{{ $business['business_name'] }}" class="h-full w-full object-contain">
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold text-slate-900">{{ $business['business_name'] }}</span>
                                <svg class="h-3.5 w-3.5 text-blue-500 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </div>
                            <span class="text-[11px] font-bold text-amber-500">★ 4.9 Verified Seller</span>
                        </div>
                    </div>
                    <a href="{{ route('shop') }}" class="text-xs font-bold text-[#e2006a] hover:underline shrink-0">
                        Visit Store
                    </a>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Tabs: Description & Reviews ── --}}
    <div class="mt-10 rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs" x-data="{ activeTab: 'description' }">
        {{-- Tab Headers --}}
        <div class="flex items-center gap-8 border-b border-slate-200 pb-3 text-sm font-bold">
            <button
                type="button"
                @click="activeTab = 'description'"
                class="relative pb-3 transition"
                :class="activeTab === 'description' ? 'text-[#e2006a]' : 'text-slate-500 hover:text-slate-800'"
            >
                Description
                <span x-show="activeTab === 'description'" class="absolute inset-x-0 bottom-0 h-0.5 rounded-full" style="background:#e2006a"></span>
            </button>
            <button
                type="button"
                @click="activeTab = 'reviews'"
                class="relative pb-3 transition"
                :class="activeTab === 'reviews' ? 'text-[#e2006a]' : 'text-slate-500 hover:text-slate-800'"
            >
                Reviews ({{ (int)($product['reviewCount'] ?? 0) }})
                <span x-show="activeTab === 'reviews'" class="absolute inset-x-0 bottom-0 h-0.5 rounded-full" style="background:#e2006a"></span>
            </button>
            @if(count($specs) > 0)
                <button
                    type="button"
                    @click="activeTab = 'specs'"
                    class="relative pb-3 transition"
                    :class="activeTab === 'specs' ? 'text-[#e2006a]' : 'text-slate-500 hover:text-slate-800'"
                >
                    Specifications
                    <span x-show="activeTab === 'specs'" class="absolute inset-x-0 bottom-0 h-0.5 rounded-full" style="background:#e2006a"></span>
                </button>
            @endif
        </div>

        {{-- Tab: Description --}}
        <div x-show="activeTab === 'description'" class="pt-6 space-y-6">
            @if(!empty($product['description']))
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 mb-2">Summary</h3>
                    <div class="product-description text-sm leading-relaxed text-slate-600">
                        {!! \App\Services\HtmlSanitizer::forDisplay($product['description']) !!}
                    </div>
                </div>
            @endif

            @if(count($features) > 0)
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 mb-3">Key Features</h3>
                    <ul class="space-y-2">
                        @foreach($features as $feature)
                            <li class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600">
                                <span class="w-1.5 h-1.5 rounded-full mt-2 shrink-0" style="background:#e2006a"></span>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(count($tags) > 0)
                <div class="pt-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Tags</h4>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($tags as $tag)
                            <a href="{{ route('shop', ['tag' => $tag]) }}" class="rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-600 hover:border-pink-300 hover:text-[#e2006a] transition">
                                #{{ $tag }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Tab: Reviews --}}
        <div x-show="activeTab === 'reviews'" class="pt-6" x-data="{ reviewModal: false, editMode: false, modalRating: 0 }">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Customer Reviews</h3>
                    <p class="text-xs text-slate-500">Based on {{ (int)($reviewSummary['count'] ?? 0) }} review{{ (int)($reviewSummary['count'] ?? 0) === 1 ? '' : 's' }}</p>
                </div>
                @if($canReview)
                    <button type="button" @click="editMode = false; modalRating = 0; reviewModal = true"
                            class="py-2.5 px-5 rounded-xl text-white font-bold text-xs shadow-xs" style="background:#e2006a">
                        Write a Review
                    </button>
                @endif
            </div>

            @forelse($reviewsList as $review)
                <div class="border-b border-slate-100 py-4 first:pt-0 last:border-0">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-pink-50 text-[#e2006a] font-bold text-xs flex items-center justify-center">
                                {{ strtoupper(substr($review->customer_name ?: 'U', 0, 1)) }}
                            </span>
                            <span class="text-xs font-bold text-slate-900">{{ $review->customer_name ?: 'Verified Customer' }}</span>
                        </div>
                        <div class="flex items-center text-amber-400 text-xs">
                            @for($i = 1; $i <= 5; $i++)
                                <span>{{ $i <= (int)$review->rating ? '★' : '☆' }}</span>
                            @endfor
                        </div>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600 mt-2">{{ $review->comment }}</p>
                </div>
            @empty
                <div class="text-center py-10 text-slate-400 text-xs">
                    No reviews yet. Be the first to review this product!
                </div>
            @endforelse
        </div>

        {{-- Tab: Specs --}}
        @if(count($specs) > 0)
            <div x-show="activeTab === 'specs'" class="pt-6">
                <div class="overflow-hidden rounded-xl border border-slate-100 divide-y divide-slate-100">
                    @foreach($specs as $i => $spec)
                        <div class="flex items-center py-3 px-4 text-xs sm:text-sm {{ $i % 2 === 0 ? 'bg-slate-50/50' : 'bg-white' }}">
                            <span class="w-1/3 font-semibold text-slate-500">{{ $spec['label'] }}</span>
                            <span class="w-2/3 font-bold text-slate-900">{{ $spec['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ── Related Products: "YOU MAY ALSO LIKE" ── --}}
    @if(!empty($relatedProducts) && count($relatedProducts) > 0)
        <section class="mt-12">
            <div class="mb-5 flex items-center justify-between border-b border-slate-200/60 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-6 rounded-full" style="background:#e2006a"></span>
                    <h2 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900 uppercase">You May Also Like</h2>
                </div>
                @if(!empty($product['category']['slug']))
                    <a class="group inline-flex items-center gap-1 text-xs font-bold text-slate-600 transition hover:text-[#e2006a]" href="{{ route('shop', ['category' => $product['category']['slug']]) }}">
                        <span>View All</span>
                        <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                @foreach(array_slice($relatedProducts, 0, 6) as $index => $rel)
                    <x-product-card :product="$rel" :index="$index" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Lightbox Modal --}}
    <div
        x-show="galleryOpen"
        x-transition.opacity
        class="fixed inset-0 z-[400] flex flex-col bg-black/90 backdrop-blur-sm"
        @keydown.escape.window="galleryOpen = false"
        @click="galleryOpen = false"
        role="dialog"
        aria-modal="true"
        style="display: none;"
    >
        <div class="flex shrink-0 items-center justify-end p-4">
            <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white hover:bg-white/40" @click.stop="galleryOpen = false">✕</button>
        </div>
        <div class="flex min-h-0 flex-1 items-center justify-center p-4 sm:p-12" @click.stop>
            <img :src="currentImage" alt="{{ $product['title'] }}" class="max-h-full max-w-full object-contain">
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    try {
        if (sessionStorage.getItem('mb_view_{{ (int) ($product['id'] ?? 0) }}')) return;
        sessionStorage.setItem('mb_view_{{ (int) ($product['id'] ?? 0) }}', '1');
        if (window.mbTrack) {
            window.mbTrack('view_item', {
                value: {{ (float) ($product['salePrice'] ?? $product['price'] ?? 0) }},
                currency: 'BDT',
                content_ids: [@js((string) ($product['id'] ?? ''))],
                content_type: 'product',
            });
        }
    } catch (e) {}
})();
</script>
@endpush
