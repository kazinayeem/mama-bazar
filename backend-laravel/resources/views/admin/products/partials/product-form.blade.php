@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $isEditing = isset($product) && !empty($product['id']);
    $actionUrl = $isEditing ? route('admin.products.update', $product['id']) : route('admin.products.store');
    $method = $isEditing ? 'PUT' : 'POST';

    // Format existing images for the uploader with old() support
    $existingImages = [];
    $rawImages = old('images');
    if ($rawImages) {
        if (is_string($rawImages)) {
            $decoded = json_decode($rawImages, true);
            $rawImages = is_array($decoded) ? $decoded : [];
        }
    } elseif (!empty($product['images']) && is_array($product['images'])) {
        $rawImages = $product['images'];
    } else {
        $rawImages = [];
    }

    if (is_array($rawImages)) {
        foreach ($rawImages as $img) {
            $url = is_array($img) ? ($img['url'] ?? '') : (is_string($img) ? $img : '');
            if ($url !== '' && !str_starts_with($url, 'blob:') && !str_starts_with($url, 'data:')) {
                $existingImages[] = [
                    'id' => uniqid('img_'),
                    'url' => $url,
                    'status' => 'done',
                    'progress' => 100,
                ];
            }
        }
    }

    // Format existing variants
    $existingVariants = [];
    if (!empty($product['variants']) && is_array($product['variants'])) {
        foreach ($product['variants'] as $v) {
            $rawThumb = $v['thumbnail'] ?? '';
            $existingVariants[] = [
                'key' => uniqid(),
                'id' => $v['id'] ?? null,
                'name' => $v['name'] ?? '',
                'options' => !empty($v['options']) ? (is_string($v['options']) ? $v['options'] : json_encode($v['options'])) : '',
                'price' => isset($v['price']) ? (string)$v['price'] : '',
                'salePrice' => isset($v['salePrice']) ? (string)$v['salePrice'] : (isset($v['discountPrice']) ? (string)$v['discountPrice'] : ''),
                'sku' => $v['sku'] ?? '',
                'barcode' => $v['barcode'] ?? '',
                'stock' => isset($v['stock']) ? (string)$v['stock'] : '0',
                'thumbnail' => $rawThumb,
                '_savedPath' => $rawThumb,
                '_preview' => null,
                'removeImage' => false,
                'availability' => isset($v['availability']) ? (bool)$v['availability'] : true,
            ];
        }
    }

    // Format existing specs
    $existingSpecs = [];
    if (!empty($product['specs']) && is_array($product['specs'])) {
        foreach ($product['specs'] as $s) {
            $existingSpecs[] = [
                'key' => uniqid(),
                'label' => $s['label'] ?? '',
                'value' => $s['value'] ?? '',
            ];
        }
    }

    // Format existing relations
    $existingRelations = [];
    if (!empty($product['relations']) && is_array($product['relations'])) {
        foreach ($product['relations'] as $r) {
            $existingRelations[] = [
                'key' => uniqid(),
                'type' => $r['type'] ?? 'frequently_bought_together',
                'relatedProductId' => (string)($r['relatedProductId'] ?? ($r['relatedProduct']['id'] ?? '')),
                'relatedTitle' => $r['relatedProduct']['title'] ?? '',
            ];
        }
    }

    $structuredDataString = '';
    if (!empty($product['structuredData'])) {
        $structuredDataString = is_string($product['structuredData']) 
            ? $product['structuredData'] 
            : json_encode($product['structuredData'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    $resolveBool = function ($snakeKey, $camelKey, $default) {
        if (session()->has('_old_input')) {
            $val = old($snakeKey, old($camelKey));
            if ($val !== null) {
                return filter_var($val, FILTER_VALIDATE_BOOLEAN);
            }
            return false;
        }
        return (bool) $default;
    };

    $initialForm = [
        'hasVariants' => $resolveBool('has_variants', 'hasVariants', !empty($existingVariants)),
        'title' => old('title', $product['title'] ?? ''),
        'slug' => old('slug', $product['slug'] ?? ''),
        'description' => old('description', $product['description'] ?? ''),
        'shortDescription' => old('short_description', old('shortDescription', $product['shortDescription'] ?? '')),
        'returnPolicy' => old('return_policy', old('returnPolicy', $product['returnPolicy'] ?? '')),
        'categoryId' => (string) old('category_id', old('categoryId', $product['categoryId'] ?? '')),
        'subCategoryId' => (string) old('sub_category_id', old('subCategoryId', $product['subCategoryId'] ?? '')),
        'childCategoryId' => (string) old('child_category_id', old('childCategoryId', $product['childCategoryId'] ?? '')),
        'brandId' => (string) old('brand_id', old('brandId', $product['brandId'] ?? '')),
        'collectionId' => (string) old('collection_id', old('collectionId', $product['collectionId'] ?? '')),
        'vendorId' => (string) old('vendor_id', old('vendorId', $product['vendorId'] ?? '')),
        'supplierId' => (string) old('supplier_id', old('supplierId', $product['supplierId'] ?? '')),
        'sku' => old('sku', $product['sku'] ?? ''),
        'barcode' => old('barcode', $product['barcode'] ?? ''),
        'warehouse' => old('warehouse', $product['warehouse'] ?? ''),
        'countryOfOrigin' => old('country_of_origin', old('countryOfOrigin', $product['countryOfOrigin'] ?? '')),
        'weight' => old('weight', $product['weight'] ?? ''),
        'dimensions' => old('dimensions', $product['dimensions'] ?? ''),
        'warranty' => old('warranty', $product['warranty'] ?? ''),
        'videoUrl' => old('video_url', old('videoUrl', $product['videoUrl'] ?? '')),
        'paymentPhoneNumber' => old('payment_phone_number', old('paymentPhoneNumber', $product['paymentPhoneNumber'] ?? '')),
        'price' => (string) old('price', isset($product['price']) ? $product['price'] : ''),
        'salePrice' => (string) old('sale_price', old('salePrice', isset($product['salePrice']) ? $product['salePrice'] : '')),
        'discount' => (string) old('discount', isset($product['discount']) ? $product['discount'] : ''),
        'costPrice' => (string) old('cost_price', old('costPrice', isset($product['costPrice']) ? $product['costPrice'] : '')),
        'profitMargin' => (string) old('profit_margin', old('profitMargin', isset($product['profitMargin']) ? $product['profitMargin'] : '')),
        'tax' => (string) old('tax', isset($product['tax']) ? $product['tax'] : ''),
        'vat' => (string) old('vat', isset($product['vat']) ? $product['vat'] : ''),
        'shippingCharge' => (string) old('shipping_charge', old('shippingCharge', isset($product['shippingCharge']) ? $product['shippingCharge'] : '')),
        'codFee' => (string) old('cod_fee', old('codFee', isset($product['codFee']) ? $product['codFee'] : '')),
        'flashSalePrice' => (string) old('flash_sale_price', old('flashSalePrice', isset($product['flashSalePrice']) ? $product['flashSalePrice'] : '')),
        'wholesalePrice' => (string) old('wholesale_price', old('wholesalePrice', isset($product['wholesalePrice']) ? $product['wholesalePrice'] : '')),
        'dealerPrice' => (string) old('dealer_price', old('dealerPrice', isset($product['dealerPrice']) ? $product['dealerPrice'] : '')),
        'stock' => (string) old('stock', isset($product['stock']) ? $product['stock'] : '0'),
        'lowStockAlert' => (string) old('low_stock_alert', old('lowStockAlert', isset($product['lowStockAlert']) ? $product['lowStockAlert'] : '')),
        'minOrder' => (string) old('min_order', old('minOrder', isset($product['minOrder']) ? $product['minOrder'] : '')),
        'maxOrder' => (string) old('max_order', old('maxOrder', isset($product['maxOrder']) ? $product['maxOrder'] : '')),
        'stockStatus' => old('stock_status', old('stockStatus', $product['stockStatus'] ?? 'in_stock')),
        'unlimitedStock' => $resolveBool('unlimited_stock', 'unlimitedStock', $product['unlimitedStock'] ?? ($product['unlimited_stock'] ?? false)),
        'backorder' => $resolveBool('backorder', 'allow_backorder', $product['backorder'] ?? false),
        'trackInventory' => $resolveBool('track_inventory', 'trackInventory', $product['trackInventory'] ?? ($product['track_inventory'] ?? true)),
        'productStatus' => old('product_status', old('productStatus', $product['productStatus'] ?? 'draft')),
        'status' => old('status', $product['status'] ?? 'inactive'),
        'isFeatured' => $resolveBool('is_featured', 'isFeatured', !empty($product['isFeatured'])),
        'isTrending' => $resolveBool('is_trending', 'isTrending', !empty($product['isTrending'])),
        'isFlashSale' => $resolveBool('is_flash_sale', 'isFlashSale', !empty($product['isFlashSale'])),
        'isNewArrival' => $resolveBool('is_new_arrival', 'isNewArrival', !empty($product['isNewArrival'])),
        'isBestSeller' => $resolveBool('is_best_seller', 'isBestSeller', !empty($product['isBestSeller'])),
        'isLimitedEdition' => $resolveBool('is_limited_edition', 'isLimitedEdition', !empty($product['isLimitedEdition'])),
        'isOfficial' => $resolveBool('is_official', 'isOfficial', !empty($product['isOfficial'])),
        'isHotDeal' => $resolveBool('is_hot_deal', 'isHotDeal', !empty($product['isHotDeal'])),
        'emiAvailable' => $resolveBool('emi_available', 'emiAvailable', !empty($product['emiAvailable'])),
        'seoTitle' => old('seo_title', old('seoTitle', $product['seoTitle'] ?? '')),
        'seoDescription' => old('seo_description', old('seoDescription', $product['seoDescription'] ?? '')),
        'seoKeywords' => old('seo_keywords', old('seoKeywords', $product['seoKeywords'] ?? '')),
        'canonicalUrl' => old('canonical_url', old('canonicalUrl', $product['canonicalUrl'] ?? '')),
        'ogImage' => old('og_image', old('ogImage', $product['ogImage'] ?? '')),
        'twitterImage' => old('twitter_image', old('twitterImage', $product['twitterImage'] ?? '')),
        'structuredData' => old('structured_data', old('structuredData', $structuredDataString)),
        'tags' => old('tags', $product['tags'] ?? []),
        'features' => old('features', $product['features'] ?? []),
        'sizeOptions' => old('size_options', old('sizeOptions', $product['sizeOptions'] ?? [])),
        'colorOptions' => old('color_options', old('colorOptions', !empty($product['colorOptions']) 
            ? array_map(fn($c) => is_array($c) ? ($c['name'] ?? '') : $c, $product['colorOptions']) 
            : [])),
        'images' => $existingImages,
        'variants' => $existingVariants,
        'specs' => $existingSpecs,
        'relations' => $existingRelations,
    ];
@endphp

<form
    id="productMainForm"
    action="{{ $actionUrl }}"
    method="POST"
    enctype="multipart/form-data"
    x-data="productForm({
        initial: {{ Js::from($initialForm) }},
        isEditing: {{ $isEditing ? 'true' : 'false' }},
        csrfToken: '{{ csrf_token() }}',
        uploadUrl: '{{ route('admin.products.upload-image') }}',
        colorsCatalog: {{ Js::from($colors) }},
        sizesCatalog: {{ Js::from($sizes) }}
    })"
    @submit.prevent="submitForm()"
    class="mx-auto w-full max-w-[1400px] space-y-6"
>
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <input type="hidden" name="save_mode" :value="saveMode">
    <input type="hidden" name="has_variants" :value="form.hasVariants ? 1 : 0">
    <input type="hidden" name="track_inventory" :value="form.trackInventory ? 1 : 0">
    <input type="hidden" name="unlimited_stock" :value="form.unlimitedStock ? 1 : 0">
    <input type="hidden" name="backorder" :value="form.backorder ? 1 : 0">
    <input type="hidden" name="emi_available" :value="form.emiAvailable ? 1 : 0">
    <input type="hidden" name="is_featured" :value="form.isFeatured ? 1 : 0">
    <input type="hidden" name="is_trending" :value="form.isTrending ? 1 : 0">
    <input type="hidden" name="is_flash_sale" :value="form.isFlashSale ? 1 : 0">
    <input type="hidden" name="is_new_arrival" :value="form.isNewArrival ? 1 : 0">
    <input type="hidden" name="is_best_seller" :value="form.isBestSeller ? 1 : 0">
    <input type="hidden" name="is_limited_edition" :value="form.isLimitedEdition ? 1 : 0">
    <input type="hidden" name="is_official" :value="form.isOfficial ? 1 : 0">
    <input type="hidden" name="is_hot_deal" :value="form.isHotDeal ? 1 : 0">
    <input type="hidden" name="tags" :value="JSON.stringify(form.tags)">
    <input type="hidden" name="features" :value="JSON.stringify(form.features)">
    <input type="hidden" name="size_options" :value="JSON.stringify(form.sizeOptions)">
    <input type="hidden" name="color_options" :value="JSON.stringify(form.colorOptions)">
    <input type="hidden" name="deleted_images" :value="JSON.stringify(deletedImageUrls)">
    <input type="hidden" name="images" :value="JSON.stringify(form.images.filter(i => (!i.status || i.status === 'done') && i.url && !i.url.startsWith('blob:') && !i.url.startsWith('data:')).map(i => i.url))">
    {{-- Variants are submitted as variants[n][…] fields (incl. image files), not JSON --}}
    <input type="hidden" name="specs" :value="JSON.stringify(form.specs)">
    <input type="hidden" name="relations" :value="JSON.stringify(form.relations)">

    <!-- Sticky Top Action Bar -->
    <div class="sticky top-0 z-30 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 shadow-xs">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <a
                    href="{{ route('admin.products.index') }}"
                    class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition shrink-0"
                    title="Back to products"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-slate-900 sm:text-base" x-text="form.title.trim() ? form.title : 'Untitled product'">Untitled product</p>
                    <p class="text-xs text-slate-500">{{ $isEditing ? 'Editing product' : 'Creating a new product' }}</p>
                </div>
                <span
                    class="hidden sm:inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border uppercase"
                    :class="{
                        'bg-emerald-50 text-emerald-700 border-emerald-200': form.productStatus === 'published',
                        'bg-slate-100 text-slate-600 border-slate-200': form.productStatus === 'draft',
                        'bg-amber-50 text-amber-700 border-amber-200': form.productStatus === 'hidden',
                        'bg-slate-200 text-slate-700 border-slate-300': form.productStatus === 'archived',
                    }"
                    x-text="form.productStatus"
                ></span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select
                    name="product_status"
                    x-model="form.productStatus"
                    class="px-3 py-1.5 text-xs font-semibold border border-slate-300 rounded-lg bg-white focus:outline-hidden"
                >
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="hidden">Hidden</option>
                    <option value="archived">Archived</option>
                </select>

                <a
                    href="{{ route('admin.products.index') }}"
                    class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition"
                >
                    Cancel
                </a>

                @if($isEditing && !empty($product['id']))
                    <a
                        href="{{ route('admin.products.show', $product['id']) }}"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Preview
                    </a>
                @endif

                <button
                    type="button"
                    @click="submitMode('draft')"
                    :disabled="submitting || uploadingCount > 0"
                    class="inline-flex items-center gap-1 px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg transition disabled:opacity-50 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span x-text="submitting && saveMode === 'draft' ? 'Saving…' : (uploadingCount > 0 ? 'Uploading…' : 'Save Draft')">Save Draft</span>
                </button>

                <button
                    type="button"
                    @click="submitMode('publish')"
                    :disabled="submitting || uploadingCount > 0"
                    class="inline-flex items-center gap-1 px-4 py-1.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow-sm transition disabled:opacity-50 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span x-text="submitting && saveMode === 'publish' ? 'Saving…' : (uploadingCount > 0 ? 'Uploading…' : (isEditing ? 'Save & Publish' : 'Publish'))">
                        {{ $isEditing ? 'Save & Publish' : 'Publish' }}
                    </span>
                </button>
            </div>
        </div>
    </div>

    @if(isset($errors) && $errors->any())
        @php
            $errorCount = $errors->count();
            $fieldAnchorMap = [
                'title' => 'field_title',
                'slug' => 'field_slug',
                'description' => 'field_description',
                'short_description' => 'field_short_description',
                'price' => 'field_price',
                'sale_price' => 'field_sale_price',
                'discount' => 'field_discount',
                'cost_price' => 'field_cost_price',
                'category_id' => 'field_category_id',
                'sub_category_id' => 'field_sub_category_id',
                'child_category_id' => 'field_child_category_id',
                'brand_id' => 'field_brand_id',
                'collection_id' => 'field_collection_id',
                'vendor_id' => 'field_vendor_id',
                'supplier_id' => 'field_supplier_id',
                'sku' => 'field_sku',
                'barcode' => 'field_barcode',
                'stock' => 'field_stock',
                'low_stock_alert' => 'field_low_stock_alert',
                'min_order' => 'field_min_order',
                'max_order' => 'field_max_order',
                'track_inventory' => 'field_track_inventory',
                'unlimited_stock' => 'field_unlimited_stock',
                'backorder' => 'field_backorder',
                'seo_title' => 'field_seo_title',
                'seo_description' => 'field_seo_description',
                'seo_keywords' => 'field_seo_keywords',
                'canonical_url' => 'field_canonical_url',
                'structured_data' => 'field_structured_data',
                'images' => 'field_images',
                'variants' => 'field_has_variants',
                'has_variants' => 'field_has_variants',
            ];
        @endphp
        <!-- Enhanced Server Validation Errors Banner -->
        <div id="errorSummaryBanner" role="alert" class="rounded-2xl border border-red-200 bg-red-50/90 p-5 shadow-xs transition-all">
            <div class="flex items-start gap-3">
                <div class="rounded-xl bg-red-100 p-2 text-red-600 shrink-0">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-bold text-red-900">
                        {{ $errorCount === 1 ? 'There is 1 validation issue with your submission' : "There are {$errorCount} validation issues that need your attention" }}
                    </h3>
                    <p class="mt-0.5 text-xs text-red-700">Please review and correct the highlighted fields below before saving.</p>
                    <ul class="mt-3 space-y-1.5 text-xs">
                        @foreach($errors->getMessages() as $field => $messages)
                            @php
                                $baseField = explode('.', $field)[0];
                                $targetId = $fieldAnchorMap[$baseField] ?? $fieldAnchorMap[$field] ?? null;
                            @endphp
                            @foreach($messages as $msg)
                                <li class="flex items-center gap-2 text-red-700">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500 shrink-0"></span>
                                    @if($targetId)
                                        <a href="#{{ $targetId }}" class="font-medium underline hover:text-red-900 transition focus:outline-none focus:ring-1 focus:ring-red-400 rounded">
                                            {{ $msg }}
                                        </a>
                                    @else
                                        <span>{{ $msg }}</span>
                                    @endif
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Client Error Banner -->
    <div x-show="validationErrors.length > 0" x-cloak class="rounded-2xl border border-red-200 bg-red-50/90 p-5 shadow-xs">
        <div class="flex items-start gap-3">
            <div class="rounded-xl bg-red-100 p-2 text-red-600 shrink-0">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-bold text-red-900" x-text="`${validationErrors.length} issue${validationErrors.length > 1 ? 's' : ''} prevent saving:`"></h3>
                <ul class="mt-2 list-disc list-inside text-xs text-red-700 space-y-1">
                    <template x-for="(err, idx) in validationErrors" :key="idx">
                        <li x-text="err"></li>
                    </template>
                </ul>
            </div>
        </div>
    </div>

    <!-- Section 1: General Information -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-5">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                General Information
            </h2>
            <p class="text-xs text-slate-500">Core product details, organization and media</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <!-- Title -->
            <div class="sm:col-span-2 space-y-1.5">
                <label for="field_title" class="text-xs font-bold text-slate-800">
                    Product Title <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="field_title"
                    name="title"
                    x-model="form.title"
                    @input="handleTitleChange($event.target.value)"
                    placeholder='e.g. Samsung 55" 4K Smart TV'
                    required
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('title') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg focus:outline-hidden focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900"
                />
                @error('title')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Slug -->
            <div class="sm:col-span-2 space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="field_slug" class="text-xs font-bold text-slate-800">Slug (URL)</label>
                    <button
                        type="button"
                        @click="resetSlugFromTitle()"
                        class="text-[11px] font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reset from title
                    </button>
                </div>
                <input
                    type="text"
                    id="field_slug"
                    name="slug"
                    x-model="form.slug"
                    @input="slugTouched = true"
                    placeholder="auto-generated from title, e.g. samsung-tv"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('slug') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg font-mono focus:outline-hidden focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900"
                />
                @error('slug')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
                <p class="text-[11px] text-slate-400">Auto-generated from title. Only English letters, numbers and hyphens.</p>
            </div>

            <!-- Short Description -->
            <div class="sm:col-span-2 space-y-1.5">
                <label for="field_short_description" class="text-xs font-bold text-slate-800">Short Description</label>
                <textarea
                    id="field_short_description"
                    name="short_description"
                    x-model="form.shortDescription"
                    rows="2"
                    placeholder="One-line product highlight shown on cards"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('short_description') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg focus:outline-hidden focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900"
                ></textarea>
                @error('short_description')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Full Description (Quill Snow rich HTML) -->
            <div
                class="sm:col-span-2 space-y-1.5"
                x-data="productRichEditor({
                    initial: form.description,
                    uploadUrl: @js(route('admin.products.upload-editor-image')),
                    csrfToken: @js(csrf_token()),
                })"
            >
                <label class="text-xs font-bold text-slate-800">Full Description</label>
                <div class="mb-rte">
                    <div class="hidden" aria-hidden="true">
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('bold') }" @click="run(e => e.chain().focus().toggleBold().run())" title="Bold (Ctrl+B)"><span class="font-bold">B</span></button>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('italic') }" @click="run(e => e.chain().focus().toggleItalic().run())" title="Italic (Ctrl+I)"><span class="italic">I</span></button>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('underline') }" @click="run(e => e.chain().focus().toggleUnderline().run())" title="Underline (Ctrl+U)"><span class="underline">U</span></button>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('strike') }" @click="run(e => e.chain().focus().toggleStrike().run())" title="Strikethrough"><span class="line-through">S</span></button>
                        <span class="mb-rte-sep"></span>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('heading', { level: 1 }) }" @click="run(e => e.chain().focus().toggleHeading({ level: 1 }).run())" title="Heading 1">H1</button>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('heading', { level: 2 }) }" @click="run(e => e.chain().focus().toggleHeading({ level: 2 }).run())" title="Heading 2">H2</button>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('heading', { level: 3 }) }" @click="run(e => e.chain().focus().toggleHeading({ level: 3 }).run())" title="Heading 3">H3</button>
                        <span class="mb-rte-sep"></span>
                        <select class="mb-rte-select" @change="setColor($event.target.value); $event.target.value=''" title="Text color">
                            <option value="">Color</option>
                            <template x-for="c in colors" :key="c.value || 'def'">
                                <option :value="c.value" x-text="c.label"></option>
                            </template>
                        </select>
                        <select class="mb-rte-select" @change="setHighlight($event.target.value); $event.target.value=''" title="Highlight">
                            <option value="">Highlight</option>
                            <template x-for="h in highlights" :key="h.value || 'none'">
                                <option :value="h.value" x-text="h.label"></option>
                            </template>
                        </select>
                        <span class="mb-rte-sep"></span>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('bulletList') }" @click="run(e => e.chain().focus().toggleBulletList().run())" title="Bullet list">• List</button>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('orderedList') }" @click="run(e => e.chain().focus().toggleOrderedList().run())" title="Numbered list">1. List</button>
                        <button type="button" class="mb-rte-btn" @click="run(e => e.chain().focus().sinkListItem('listItem').run())" title="Indent">Indent</button>
                        <button type="button" class="mb-rte-btn" @click="run(e => e.chain().focus().liftListItem('listItem').run())" title="Outdent">Outdent</button>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('blockquote') }" @click="run(e => e.chain().focus().toggleBlockquote().run())" title="Blockquote">Quote</button>
                        <button type="button" class="mb-rte-btn" @click="run(e => e.chain().focus().setHorizontalRule().run())" title="Horizontal rule">HR</button>
                        <span class="mb-rte-sep"></span>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive({ textAlign: 'left' }) }" @click="run(e => e.chain().focus().setTextAlign('left').run())" title="Align left">Left</button>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive({ textAlign: 'center' }) }" @click="run(e => e.chain().focus().setTextAlign('center').run())" title="Align center">Center</button>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive({ textAlign: 'right' }) }" @click="run(e => e.chain().focus().setTextAlign('right').run())" title="Align right">Right</button>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive({ textAlign: 'justify' }) }" @click="run(e => e.chain().focus().setTextAlign('justify').run())" title="Justify">Justify</button>
                        <span class="mb-rte-sep"></span>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('link') }" @click="setLink()" title="Insert link">Link</button>
                        <button type="button" class="mb-rte-btn" :disabled="uploading" @click="$refs.imageInput.click()" title="Upload image" x-text="uploading ? '…' : 'Image'"></button>
                        <button type="button" class="mb-rte-btn" :class="{ 'is-active': isActive('table') }" @click="run(e => e.isActive('table') ? e.chain().focus().deleteTable().run() : e.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run())" title="Insert / delete table">Table</button>
                        <template x-if="isActive('table')">
                            <span class="inline-flex items-center gap-0.5">
                                <button type="button" class="mb-rte-btn" @click="run(e => e.chain().focus().addRowAfter().run())" title="Add row">+Row</button>
                                <button type="button" class="mb-rte-btn" @click="run(e => e.chain().focus().addColumnAfter().run())" title="Add column">+Col</button>
                                <button type="button" class="mb-rte-btn" @click="run(e => e.chain().focus().deleteRow().run())" title="Delete row">-Row</button>
                                <button type="button" class="mb-rte-btn" @click="run(e => e.chain().focus().deleteColumn().run())" title="Delete column">-Col</button>
                            </span>
                        </template>
                        <span class="mb-rte-sep"></span>
                        <button type="button" class="mb-rte-btn" @click="clearFormat()" title="Clear formatting">Clear</button>
                        <button type="button" class="mb-rte-btn" @click="run(e => e.chain().focus().undo().run())" title="Undo (Ctrl+Z)">Undo</button>
                        <button type="button" class="mb-rte-btn" @click="run(e => e.chain().focus().redo().run())" title="Redo (Ctrl+Shift+Z)">Redo</button>
                        <button type="button" class="mb-rte-btn ml-auto text-[10px] font-mono" @click="toggleSource()" title="HTML source">HTML</button>
                        <input type="file" class="hidden" x-ref="imageInput" accept="image/jpeg,image/png,image/webp" @change="uploadImages($event.target.files)">
                    </div>
                    <div x-ref="editorMount" class="min-h-[180px]" aria-label="Full product description"></div>
                    <textarea
                        x-cloak
                        x-show="sourceMode"
                        x-model="sourceHtml"
                        @input="onSourceInput()"
                        rows="10"
                        class="w-full border-0 p-3 text-xs font-mono focus:outline-hidden bg-slate-900 text-slate-100"
                        aria-label="HTML source"
                    ></textarea>
                </div>
                <p class="text-[11px] text-slate-400">Rich HTML is sanitized on save. Images upload to local storage only (JPG/PNG/WebP).</p>
                {{-- Local descriptionHtml is the source of truth for submit (parent form is also synced). --}}
                <input type="hidden" name="description" x-ref="descriptionInput" :value="descriptionHtml">
            </div>
        </div>

        <!-- Classification & Reference Selects -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 pt-2">
            <!-- Category -->
            <div class="space-y-1.5">
                <label for="field_category_id" class="text-xs font-bold text-slate-800">Category</label>
                <select
                    id="field_category_id"
                    name="category_id"
                    x-model="form.categoryId"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('category_id') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg bg-white"
                >
                    <option value="">Select Category</option>
                    @foreach($categories->whereNull('parent_id') as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Sub Category -->
            <div class="space-y-1.5">
                <label for="field_sub_category_id" class="text-xs font-bold text-slate-800">Sub-category</label>
                <select
                    id="field_sub_category_id"
                    name="sub_category_id"
                    x-model="form.subCategoryId"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('sub_category_id') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg bg-white"
                >
                    <option value="">Select Sub-category</option>
                    @foreach($categories->whereNotNull('parent_id') as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>
                @error('sub_category_id')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Child Category -->
            <div class="space-y-1.5">
                <label for="field_child_category_id" class="text-xs font-bold text-slate-800">Child category</label>
                <select
                    id="field_child_category_id"
                    name="child_category_id"
                    x-model="form.childCategoryId"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('child_category_id') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg bg-white"
                >
                    <option value="">Select Child Category</option>
                    @foreach($categories->whereNotNull('parent_id') as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>
                @error('child_category_id')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Brand -->
            <div class="space-y-1.5">
                <label for="field_brand_id" class="text-xs font-bold text-slate-800">Brand</label>
                <select
                    id="field_brand_id"
                    name="brand_id"
                    x-model="form.brandId"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('brand_id') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg bg-white"
                >
                    <option value="">Select Brand</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
                @error('brand_id')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Collection -->
            <div class="space-y-1.5">
                <label for="field_collection_id" class="text-xs font-bold text-slate-800">Collection</label>
                <select
                    id="field_collection_id"
                    name="collection_id"
                    x-model="form.collectionId"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('collection_id') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg bg-white"
                >
                    <option value="">Select Collection</option>
                    @foreach($collections as $col)
                        <option value="{{ $col->id }}">{{ $col->name }}</option>
                    @endforeach
                </select>
                @error('collection_id')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Vendor -->
            <div class="space-y-1.5">
                <label for="field_vendor_id" class="text-xs font-bold text-slate-800">Vendor</label>
                <select
                    id="field_vendor_id"
                    name="vendor_id"
                    x-model="form.vendorId"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('vendor_id') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg bg-white"
                >
                    <option value="">Select Vendor</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                    @endforeach
                </select>
                @error('vendor_id')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Supplier -->
            <div class="space-y-1.5">
                <label for="field_supplier_id" class="text-xs font-bold text-slate-800">Supplier</label>
                <select
                    id="field_supplier_id"
                    name="supplier_id"
                    x-model="form.supplierId"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('supplier_id') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg bg-white"
                >
                    <option value="">Select Supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- SKU -->
            <div class="space-y-1.5">
                <label for="field_sku" class="text-xs font-bold text-slate-800">SKU</label>
                <input
                    type="text"
                    id="field_sku"
                    name="sku"
                    x-model="form.sku"
                    placeholder="e.g. TV-55-4K"
                    class="w-full px-3 py-2 text-xs font-mono border {{ $errors->has('sku') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('sku')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Barcode -->
            <div class="space-y-1.5">
                <label for="field_barcode" class="text-xs font-bold text-slate-800">Barcode</label>
                <input
                    type="text"
                    id="field_barcode"
                    name="barcode"
                    x-model="form.barcode"
                    placeholder="e.g. 8801234567890"
                    class="w-full px-3 py-2 text-xs font-mono border {{ $errors->has('barcode') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('barcode')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Country of Origin -->
            <div class="space-y-1.5">
                <label for="field_country_of_origin" class="text-xs font-bold text-slate-800">Country of Origin</label>
                <input
                    type="text"
                    id="field_country_of_origin"
                    name="country_of_origin"
                    x-model="form.countryOfOrigin"
                    placeholder="e.g. Bangladesh"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Warehouse -->
            <div class="space-y-1.5">
                <label for="field_warehouse" class="text-xs font-bold text-slate-800">Warehouse</label>
                <input
                    type="text"
                    id="field_warehouse"
                    name="warehouse"
                    x-model="form.warehouse"
                    placeholder="e.g. Dhaka Main"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Video URL -->
            <div class="space-y-1.5">
                <label for="field_video_url" class="text-xs font-bold text-slate-800">Video URL</label>
                <input
                    type="text"
                    id="field_video_url"
                    name="video_url"
                    x-model="form.videoUrl"
                    placeholder="https://youtube.com/watch?v=…"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Warranty -->
            <div class="space-y-1.5">
                <label for="field_warranty" class="text-xs font-bold text-slate-800">Warranty</label>
                <input
                    type="text"
                    id="field_warranty"
                    name="warranty"
                    x-model="form.warranty"
                    placeholder="e.g. 1 year official"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Weight -->
            <div class="space-y-1.5">
                <label for="field_weight" class="text-xs font-bold text-slate-800">Weight</label>
                <input
                    type="text"
                    id="field_weight"
                    name="weight"
                    x-model="form.weight"
                    placeholder="e.g. 5.5 kg"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Dimensions -->
            <div class="space-y-1.5">
                <label for="field_dimensions" class="text-xs font-bold text-slate-800">Dimensions</label>
                <input
                    type="text"
                    id="field_dimensions"
                    name="dimensions"
                    x-model="form.dimensions"
                    placeholder="e.g. 123 x 71 x 8 cm"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Payment Phone -->
            <div class="space-y-1.5">
                <label for="field_payment_phone_number" class="text-xs font-bold text-slate-800">Payment Phone (bKash / Nagad)</label>
                <input
                    type="text"
                    id="field_payment_phone_number"
                    name="payment_phone_number"
                    x-model="form.paymentPhoneNumber"
                    placeholder="e.g. 01711111111"
                    class="w-full px-3 py-2 text-xs font-mono border border-slate-300 rounded-lg"
                />
            </div>
        </div>

        <!-- Tags & Features inputs -->
        <div class="grid gap-4 sm:grid-cols-2 pt-2">
            <!-- Tags -->
            <div class="space-y-1.5" x-data="{ newTag: '' }">
                <label class="text-xs font-bold text-slate-800">Tags</label>
                <div class="flex flex-wrap items-center gap-1.5 min-h-[38px] p-2 border border-slate-300 rounded-lg bg-white">
                    <template x-for="(tag, idx) in form.tags" :key="idx">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-700 border border-slate-200">
                            <span x-text="tag"></span>
                            <button type="button" @click="form.tags.splice(idx, 1)" class="text-slate-400 hover:text-slate-700">×</button>
                        </span>
                    </template>
                    <input
                        type="text"
                        x-model="newTag"
                        @keydown.enter.prevent="if(newTag.trim()){ form.tags.push(newTag.trim()); newTag = ''; }"
                        placeholder="Add tag, press Enter"
                        class="flex-1 min-w-[120px] text-xs border-0 p-0 focus:outline-hidden"
                    />
                </div>
            </div>

            <!-- Features -->
            <div class="space-y-1.5" x-data="{ newFeature: '' }">
                <label class="text-xs font-bold text-slate-800">Features</label>
                <div class="flex flex-wrap items-center gap-1.5 min-h-[38px] p-2 border border-slate-300 rounded-lg bg-white">
                    <template x-for="(feat, idx) in form.features" :key="idx">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-700 border border-slate-200">
                            <span x-text="feat"></span>
                            <button type="button" @click="form.features.splice(idx, 1)" class="text-slate-400 hover:text-slate-700">×</button>
                        </span>
                    </template>
                    <input
                        type="text"
                        x-model="newFeature"
                        @keydown.enter.prevent="if(newFeature.trim()){ form.features.push(newFeature.trim()); newFeature = ''; }"
                        placeholder="Add feature, press Enter"
                        class="flex-1 min-w-[120px] text-xs border-0 p-0 focus:outline-hidden"
                    />
                </div>
            </div>
        </div>

        <!-- Return Policy -->
        <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-800">Return Policy</label>
            <textarea
                name="return_policy"
                x-model="form.returnPolicy"
                rows="2"
                placeholder="Return & exchange policy"
                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
            ></textarea>
        </div>
    </div>

    <!-- Section 2: Pricing -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-5">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Pricing
            </h2>
            <p class="text-xs text-slate-500">Sale prices, costs and channel-specific pricing in Taka (৳)</p>
        </div>

        <!-- Variant Pricing Banner -->
        <div x-show="form.hasVariants && form.variants.length > 0" class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-xs text-slate-600">
                    <p class="font-bold text-emerald-900 text-sm">Variant pricing enabled</p>
                    <p class="mt-0.5">Prices are managed separately for each variant. Set the price, sale price, and stock in the Variants section below.</p>
                </div>
            </div>
        </div>

        <div x-show="!form.hasVariants || form.variants.length === 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Price -->
            <div class="space-y-1.5">
                <label for="field_price" class="text-xs font-bold text-slate-800">
                    Price (৳) <span class="text-red-500">*</span>
                </label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="field_price"
                    name="price"
                    x-model="form.price"
                    @input="handlePriceChange($event.target.value)"
                    placeholder="45000"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('price') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('price')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Sale Price -->
            <div class="space-y-1.5">
                <label for="field_sale_price" class="text-xs font-bold text-slate-800">Sale Price (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="field_sale_price"
                    name="sale_price"
                    x-model="form.salePrice"
                    @input="handleSalePriceChange($event.target.value)"
                    placeholder="42000"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('sale_price') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('sale_price')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Discount % -->
            <div class="space-y-1.5">
                <label for="field_discount" class="text-xs font-bold text-slate-800">Discount (%)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    id="field_discount"
                    name="discount"
                    x-model="form.discount"
                    @input="handleDiscountChange($event.target.value)"
                    placeholder="10"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('discount') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('discount')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Cost Price -->
            <div class="space-y-1.5">
                <label for="field_cost_price" class="text-xs font-bold text-slate-800">Cost Price (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="field_cost_price"
                    name="cost_price"
                    x-model="form.costPrice"
                    placeholder="35000"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('cost_price') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('cost_price')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Profit Margin -->
            <div class="space-y-1.5">
                <label for="field_profit_margin" class="text-xs font-bold text-slate-800">Profit Margin (%)</label>
                <input
                    type="number"
                    step="0.01"
                    id="field_profit_margin"
                    name="profit_margin"
                    x-model="form.profitMargin"
                    placeholder="Auto-calculated"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('profit_margin') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('profit_margin')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Flash Sale Price -->
            <div class="space-y-1.5">
                <label for="field_flash_sale_price" class="text-xs font-bold text-slate-800">Flash Sale Price (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="field_flash_sale_price"
                    name="flash_sale_price"
                    x-model="form.flashSalePrice"
                    placeholder="39990"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('flash_sale_price') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('flash_sale_price')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Wholesale Price -->
            <div class="space-y-1.5">
                <label for="field_wholesale_price" class="text-xs font-bold text-slate-800">Wholesale Price (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="field_wholesale_price"
                    name="wholesale_price"
                    x-model="form.wholesalePrice"
                    placeholder="38000"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('wholesale_price') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('wholesale_price')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Dealer Price -->
            <div class="space-y-1.5">
                <label for="field_dealer_price" class="text-xs font-bold text-slate-800">Dealer Price (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="field_dealer_price"
                    name="dealer_price"
                    x-model="form.dealerPrice"
                    placeholder="37000"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('dealer_price') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('dealer_price')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Tax -->
            <div class="space-y-1.5">
                <label for="field_tax" class="text-xs font-bold text-slate-800">Tax (%)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="field_tax"
                    name="tax"
                    x-model="form.tax"
                    placeholder="5"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('tax') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('tax')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- VAT -->
            <div class="space-y-1.5">
                <label for="field_vat" class="text-xs font-bold text-slate-800">VAT (%)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="field_vat"
                    name="vat"
                    x-model="form.vat"
                    placeholder="15"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('vat') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('vat')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Shipping Charge -->
            <div class="space-y-1.5">
                <label for="field_shipping_charge" class="text-xs font-bold text-slate-800">Shipping Charge (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="field_shipping_charge"
                    name="shipping_charge"
                    x-model="form.shippingCharge"
                    placeholder="100"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('shipping_charge') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('shipping_charge')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- COD Fee -->
            <div class="space-y-1.5">
                <label for="field_cod_fee" class="text-xs font-bold text-slate-800">COD Fee (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="field_cod_fee"
                    name="cod_fee"
                    x-model="form.codFee"
                    placeholder="50"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('cod_fee') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('cod_fee')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Section 3: Inventory -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-5">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Inventory
            </h2>
            <p class="text-xs text-slate-500">Stock levels, reorder alerts and fulfillment rules</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Stock -->
            <div class="space-y-1.5">
                <label for="field_stock" class="text-xs font-bold text-slate-800">
                    Stock Quantity <span x-show="form.hasVariants">(Base)</span>
                </label>
                <input
                    type="number"
                    min="0"
                    id="field_stock"
                    name="stock"
                    x-model="form.stock"
                    :disabled="form.hasVariants && form.variants.length > 0"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('stock') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg disabled:bg-slate-100 disabled:text-slate-400"
                />
                @error('stock')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
                <p x-show="form.hasVariants && form.variants.length > 0" class="text-[10px] text-slate-400">Managed per variant below</p>
            </div>

            <!-- Low Stock Alert -->
            <div class="space-y-1.5">
                <label for="field_low_stock_alert" class="text-xs font-bold text-slate-800">Low Stock Alert</label>
                <input
                    type="number"
                    min="0"
                    id="field_low_stock_alert"
                    name="low_stock_alert"
                    x-model="form.lowStockAlert"
                    placeholder="5"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('low_stock_alert') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('low_stock_alert')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Stock Status -->
            <div class="space-y-1.5">
                <label for="field_stock_status" class="text-xs font-bold text-slate-800">Stock Status</label>
                <select
                    id="field_stock_status"
                    name="stock_status"
                    x-model="form.stockStatus"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('stock_status') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg bg-white"
                >
                    <option value="in_stock">In stock</option>
                    <option value="low_stock">Low stock</option>
                    <option value="out_of_stock">Out of stock</option>
                    <option value="on_backorder">On backorder</option>
                </select>
                @error('stock_status')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Min Order -->
            <div class="space-y-1.5">
                <label for="field_min_order" class="text-xs font-bold text-slate-800">Minimum Order Qty</label>
                <input
                    type="number"
                    min="0"
                    id="field_min_order"
                    name="min_order"
                    x-model="form.minOrder"
                    placeholder="1"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('min_order') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('min_order')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Max Order -->
            <div class="space-y-1.5">
                <label for="field_max_order" class="text-xs font-bold text-slate-800">Maximum Order Qty</label>
                <input
                    type="number"
                    min="0"
                    id="field_max_order"
                    name="max_order"
                    x-model="form.maxOrder"
                    placeholder="10"
                    class="w-full px-3 py-2 text-xs border {{ $errors->has('max_order') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-300' }} rounded-lg"
                />
                @error('max_order')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>
        </div>

        <!-- Inventory Toggles -->
        <div class="grid gap-3 sm:grid-cols-3 pt-2">
            <!-- Unlimited Stock -->
            <div>
                <div id="field_unlimited_stock" class="flex items-center justify-between gap-3 rounded-xl border {{ $errors->has('unlimited_stock') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-200 bg-white' }} p-3">
                    <div>
                        <p class="text-xs font-bold text-slate-900">Unlimited Stock</p>
                        <p class="text-[11px] text-slate-400">Ignore stock counting</p>
                    </div>
                    <input
                        type="checkbox"
                        x-model="form.unlimitedStock"
                        class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-4 w-4"
                    />
                </div>
                @error('unlimited_stock')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Backorder -->
            <div>
                <div id="field_backorder" class="flex items-center justify-between gap-3 rounded-xl border {{ $errors->has('backorder') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-200 bg-white' }} p-3">
                    <div>
                        <p class="text-xs font-bold text-slate-900">Allow Backorder</p>
                        <p class="text-[11px] text-slate-400">Accept orders when out of stock</p>
                    </div>
                    <input
                        type="checkbox"
                        x-model="form.backorder"
                        class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-4 w-4"
                    />
                </div>
                @error('backorder')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Track Inventory -->
            <div>
                <div id="field_track_inventory" class="flex items-center justify-between gap-3 rounded-xl border {{ $errors->has('track_inventory') ? 'border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field' : 'border-slate-200 bg-white' }} p-3">
                    <div>
                        <p class="text-xs font-bold text-slate-900">Track Inventory</p>
                        <p class="text-[11px] text-slate-400">Decrement stock on orders</p>
                    </div>
                    <input
                        type="checkbox"
                        x-model="form.trackInventory"
                        class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-4 w-4"
                    />
                </div>
                @error('track_inventory')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Section 4: Images (Local Storage Uploader) -->
    <div id="field_images" class="rounded-2xl border @if($errors->has('images') || $errors->has('images.*')) border-red-500 ring-1 ring-red-500 is-invalid-field @else border-slate-200 @endif bg-white p-5 sm:p-6 shadow-xs space-y-5">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Images
                </h2>
                <p class="text-xs text-slate-500">Local storage uploader — drag & drop, reorder, replace or delete</p>
            </div>
            <div x-show="uploadingCount > 0" x-cloak class="flex items-center gap-1.5 text-xs text-brand-green-700 bg-brand-green-50 px-2.5 py-1 rounded-full font-medium">
                <svg class="w-3.5 h-3.5 animate-spin text-brand-green-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span x-text="`Uploading ${uploadingCount} image${uploadingCount > 1 ? 's' : ''}...`"></span>
            </div>
        </div>

        @error('images')
            <p class="text-xs font-medium text-red-600 mt-1 flex items-center gap-1">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ $message }}</span>
            </p>
        @enderror
        @error('images.*')
            <p class="text-xs font-medium text-red-600 mt-1 flex items-center gap-1">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ $message }}</span>
            </p>
        @enderror

        <!-- Hidden input for single-image replace -->
        <input
            type="file"
            x-ref="replaceImageInput"
            accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
            class="hidden"
            @change="onReplaceFileSelected($event)"
        />

        <!-- Dropzone Area -->
        <div
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="isDragging = false; uploadFiles($event.dataTransfer.files)"
            @click="$refs.imageUploadInput.click()"
            class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-8 text-center cursor-pointer transition select-none"
            :class="isDragging ? 'border-brand-green-600 bg-brand-green-50/60' : 'border-slate-300 hover:border-brand-green-500 hover:bg-slate-50/50'"
        >
            <input
                type="file"
                multiple
                accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
                x-ref="imageUploadInput"
                class="hidden"
                @change="uploadFiles($event.target.files)"
            />
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <div>
                <p class="text-xs font-bold text-slate-800">
                    Drag &amp; drop product images, or <span class="text-brand-green-600 underline">browse</span>
                </p>
                <p class="text-[11px] text-slate-400">JPEG · JPG · PNG · WebP · GIF · SVG — up to 20MB</p>
            </div>
        </div>

        <!-- Image Tiles Grid -->
        <div x-show="form.images.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5 pt-2">
            <template x-for="(img, idx) in form.images" :key="img.id || img.url || idx">
                <div
                    draggable="true"
                    @dragstart="onImageDragStart($event, idx)"
                    @dragover.prevent="onImageDragOver($event, idx)"
                    @dragleave="onImageDragLeave($event, idx)"
                    @drop.prevent="onImageDrop($event, idx)"
                    @dragend="onImageDragEnd()"
                    class="group relative aspect-square overflow-hidden rounded-xl border bg-slate-100 flex items-center justify-center transition-all select-none"
                    :class="{
                        'ring-2 ring-brand-green-500 border-brand-green-500 shadow-md': idx === 0,
                        'border-slate-200': idx !== 0,
                        'opacity-40 scale-95 border-dashed border-slate-400': draggedImageIndex === idx,
                        'ring-2 ring-blue-500 border-blue-500 scale-[1.02]': dragOverIndex === idx && draggedImageIndex !== idx
                    }"
                >
                    <!-- Image tag with error fallback -->
                    <img
                        :src="img.url"
                        alt=""
                        class="h-full w-full object-cover transition"
                        :class="img.status === 'uploading' ? 'filter blur-xs scale-105' : ''"
                        x-on:error="handleImgError($event, img)"
                        loading="lazy"
                    >

                    <!-- Uploading status overlay -->
                    <div
                        x-show="img.status === 'uploading'"
                        class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex flex-col items-center justify-center text-white text-xs gap-1.5 z-20"
                    >
                        <svg class="w-5 h-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span class="text-[10px] font-semibold tracking-wide">Uploading...</span>
                    </div>

                    <!-- Upload Failed Error Overlay -->
                    <div
                        x-show="img.status === 'error'"
                        class="absolute inset-0 bg-red-950/85 p-2 flex flex-col items-center justify-center text-center text-white text-xs gap-1.5 z-20"
                    >
                        <svg class="w-5 h-5 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="text-[10px] font-bold text-red-200" x-text="img.errorMessage || 'Upload failed'">Upload failed</span>
                        <div class="flex items-center gap-1.5 mt-1">
                            <button
                                type="button"
                                @click.stop="retryUpload(img)"
                                class="px-2 py-0.5 text-[10px] font-semibold bg-white text-red-900 rounded hover:bg-red-50 cursor-pointer"
                            >
                                Retry
                            </button>
                            <button
                                type="button"
                                @click.stop="removeImage(idx)"
                                class="px-2 py-0.5 text-[10px] font-semibold bg-red-800 text-white rounded hover:bg-red-700 cursor-pointer"
                            >
                                Remove
                            </button>
                        </div>
                    </div>

                    <!-- Main Image Badge -->
                    <span
                        x-show="idx === 0"
                        class="absolute left-1.5 top-1.5 flex items-center gap-1 rounded-[4px] bg-brand-green-600 px-1.5 py-0.5 text-[9px] font-bold text-white shadow-xs z-10"
                    >
                        ★ Main
                    </span>

                    <!-- Set as Main Button (for non-main images) -->
                    <button
                        type="button"
                        x-show="idx !== 0 && img.status !== 'uploading' && img.status !== 'error'"
                        @click.stop="makeMain(idx)"
                        class="absolute left-1.5 top-1.5 rounded-[4px] bg-slate-900/80 hover:bg-slate-900 text-white px-1.5 py-0.5 text-[9px] font-bold shadow-xs z-10 opacity-0 group-hover:opacity-100 transition cursor-pointer"
                        title="Set as main thumbnail"
                    >
                        ★ Set Main
                    </button>

                    <!-- Drag Handle Indicator on Hover -->
                    <div
                        class="absolute right-1.5 top-1.5 p-1 rounded-[4px] bg-black/40 text-white/90 opacity-0 group-hover:opacity-100 transition cursor-grab active:cursor-grabbing z-10"
                        title="Drag to reorder"
                    >
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                            <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                            <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                        </svg>
                    </div>

                    <!-- Overlay Controls on Hover (Move, Replace, Delete) -->
                    <div
                        x-show="img.status !== 'uploading' && img.status !== 'error'"
                        class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/85 via-black/50 to-transparent px-2 pb-1.5 pt-6 opacity-0 transition group-hover:opacity-100 z-10"
                    >
                        <!-- Move Arrows -->
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click.stop="moveImage(idx, idx - 1)"
                                :disabled="idx === 0"
                                class="p-1 rounded bg-white/20 text-white hover:bg-white/40 disabled:opacity-20 text-xs transition cursor-pointer"
                                title="Move left"
                            >
                                ←
                            </button>
                            <button
                                type="button"
                                @click.stop="moveImage(idx, idx + 1)"
                                :disabled="idx === form.images.length - 1"
                                class="p-1 rounded bg-white/20 text-white hover:bg-white/40 disabled:opacity-20 text-xs transition cursor-pointer"
                                title="Move right"
                            >
                                →
                            </button>
                        </div>

                        <!-- Actions: Replace & Delete -->
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click.stop="triggerReplace(idx)"
                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-white/20 text-white hover:bg-white/40 text-[10px] font-semibold transition cursor-pointer"
                                title="Replace this image with a new file"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Replace</span>
                            </button>
                            <button
                                type="button"
                                @click.stop="removeImage(idx)"
                                class="p-1 rounded bg-red-600/80 text-white hover:bg-red-600 text-xs transition cursor-pointer"
                                title="Delete image"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <p class="text-[11px] text-slate-400">The first image is used as the product thumbnail across the store.</p>
    </div>

    <!-- Section 5: Variants (Dynamic Variant Generator) -->
    <div id="field_variants" class="rounded-2xl border @if($errors->has('has_variants') || $errors->has('variants') || $errors->has('variants.*')) border-red-500 ring-1 ring-red-500 is-invalid-field @else border-slate-200 @endif bg-white p-5 sm:p-6 shadow-xs space-y-5" x-data="variantManager()">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Variants
            </h2>
            <p class="text-xs text-slate-500">Create product variants with individual pricing, stock, and images</p>
        </div>

        <!-- Has Variants Switch -->
        <div id="field_has_variants" class="flex items-center justify-between gap-4 rounded-xl border @error('has_variants') border-red-500 ring-1 ring-red-500 is-invalid-field @else border-slate-200 @enderror p-4 bg-slate-50/50">
            <div>
                <p class="text-xs font-bold text-slate-900">Product has variants</p>
                <p class="text-[11px] text-slate-500" x-text="form.hasVariants ? 'ON — sold in multiple variants (e.g. colors/sizes) with individual pricing and stock.' : 'OFF — simple product with a single price and stock.'"></p>
            </div>
            <input
                type="checkbox"
                x-model="form.hasVariants"
                class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-5 w-5"
            />
        </div>
        @error('has_variants')
            <p class="text-xs font-medium text-red-600 mt-1 flex items-center gap-1">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ $message }}</span>
            </p>
        @enderror
        @error('variants')
            <p class="text-xs font-medium text-red-600 mt-1 flex items-center gap-1">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ $message }}</span>
            </p>
        @enderror

        <!-- Variants workspace — only mount fields when variants are enabled -->
        <div x-show="form.hasVariants" x-cloak class="space-y-5">
            <!-- Color & Size Selection -->
            <div class="grid gap-4 sm:grid-cols-2">
                <!-- Color Options -->
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-800">Color Options</label>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="color in colorsCatalog" :key="color.id">
                            <button
                                type="button"
                                @click="toggleColor(color.name)"
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs transition"
                                :class="form.colorOptions.includes(color.name) ? 'border-slate-900 bg-slate-900 text-white font-semibold' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400'"
                            >
                                <span class="h-2.5 w-2.5 rounded-full border border-black/20" :style="{ backgroundColor: color.hex || '#000' }"></span>
                                <span x-text="color.name"></span>
                                <span x-show="form.colorOptions.includes(color.name)">✓</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Size Options -->
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-800">Size Options</label>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="size in sizesCatalog" :key="size.id">
                            <button
                                type="button"
                                @click="toggleSize(size.name)"
                                class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs transition"
                                :class="form.sizeOptions.includes(size.name) ? 'border-slate-900 bg-slate-900 text-white font-semibold' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400'"
                            >
                                <span x-text="size.name"></span>
                                <span x-show="form.sizeOptions.includes(size.name)" class="ml-1">✓</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Generator Actions -->
            <div class="flex flex-wrap items-center gap-2 pt-2">
                <button
                    type="button"
                    @click="generateCombinations()"
                    :disabled="form.colorOptions.length === 0 && form.sizeOptions.length === 0"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-slate-900 rounded-lg hover:bg-slate-800 shadow-xs transition disabled:opacity-40"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    Generate Variants
                </button>
                <button
                    type="button"
                    @click="addBlankVariant()"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition"
                >
                    + Add Variant
                </button>
                <button
                    type="button"
                    x-show="selectedVariants.length > 0"
                    @click="bulkEditOpen = !bulkEditOpen"
                    class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition"
                >
                    <span x-text="bulkEditOpen ? 'Close Bulk Edit' : 'Bulk Edit (' + selectedVariants.length + ')'"></span>
                </button>
                <button
                    type="button"
                    x-show="selectedVariants.length > 0"
                    @click="deleteSelectedVariants()"
                    class="px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition"
                >
                    Delete Selected (<span x-text="selectedVariants.length"></span>)
                </button>
            </div>

            <!-- Bulk Edit Toolbar for Variants -->
            <div x-show="bulkEditOpen && selectedVariants.length > 0" class="rounded-xl border border-slate-300 bg-slate-50 p-4 space-y-3">
                <p class="text-xs font-bold text-slate-800">Bulk Update Selected Variants (<span x-text="selectedVariants.length"></span>)</p>
                <div class="grid gap-3 sm:grid-cols-4">
                    <div>
                        <label class="text-[11px] text-slate-500 block mb-1">Set Price (৳)</label>
                        <input type="number" step="0.01" x-model="bulkPrice" placeholder="1490" class="w-full px-2 py-1 text-xs border border-slate-300 rounded bg-white">
                    </div>
                    <div>
                        <label class="text-[11px] text-slate-500 block mb-1">Set Sale Price (৳)</label>
                        <input type="number" step="0.01" x-model="bulkSalePrice" placeholder="1290" class="w-full px-2 py-1 text-xs border border-slate-300 rounded bg-white">
                    </div>
                    <div>
                        <label class="text-[11px] text-slate-500 block mb-1">Set Stock</label>
                        <input type="number" x-model="bulkStock" placeholder="20" class="w-full px-2 py-1 text-xs border border-slate-300 rounded bg-white">
                    </div>
                    <div class="flex items-end">
                        <button
                            type="button"
                            @click="applyBulkEdit()"
                            class="w-full px-3 py-1.5 text-xs font-bold text-white bg-slate-900 rounded hover:bg-slate-800"
                        >
                            Apply to Selected
                        </button>
                    </div>
                </div>
            </div>

            <!-- Variants Table -->
            <div x-show="form.variants.length > 0" class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px] uppercase tracking-wider">
                            <th class="w-8 px-2.5 py-2.5 text-center">
                                <input
                                    type="checkbox"
                                    :checked="selectedVariants.length === form.variants.length && form.variants.length > 0"
                                    @change="toggleSelectAllVariants($event.target.checked)"
                                    class="rounded border-slate-300"
                                />
                            </th>
                            <th class="px-2.5 py-2.5 min-w-[140px]">Variant</th>
                            <th class="px-2.5 py-2.5 min-w-[160px]">Options JSON</th>
                            <th class="px-2.5 py-2.5 w-28">SKU</th>
                            <th class="px-2.5 py-2.5 w-24">Price (৳)</th>
                            <th class="px-2.5 py-2.5 w-24">Sale Price (৳)</th>
                            <th class="px-2.5 py-2.5 w-20">Stock</th>
                            <th class="px-2.5 py-2.5 w-40">Image</th>
                            <th class="px-2.5 py-2.5 w-16 text-center">Active</th>
                            <th class="px-2.5 py-2.5 w-8"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(v, idx) in form.variants" :key="v.key">
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-2.5 py-2 text-center">
                                    <input type="checkbox" :value="v.key" x-model="selectedVariants" class="rounded border-slate-300">
                                </td>
                                <td class="px-2.5 py-2">
                                    <input type="hidden" :name="`variants[${idx}][id]`" :value="v.id || ''">
                                    <input type="hidden" :name="`variants[${idx}][barcode]`" :value="v.barcode || ''">
                                    <input type="hidden" :name="`variants[${idx}][availability]`" :value="v.availability ? 1 : 0">
                                    <input type="hidden" :name="`variants[${idx}][thumbnail]`" :value="persistedVariantPath(v)">
                                    <input type="hidden" :name="`variants[${idx}][remove_image]`" :value="v.removeImage ? 1 : 0">
                                    <input
                                        type="text"
                                        :name="`variants[${idx}][name]`"
                                        x-model="v.name"
                                        placeholder="Black / L"
                                        class="w-full px-2 py-1 text-xs border border-slate-300 rounded font-medium"
                                    />
                                </td>
                                <td class="px-2.5 py-2">
                                    <input
                                        type="text"
                                        :name="`variants[${idx}][options]`"
                                        x-model="v.options"
                                        placeholder='{"Color":"Black","Size":"L"}'
                                        class="w-full px-2 py-1 text-[11px] font-mono border border-slate-300 rounded"
                                        title="Option axes as JSON — any keys (Color, Size, Storage, RAM, Strap, …)"
                                    />
                                </td>
                                <td class="px-2.5 py-2">
                                    <input
                                        type="text"
                                        :name="`variants[${idx}][sku]`"
                                        x-model="v.sku"
                                        placeholder="SKU"
                                        class="w-full px-2 py-1 text-xs font-mono border border-slate-300 rounded"
                                    />
                                </td>
                                <td class="px-2.5 py-2">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :name="`variants[${idx}][price]`"
                                        x-model="v.price"
                                        placeholder="1490"
                                        class="w-full px-2 py-1 text-xs border border-slate-300 rounded"
                                    />
                                </td>
                                <td class="px-2.5 py-2">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :name="`variants[${idx}][salePrice]`"
                                        x-model="v.salePrice"
                                        placeholder="1290"
                                        class="w-full px-2 py-1 text-xs border border-slate-300 rounded"
                                    />
                                </td>
                                <td class="px-2.5 py-2">
                                    <input
                                        type="number"
                                        min="0"
                                        :name="`variants[${idx}][stock]`"
                                        x-model="v.stock"
                                        placeholder="0"
                                        class="w-full px-2 py-1 text-xs border border-slate-300 rounded text-center"
                                    />
                                </td>
                                <td class="px-2.5 py-2">
                                    <div
                                        class="flex flex-col items-center gap-1.5 min-w-[7.5rem]"
                                        @dragover.prevent="$event.currentTarget.classList.add('ring-2','ring-emerald-400')"
                                        @dragleave.prevent="$event.currentTarget.classList.remove('ring-2','ring-emerald-400')"
                                        @drop.prevent="$event.currentTarget.classList.remove('ring-2','ring-emerald-400'); onVariantImageDrop($event, v, idx)"
                                    >
                                        <div
                                            class="w-14 h-14 rounded-lg border border-slate-200 bg-slate-50 overflow-hidden flex items-center justify-center shrink-0"
                                            :class="variantImageSrc(v) ? '' : 'border-dashed'"
                                        >
                                            <template x-if="variantImageSrc(v)">
                                                <img :src="variantImageSrc(v)" alt="" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!variantImageSrc(v)">
                                                <div class="flex flex-col items-center gap-0.5 p-1 text-center">
                                                    <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    <span class="text-[9px] text-slate-400 leading-tight">Image</span>
                                                </div>
                                            </template>
                                        </div>

                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                            class="hidden"
                                            :name="`variants[${idx}][image]`"
                                            :data-varkey="v.key"
                                            @change="onVariantImageSelected($event, v)"
                                        >

                                        <div class="flex flex-wrap items-center justify-center gap-1">
                                            <button
                                                type="button"
                                                @click="openVariantFilePicker($event, v.key)"
                                                class="px-1.5 py-0.5 text-[10px] font-semibold rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                                                x-text="variantImageSrc(v) ? 'Replace' : 'Choose Image'"
                                            ></button>
                                            <button
                                                type="button"
                                                x-show="variantImageSrc(v)"
                                                @click.stop="removeVariantImage($event, v)"
                                                class="px-1.5 py-0.5 text-[10px] font-semibold rounded border border-red-200 bg-red-50 text-red-600 hover:bg-red-100"
                                            >Remove</button>
                                        </div>
                                        <p class="text-[9px] text-slate-400 leading-tight text-center">JPG · PNG · WEBP · 5MB</p>
                                    </div>
                                </td>
                                <td class="px-2.5 py-2 text-center">
                                    <input
                                        type="checkbox"
                                        x-model="v.availability"
                                        class="rounded border-slate-300 text-slate-900"
                                    />
                                </td>
                                <td class="px-2.5 py-2 text-center">
                                    <button
                                        type="button"
                                        @click="form.variants.splice(idx, 1)"
                                        class="text-red-500 hover:text-red-700 p-1 text-xs"
                                    >
                                        ×
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="form.variants.length === 0" class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-xs text-slate-500">
                No variants yet. Select color and size options above, then click <strong>Generate Variants</strong>.
                For Storage/RAM/Strap axes, add a blank variant and edit the <strong>Options JSON</strong> column (e.g. <code>{"Storage":"256GB","Color":"Black"}</code>).
            </div>
        </div>
    </div>

    <!-- Section 6: Specifications -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-5" x-data="specManager()">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Specifications
            </h2>
            <p class="text-xs text-slate-500">Technical attributes shown as a key/value table on the product page</p>
        </div>

        <div class="space-y-3">
            <template x-for="(s, idx) in form.specs" :key="s.key">
                <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto] items-center">
                    <input
                        type="text"
                        x-model="s.label"
                        placeholder="Label (e.g. Display Size)"
                        class="px-3 py-2 text-xs border border-slate-300 rounded-lg"
                    />
                    <input
                        type="text"
                        x-model="s.value"
                        placeholder="Value (e.g. 6.7 inches Super Retina)"
                        class="px-3 py-2 text-xs border border-slate-300 rounded-lg"
                    />
                    <button
                        type="button"
                        @click="form.specs.splice(idx, 1)"
                        class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg"
                        title="Remove specification"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>

        <button
            type="button"
            @click="addSpec()"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition"
        >
            + Add Specification
        </button>
    </div>

    <!-- Section 7: SEO & Marketing Flags -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-5">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                SEO &amp; Marketing
            </h2>
            <p class="text-xs text-slate-500">Storefront flags and search engine metadata</p>
        </div>

        <!-- 9 Storefront Flags -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $flags = [
                    ['isFeatured', 'Featured', 'Show in featured sections'],
                    ['isTrending', 'Trending', 'Show in trending section'],
                    ['isFlashSale', 'Flash Sale', 'Show in flash sale section'],
                    ['isNewArrival', 'New Arrival', 'Show in new arrivals'],
                    ['isBestSeller', 'Best Seller', 'Show in best sellers'],
                    ['isLimitedEdition', 'Limited Edition', 'Mark as limited edition'],
                    ['isOfficial', 'Official', 'Official brand product'],
                    ['isHotDeal', 'Hot Deal', 'Show in hot deals'],
                    ['emiAvailable', 'EMI Available', 'Allow EMI installment payment'],
                ];
            @endphp
            @foreach($flags as [$key, $label, $desc])
                @php $snakeKey = Str::snake($key); @endphp
                <div id="field_{{ $snakeKey }}" class="flex items-center justify-between gap-3 rounded-xl border @error($snakeKey) border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field @else border-slate-200 @enderror p-3 bg-white">
                    <div>
                        <p class="text-xs font-bold text-slate-900">{{ $label }}</p>
                        <p class="text-[11px] text-slate-400">{{ $desc }}</p>
                        @error($snakeKey)
                            <p class="text-[10px] font-medium text-red-600 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <input
                        type="checkbox"
                        x-model="form.{{ $key }}"
                        class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-4 w-4"
                    />
                </div>
            @endforeach
        </div>

        <!-- SEO Metadata Fields -->
        <div class="grid gap-4 sm:grid-cols-2 pt-2">
            <div class="sm:col-span-2 space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="field_seo_title" class="text-xs font-bold text-slate-800">SEO Title</label>
                    <span class="text-[11px] text-slate-400 font-medium" x-text="(form.seoTitle ? form.seoTitle.length : 0) + ' chars (recommended 50-60)'"></span>
                </div>
                <input
                    type="text"
                    id="field_seo_title"
                    name="seo_title"
                    x-model="form.seoTitle"
                    placeholder="Meta title"
                    class="w-full px-3 py-2 text-xs border @error('seo_title') border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field @else border-slate-300 @enderror rounded-lg"
                />
                @error('seo_title')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div class="sm:col-span-2 space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="field_seo_description" class="text-xs font-bold text-slate-800">SEO Description</label>
                    <span class="text-[11px] text-slate-400 font-medium" x-text="(form.seoDescription ? form.seoDescription.length : 0) + ' chars (recommended 140-160)'"></span>
                </div>
                <textarea
                    id="field_seo_description"
                    name="seo_description"
                    x-model="form.seoDescription"
                    rows="2"
                    placeholder="Meta description"
                    class="w-full px-3 py-2 text-xs border @error('seo_description') border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field @else border-slate-300 @enderror rounded-lg"
                ></textarea>
                @error('seo_description')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            {{-- Live Search Engine Snippet Preview --}}
            <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50/80 p-3.5 space-y-1">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Search Engine Snippet Preview</p>
                <p class="text-xs text-emerald-700 font-mono truncate" x-text="form.canonicalUrl || ('{{ url('/products') }}/' + (form.slug || 'product-slug'))"></p>
                <p class="text-sm font-semibold text-blue-700 truncate" x-text="(form.seoTitle || form.title || 'Product Title') + ' | Mama Bazar'"></p>
                <p class="text-xs text-slate-600 line-clamp-2" x-text="form.seoDescription || form.shortDescription || 'Shop ' + (form.title || 'this product') + ' at Mama Bazar. Fast delivery across Bangladesh.'"></p>
            </div>

            <div class="sm:col-span-2 space-y-1.5">
                <label for="field_seo_keywords" class="text-xs font-bold text-slate-800">SEO Keywords</label>
                <input
                    type="text"
                    id="field_seo_keywords"
                    name="seo_keywords"
                    x-model="form.seoKeywords"
                    placeholder="comma, separated, keywords"
                    class="w-full px-3 py-2 text-xs border @error('seo_keywords') border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field @else border-slate-300 @enderror rounded-lg"
                />
                @error('seo_keywords')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div class="sm:col-span-2 space-y-1.5">
                <label for="field_canonical_url" class="text-xs font-bold text-slate-800">Canonical URL</label>
                <input
                    type="text"
                    id="field_canonical_url"
                    name="canonical_url"
                    x-model="form.canonicalUrl"
                    placeholder="https://example.com/products/…"
                    class="w-full px-3 py-2 text-xs border @error('canonical_url') border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field @else border-slate-300 @enderror rounded-lg"
                />
                @error('canonical_url')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="field_og_image" class="text-xs font-bold text-slate-800">OG Image URL</label>
                <input
                    type="text"
                    id="field_og_image"
                    name="og_image"
                    x-model="form.ogImage"
                    placeholder="https://…"
                    class="w-full px-3 py-2 text-xs border @error('og_image') border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field @else border-slate-300 @enderror rounded-lg"
                />
                @error('og_image')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="field_twitter_image" class="text-xs font-bold text-slate-800">Twitter Image URL</label>
                <input
                    type="text"
                    id="field_twitter_image"
                    name="twitter_image"
                    x-model="form.twitterImage"
                    placeholder="https://…"
                    class="w-full px-3 py-2 text-xs border @error('twitter_image') border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field @else border-slate-300 @enderror rounded-lg"
                />
                @error('twitter_image')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>

            <!-- Structured Data JSON -->
            <div class="sm:col-span-2 space-y-1.5" x-data="{ jsonError: null }">
                <label for="field_structured_data" class="text-xs font-bold text-slate-800">Structured Data (JSON-LD)</label>
                <textarea
                    id="field_structured_data"
                    name="structured_data"
                    x-model="form.structuredData"
                    @input="
                        if(form.structuredData.trim()){
                            try { JSON.parse(form.structuredData); jsonError = null; }
                            catch(e) { jsonError = e.message; }
                        } else { jsonError = null; }
                    "
                    rows="4"
                    placeholder='{"@type":"Product",…}'
                    class="w-full px-3 py-2 text-xs font-mono border @error('structured_data') border-red-500 ring-1 ring-red-500 bg-red-50/20 is-invalid-field @else border-slate-300 @enderror rounded-lg"
                ></textarea>
                <p x-show="jsonError" x-text="'Invalid JSON: ' + jsonError" class="text-[11px] text-red-600 font-semibold"></p>
                @error('structured_data')
                    <p class="text-[11px] font-medium text-red-600 mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Section 8: Related Products -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-5" x-data="relationManager()">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Related Products
            </h2>
            <p class="text-xs text-slate-500">Link products to show recommendations on the product page</p>
        </div>

        <div class="space-y-3">
            <template x-for="(r, idx) in form.relations" :key="r.key">
                <div class="rounded-xl border border-slate-200 p-4 space-y-3 bg-slate-50/40">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700" x-text="'Relation ' + (idx + 1)"></span>
                        <button type="button" @click="form.relations.splice(idx, 1)" class="text-xs text-red-600 hover:text-red-700 font-semibold">
                            Remove
                        </button>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-[11px] text-slate-500 block mb-1">Relation Type</label>
                            <select x-model="r.type" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white">
                                <option value="frequently_bought_together">Frequently bought together</option>
                                <option value="cross_sell">Cross-sell</option>
                                <option value="up_sell">Up-sell</option>
                                <option value="accessories">Accessories</option>
                                <option value="similar">Similar products</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[11px] text-slate-500 block mb-1">Related Product ID or SKU</label>
                            <input
                                type="text"
                                x-model="r.relatedProductId"
                                placeholder="Enter Product ID (e.g. 42)"
                                class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <button
            type="button"
            @click="addRelation()"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg transition"
        >
            + Add Relation
        </button>
    </div>

    <!-- Mobile Bottom Action Bar -->
    <div class="sticky bottom-0 z-30 -mx-4 flex items-center gap-2 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:hidden shadow-lg">
        <button
            type="button"
            @click="submitMode('draft')"
            :disabled="submitting"
            class="flex-1 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg"
        >
            Draft
        </button>
        <button
            type="button"
            @click="submitMode('publish')"
            :disabled="submitting"
            class="flex-1 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-lg"
        >
            {{ $isEditing ? 'Save & Publish' : 'Publish' }}
        </button>
    </div>
</form>

<script>
document.addEventListener('alpine:init', () => {

    // Variant manager
    Alpine.data('variantManager', () => ({
        selectedVariants: [],
        bulkEditOpen: false,
        bulkPrice: '',
        bulkSalePrice: '',
        bulkStock: '',

        toggleColor(colorName) {
            const idx = this.form.colorOptions.indexOf(colorName);
            if (idx > -1) {
                this.form.colorOptions.splice(idx, 1);
            } else {
                this.form.colorOptions.push(colorName);
            }
        },
        toggleSize(sizeName) {
            const idx = this.form.sizeOptions.indexOf(sizeName);
            if (idx > -1) {
                this.form.sizeOptions.splice(idx, 1);
            } else {
                this.form.sizeOptions.push(sizeName);
            }
        },
        generateCombinations() {
            const colors = this.form.colorOptions.length > 0 ? this.form.colorOptions : [''];
            const sizes = this.form.sizeOptions.length > 0 ? this.form.sizeOptions : [''];

            const existingKeys = new Set(this.form.variants.map(v => {
                try {
                    const parsed = JSON.parse(v.options || '{}');
                    const color = parsed.Color || parsed.color || '';
                    const size = parsed.Size || parsed.size || '';
                    return `${color}::${size}`.toLowerCase();
                } catch(e) {
                    return v.name.toLowerCase();
                }
            }));

            let added = 0;
            colors.forEach(color => {
                sizes.forEach(size => {
                    const parts = [];
                    // Capitalized keys match storefront generic option engine / seeded catalog
                    const opts = {};
                    if (color) { parts.push(color); opts.Color = color; }
                    if (size) { parts.push(size); opts.Size = size; }
                    const name = parts.join(' / ');
                    const key = `${color}::${size}`.toLowerCase();

                    if (!existingKeys.has(key) && name) {
                        const skuParts = [
                            this.form.sku ? this.form.sku.toUpperCase().replace(/[^A-Z0-9]/g, '-') : '',
                            color ? color.substring(0, 3).toUpperCase() : '',
                            size ? size.toUpperCase() : ''
                        ].filter(Boolean);

                        this.form.variants.push({
                            key: Math.random().toString(36).substring(7),
                            id: null,
                            name: name,
                            options: JSON.stringify(opts),
                            price: this.form.price || '',
                            salePrice: this.form.salePrice || '',
                            sku: skuParts.join('-'),
                            barcode: '',
                            stock: this.form.stock || '0',
                            thumbnail: '',
                            _savedPath: '',
                            _preview: null,
                            removeImage: false,
                            availability: true
                        });
                        added++;
                    }
                });
            });

            if (added > 0) {
                alert(`${added} variant(s) generated.`);
            } else {
                alert('All combinations already exist.');
            }
        },
        addBlankVariant() {
            this.form.variants.push({
                key: Math.random().toString(36).substring(7),
                id: null,
                name: '',
                options: '{}',
                price: this.form.price || '',
                salePrice: this.form.salePrice || '',
                sku: '',
                barcode: '',
                stock: '0',
                thumbnail: '',
                _savedPath: '',
                _preview: null,
                removeImage: false,
                availability: true
            });
        },
        toggleSelectAllVariants(checked) {
            if (checked) {
                this.selectedVariants = this.form.variants.map(v => v.key);
            } else {
                this.selectedVariants = [];
            }
        },
        deleteSelectedVariants() {
            this.form.variants = this.form.variants.filter(v => !this.selectedVariants.includes(v.key));
            this.selectedVariants = [];
            this.bulkEditOpen = false;
        },
        applyBulkEdit() {
            this.form.variants.forEach(v => {
                if (this.selectedVariants.includes(v.key)) {
                    if (this.bulkPrice !== '') v.price = this.bulkPrice;
                    if (this.bulkSalePrice !== '') v.salePrice = this.bulkSalePrice;
                    if (this.bulkStock !== '') v.stock = this.bulkStock;
                }
            });
            this.bulkEditOpen = false;
            this.bulkPrice = '';
            this.bulkSalePrice = '';
            this.bulkStock = '';
        },

        // ---------- Variant image (multipart on form submit) ----------
        normalizeVariantSrc(path) {
            if (!path) return '';
            if (
                path.startsWith('blob:') ||
                path.startsWith('data:') ||
                path.startsWith('http://') ||
                path.startsWith('https://') ||
                path.startsWith('/storage/') ||
                path.startsWith('/uploads/')
            ) {
                return path;
            }
            if (path.startsWith('storage/')) return '/' + path;
            return '/storage/' + path.replace(/^\//, '');
        },
        variantImageSrc(v) {
            if (v.removeImage) return '';
            if (v._preview) return v._preview;
            return this.normalizeVariantSrc(v.thumbnail || v._savedPath || '');
        },
        persistedVariantPath(v) {
            // Always send the last known disk path so the server can delete on remove/replace.
            // remove_image=1 tells the backend to clear the DB field after cleanup.
            const saved = v._savedPath || '';
            if (saved && !saved.startsWith('blob:') && !saved.startsWith('data:')) return saved;
            const t = v.thumbnail || '';
            if (t && !t.startsWith('blob:') && !t.startsWith('data:')) return t;
            return '';
        },
        openVariantFilePicker(event, varKey) {
            const input = event.target.closest('tr').querySelector(`input[data-varkey="${varKey}"]`);
            if (input) input.click();
        },
        validateVariantImageFile(file) {
            if (!file) return false;
            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            const okType = allowed.includes(file.type) || /\.(jpe?g|png|webp)$/i.test(file.name || '');
            if (!okType) {
                alert('Only JPG, PNG, and WEBP images are allowed.');
                return false;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('Image must be 5 MB or smaller.');
                return false;
            }
            return true;
        },
        onVariantImageSelected(event, variant) {
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            if (!this.validateVariantImageFile(file)) {
                event.target.value = '';
                return;
            }
            if (variant._preview) URL.revokeObjectURL(variant._preview);
            variant._preview = URL.createObjectURL(file);
            variant.removeImage = false;
            // Keep _savedPath so replace can delete the old file server-side
        },
        onVariantImageDrop(event, variant, idx) {
            const file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
            if (!file) return;
            if (!this.validateVariantImageFile(file)) return;
            const input = event.currentTarget.querySelector(`input[data-varkey="${variant.key}"]`)
                || event.target.closest('tr')?.querySelector(`input[name="variants[${idx}][image]"]`);
            if (!input) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            if (variant._preview) URL.revokeObjectURL(variant._preview);
            variant._preview = URL.createObjectURL(file);
            variant.removeImage = false;
        },
        removeVariantImage(event, variant) {
            if (variant._preview) {
                URL.revokeObjectURL(variant._preview);
                variant._preview = null;
            }
            variant.removeImage = true;
            variant.thumbnail = '';
            const input = event.target.closest('tr')?.querySelector(`input[data-varkey="${variant.key}"]`);
            if (input) input.value = '';
        }
    }));

    // Spec manager
    Alpine.data('specManager', () => ({
        addSpec() {
            this.form.specs.push({
                key: Math.random().toString(36).substring(7),
                label: '',
                value: ''
            });
        }
    }));

    // Relation manager
    Alpine.data('relationManager', () => ({
        addRelation() {
            this.form.relations.push({
                key: Math.random().toString(36).substring(7),
                type: 'frequently_bought_together',
                relatedProductId: '',
                relatedTitle: ''
            });
        }
    }));

    // Main Product Form Data
    Alpine.data('productForm', (config) => ({
        form: config.initial,
        isEditing: config.isEditing,
        csrfToken: config.csrfToken,
        uploadUrl: config.uploadUrl,
        colorsCatalog: config.colorsCatalog,
        sizesCatalog: config.sizesCatalog,
        slugTouched: config.isEditing,
        saveMode: 'publish',
        submitting: false,
        validationErrors: [],

        init() {
            @if(isset($errors) && $errors->any())
                this.$nextTick(() => {
                    const firstInvalid = document.querySelector('.is-invalid-field');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        if (window.innerWidth >= 768) {
                            const focusable = firstInvalid.matches('input:not([type="hidden"]), select, textarea') 
                                ? firstInvalid 
                                : firstInvalid.querySelector('input:not([type="hidden"]), select, textarea');
                            if (focusable) {
                                try { focusable.focus({ preventScroll: true }); } catch(e) {}
                            }
                        }
                    }
                });
            @endif
        },

        // Image Management State
        isDragging: false,
        draggedImageIndex: null,
        dragOverIndex: null,
        replaceTargetIndex: null,
        deletedImageUrls: [],
        uploadingCount: 0,

        handleImgError(event, img) {
            if (img && img.url && img.url.startsWith('/storage/')) {
                const fallbackUrl = img.url.replace(/^\/storage\//, '/uploads/');
                if (event.target.src !== window.location.origin + fallbackUrl && event.target.src !== fallbackUrl) {
                    event.target.src = fallbackUrl;
                    return;
                }
            }
            event.target.src = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200' width='100%' height='100%' fill='%23f1f5f9'><rect width='200' height='200' rx='12'/><path d='M65 135l25-30 20 22 25-32 30 40H35z' fill='%23cbd5e1'/><circle cx='70' cy='65' r='14' fill='%23cbd5e1'/></svg>";
        },

        uploadFiles(files) {
            if (!files || files.length === 0) return;
            const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];

            Array.from(files).forEach(file => {
                const okType = allowed.includes(file.type) || /\.(jpe?g|png|webp|gif|svg)$/i.test(file.name || '');
                if (!okType) {
                    alert(`"${file.name}" is not a supported image type (JPEG, PNG, WebP, GIF, SVG).`);
                    return;
                }
                if (file.size > 20 * 1024 * 1024) {
                    alert(`"${file.name}" exceeds the 20MB limit.`);
                    return;
                }

                const tempId = Math.random().toString(36).substring(7);
                const localUrl = URL.createObjectURL(file);
                const item = {
                    id: tempId,
                    url: localUrl,
                    status: 'uploading',
                    progress: 25,
                    _file: file,
                    errorMessage: null
                };

                this.form.images.push(item);
                this.uploadSingleFile(file, tempId, localUrl);
            });
        },

        uploadSingleFile(file, tempId, localUrl) {
            this.uploadingCount++;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', this.csrfToken);

            fetch(this.uploadUrl, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
            .then(async r => {
                const res = await r.json().catch(() => ({}));
                if (!r.ok || !res.success) {
                    throw new Error(res.message || ('Server error: HTTP ' + r.status));
                }
                return res;
            })
            .then(res => {
                const serverUrl = res.url || (res.urls && res.urls[0]) || (res.data && res.data.url);
                if (!serverUrl) throw new Error('No URL returned from server.');

                const found = this.form.images.find(img => img.id === tempId);
                if (found) {
                    try { URL.revokeObjectURL(localUrl); } catch(e) {}
                    found.url = serverUrl;
                    found.status = 'done';
                    found.progress = 100;
                    delete found._file;
                }
            })
            .catch(err => {
                console.error('Image upload failed:', err);
                const found = this.form.images.find(img => img.id === tempId);
                if (found) {
                    found.status = 'error';
                    found.errorMessage = err.message || 'Upload failed';
                }
            })
            .finally(() => {
                this.uploadingCount = Math.max(0, this.uploadingCount - 1);
            });
        },

        retryUpload(img) {
            if (!img._file) {
                alert('File data is no longer available in memory. Please remove and re-select the image.');
                return;
            }
            img.status = 'uploading';
            img.errorMessage = null;
            this.uploadSingleFile(img._file, img.id, img.url);
        },

        triggerReplace(idx) {
            this.replaceTargetIndex = idx;
            if (this.$refs.replaceImageInput) {
                this.$refs.replaceImageInput.value = '';
                this.$refs.replaceImageInput.click();
            }
        },

        onReplaceFileSelected(event) {
            const file = event.target.files && event.target.files[0];
            const targetIdx = this.replaceTargetIndex;
            if (!file || targetIdx === null || targetIdx === undefined || !this.form.images[targetIdx]) return;

            const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
            const okType = allowed.includes(file.type) || /\.(jpe?g|png|webp|gif|svg)$/i.test(file.name || '');
            if (!okType) {
                alert('Only JPEG, PNG, WebP, GIF, and SVG images are allowed.');
                return;
            }
            if (file.size > 20 * 1024 * 1024) {
                alert('Image must be 20MB or smaller.');
                return;
            }

            const oldItem = this.form.images[targetIdx];
            const oldUrl = oldItem.url;
            if (oldUrl && !oldUrl.startsWith('blob:') && !oldUrl.startsWith('data:') && !this.deletedImageUrls.includes(oldUrl)) {
                this.deletedImageUrls.push(oldUrl);
            }

            const tempId = Math.random().toString(36).substring(7);
            const localUrl = URL.createObjectURL(file);
            const newItem = {
                id: tempId,
                url: localUrl,
                status: 'uploading',
                progress: 25,
                _file: file,
                errorMessage: null
            };

            // Replace in-place at the exact same index
            this.form.images.splice(targetIdx, 1, newItem);
            this.replaceTargetIndex = null;
            this.uploadSingleFile(file, tempId, localUrl);
        },

        removeImage(idx) {
            if (idx < 0 || idx >= this.form.images.length) return;
            if (!confirm('Are you sure you want to remove this image?')) return;

            const removed = this.form.images.splice(idx, 1)[0];
            if (removed && removed.url) {
                if (removed.url.startsWith('blob:')) {
                    try { URL.revokeObjectURL(removed.url); } catch(e) {}
                } else if (!removed.url.startsWith('data:') && !this.deletedImageUrls.includes(removed.url)) {
                    this.deletedImageUrls.push(removed.url);
                }
            }
        },

        makeMain(idx) {
            if (idx <= 0 || idx >= this.form.images.length) return;
            const item = this.form.images.splice(idx, 1)[0];
            this.form.images.unshift(item);
        },

        moveImage(from, to) {
            if (to < 0 || to >= this.form.images.length || from === to) return;
            const item = this.form.images.splice(from, 1)[0];
            this.form.images.splice(to, 0, item);
        },

        onImageDragStart(event, idx) {
            this.draggedImageIndex = idx;
            event.dataTransfer.effectAllowed = 'move';
            try { event.dataTransfer.setData('text/plain', idx.toString()); } catch(e) {}
        },

        onImageDragOver(event, idx) {
            this.dragOverIndex = idx;
        },

        onImageDragLeave(event, idx) {
            if (this.dragOverIndex === idx) {
                this.dragOverIndex = null;
            }
        },

        onImageDrop(event, idx) {
            if (this.draggedImageIndex !== null && this.draggedImageIndex !== idx) {
                const item = this.form.images.splice(this.draggedImageIndex, 1)[0];
                this.form.images.splice(idx, 0, item);
            }
            this.draggedImageIndex = null;
            this.dragOverIndex = null;
        },

        onImageDragEnd() {
            this.draggedImageIndex = null;
            this.dragOverIndex = null;
        },

        handleTitleChange(title) {
            if (!this.slugTouched) {
                this.form.slug = title.toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        },

        resetSlugFromTitle() {
            this.form.slug = this.form.title.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            this.slugTouched = false;
        },

        handlePriceChange(val) {
            const p = parseFloat(val);
            if (!isNaN(p) && p > 0) {
                const d = parseFloat(this.form.discount);
                const s = parseFloat(this.form.salePrice);
                if (!isNaN(d) && d >= 0) {
                    this.form.salePrice = (p - (p * d) / 100).toFixed(2);
                } else if (!isNaN(s) && s > 0 && s < p) {
                    this.form.discount = Math.round(((p - s) / p) * 100).toString();
                }
            }
        },

        handleSalePriceChange(val) {
            const p = parseFloat(this.form.price);
            const s = parseFloat(val);
            if (!isNaN(p) && p > 0 && !isNaN(s) && s >= 0 && s < p) {
                this.form.discount = Math.round(((p - s) / p) * 100).toString();
            }
        },

        handleDiscountChange(val) {
            const p = parseFloat(this.form.price);
            const d = parseFloat(val);
            if (!isNaN(p) && p > 0 && !isNaN(d) && d >= 0) {
                this.form.salePrice = (p - (p * Math.min(d, 100)) / 100).toFixed(2);
            }
        },

        submitMode(mode) {
            if (this.submitting) return;
            this.saveMode = mode;
            this.submitForm();
        },

        validate() {
            this.validationErrors = [];
            if (!this.form.title.trim()) {
                this.validationErrors.push('Product title is required.');
            }
            if (this.form.slug.trim() && !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(this.form.slug.trim())) {
                this.validationErrors.push('Slug may only contain English lowercase letters, numbers, and hyphens.');
            }
            if (!this.form.hasVariants || this.form.variants.length === 0) {
                const p = parseFloat(this.form.price);
                if (isNaN(p) || p <= 0) {
                    this.validationErrors.push('A valid positive price is required when no variants are defined.');
                }
            }
            if (this.uploadingCount > 0 || this.form.images.some(i => i.status === 'uploading')) {
                this.validationErrors.push('Please wait for image uploads to complete before saving.');
            }
            if (this.form.images.some(i => i.status === 'error')) {
                this.validationErrors.push('Please retry or remove failed image uploads before saving.');
            }
            if (this.form.structuredData && this.form.structuredData.trim()) {
                try { JSON.parse(this.form.structuredData); }
                catch(e) { this.validationErrors.push('Structured Data (JSON-LD) contains invalid JSON.'); }
            }
            return this.validationErrors.length === 0;
        },

        submitForm() {
            if (this.submitting) return;
            if (!this.validate()) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            this.submitting = true;
            const formEl = document.getElementById('productMainForm');
            // When variants are off, disable named variant fields so they are not posted
            if (!this.form.hasVariants) {
                formEl.querySelectorAll('[name^="variants["]').forEach((el) => { el.disabled = true; });
            }
            formEl.submit();
        }
    }));
});
</script>
