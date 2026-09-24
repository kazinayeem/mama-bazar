@php
    $isArchived = ($product['productStatus'] ?? '') === 'archived';
    $status = $product['productStatus'] ?? ($product['status'] ?? 'draft');
    $variantsCount = isset($product['variants']) ? count($product['variants']) : 0;
    $stock = (int) ($product['stock'] ?? 0);
    $lowStockAlert = (int) ($product['lowStockAlert'] ?? 10);
    $firstImage = !empty($product['images'][0]) ? $product['images'][0] : null;
    $brandName = $product['brandInfo']['name'] ?? ($product['brand'] ?: null);
    $categoryName = $product['category']['name'] ?? null;
    $statusBadge = match($status) {
        'published', 'active' => 'bg-brand-green-50 text-brand-green-700 border-brand-green-200',
        'draft' => 'bg-slate-100 text-slate-600 border-slate-200',
        'hidden' => 'bg-amber-50 text-amber-700 border-amber-200',
        'archived' => 'bg-slate-200 text-slate-600 border-slate-300',
        default => 'bg-slate-100 text-slate-600 border-slate-200',
    };
@endphp
<div class="rounded-xl border border-slate-200 bg-white p-3 shadow-xs {{ $isArchived ? 'opacity-50' : '' }}">
    <div class="flex gap-3">
        <input type="checkbox" :value="{{ $product['id'] }}" x-model="selected"
               class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-green-600" />
        @if($firstImage)
            <img src="{{ $firstImage }}" alt="" class="h-14 w-14 shrink-0 rounded-lg border border-slate-200 object-cover bg-slate-50" loading="lazy" />
        @else
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-slate-400">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <a href="{{ route('admin.products.show', $product['id']) }}" class="line-clamp-2 text-sm font-semibold text-slate-900 hover:text-brand-green-700">
                        {{ $product['title'] }}
                    </a>
                    <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $product['sku'] ?: '—' }}</p>
                    <p class="truncate text-[11px] text-slate-500">{{ $brandName ?: '—' }} · {{ $categoryName ?: '—' }}</p>
                </div>
                <div class="relative shrink-0" x-data="{ open: false }">
                    <button type="button" @click="open = !open" @click.outside="open = false"
                            class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100" aria-label="Actions">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                        <a href="{{ route('admin.products.show', $product['id']) }}" class="block px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">View</a>
                        <a href="{{ route('admin.products.edit', $product['id']) }}" class="block px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">Edit</a>
                        <button type="button" @click="open = false; duplicateProduct({{ $product['id'] }})" class="block w-full px-3 py-2 text-left text-xs text-slate-700 hover:bg-slate-50">Duplicate</button>
                        @if(!empty($product['slug']))
                            <a href="{{ url('/products/' . $product['slug']) }}" target="_blank" class="block px-3 py-2 text-xs text-slate-700 hover:bg-slate-50">Storefront</a>
                        @endif
                        <button type="button" @click="open = false; confirmDelete({{ $product['id'] }}, @js($product['title']))" class="block w-full px-3 py-2 text-left text-xs text-red-600 hover:bg-red-50">Delete</button>
                    </div>
                </div>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold border capitalize {{ $statusBadge }}">{{ $status }}</span>
                <p class="text-sm font-bold text-slate-900">৳{{ number_format((float)($product['price'] ?? 0), 2) }}</p>
                @if((float)($product['discount'] ?? 0) > 0)
                    <span class="text-[10px] font-semibold text-red-600">-{{ $product['discount'] }}%</span>
                @endif
                @if($stock <= 0)
                    <span class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">Out</span>
                @elseif($stock <= $lowStockAlert)
                    <span class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Stock {{ $stock }}</span>
                @else
                    <span class="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-semibold bg-brand-green-50 text-brand-green-700 border border-brand-green-200">Stock {{ $stock }}</span>
                @endif
                @if($variantsCount > 0)
                    <span class="text-[10px] font-semibold text-violet-600">{{ $variantsCount }} variants</span>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2.5"
         x-data="{ featured: {{ !empty($product['isFeatured']) ? 'true' : 'false' }}, loading: false }">
        <span class="text-[11px] font-semibold text-slate-500">Featured</span>
        <button type="button"
                @click="
                    loading = true;
                    fetch('{{ route('admin.products.toggle-featured', $product['id']) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ featured: !featured })
                    })
                    .then(r => r.json())
                    .then(d => { featured = d.isFeatured; })
                    .finally(() => { loading = false; })
                "
                :disabled="loading"
                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors"
                :class="featured ? 'bg-brand-green-600' : 'bg-slate-300'"
                role="switch"
                :aria-checked="featured">
            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition"
                  :class="featured ? 'translate-x-4' : 'translate-x-0'"></span>
        </button>
    </div>
</div>
