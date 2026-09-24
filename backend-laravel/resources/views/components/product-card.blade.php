@props(['product', 'index' => 0])

@php
    $images = is_array($product->images) ? $product->images : (is_string($product->images) ? json_decode($product->images, true) : []);
    $firstImage = !empty($images) ? $images[0] : '/brandlogo.png';
    $price = (float) $product->price;
    $salePrice = (float) ($product->sale_price ?? $product->salePrice ?? 0);
    $discount = (float) ($product->discount ?? 0);
    
    // Effective price calculation matching React
    $effectivePrice = $salePrice > 0 ? $salePrice : ($discount > 0 ? round($price - ($price * $discount / 100)) : $price);
    $hasDiscount = ($salePrice > 0 && $salePrice < $price) || $discount > 0;
    $discountPercent = $discount > 0 ? round($discount) : ($salePrice > 0 && $salePrice < $price ? round((($price - $salePrice) / $price) * 100) : 0);
    
    $colorOptions = is_array($product->color_options ?? $product->colorOptions ?? null) 
        ? ($product->color_options ?? $product->colorOptions) 
        : (is_string($product->color_options ?? $product->colorOptions ?? null) ? json_decode($product->color_options ?? $product->colorOptions, true) : []);
    
    $sizeOptions = is_array($product->size_options ?? $product->sizeOptions ?? null) 
        ? ($product->size_options ?? $product->sizeOptions) 
        : (is_string($product->size_options ?? $product->sizeOptions ?? null) ? json_decode($product->size_options ?? $product->sizeOptions, true) : []);

    $stock = (int) ($product->stock ?? 0);
    $isOutOfStock = $stock <= 0 && !($product->unlimited_stock ?? false);
    $isLowStock = $stock > 0 && $stock <= ($product->low_stock_alert ?? 10) && !($product->unlimited_stock ?? false);
    $brandName = $product->brandInfo->name ?? $product->brand ?? '';
@endphp

<article class="product-card group relative flex h-full flex-col overflow-hidden rounded-2xl border border-brand-green-100 bg-white transition duration-200"
         x-data="{
             added: false,
             selectedColor: '{{ !empty($colorOptions) ? ($colorOptions[0]['name'] ?? '') : '' }}',
             selectedSize: '{{ !empty($sizeOptions) ? $sizeOptions[0] : '' }}',
             addToCart() {
                 if ({{ $isOutOfStock ? 'true' : 'false' }}) return;
                 $store.cart.addItem({
                     id: {{ $product->id }},
                     title: {{ json_encode($product->title) }},
                     slug: {{ json_encode($product->slug) }},
                     price: {{ $effectivePrice }},
                     image: {{ json_encode($firstImage) }},
                     color: this.selectedColor,
                     size: this.selectedSize,
                     quantity: 1
                 });
                 this.added = true;
                 setTimeout(() => this.added = false, 900);
             }
         }">
    
    <!-- Image Area -->
    <div class="relative h-[190px] sm:h-[210px] shrink-0 overflow-hidden bg-white p-3">
        <a href="{{ route('products.show', $product->slug) }}" class="flex h-full w-full items-center justify-center">
            <img src="{{ $firstImage }}" 
                 alt="{{ $product->title }}" 
                 loading="lazy"
                 class="h-full w-full object-contain transition-transform duration-500 ease-out group-hover:scale-[1.06]">
        </a>

        <!-- Badge Priority: Discount > Flash Sale > New Arrival > Best Seller -->
        @if($hasDiscount && $discountPercent > 0)
            <span class="absolute left-2.5 top-2.5 inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold bg-brand-orange-500 text-white shadow-xs">
                -{{ $discountPercent }}%
            </span>
        @elseif($product->is_flash_sale ?? false)
            <span class="absolute left-2.5 top-2.5 inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold bg-brand-orange-50 text-brand-orange-600 border border-brand-orange-200">
                ⚡ Flash Sale
            </span>
        @elseif($product->is_new_arrival ?? false)
            <span class="absolute left-2.5 top-2.5 inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold bg-brand-green-500 text-white">
                New
            </span>
        @elseif($product->is_best_seller ?? false)
            <span class="absolute left-2.5 top-2.5 inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-bold bg-amber-500 text-white">
                Best Seller
            </span>
        @endif

        <!-- Out of stock overlay -->
        @if($isOutOfStock)
            <div class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-xs">
                <span class="rounded-md bg-slate-800 px-3 py-1 text-xs font-bold text-white">Out of Stock</span>
            </div>
        @endif

        <!-- Hover action buttons (Quick view & Wishlist) -->
        <div class="absolute right-2.5 top-2.5 flex flex-col gap-1.5 sm:translate-x-2 sm:opacity-0 sm:transition-all sm:duration-200 sm:group-hover:translate-x-0 sm:group-hover:opacity-100">
            <button type="button" 
                    @click="$dispatch('open-quick-view', { id: {{ $product->id }} })"
                    aria-label="Quick view" 
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-600 shadow-md transition hover:bg-brand-green-500 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
            <button type="button" 
                    @click="$store.cart.toggleWishlist({{ $product->id }})"
                    aria-label="Wishlist" 
                    :class="$store.cart.hasWishlist({{ $product->id }}) ? 'bg-red-500 text-white' : 'bg-white text-slate-600 hover:bg-red-500 hover:text-white'"
                    class="flex h-9 w-9 items-center justify-center rounded-full shadow-md transition">
                <svg class="w-4 h-4" :class="$store.cart.hasWishlist({{ $product->id }}) ? 'fill-current' : 'fill-none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            </button>
        </div>
    </div>

    <!-- Body Information -->
    <div class="flex flex-1 flex-col gap-1.5 p-3.5">
        <!-- Brand & Rating Row -->
        <div class="flex items-center justify-between gap-2">
            <p class="text-[10px] font-bold uppercase tracking-wider text-brand-green-600 truncate">
                {{ $brandName ?: 'Mama Bazar' }}
            </p>
            @if(!empty($product->shop_rating) || !empty($product->rating))
            <div class="flex items-center gap-1">
                <span class="text-amber-400 text-xs">★</span>
                <span class="text-[10px] font-semibold text-slate-500">{{ number_format((float) ($product->shop_rating ?? $product->rating), 1) }}</span>
            </div>
            @endif
        </div>

        <!-- Title -->
        <a href="{{ route('products.show', $product->slug) }}" 
           class="line-clamp-2 min-h-[2.4rem] text-[13px] font-semibold leading-snug text-slate-800 transition hover:text-brand-green-600">
            {{ $product->title }}
        </a>

        <!-- Price Row -->
        <div class="flex flex-wrap items-baseline gap-x-2 pt-0.5">
            <span class="text-base font-extrabold text-slate-900">৳{{ number_format($effectivePrice, 0) }}</span>
            @if($hasDiscount)
                <span class="text-xs text-slate-400 line-through">৳{{ number_format($price, 0) }}</span>
            @endif
            @if($isLowStock)
                <span class="ml-auto text-[10px] font-bold text-brand-orange-600">Only {{ $stock }} left</span>
            @endif
        </div>

        <!-- Color & Size Swatches if present -->
        @if(!empty($colorOptions))
            <div class="flex items-center gap-1.5 pt-1 overflow-hidden">
                @foreach(array_slice($colorOptions, 0, 5) as $color)
                    <button type="button" 
                            @click="selectedColor = '{{ $color['name'] ?? '' }}'"
                            title="{{ $color['name'] ?? '' }}"
                            :class="selectedColor === '{{ $color['name'] ?? '' }}' ? 'ring-2 ring-brand-green-500 scale-110' : 'ring-1 ring-slate-200'"
                            class="h-3.5 w-3.5 rounded-full transition"
                            style="background-color: {{ $color['value'] ?? '#cccccc' }}">
                    </button>
                @endforeach
            </div>
        @endif

        <!-- Add to Cart CTA Button -->
        <div class="mt-auto pt-3">
            <button type="button" 
                    @click="addToCart()" 
                    :disabled="{{ $isOutOfStock ? 'true' : 'false' }}"
                    :class="added ? 'bg-brand-green-500 text-white' : '{{ $isOutOfStock ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-brand-orange-500 hover:bg-brand-orange-600 text-white' }}'"
                    class="w-full py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-xs active:scale-95">
                <template x-if="added">
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Added!
                    </span>
                </template>
                <template x-if="!added">
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        {{ $isOutOfStock ? 'Out of Stock' : 'Add to Cart' }}
                    </span>
                </template>
            </button>
        </div>

    </div>

</article>
