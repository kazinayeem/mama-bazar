@props(['product'])

@php
    $p = is_array($product) ? $product : (array) $product;
    $images = $p['images'] ?? [];
    if (is_string($images)) {
        $images = json_decode($images, true) ?: [];
    }
    $firstImage = !empty($images) ? (is_array($images[0]) ? ($images[0]['url'] ?? reset($images[0])) : $images[0]) : '/brandlogo.png';
    $price = (float) ($p['price'] ?? 0);
    $salePrice = (float) ($p['salePrice'] ?? $p['sale_price'] ?? 0);
    $discount = (float) ($p['discount'] ?? 0);
    $effective = $salePrice > 0 ? $salePrice : ($discount > 0 ? round($price - ($price * $discount / 100)) : $price);
    $hasDiscount = ($salePrice > 0 && $salePrice < $price) || $discount > 0;
    $slug = $p['slug'] ?? '#';
    $title = $p['title'] ?? 'Product';
    $brand = $p['brandInfo']['name'] ?? $p['brand'] ?? 'Mama Bazar';
    $id = (int) ($p['id'] ?? 0);
@endphp

<article class="product-card group relative flex h-full flex-col overflow-hidden rounded-2xl border border-brand-green-100 bg-white">
    <div class="relative h-[170px] shrink-0 overflow-hidden bg-white p-3 sm:h-[190px]">
        <a href="{{ route('products.show', $slug) }}" class="flex h-full w-full items-center justify-center">
            <img src="{{ $firstImage }}" alt="{{ $title }}" loading="lazy" class="h-full w-full object-contain transition-transform duration-500 group-hover:scale-[1.06]">
        </a>
        @if($hasDiscount)
            <span class="absolute left-2.5 top-2.5 rounded-md bg-brand-orange-500 px-2 py-0.5 text-[11px] font-bold text-white">Sale</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-1.5 p-3.5">
        <p class="truncate text-[10px] font-bold uppercase tracking-wider text-brand-green-600">{{ $brand }}</p>
        <a href="{{ route('products.show', $slug) }}" class="line-clamp-2 min-h-[2.4rem] text-[13px] font-semibold leading-snug text-slate-800 hover:text-brand-green-600">{{ $title }}</a>
        <div class="flex items-baseline gap-2 pt-0.5">
            <span class="text-base font-extrabold text-slate-900">৳{{ number_format($effective, 0) }}</span>
            @if($hasDiscount)
                <span class="text-xs text-slate-400 line-through">৳{{ number_format($price, 0) }}</span>
            @endif
        </div>
        <div class="mt-auto pt-3">
            <button
                type="button"
                @click="$store.cart.addItem({ id: {{ $id }}, title: {{ json_encode($title) }}, slug: {{ json_encode($slug) }}, price: {{ $effective }}, image: {{ json_encode($firstImage) }}, quantity: 1 })"
                class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-brand-orange-500 px-3 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-brand-orange-600 active:scale-95"
            >
                Add to Cart
            </button>
        </div>
    </div>
</article>
