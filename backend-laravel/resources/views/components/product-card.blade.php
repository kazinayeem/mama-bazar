@props(['product', 'index' => 0])

@php
    // Accept Eloquent model OR homepage array payload
    $isArr = is_array($product);
    $get = function ($key, $alt = null, $default = null) use ($product, $isArr) {
        if ($isArr) {
            return $product[$key] ?? ($alt ? ($product[$alt] ?? $default) : $default);
        }
        return data_get($product, $key, $alt ? data_get($product, $alt, $default) : $default);
    };

    $id = (int) $get('id', null, 0);
    $title = (string) $get('title', null, 'Product');
    $slug = (string) $get('slug', null, '#');

    $images = $get('images', null, []);
    if (is_string($images)) {
        $images = json_decode($images, true) ?: [];
    }
    if (!is_array($images)) {
        $images = [];
    }
    $firstRaw = $images[0] ?? null;
    $firstImage = is_array($firstRaw)
        ? ($firstRaw['url'] ?? reset($firstRaw) ?: '/brandlogo.png')
        : ($firstRaw ?: ($get('image') ?: '/brandlogo.png'));

    $price = (float) $get('price', null, 0);
    $salePrice = (float) $get('salePrice', 'sale_price', 0);
    $discount = (float) $get('discount', null, 0);
    $effectivePrice = $salePrice > 0
        ? $salePrice
        : ($discount > 0 ? round($price - ($price * $discount / 100)) : $price);
    $hasDiscount = ($salePrice > 0 && $salePrice < $price) || $discount > 0;
    $discountPercent = $discount > 0
        ? (int) round($discount)
        : ($salePrice > 0 && $salePrice < $price ? (int) round((($price - $salePrice) / $price) * 100) : 0);

    $brandInfo = $get('brandInfo', 'brand_info');
    $brandName = is_array($brandInfo)
        ? ($brandInfo['name'] ?? '')
        : (is_object($brandInfo) ? ($brandInfo->name ?? '') : '');
    if ($brandName === '') {
        $brandName = (string) ($get('brand') ?: '');
    }

    $colorOptions = $get('colorOptions', 'color_options', []);
    if (is_string($colorOptions)) {
        $colorOptions = json_decode($colorOptions, true) ?: [];
    }
    if (!is_array($colorOptions)) {
        $colorOptions = [];
    }

    $sizeOptions = $get('sizeOptions', 'size_options', []);
    if (is_string($sizeOptions)) {
        $sizeOptions = json_decode($sizeOptions, true) ?: [];
    }
    if (!is_array($sizeOptions)) {
        $sizeOptions = [];
    }

    $stock = (int) $get('stock', null, 0);
    $unlimited = (bool) $get('unlimited_stock', 'unlimitedStock', false);
    $isOutOfStock = $stock <= 0 && !$unlimited;
    $lowAlert = (int) $get('low_stock_alert', 'lowStockAlert', 10);
    $isLowStock = $stock > 0 && $stock <= $lowAlert && !$unlimited;

    $rating = (float) ($get('shop_rating', 'shopRating') ?: $get('rating', null, 0));
    $reviewCount = (int) ($get('review_count', 'reviewCount', 0));

    $isFlash = (bool) $get('is_flash_sale', 'isFlashSale', false);
    $isNew = (bool) $get('is_new_arrival', 'isNewArrival', false);
    $isBest = (bool) $get('is_best_seller', 'isBestSeller', false);

    $productUrl = $slug && $slug !== '#' ? route('products.show', $slug) : route('shop');
    $defaultColor = !empty($colorOptions) ? ($colorOptions[0]['name'] ?? '') : '';
    $defaultSize = !empty($sizeOptions) ? ($sizeOptions[0] ?? '') : '';
@endphp

<article
    class="product-card store-card group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-200/80 bg-white transition-all duration-200 hover:-translate-y-1 hover:border-pink-200 hover:shadow-md"
    style="animation-delay: {{ min((int) $index * 40, 400) }}ms"
    x-data="{
        added: false,
        selectedColor: @js($defaultColor),
        selectedSize: @js($defaultSize),
        addToCart() {
            if ({{ $isOutOfStock ? 'true' : 'false' }}) return;
            $store.cart.addItem({
                id: {{ $id }},
                title: @js($title),
                slug: @js($slug),
                price: {{ $effectivePrice }},
                image: @js($firstImage),
                color: this.selectedColor,
                size: this.selectedSize,
            });
            this.added = true;
            setTimeout(() => this.added = false, 900);
        }
    }"
>
    {{-- Image: fixed aspect so all cards align --}}
    <div class="relative aspect-square shrink-0 overflow-hidden bg-white">
        <a href="{{ $productUrl }}" class="flex h-full w-full items-center justify-center p-3 sm:p-4" tabindex="-1" aria-label="{{ $title }}">
            <img
                src="{{ $firstImage }}"
                alt="{{ $title }}"
                width="240"
                height="240"
                loading="lazy"
                decoding="async"
                class="h-full w-full object-contain transition-transform duration-300 ease-out group-hover:scale-105"
                onerror="this.onerror=null;this.src='/brandlogo.png';"
            >
        </a>

        @if($hasDiscount && $discountPercent > 0)
            <span class="absolute left-2.5 top-2.5 rounded px-2 py-0.5 text-[10px] font-extrabold text-white shadow-xs" style="background:#e2006a">-{{ $discountPercent }}%</span>
        @elseif($isFlash)
            <span class="absolute left-2.5 top-2.5 rounded px-2 py-0.5 text-[10px] font-extrabold text-white shadow-xs" style="background:#f47b20">Flash</span>
        @elseif($isNew)
            <span class="absolute left-2.5 top-2.5 rounded px-2 py-0.5 text-[10px] font-extrabold text-white shadow-xs" style="background:#e2006a">New</span>
        @elseif($isBest)
            <span class="absolute left-2.5 top-2.5 rounded px-2 py-0.5 text-[10px] font-extrabold text-white shadow-xs bg-amber-500">Best</span>
        @endif

        @if($isOutOfStock)
            <div class="absolute inset-0 flex items-center justify-center bg-white/80">
                <span class="rounded bg-slate-800 px-3 py-1 text-xs font-bold text-white">Out of Stock</span>
            </div>
        @endif

        <div class="absolute right-2 top-2 sm:right-2.5 sm:top-2.5 flex flex-col gap-1.5 opacity-100 transition sm:translate-x-1 sm:opacity-0 sm:group-hover:translate-x-0 sm:group-hover:opacity-100 sm:group-focus-within:translate-x-0 sm:group-focus-within:opacity-100">
            <button
                type="button"
                @click="$dispatch('open-quick-view', { id: {{ $id }} })"
                aria-label="Quick view {{ $title }}"
                class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-600 shadow-md transition hover:text-white"
                style="hover:background:#e2006a"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
            <button
                type="button"
                @click="$store.cart.toggleWishlist({{ $id }})"
                aria-label="Toggle wishlist"
                :class="$store.cart.hasWishlist({{ $id }}) ? 'text-white' : 'bg-white text-slate-500 hover:text-white'"
                :style="$store.cart.hasWishlist({{ $id }}) ? 'background:#e2006a' : ''"
                class="flex h-8 w-8 items-center justify-center rounded-full shadow-md transition"
            >
                <svg class="h-3.5 w-3.5" :class="$store.cart.hasWishlist({{ $id }}) ? 'fill-current' : 'fill-none'" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            </button>
        </div>
    </div>

    <div class="flex flex-1 flex-col gap-1 p-3">
        <div class="flex items-center justify-between gap-1.5">
            <p class="min-h-[14px] truncate text-[10px] font-bold uppercase tracking-wider text-slate-400">
                {{ $brandName !== '' ? $brandName : "\u{00A0}" }}
            </p>
            @if($rating > 0 || $reviewCount > 0)
                <span class="flex shrink-0 items-center gap-0.5 text-[11px] text-slate-600 font-bold" aria-label="Rating {{ number_format($rating, 1) }}">
                    <span class="text-amber-400" aria-hidden="true">★</span>
                    <span>{{ number_format($rating, 1) }}</span>
                    @if($reviewCount > 0)
                        <span class="text-slate-400 font-normal">({{ $reviewCount }})</span>
                    @endif
                </span>
            @endif
        </div>

        <a href="{{ $productUrl }}" class="line-clamp-2 min-h-[2.25rem] text-xs font-semibold leading-snug text-slate-800 transition hover:text-[#e2006a] break-words">
            {{ $title }}
        </a>

        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 pt-0.5">
            <span class="text-sm sm:text-base font-extrabold tracking-tight" style="color:#e2006a">৳{{ number_format($effectivePrice, 0) }}</span>
            @if($hasDiscount)
                <span class="text-[11px] sm:text-xs text-slate-400 line-through">৳{{ number_format($price, 0) }}</span>
            @endif
            @if($isLowStock)
                <span class="ml-auto shrink-0 text-[10px] font-bold text-orange-600">Only {{ $stock }} left</span>
            @endif
        </div>

        @if(!empty($colorOptions))
            <div class="flex min-h-[18px] flex-wrap items-center gap-1 overflow-hidden pt-1">
                @foreach(array_slice($colorOptions, 0, 6) as $color)
                    <button
                        type="button"
                        @click="selectedColor = @js($color['name'] ?? '')"
                        aria-label="Select color {{ $color['name'] ?? '' }}"
                        :class="selectedColor === @js($color['name'] ?? '') ? 'scale-110' : 'border-slate-200'"
                        class="h-3.5 w-3.5 shrink-0 rounded-full border-2 transition"
                        style="background-color: {{ $color['value'] ?? '#cccccc' }};"
                        :style="selectedColor === @js($color['name'] ?? '') ? 'border-color:#e2006a' : ''"
                    ></button>
                @endforeach
            </div>
        @endif

        {{-- Actions pinned to bottom --}}
        <div class="mt-auto flex items-center gap-1.5 pt-2">
            <button
                type="button"
                @click="addToCart()"
                :disabled="{{ $isOutOfStock ? 'true' : 'false' }}"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-700 transition hover:border-pink-300 hover:bg-pink-50 hover:text-[#e2006a]"
                aria-label="Add to cart"
            >
                <template x-if="added">
                    <svg class="h-4 w-4" style="color:#e2006a" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template x-if="!added">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </template>
            </button>
            <a
                href="{{ $productUrl }}"
                class="flex flex-1 h-9 items-center justify-center rounded-lg text-white text-xs font-bold transition shadow-xs hover:brightness-110"
                style="background:#e2006a"
            >
                Buy Now
            </a>
        </div>
    </div>
</article>
