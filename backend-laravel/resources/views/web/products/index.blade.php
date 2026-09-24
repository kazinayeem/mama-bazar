@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Breadcrumb & Title -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-brand-green-100">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('home') }}" class="hover:text-brand-green-600">Home</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Shop</span>
                @if($selectedCategory)
                    <span>/</span>
                    <span class="text-brand-green-600 font-semibold">{{ $selectedCategory->name }}</span>
                @endif
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                {{ $selectedCategory ? $selectedCategory->name : ($query ? "Search results for '{$query}'" : 'All Products') }}
            </h1>
        </div>

        <!-- Sorting -->
        <form method="GET" action="{{ route('shop') }}" class="flex items-center gap-2">
            @if(request('category')) <input type="hidden" name="category" value="{{ request('category') }}"> @endif
            @if(request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            @if(request('sale')) <input type="hidden" name="sale" value="{{ request('sale') }}"> @endif
            
            <label for="sort" class="text-xs font-semibold text-slate-500">Sort by:</label>
            <select name="sort" id="sort" onchange="this.form.submit()" class="text-xs rounded-xl border border-brand-green-200 bg-white py-1.5 px-3 focus:outline-none focus:border-brand-green-500">
                <option value="newest" {{ $currentSort === 'newest' ? 'selected' : '' }}>Newest</option>
                <option value="price_asc" {{ $currentSort === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                <option value="price_desc" {{ $currentSort === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
            </select>
        </form>
    </div>

    <!-- Category Pills Row -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
        <a href="{{ route('shop') }}" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition whitespace-nowrap {{ !request('category') ? 'bg-brand-green-600 text-white' : 'bg-white border border-brand-green-200 text-slate-700 hover:bg-brand-green-50' }}">
            All Categories
        </a>
        @foreach($categories as $cat)
            <a href="{{ route('shop', ['category' => $cat->slug]) }}" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition whitespace-nowrap {{ request('category') === $cat->slug ? 'bg-brand-green-600 text-white' : 'bg-white border border-brand-green-200 text-slate-700 hover:bg-brand-green-50' }}">
                {{ $cat->name }}
            </a>
        @endforeach
    </div>

    <!-- Product Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-5">
        @forelse($products as $product)
            <div class="product-card p-3.5 flex flex-col justify-between">
                <a href="{{ route('products.show', $product['slug']) }}" class="block">
                    <div class="aspect-square rounded-xl bg-slate-50 overflow-hidden mb-2 relative">
                        @php
                            $imgs = is_array($product['images']) ? $product['images'] : json_decode($product['images'], true);
                            $firstImg = !empty($imgs) ? $imgs[0] : '/brandlogo.png';
                        @endphp
                        <img src="{{ $firstImg }}" alt="{{ $product['title'] }}" class="w-full h-full object-cover">
                        @if(!empty($product['sale_price']) && $product['sale_price'] < $product['price'])
                            <span class="absolute top-2 left-2 badge-deal">
                                -{{ round((($product['price'] - $product['sale_price']) / $product['price']) * 100) }}%
                            </span>
                        @endif
                    </div>
                    <h3 class="text-xs font-bold text-slate-800 line-clamp-2 hover:text-brand-green-600 transition">{{ $product['title'] }}</h3>
                </a>
                <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-extrabold text-brand-green-700">
                            ৳{{ number_format(!empty($product['sale_price']) ? $product['sale_price'] : $product['price'], 0) }}
                        </div>
                        @if(!empty($product['sale_price']) && $product['sale_price'] < $product['price'])
                            <div class="text-[10px] text-slate-400 line-through">
                                ৳{{ number_format($product['price'], 0) }}
                            </div>
                        @endif
                    </div>
                    <button type="button" @click="$store.cart.addItem({{ json_encode($product) }})" class="flex items-center gap-1 px-3 py-1.5 rounded-full bg-brand-green-50 border border-brand-green-200 text-brand-green-700 hover:bg-brand-green-500 hover:text-white transition text-xs font-bold">
                        <span>Add</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-slate-400">
                <p class="text-base font-semibold">No products found matching your search.</p>
                <a href="{{ route('shop') }}" class="inline-block mt-3 px-5 py-2 rounded-full bg-brand-green-500 text-white text-xs font-bold">Clear Filters</a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($pagination) && $pagination['totalPages'] > 1)
        <div class="flex items-center justify-center gap-2 pt-8">
            @for($i = 1; $i <= $pagination['totalPages']; $i++)
                <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}" class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition {{ $pagination['page'] == $i ? 'bg-brand-green-600 text-white' : 'bg-white border border-brand-green-200 text-slate-700 hover:bg-brand-green-50' }}">
                    {{ $i }}
                </a>
            @endfor
        </div>
    @endif

</div>
@endsection
