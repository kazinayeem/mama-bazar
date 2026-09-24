@extends('layouts.app')

@section('content')
<div class="space-y-12 pb-12">

    <!-- Hero Section / Promo Banner -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-brand-green-700 via-brand-green-600 to-brand-green-500 text-white shadow-lift">
            <div class="p-8 sm:p-12 md:p-16 max-w-xl space-y-4">
                <span class="inline-block px-3 py-1 rounded-full bg-brand-orange-500 text-white text-[11px] font-bold uppercase tracking-wider">
                    🔥 Special Weekly Offer
                </span>
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-tight">
                    Daily Fresh Groceries & Essentials At Your Door
                </h1>
                <p class="text-brand-green-50 text-sm sm:text-base leading-relaxed">
                    Shop pure foods, daily groceries, kitchen items, and household essentials at guaranteed competitive prices.
                </p>
                <div class="pt-2 flex flex-wrap items-center gap-3">
                    <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-brand-orange-500 text-white text-sm font-bold shadow-md hover:bg-brand-orange-600 transition">
                        Shop Now
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    <a href="{{ route('shop', ['sale' => 'true']) }}" class="inline-flex items-center px-6 py-3 rounded-full bg-white/10 hover:bg-white/20 text-white text-sm font-bold backdrop-blur-xs transition">
                        View Hot Deals
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Strip -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-5 rounded-2xl bg-white border border-brand-green-100 shadow-soft">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-brand-green-50 flex items-center justify-center text-brand-green-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">Fast Delivery</h4>
                    <p class="text-[11px] text-slate-500">Across Bangladesh</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-brand-green-50 flex items-center justify-center text-brand-green-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">Cash on Delivery</h4>
                    <p class="text-[11px] text-slate-500">Pay when received</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-brand-green-50 flex items-center justify-center text-brand-green-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">100% Authentic</h4>
                    <p class="text-[11px] text-slate-500">Guaranteed quality</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-brand-green-50 flex items-center justify-center text-brand-green-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">24/7 Support</h4>
                    <p class="text-[11px] text-slate-500">Dedicated assistance</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Shop by Category</h2>
                <p class="text-xs text-slate-500 mt-1">Explore our wide selection of daily essentials</p>
            </div>
            <a href="{{ route('shop') }}" class="text-xs font-bold text-brand-green-600 hover:text-brand-green-700 flex items-center gap-1">
                <span>View All</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @forelse($categories as $category)
                <a href="{{ route('shop', ['category' => $category->slug]) }}" class="group p-4 rounded-2xl bg-white border border-brand-green-100 hover:border-brand-green-400 hover:shadow-card transition flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-brand-green-50 flex items-center justify-center p-2 mb-3 group-hover:scale-105 transition">
                        @if($category->image)
                            <img src="{{ $category->image }}" alt="{{ $category->name }}" class="w-12 h-12 object-contain">
                        @else
                            <span class="text-xl font-black text-brand-green-600">{{ substr($category->name, 0, 1) }}</span>
                        @endif
                    </div>
                    <span class="text-xs font-bold text-slate-800 group-hover:text-brand-green-600 transition truncate max-w-full">
                        {{ $category->name }}
                    </span>
                </a>
            @empty
                <div class="col-span-full py-8 text-center text-slate-400 text-sm">
                    No categories found.
                </div>
            @endforelse
        </div>
    </section>

    <!-- Flash Deals Section -->
    @if($flashSaleProducts->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="p-6 rounded-3xl bg-gradient-to-r from-brand-orange-50 via-white to-brand-green-50 border border-brand-orange-200">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🔥</span>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Flash Sale Deals</h2>
                        <span class="badge-deal ml-2">Limited Time</span>
                    </div>
                    <a href="{{ route('shop', ['sale' => 'true']) }}" class="text-xs font-bold text-brand-orange-600 hover:text-brand-orange-700">
                        See All Deals &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($flashSaleProducts as $product)
                        <div class="product-card p-3 flex flex-col justify-between">
                            <a href="{{ route('products.show', $product->slug) }}" class="block">
                                <div class="aspect-square rounded-xl bg-slate-50 overflow-hidden mb-2 relative">
                                    @php
                                        $imgs = is_array($product->images) ? $product->images : json_decode($product->images, true);
                                        $firstImg = !empty($imgs) ? $imgs[0] : '/brandlogo.png';
                                    @endphp
                                    <img src="{{ $firstImg }}" alt="{{ $product->title }}" class="w-full h-full object-cover">
                                    @if($product->sale_price && $product->sale_price < $product->price)
                                        <span class="absolute top-2 left-2 badge-deal">
                                            -{{ round((($product->price - $product->sale_price) / $product->price) * 100) }}%
                                        </span>
                                    @endif
                                </div>
                                <h3 class="text-xs font-bold text-slate-800 line-clamp-2">{{ $product->title }}</h3>
                            </a>
                            <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-extrabold text-brand-orange-600">
                                        ৳{{ number_format($product->sale_price ?: $product->price, 0) }}
                                    </div>
                                    @if($product->sale_price && $product->sale_price < $product->price)
                                        <div class="text-[10px] text-slate-400 line-through">
                                            ৳{{ number_format($product->price, 0) }}
                                        </div>
                                    @endif
                                </div>
                                <button type="button" @click="$store.cart.addItem({{ json_encode($product) }})" class="p-2 rounded-full bg-brand-orange-500 text-white hover:bg-brand-orange-600 transition shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Featured Products Section -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Popular Products</h2>
                <p class="text-xs text-slate-500 mt-1">Handpicked favorites loved by our customers</p>
            </div>
            <a href="{{ route('shop') }}" class="text-xs font-bold text-brand-green-600 hover:text-brand-green-700">
                Explore Shop &rarr;
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-5">
            @forelse($featuredProducts as $product)
                <div class="product-card p-3.5 flex flex-col justify-between">
                    <a href="{{ route('products.show', $product->slug) }}" class="block">
                        <div class="aspect-square rounded-xl bg-slate-50 overflow-hidden mb-2 relative">
                            @php
                                $imgs = is_array($product->images) ? $product->images : json_decode($product->images, true);
                                $firstImg = !empty($imgs) ? $imgs[0] : '/brandlogo.png';
                            @endphp
                            <img src="{{ $firstImg }}" alt="{{ $product->title }}" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xs font-bold text-slate-800 line-clamp-2 hover:text-brand-green-600 transition">{{ $product->title }}</h3>
                    </a>
                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-extrabold text-brand-green-700">
                                ৳{{ number_format($product->sale_price ?: $product->price, 0) }}
                            </div>
                            @if($product->sale_price && $product->sale_price < $product->price)
                                <div class="text-[10px] text-slate-400 line-through">
                                    ৳{{ number_format($product->price, 0) }}
                                </div>
                            @endif
                        </div>
                        <button type="button" @click="$store.cart.addItem({{ json_encode($product) }})" class="flex items-center gap-1 px-3 py-1.5 rounded-full bg-brand-green-50 border border-brand-green-200 text-brand-green-700 hover:bg-brand-green-500 hover:text-white transition text-xs font-bold">
                            <span>Add</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-slate-400 text-sm">
                    No products currently featured.
                </div>
            @endforelse
        </div>
    </section>

</div>
@endsection
