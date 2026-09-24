{{-- Shared shop filters (desktop sidebar + mobile drawer) --}}
@php
    $sectionClass = 'rounded-[18px] border border-slate-100 bg-white p-4 shadow-soft sm:p-5';
    $titleClass = 'mb-3 text-xs font-extrabold uppercase tracking-[0.16em] text-slate-900';
    $rowClass = 'flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition';
@endphp

{{-- Categories --}}
<div class="{{ $sectionClass }}" x-data="{ open: true }">
    <button type="button" class="flex w-full items-center justify-between" @click="open = !open">
        <h3 class="{{ $titleClass }} mb-0">Categories</h3>
        <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" class="mt-3 space-y-0.5">
        <a href="{{ $shopUrl([], ['category', 'subcategory']) }}"
           class="{{ $rowClass }} {{ !$selectedCategory ? 'bg-brand-green-50 text-brand-green-700' : 'text-slate-600 hover:bg-slate-50' }}">
            All Products
        </a>
        @foreach($categories as $cat)
            @php $isActive = $selectedCategory && $selectedCategory->id === $cat->id; @endphp
            <div x-data="{ expanded: {{ $isActive ? 'true' : 'false' }} }">
                <div class="flex items-center gap-1">
                    <a href="{{ $shopUrl(['category' => $cat->slug], ['subcategory']) }}"
                       class="{{ $rowClass }} flex-1 {{ $isActive && !$selectedSubcategory ? 'bg-brand-green-50 text-brand-green-700' : ($isActive ? 'text-brand-green-700' : 'text-slate-600 hover:bg-slate-50') }}">
                        <span class="flex min-w-0 items-center gap-2.5">
                            @if($cat->image)
                                <img src="{{ $cat->image }}" alt="" class="h-5 w-5 rounded-md object-cover">
                            @else
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-brand-green-50 text-[9px] font-black text-brand-green-700">{{ mb_substr($cat->name, 0, 1) }}</span>
                            @endif
                            <span class="truncate">{{ $cat->name }}</span>
                        </span>
                        <span class="ml-2 shrink-0 text-[11px] text-slate-400">{{ $cat->product_count ?? 0 }}</span>
                    </a>
                    @if($cat->children->isNotEmpty())
                        <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100" @click="expanded = !expanded" :aria-expanded="expanded.toString()" aria-label="Toggle subcategories">
                            <svg class="h-3.5 w-3.5 transition" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    @endif
                </div>
                @if($cat->children->isNotEmpty())
                    <div x-show="expanded" class="ml-4 space-y-0.5 border-l border-slate-100 pl-2">
                        @foreach($cat->children as $child)
                            <a href="{{ $shopUrl(['category' => $cat->slug, 'subcategory' => $child->slug]) }}"
                               class="{{ $rowClass }} {{ ($selectedSubcategory->id ?? null) === $child->id ? 'bg-brand-green-50 text-brand-green-700' : 'text-slate-500 hover:bg-slate-50' }}">
                                <span class="truncate">{{ $child->name }}</span>
                                <span class="text-[11px] text-slate-400">{{ $child->product_count ?? 0 }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

{{-- Subcategories when parent selected (extra clarity) --}}
@if($selectedCategory && $subcategories->isNotEmpty())
<div class="{{ $sectionClass }}" x-data="{ open: true }">
    <button type="button" class="flex w-full items-center justify-between" @click="open = !open">
        <h3 class="{{ $titleClass }} mb-0">Subcategories</h3>
        <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" class="mt-3 space-y-0.5">
        @foreach($subcategories as $child)
            <a href="{{ $shopUrl(['category' => $selectedCategory->slug, 'subcategory' => $child->slug]) }}"
               class="{{ $rowClass }} {{ ($selectedSubcategory->id ?? null) === $child->id ? 'bg-brand-green-50 text-brand-green-700' : 'text-slate-600 hover:bg-slate-50' }}">
                <span>{{ $child->name }}</span>
                <span class="text-[11px] text-slate-400">{{ $child->product_count ?? 0 }}</span>
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- Brands --}}
<div class="{{ $sectionClass }}" x-data="{ open: true, brandQ: '', showAll: false }">
    <button type="button" class="flex w-full items-center justify-between" @click="open = !open">
        <h3 class="{{ $titleClass }} mb-0">Brands</h3>
        <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" class="mt-3 space-y-2">
        <input type="search" x-model="brandQ" placeholder="Search brands…" class="w-full rounded-full border border-slate-200 px-3 py-2 text-xs outline-none focus:border-brand-green-500" aria-label="Search brands">
        <div class="max-h-56 space-y-0.5 overflow-y-auto">
            @foreach($brands as $i => $brand)
                <a href="{{ ($selectedBrand->slug ?? null) === $brand->slug ? $shopUrl([], ['brand']) : $shopUrl(['brand' => $brand->slug]) }}"
                   x-show="(!brandQ || {{ json_encode(mb_strtolower($brand->name)) }}.includes(brandQ.toLowerCase())) && (showAll || {{ $i }} < 8)"
                   class="{{ $rowClass }} {{ ($selectedBrand->slug ?? null) === $brand->slug ? 'bg-brand-green-50 text-brand-green-700' : 'text-slate-600 hover:bg-slate-50' }}">
                    <span class="flex min-w-0 items-center gap-2.5">
                        @if($brand->logo)
                            <img src="{{ $brand->logo }}" alt="" class="h-5 w-5 rounded-md object-contain">
                        @else
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-brand-green-50 text-[9px] font-black text-brand-green-700">{{ mb_substr($brand->name, 0, 1) }}</span>
                        @endif
                        <span class="truncate">{{ $brand->name }}</span>
                    </span>
                    <span class="text-[11px] text-slate-400">{{ $brand->products_count }}</span>
                </a>
            @endforeach
        </div>
        @if($brands->count() > 8)
            <button type="button" class="text-xs font-bold text-brand-green-600 hover:underline" @click="showAll = !showAll" x-text="showAll ? 'Show less' : 'Show more'"></button>
        @endif
    </div>
</div>

{{-- Price --}}
<div class="{{ $sectionClass }}" x-data="{ open: true }">
    <button type="button" class="flex w-full items-center justify-between" @click="open = !open">
        <h3 class="{{ $titleClass }} mb-0">Price Range</h3>
        <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" class="mt-3 space-y-3">
        <form method="GET" action="{{ route('shop') }}" class="space-y-2">
            @foreach(request()->except(['minPrice', 'maxPrice', 'min_price', 'max_price', 'page']) as $key => $val)
                @if(is_scalar($val))
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endif
            @endforeach
            <div class="flex gap-2">
                <label class="sr-only" for="minPrice">Min price</label>
                <input id="minPrice" type="number" name="minPrice" min="0" step="1"
                       value="{{ $minPrice !== null ? (int) $minPrice : '' }}"
                       placeholder="Min ৳" class="w-full rounded-full border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-green-500">
                <label class="sr-only" for="maxPrice">Max price</label>
                <input id="maxPrice" type="number" name="maxPrice" min="0" step="1"
                       value="{{ $maxPrice !== null ? (int) $maxPrice : '' }}"
                       placeholder="Max ৳" class="w-full rounded-full border border-slate-200 px-3 py-2 text-sm outline-none focus:border-brand-green-500">
            </div>
            <button type="submit" class="w-full rounded-full bg-brand-green-600 py-2 text-xs font-bold text-white hover:bg-brand-green-700">Apply</button>
        </form>
        <div class="space-y-1">
            @foreach($quickRanges as $range)
                @php
                    $active = (int) ($minPrice ?? -1) === (int) ($range['min'] ?? -1)
                        && (int) ($maxPrice ?? -1) === (int) ($range['max'] ?? -1);
                @endphp
                <a href="{{ $shopUrl(['minPrice' => $range['min'], 'maxPrice' => $range['max']]) }}"
                   class="{{ $rowClass }} {{ $active ? 'bg-brand-green-50 text-brand-green-700' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $range['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>

{{-- Availability --}}
<div class="{{ $sectionClass }}" x-data="{ open: true }">
    <button type="button" class="flex w-full items-center justify-between" @click="open = !open">
        <h3 class="{{ $titleClass }} mb-0">Availability</h3>
        <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" class="mt-3 space-y-1">
        @foreach([
            'in_stock' => ['In Stock', $stockCounts['in_stock'] ?? 0],
            'low_stock' => ['Low Stock', $stockCounts['low_stock'] ?? 0],
            'out_of_stock' => ['Out of Stock', $stockCounts['out_of_stock'] ?? 0],
        ] as $key => [$label, $count])
            <a href="{{ $availability === $key ? $shopUrl([], ['availability', 'stock']) : $shopUrl(['availability' => $key], ['stock']) }}"
               class="{{ $rowClass }} {{ $availability === $key ? 'bg-brand-green-50 text-brand-green-700' : 'text-slate-600 hover:bg-slate-50' }}">
                <span>{{ $label }}</span>
                <span class="text-[11px] text-slate-400">{{ $count }}</span>
            </a>
        @endforeach
        <a href="{{ $onSale ? $shopUrl([], ['sale']) : $shopUrl(['sale' => '1']) }}"
           class="{{ $rowClass }} {{ $onSale ? 'bg-brand-orange-50 text-brand-orange-700' : 'text-slate-600 hover:bg-slate-50' }}">
            <span>On Sale</span>
        </a>
    </div>
</div>

{{-- Rating (only if reviews exist) --}}
@if(!empty($hasReviews))
<div class="{{ $sectionClass }}" x-data="{ open: true }">
    <button type="button" class="flex w-full items-center justify-between" @click="open = !open">
        <h3 class="{{ $titleClass }} mb-0">Rating</h3>
        <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" class="mt-3 space-y-1">
        @foreach([4.5, 4, 3] as $r)
            <a href="{{ (float) $rating === (float) $r ? $shopUrl([], ['rating']) : $shopUrl(['rating' => $r]) }}"
               class="{{ $rowClass }} {{ (float) $rating === (float) $r ? 'bg-brand-green-50 text-brand-green-700' : 'text-slate-600 hover:bg-slate-50' }}">
                <span class="text-amber-400">{{ str_repeat('★', (int) floor($r)) }}{{ $r != floor($r) ? '☆' : '' }}</span>
                <span>& up</span>
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- Color attributes --}}
@if(!empty($hasColorData) && $colorOptions->isNotEmpty())
<div class="{{ $sectionClass }}" x-data="{ open: true }">
    <button type="button" class="flex w-full items-center justify-between" @click="open = !open">
        <h3 class="{{ $titleClass }} mb-0">Color</h3>
        <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" class="mt-3 flex flex-wrap gap-2">
        @foreach($colorOptions as $c)
            <a href="{{ $color === $c->name ? $shopUrl([], ['color']) : $shopUrl(['color' => $c->name]) }}"
               title="{{ $c->display_name ?? $c->name }}"
               class="flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $color === $c->name ? 'border-brand-green-500 bg-brand-green-50 text-brand-green-700' : 'border-slate-200 text-slate-600' }}">
                <span class="h-3 w-3 rounded-full ring-1 ring-slate-200" style="background-color: {{ $c->hex ?? '#ccc' }}"></span>
                {{ $c->display_name ?? $c->name }}
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- Size attributes --}}
@if(!empty($hasSizeData) && $sizeOptions->isNotEmpty())
<div class="{{ $sectionClass }}" x-data="{ open: true }">
    <button type="button" class="flex w-full items-center justify-between" @click="open = !open">
        <h3 class="{{ $titleClass }} mb-0">Size</h3>
        <svg class="h-4 w-4 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" class="mt-3 flex flex-wrap gap-2">
        @foreach($sizeOptions as $s)
            <a href="{{ $size === $s->name ? $shopUrl([], ['size']) : $shopUrl(['size' => $s->name]) }}"
               class="rounded-full border px-3 py-1 text-xs font-semibold {{ $size === $s->name ? 'border-brand-green-500 bg-brand-green-50 text-brand-green-700' : 'border-slate-200 text-slate-600' }}">
                {{ $s->name }}
            </a>
        @endforeach
    </div>
</div>
@endif

@if(count($activeFilters ?? []))
    <a href="{{ route('shop') }}" class="block w-full rounded-full border border-slate-200 bg-white px-4 py-3 text-center text-sm font-bold text-slate-600 transition hover:border-brand-orange-400 hover:text-brand-orange-600">
        Clear all filters ({{ count($activeFilters) }})
    </a>
@endif
