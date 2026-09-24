@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12" x-data="{ 
    qty: 1,
    activeImg: '{{ !empty($product['images'][0]) ? $product['images'][0] : '/brandlogo.png' }}'
}">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-brand-green-600">Home</a>
        <span>/</span>
        <a href="{{ route('shop') }}" class="hover:text-brand-green-600">Shop</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold truncate">{{ $product['title'] }}</span>
    </div>

    <!-- Product Overview Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 bg-white p-6 sm:p-8 rounded-3xl border border-brand-green-100 shadow-soft">
        
        <!-- Gallery -->
        <div class="space-y-4">
            <div class="aspect-square rounded-2xl bg-slate-50 overflow-hidden border border-slate-100 flex items-center justify-center p-4">
                <img :src="activeImg" alt="{{ $product['title'] }}" class="max-h-full max-w-full object-contain">
            </div>

            @if(!empty($product['images']) && count($product['images']) > 1)
                <div class="flex items-center gap-3 overflow-x-auto pb-2">
                    @foreach($product['images'] as $img)
                        <button type="button" @click="activeImg = '{{ $img }}'" class="w-16 h-16 rounded-xl border p-1 shrink-0 overflow-hidden" :class="activeImg === '{{ $img }}' ? 'border-brand-green-500 ring-2 ring-brand-green-100' : 'border-slate-200'">
                            <img src="{{ $img }}" alt="" class="w-full h-full object-cover rounded-lg">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Product Details -->
        <div class="space-y-6 flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-brand-green-50 border border-brand-green-200 text-brand-green-700 text-[10px] font-bold uppercase">
                        In Stock
                    </span>
                    @if(!empty($product['brand']))
                        <span class="text-xs text-slate-400 font-medium">Brand: {{ $product['brand'] }}</span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight leading-snug">
                    {{ $product['title'] }}
                </h1>

                <!-- Price Box -->
                <div class="p-4 rounded-2xl bg-brand-green-50/50 border border-brand-green-100 flex items-baseline gap-3">
                    <span class="text-3xl font-extrabold text-brand-green-700">
                        ৳{{ number_format(!empty($product['sale_price']) ? $product['sale_price'] : $product['price'], 0) }}
                    </span>
                    @if(!empty($product['sale_price']) && $product['sale_price'] < $product['price'])
                        <span class="text-base text-slate-400 line-through">
                            ৳{{ number_format($product['price'], 0) }}
                        </span>
                        <span class="badge-deal">
                            Save ৳{{ number_format($product['price'] - $product['sale_price'], 0) }}
                        </span>
                    @endif
                </div>

                <!-- Short description -->
                @if(!empty($product['short_description']))
                    <p class="text-sm text-slate-600 leading-relaxed">
                        {{ $product['short_description'] }}
                    </p>
                @endif
            </div>

            <!-- Actions -->
            <div class="pt-6 border-t border-slate-100 space-y-4">
                <div class="flex items-center gap-4">
                    <div class="flex items-center border border-slate-200 rounded-full bg-slate-50 p-1">
                        <button type="button" @click="if (qty > 1) qty--" class="w-8 h-8 rounded-full bg-white text-slate-700 text-sm font-bold flex items-center justify-center shadow-xs hover:bg-slate-100">-</button>
                        <span class="w-12 text-center text-sm font-bold" x-text="qty"></span>
                        <button type="button" @click="qty++" class="w-8 h-8 rounded-full bg-white text-slate-700 text-sm font-bold flex items-center justify-center shadow-xs hover:bg-slate-100">+</button>
                    </div>

                    <button type="button" @click="$store.cart.addItem({{ json_encode($product) }}, null, qty)" class="flex-1 py-3 px-6 rounded-full bg-brand-orange-500 hover:bg-brand-orange-600 text-white font-bold text-sm shadow-md transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span>Add To Cart</span>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <span class="text-brand-green-500">✓</span> Cash on Delivery Available
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-brand-green-500">✓</span> Fast Home Delivery
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Description & Specs Tabs -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-brand-green-100 shadow-soft space-y-6">
        <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">Product Description & Details</h2>
        <div class="prose max-w-none text-sm text-slate-600 leading-relaxed">
            @if(!empty($product['description']))
                {!! nl2br(e($product['description'])) !!}
            @else
                <p>Detailed product specifications and usage instructions are provided with the physical packaging.</p>
            @endif
        </div>

        @if(!empty($product['specs']) && count($product['specs']) > 0)
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-sm font-bold text-slate-800 mb-3">Specifications</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    @foreach($product['specs'] as $spec)
                        <div class="flex p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="w-1/3 font-semibold text-slate-500">{{ $spec['label'] }}</span>
                            <span class="w-2/3 text-slate-800">{{ $spec['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Related Products -->
    @if(!empty($relatedProducts) && count($relatedProducts) > 0)
        <div class="space-y-6">
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">You May Also Like</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-5">
                @foreach(array_slice($relatedProducts, 0, 4) as $rel)
                    <div class="product-card p-3 flex flex-col justify-between">
                        <a href="{{ route('products.show', $rel['slug']) }}" class="block">
                            <div class="aspect-square rounded-xl bg-slate-50 overflow-hidden mb-2">
                                <img src="{{ !empty($rel['images'][0]) ? $rel['images'][0] : '/brandlogo.png' }}" class="w-full h-full object-cover">
                            </div>
                            <h4 class="text-xs font-bold text-slate-800 truncate">{{ $rel['title'] }}</h4>
                            <p class="text-xs font-extrabold text-brand-green-700 mt-1">৳{{ number_format($rel['price'], 0) }}</p>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
