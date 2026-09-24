@extends('layouts.admin', [
    'title' => 'View Product: ' . ($product['title'] ?? 'Product'),
    'headerTitle' => 'Product Details'
])

@section('content')
<div class="mx-auto w-full max-w-[1400px] space-y-5">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('admin.products.index') }}"
                class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition shrink-0"
                title="Back to products"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="admin-page-title">{{ $product['title'] }}</h1>
                <p class="flex items-center gap-2 text-xs text-slate-500">
                    <span>SKU: {{ $product['sku'] ?: '—' }}</span>
                    <span>·</span>
                    <span>Created {{ !empty($product['createdAt']) ? \Carbon\Carbon::parse($product['createdAt'])->format('M d, Y') : '—' }}</span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if(!empty($product['slug']))
                <a
                    href="{{ url('/products/' . $product['slug']) }}"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-xs transition"
                >
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    View on storefront
                </a>
            @endif

            <a
                href="{{ route('admin.products.edit', $product['id']) }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow-sm transition"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
        </div>
    </div>

    <!-- Status + Badges Card -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs flex flex-wrap items-center gap-2">
        @php
            $status = $product['productStatus'] ?? ($product['status'] ?? 'draft');
            $statusVariant = match($status) {
                'published', 'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'draft' => 'bg-slate-100 text-slate-600 border-slate-200',
                'hidden' => 'bg-amber-50 text-amber-700 border-amber-200',
                'archived' => 'bg-slate-200 text-slate-700 border-slate-300',
                default => 'bg-slate-100 text-slate-600 border-slate-200',
            };
        @endphp
        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold border capitalize {{ $statusVariant }}">
            {{ $status }}
        </span>

        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold {{ (int)$product['stock'] > 0 ? 'bg-slate-100 text-slate-700 border border-slate-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            Stock: {{ $product['stock'] }}
        </span>

        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-slate-50 text-slate-600 border border-slate-200">
            Stock status: {{ $product['stockStatus'] ?? 'in_stock' }}
        </span>

        @php
            $flagMap = [
                'isFeatured' => 'Featured',
                'isTrending' => 'Trending',
                'isFlashSale' => 'Flash Sale',
                'isNewArrival' => 'New Arrival',
                'isBestSeller' => 'Best Seller',
                'isLimitedEdition' => 'Limited Edition',
                'isOfficial' => 'Official',
                'isHotDeal' => 'Hot Deal',
                'emiAvailable' => 'EMI Available',
            ];
        @endphp
        @foreach($flagMap as $key => $label)
            @if(!empty($product[$key]))
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    {{ $label }}
                </span>
            @endif
        @endforeach
    </div>

    <!-- Images + Details Grid -->
    <div class="grid gap-5 lg:grid-cols-3">
        <!-- Images Card -->
        <div class="lg:col-span-1 rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Images</h2>
            @if(empty($product['images']))
                <p class="text-xs text-slate-400">No images uploaded</p>
            @else
                <div class="grid grid-cols-2 gap-2">
                    @foreach($product['images'] as $img)
                        <img
                            src="{{ $img }}"
                            alt="{{ $product['title'] }}"
                            class="aspect-square w-full rounded-xl border border-slate-200 object-cover bg-slate-50"
                        />
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Details Card -->
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Details</h2>
            <div class="divide-y divide-slate-100 text-xs">
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Price</span>
                    <span class="font-bold text-slate-900">৳{{ number_format((float)($product['price'] ?? 0), 2) }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Sale price</span>
                    <span class="font-semibold text-slate-900">{{ !empty($product['salePrice']) ? '৳' . number_format((float)$product['salePrice'], 2) : '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Discount</span>
                    <span class="text-slate-700">{{ (float)($product['discount'] ?? 0) > 0 ? '-' . $product['discount'] . '%' : '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Cost price</span>
                    <span class="text-slate-700">{{ !empty($product['costPrice']) ? '৳' . number_format((float)$product['costPrice'], 2) : '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Category</span>
                    <span class="text-slate-900 font-semibold">{{ $product['category']['name'] ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Sub-category</span>
                    <span class="text-slate-700">{{ $product['subCategory']['name'] ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Child category</span>
                    <span class="text-slate-700">{{ $product['childCategory']['name'] ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Brand</span>
                    <span class="text-slate-900 font-semibold">{{ $product['brandInfo']['name'] ?? ($product['brand'] ?: '—') }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Collection</span>
                    <span class="text-slate-700">{{ $product['collection']['name'] ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Vendor</span>
                    <span class="text-slate-700">{{ $product['vendor']['name'] ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Supplier</span>
                    <span class="text-slate-700">{{ $product['supplierInfo']['name'] ?? ($product['supplier'] ?: '—') }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Country of origin</span>
                    <span class="text-slate-700">{{ $product['countryOfOrigin'] ?: '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Weight</span>
                    <span class="text-slate-700">{{ $product['weight'] ?: '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Dimensions</span>
                    <span class="text-slate-700">{{ $product['dimensions'] ?: '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Warranty</span>
                    <span class="text-slate-700">{{ $product['warranty'] ?: '—' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="font-medium text-slate-500">Tags</span>
                    <span class="text-slate-700">{{ !empty($product['tags']) ? implode(', ', $product['tags']) : '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Description Card -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-3">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Description</h2>
        @if(!empty($product['description']))
            <div class="prose prose-sm max-w-none text-slate-700 text-xs">
                {!! $product['description'] !!}
            </div>
        @else
            <p class="text-xs text-slate-400">No description provided</p>
        @endif
    </div>

    <!-- Specs & Variants 2-col Grid -->
    <div class="grid gap-5 lg:grid-cols-2">
        <!-- Specifications -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-3">
            <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Specifications</h2>
            @if(empty($product['specs']))
                <p class="text-xs text-slate-400">No specifications defined</p>
            @else
                <div class="divide-y divide-slate-100 text-xs">
                    @foreach($product['specs'] as $spec)
                        <div class="flex items-center justify-between py-2">
                            <span class="font-medium text-slate-500">{{ $spec['label'] }}</span>
                            <span class="font-semibold text-slate-800 text-right">{{ $spec['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Variants -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-3">
            <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Variants</h2>
            @if(empty($product['variants']))
                <p class="text-xs text-slate-400">No variants for this product (simple product)</p>
            @else
                <div class="space-y-2">
                    @foreach($product['variants'] as $v)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 text-xs bg-slate-50/50">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 truncate">{{ $v['name'] }}</p>
                                <p class="text-[11px] font-mono text-slate-500">{{ $v['sku'] ?: 'No SKU' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-slate-900">৳{{ number_format((float)($v['price'] ?: $product['price']), 2) }}</p>
                                <p class="text-[11px] text-slate-500">Qty: {{ $v['stock'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Related Products -->
    @if(!empty($product['relations']))
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-3">
            <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">Related Products</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($product['relations'] as $rel)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs border border-slate-200 bg-slate-50 text-slate-700">
                        <span class="font-semibold capitalize">{{ str_replace('_', ' ', $rel['type'] ?? '') }}:</span>
                        <span>#{{ $rel['relatedProductId'] ?? ($rel['relatedProduct']['id'] ?? '') }}</span>
                        @if(!empty($rel['relatedProduct']['title']))
                            <span class="text-slate-500 font-medium">({{ $rel['relatedProduct']['title'] }})</span>
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <!-- SEO Card -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-3">
        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2">SEO &amp; Metadata</h2>
        <div class="divide-y divide-slate-100 text-xs">
            <div class="flex items-center justify-between py-2">
                <span class="font-medium text-slate-500">SEO title</span>
                <span class="text-slate-800">{{ $product['seoTitle'] ?: '—' }}</span>
            </div>
            <div class="flex items-center justify-between py-2">
                <span class="font-medium text-slate-500">SEO description</span>
                <span class="text-slate-800 max-w-md text-right">{{ $product['seoDescription'] ?: '—' }}</span>
            </div>
            <div class="flex items-center justify-between py-2">
                <span class="font-medium text-slate-500">SEO keywords</span>
                <span class="text-slate-800">{{ $product['seoKeywords'] ?: '—' }}</span>
            </div>
            <div class="flex items-center justify-between py-2">
                <span class="font-medium text-slate-500">Canonical URL</span>
                <span class="text-slate-800 font-mono">{{ $product['canonicalUrl'] ?: '—' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
