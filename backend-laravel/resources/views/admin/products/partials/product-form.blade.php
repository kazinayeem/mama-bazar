@php
    $isEditing = isset($product) && !empty($product['id']);
    $actionUrl = $isEditing ? route('admin.products.update', $product['id']) : route('admin.products.store');
    $method = $isEditing ? 'PUT' : 'POST';

    // Format existing images for the uploader
    $existingImages = [];
    if (!empty($product['images']) && is_array($product['images'])) {
        foreach ($product['images'] as $url) {
            $existingImages[] = [
                'id' => uniqid(),
                'url' => $url,
                'status' => 'done',
                'progress' => 100,
            ];
        }
    }

    // Format existing variants
    $existingVariants = [];
    if (!empty($product['variants']) && is_array($product['variants'])) {
        foreach ($product['variants'] as $v) {
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
                'thumbnail' => $v['thumbnail'] ?? '',
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

    $initialForm = [
        'hasVariants' => !empty($existingVariants),
        'title' => $product['title'] ?? '',
        'slug' => $product['slug'] ?? '',
        'description' => $product['description'] ?? '',
        'shortDescription' => $product['shortDescription'] ?? '',
        'returnPolicy' => $product['returnPolicy'] ?? '',
        'categoryId' => !empty($product['categoryId']) ? (string)$product['categoryId'] : '',
        'subCategoryId' => !empty($product['subCategoryId']) ? (string)$product['subCategoryId'] : '',
        'childCategoryId' => !empty($product['childCategoryId']) ? (string)$product['childCategoryId'] : '',
        'brandId' => !empty($product['brandId']) ? (string)$product['brandId'] : '',
        'collectionId' => !empty($product['collectionId']) ? (string)$product['collectionId'] : '',
        'vendorId' => !empty($product['vendorId']) ? (string)$product['vendorId'] : '',
        'supplierId' => !empty($product['supplierId']) ? (string)$product['supplierId'] : '',
        'sku' => $product['sku'] ?? '',
        'barcode' => $product['barcode'] ?? '',
        'warehouse' => $product['warehouse'] ?? '',
        'countryOfOrigin' => $product['countryOfOrigin'] ?? '',
        'weight' => $product['weight'] ?? '',
        'dimensions' => $product['dimensions'] ?? '',
        'warranty' => $product['warranty'] ?? '',
        'videoUrl' => $product['videoUrl'] ?? '',
        'paymentPhoneNumber' => $product['paymentPhoneNumber'] ?? '',
        'price' => isset($product['price']) ? (string)$product['price'] : '',
        'salePrice' => isset($product['salePrice']) ? (string)$product['salePrice'] : '',
        'discount' => isset($product['discount']) ? (string)$product['discount'] : '',
        'costPrice' => isset($product['costPrice']) ? (string)$product['costPrice'] : '',
        'profitMargin' => isset($product['profitMargin']) ? (string)$product['profitMargin'] : '',
        'tax' => isset($product['tax']) ? (string)$product['tax'] : '',
        'vat' => isset($product['vat']) ? (string)$product['vat'] : '',
        'shippingCharge' => isset($product['shippingCharge']) ? (string)$product['shippingCharge'] : '',
        'codFee' => isset($product['codFee']) ? (string)$product['codFee'] : '',
        'flashSalePrice' => isset($product['flashSalePrice']) ? (string)$product['flashSalePrice'] : '',
        'wholesalePrice' => isset($product['wholesalePrice']) ? (string)$product['wholesalePrice'] : '',
        'dealerPrice' => isset($product['dealerPrice']) ? (string)$product['dealerPrice'] : '',
        'stock' => isset($product['stock']) ? (string)$product['stock'] : '0',
        'lowStockAlert' => isset($product['lowStockAlert']) ? (string)$product['lowStockAlert'] : '',
        'minOrder' => isset($product['minOrder']) ? (string)$product['minOrder'] : '',
        'maxOrder' => isset($product['maxOrder']) ? (string)$product['maxOrder'] : '',
        'stockStatus' => $product['stockStatus'] ?? 'in_stock',
        'unlimitedStock' => !empty($product['unlimitedStock']),
        'backorder' => !empty($product['backorder']),
        'trackInventory' => $product['trackInventory'] ?? true,
        'productStatus' => $product['productStatus'] ?? 'draft',
        'status' => $product['status'] ?? 'inactive',
        'isFeatured' => !empty($product['isFeatured']),
        'isTrending' => !empty($product['isTrending']),
        'isFlashSale' => !empty($product['isFlashSale']),
        'isNewArrival' => !empty($product['isNewArrival']),
        'isBestSeller' => !empty($product['isBestSeller']),
        'isLimitedEdition' => !empty($product['isLimitedEdition']),
        'isOfficial' => !empty($product['isOfficial']),
        'isHotDeal' => !empty($product['isHotDeal']),
        'emiAvailable' => !empty($product['emiAvailable']),
        'seoTitle' => $product['seoTitle'] ?? '',
        'seoDescription' => $product['seoDescription'] ?? '',
        'seoKeywords' => $product['seoKeywords'] ?? '',
        'canonicalUrl' => $product['canonicalUrl'] ?? '',
        'ogImage' => $product['ogImage'] ?? '',
        'twitterImage' => $product['twitterImage'] ?? '',
        'structuredData' => $structuredDataString,
        'tags' => $product['tags'] ?? [],
        'features' => $product['features'] ?? [],
        'sizeOptions' => $product['sizeOptions'] ?? [],
        'colorOptions' => !empty($product['colorOptions']) 
            ? array_map(fn($c) => is_array($c) ? ($c['name'] ?? '') : $c, $product['colorOptions']) 
            : [],
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
    <input type="hidden" name="tags" :value="JSON.stringify(form.tags)">
    <input type="hidden" name="features" :value="JSON.stringify(form.features)">
    <input type="hidden" name="size_options" :value="JSON.stringify(form.sizeOptions)">
    <input type="hidden" name="color_options" :value="JSON.stringify(form.colorOptions)">
    <input type="hidden" name="images" :value="JSON.stringify(form.images.map(i => i.url))">
    <input type="hidden" name="variants" :value="JSON.stringify(form.variants)">
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
                    :disabled="submitting"
                    class="inline-flex items-center gap-1 px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg transition disabled:opacity-50"
                >
                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span x-text="submitting && saveMode === 'draft' ? 'Saving…' : 'Save Draft'">Save Draft</span>
                </button>

                <button
                    type="button"
                    @click="submitMode('publish')"
                    :disabled="submitting"
                    class="inline-flex items-center gap-1 px-4 py-1.5 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow-sm transition disabled:opacity-50"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span x-text="submitting && saveMode === 'publish' ? 'Saving…' : (isEditing ? 'Save & Publish' : 'Publish')">
                        {{ $isEditing ? 'Save & Publish' : 'Publish' }}
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Error Banner -->
    <div x-show="validationErrors.length > 0" x-cloak class="rounded-xl border border-red-200 bg-red-50 p-4">
        <p class="text-xs font-bold text-red-700">Please correct the following issues:</p>
        <ul class="mt-1 list-disc list-inside text-xs text-red-600 space-y-0.5">
            <template x-for="(err, idx) in validationErrors" :key="idx">
                <li x-text="err"></li>
            </template>
        </ul>
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
                <label class="text-xs font-bold text-slate-800">
                    Product Title <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="title"
                    x-model="form.title"
                    @input="handleTitleChange($event.target.value)"
                    placeholder='e.g. Samsung 55" 4K Smart TV'
                    required
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900"
                />
            </div>

            <!-- Slug -->
            <div class="sm:col-span-2 space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-800">Slug (URL)</label>
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
                    name="slug"
                    x-model="form.slug"
                    @input="slugTouched = true"
                    placeholder="auto-generated from title, e.g. samsung-tv"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg font-mono focus:outline-hidden focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900"
                />
                <p class="text-[11px] text-slate-400">Auto-generated from title. Only English letters, numbers and hyphens.</p>
            </div>

            <!-- Short Description -->
            <div class="sm:col-span-2 space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Short Description</label>
                <textarea
                    name="short_description"
                    x-model="form.shortDescription"
                    rows="2"
                    placeholder="One-line product highlight shown on cards"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-hidden focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900"
                ></textarea>
            </div>

            <!-- Full Description (Rich HTML) -->
            <div class="sm:col-span-2 space-y-1.5" x-data="richEditor({ initial: form.description })">
                <label class="text-xs font-bold text-slate-800">Full Description</label>
                <!-- Editor Toolbar -->
                <div class="flex flex-wrap items-center gap-1 rounded-t-lg border border-slate-300 bg-slate-50 px-2 py-1.5 text-xs">
                    <button type="button" @click="format('bold')" class="p-1 hover:bg-slate-200 rounded font-bold" title="Bold">B</button>
                    <button type="button" @click="format('italic')" class="p-1 hover:bg-slate-200 rounded italic" title="Italic">I</button>
                    <button type="button" @click="format('underline')" class="p-1 hover:bg-slate-200 rounded underline" title="Underline">U</button>
                    <span class="text-slate-300">|</span>
                    <button type="button" @click="formatBlock('h2')" class="p-1 hover:bg-slate-200 rounded text-[11px] font-bold" title="Heading 2">H2</button>
                    <button type="button" @click="formatBlock('h3')" class="p-1 hover:bg-slate-200 rounded text-[11px] font-bold" title="Heading 3">H3</button>
                    <button type="button" @click="formatBlock('p')" class="p-1 hover:bg-slate-200 rounded text-[11px]" title="Paragraph">¶</button>
                    <span class="text-slate-300">|</span>
                    <button type="button" @click="format('insertUnorderedList')" class="p-1 hover:bg-slate-200 rounded" title="Bullet List">• List</button>
                    <button type="button" @click="format('insertOrderedList')" class="p-1 hover:bg-slate-200 rounded" title="Numbered List">1. List</button>
                    <button type="button" @click="toggleSource()" class="ml-auto p-1 hover:bg-slate-200 rounded text-[10px] font-mono text-slate-600" x-text="sourceMode ? 'WYSIWYG' : 'HTML View'"></button>
                </div>

                <div
                    x-show="!sourceMode"
                    x-ref="editor"
                    contenteditable="true"
                    @input="syncToForm()"
                    class="min-h-[160px] w-full rounded-b-lg border-x border-b border-slate-300 p-3 text-xs focus:outline-hidden prose prose-sm max-w-none bg-white"
                ></div>

                <textarea
                    x-show="sourceMode"
                    x-model="form.description"
                    rows="8"
                    class="w-full rounded-b-lg border-x border-b border-slate-300 p-3 text-xs font-mono focus:outline-hidden bg-slate-900 text-slate-100"
                ></textarea>
                <input type="hidden" name="description" :value="form.description">
            </div>
        </div>

        <!-- Classification & Reference Selects -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 pt-2">
            <!-- Category -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Category</label>
                <select
                    name="category_id"
                    x-model="form.categoryId"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">Select Category</option>
                    @foreach($categories->whereNull('parent_id') as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sub Category -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Sub-category</label>
                <select
                    name="sub_category_id"
                    x-model="form.subCategoryId"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">Select Sub-category</option>
                    @foreach($categories->whereNotNull('parent_id') as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Child Category -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Child category</label>
                <select
                    name="child_category_id"
                    x-model="form.childCategoryId"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">Select Child Category</option>
                    @foreach($categories->whereNotNull('parent_id') as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Brand -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Brand</label>
                <select
                    name="brand_id"
                    x-model="form.brandId"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">Select Brand</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Collection -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Collection</label>
                <select
                    name="collection_id"
                    x-model="form.collectionId"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">Select Collection</option>
                    @foreach($collections as $col)
                        <option value="{{ $col->id }}">{{ $col->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Vendor -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Vendor</label>
                <select
                    name="vendor_id"
                    x-model="form.vendorId"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">Select Vendor</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Supplier -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Supplier</label>
                <select
                    name="supplier_id"
                    x-model="form.supplierId"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="">Select Supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- SKU -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">SKU</label>
                <input
                    type="text"
                    name="sku"
                    x-model="form.sku"
                    placeholder="e.g. TV-55-4K"
                    class="w-full px-3 py-2 text-xs font-mono border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Barcode -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Barcode</label>
                <input
                    type="text"
                    name="barcode"
                    x-model="form.barcode"
                    placeholder="e.g. 8801234567890"
                    class="w-full px-3 py-2 text-xs font-mono border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Country of Origin -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Country of Origin</label>
                <input
                    type="text"
                    name="country_of_origin"
                    x-model="form.countryOfOrigin"
                    placeholder="e.g. Bangladesh"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Warehouse -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Warehouse</label>
                <input
                    type="text"
                    name="warehouse"
                    x-model="form.warehouse"
                    placeholder="e.g. Dhaka Main"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Video URL -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Video URL</label>
                <input
                    type="text"
                    name="video_url"
                    x-model="form.videoUrl"
                    placeholder="https://youtube.com/watch?v=…"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Warranty -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Warranty</label>
                <input
                    type="text"
                    name="warranty"
                    x-model="form.warranty"
                    placeholder="e.g. 1 year official"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Weight -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Weight</label>
                <input
                    type="text"
                    name="weight"
                    x-model="form.weight"
                    placeholder="e.g. 5.5 kg"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Dimensions -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Dimensions</label>
                <input
                    type="text"
                    name="dimensions"
                    x-model="form.dimensions"
                    placeholder="e.g. 123 x 71 x 8 cm"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Payment Phone -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Payment Phone (bKash / Nagad)</label>
                <input
                    type="text"
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
                <label class="text-xs font-bold text-slate-800">
                    Price (৳) <span class="text-red-500">*</span>
                </label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="price"
                    x-model="form.price"
                    @input="handlePriceChange($event.target.value)"
                    placeholder="45000"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Sale Price -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Sale Price (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="sale_price"
                    x-model="form.salePrice"
                    @input="handleSalePriceChange($event.target.value)"
                    placeholder="42000"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Discount % -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Discount (%)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    name="discount"
                    x-model="form.discount"
                    @input="handleDiscountChange($event.target.value)"
                    placeholder="10"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Cost Price -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Cost Price (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="cost_price"
                    x-model="form.costPrice"
                    placeholder="35000"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Profit Margin -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Profit Margin (%)</label>
                <input
                    type="number"
                    step="0.01"
                    name="profit_margin"
                    x-model="form.profitMargin"
                    placeholder="Auto-calculated"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Flash Sale Price -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Flash Sale Price (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="flash_sale_price"
                    x-model="form.flashSalePrice"
                    placeholder="39990"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Wholesale Price -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Wholesale Price (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="wholesale_price"
                    x-model="form.wholesalePrice"
                    placeholder="38000"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Dealer Price -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Dealer Price (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="dealer_price"
                    x-model="form.dealerPrice"
                    placeholder="37000"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Tax -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Tax (%)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="tax"
                    x-model="form.tax"
                    placeholder="5"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- VAT -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">VAT (%)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="vat"
                    x-model="form.vat"
                    placeholder="15"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Shipping Charge -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Shipping Charge (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="shipping_charge"
                    x-model="form.shippingCharge"
                    placeholder="100"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- COD Fee -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">COD Fee (৳)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="cod_fee"
                    x-model="form.codFee"
                    placeholder="50"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
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
                <label class="text-xs font-bold text-slate-800">
                    Stock Quantity <span x-show="form.hasVariants">(Base)</span>
                </label>
                <input
                    type="number"
                    min="0"
                    name="stock"
                    x-model="form.stock"
                    :disabled="form.hasVariants && form.variants.length > 0"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg disabled:bg-slate-100 disabled:text-slate-400"
                />
                <p x-show="form.hasVariants && form.variants.length > 0" class="text-[10px] text-slate-400">Managed per variant below</p>
            </div>

            <!-- Low Stock Alert -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Low Stock Alert</label>
                <input
                    type="number"
                    min="0"
                    name="low_stock_alert"
                    x-model="form.lowStockAlert"
                    placeholder="5"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Stock Status -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Stock Status</label>
                <select
                    name="stock_status"
                    x-model="form.stockStatus"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white"
                >
                    <option value="in_stock">In stock</option>
                    <option value="low_stock">Low stock</option>
                    <option value="out_of_stock">Out of stock</option>
                    <option value="on_backorder">On backorder</option>
                </select>
            </div>

            <!-- Min Order -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Minimum Order Qty</label>
                <input
                    type="number"
                    min="0"
                    name="min_order"
                    x-model="form.minOrder"
                    placeholder="1"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Max Order -->
            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Maximum Order Qty</label>
                <input
                    type="number"
                    min="0"
                    name="max_order"
                    x-model="form.maxOrder"
                    placeholder="10"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>
        </div>

        <!-- Inventory Toggles -->
        <div class="grid gap-3 sm:grid-cols-3 pt-2">
            <!-- Unlimited Stock -->
            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 bg-white">
                <div>
                    <p class="text-xs font-bold text-slate-900">Unlimited Stock</p>
                    <p class="text-[11px] text-slate-400">Ignore stock counting</p>
                </div>
                <input
                    type="checkbox"
                    name="unlimited_stock"
                    x-model="form.unlimitedStock"
                    class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-4 w-4"
                />
            </div>

            <!-- Backorder -->
            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 bg-white">
                <div>
                    <p class="text-xs font-bold text-slate-900">Allow Backorder</p>
                    <p class="text-[11px] text-slate-400">Accept orders when out of stock</p>
                </div>
                <input
                    type="checkbox"
                    name="backorder"
                    x-model="form.backorder"
                    class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-4 w-4"
                />
            </div>

            <!-- Track Inventory -->
            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 bg-white">
                <div>
                    <p class="text-xs font-bold text-slate-900">Track Inventory</p>
                    <p class="text-[11px] text-slate-400">Decrement stock on orders</p>
                </div>
                <input
                    type="checkbox"
                    name="track_inventory"
                    x-model="form.trackInventory"
                    class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-4 w-4"
                />
            </div>
        </div>
    </div>

    <!-- Section 4: Images (Local Storage Uploader) -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-5" x-data="imageUploader()">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Images
            </h2>
            <p class="text-xs text-slate-500">Local storage uploader — drag & drop, reorder, replace or delete</p>
        </div>

        <!-- Dropzone Area -->
        <div
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="isDragging = false; uploadFiles($event.dataTransfer.files)"
            @click="$refs.imageUploadInput.click()"
            class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-8 text-center cursor-pointer transition"
            :class="isDragging ? 'border-slate-900 bg-slate-50' : 'border-slate-300 hover:border-slate-400 hover:bg-slate-50/50'"
        >
            <input
                type="file"
                multiple
                accept="image/*"
                x-ref="imageUploadInput"
                class="hidden"
                @change="uploadFiles($event.target.files)"
            />
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <div>
                <p class="text-xs font-bold text-slate-800">
                    Drag &amp; drop product images, or <span class="text-emerald-700 underline">browse</span>
                </p>
                <p class="text-[11px] text-slate-400">JPEG · PNG · WebP · GIF — stored locally in storage/app/public</p>
            </div>
        </div>

        <!-- Image Tiles Grid -->
        <div x-show="form.images.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5 pt-2">
            <template x-for="(img, idx) in form.images" :key="img.id">
                <div class="group relative aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                    <img :src="img.url" alt="" class="h-full w-full object-cover">

                    <!-- Thumbnail Badge for Main Image -->
                    <span
                        x-show="idx === 0"
                        class="absolute left-1.5 top-1.5 flex items-center gap-1 rounded bg-slate-900 px-1.5 py-0.5 text-[9px] font-bold text-white shadow-xs"
                    >
                        ★ Main
                    </span>

                    <!-- Overlay Controls on Hover -->
                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/80 to-transparent px-2 pb-2 pt-6 opacity-0 transition group-hover:opacity-100">
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="moveImage(idx, idx - 1)"
                                :disabled="idx === 0"
                                class="p-1 rounded bg-white/20 text-white hover:bg-white/40 disabled:opacity-30 text-xs"
                                title="Move left"
                            >
                                ←
                            </button>
                            <button
                                type="button"
                                @click="moveImage(idx, idx + 1)"
                                :disabled="idx === form.images.length - 1"
                                class="p-1 rounded bg-white/20 text-white hover:bg-white/40 disabled:opacity-30 text-xs"
                                title="Move right"
                            >
                                →
                            </button>
                        </div>

                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                x-show="idx !== 0"
                                @click="makeMain(idx)"
                                class="p-1 rounded bg-white/20 text-white hover:bg-white/40 text-xs"
                                title="Set as main"
                            >
                                ★
                            </button>
                            <button
                                type="button"
                                @click="removeImage(idx)"
                                class="p-1 rounded bg-red-600/80 text-white hover:bg-red-600 text-xs"
                                title="Delete"
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
    <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-xs space-y-5" x-data="variantManager()">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Variants
            </h2>
            <p class="text-xs text-slate-500">Create product variants with individual pricing, stock, and images</p>
        </div>

        <!-- Has Variants Switch -->
        <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4 bg-slate-50/50">
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

        <!-- Variants Workspace -->
        <div x-show="form.hasVariants" class="space-y-5">
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
                            <th class="px-2.5 py-2.5 w-28">SKU</th>
                            <th class="px-2.5 py-2.5 w-24">Price (৳)</th>
                            <th class="px-2.5 py-2.5 w-24">Sale Price (৳)</th>
                            <th class="px-2.5 py-2.5 w-20">Stock</th>
                            <th class="px-2.5 py-2.5 w-32">Image</th>
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
                                    <input
                                        type="text"
                                        x-model="v.name"
                                        placeholder="Black / L"
                                        class="w-full px-2 py-1 text-xs border border-slate-300 rounded font-medium"
                                    />
                                </td>
                                <td class="px-2.5 py-2">
                                    <input
                                        type="text"
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
                                        x-model="v.salePrice"
                                        placeholder="1290"
                                        class="w-full px-2 py-1 text-xs border border-slate-300 rounded"
                                    />
                                </td>
                                <td class="px-2.5 py-2">
                                    <input
                                        type="number"
                                        min="0"
                                        x-model="v.stock"
                                        placeholder="0"
                                        class="w-full px-2 py-1 text-xs border border-slate-300 rounded text-center"
                                    />
                                </td>
                                <td class="px-2.5 py-2">
                                    <div class="flex flex-col items-center gap-1.5">
                                        {{-- Thumbnail preview / click to pick --}}
                                        <div class="w-14 h-14 rounded-lg border border-slate-200 bg-slate-50 overflow-hidden flex items-center justify-center shrink-0 cursor-pointer"
                                             :class="v.thumbnail ? '' : 'border-dashed'"
                                             @click="openVariantFilePicker($event, v.key)">
                                            <template x-if="v.thumbnail">
                                                <img :src="v.thumbnail" class="w-full h-full object-cover" title="Click to replace">
                                            </template>
                                            <template x-if="!v.thumbnail">
                                                <div class="flex flex-col items-center gap-0.5 p-1 text-center">
                                                    <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    <span class="text-[9px] text-slate-400 leading-tight">Add</span>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Hidden file input — identified by data-varkey, not :x-ref --}}
                                        <input
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            class="hidden"
                                            :data-varkey="v.key"
                                            @change="uploadVariantImage($event, v)"
                                        >

                                        {{-- Remove button --}}
                                        <button
                                            x-show="v.thumbnail && !v._uploading"
                                            type="button"
                                            @click.stop="v.thumbnail = ''"
                                            class="text-[9px] text-red-400 hover:text-red-600 leading-none mt-0.5"
                                        >Remove</button>

                                        {{-- Upload spinner --}}
                                        <span x-show="v._uploading" class="text-[9px] text-brand-green-600 animate-pulse">Uploading…</span>
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
                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 bg-white">
                    <div>
                        <p class="text-xs font-bold text-slate-900">{{ $label }}</p>
                        <p class="text-[11px] text-slate-400">{{ $desc }}</p>
                    </div>
                    <input
                        type="checkbox"
                        name="{{ Str::snake($key) }}"
                        x-model="form.{{ $key }}"
                        class="rounded border-slate-300 text-slate-900 focus:ring-slate-900 h-4 w-4"
                    />
                </div>
            @endforeach
        </div>

        <!-- SEO Metadata Fields -->
        <div class="grid gap-4 sm:grid-cols-2 pt-2">
            <div class="sm:col-span-2 space-y-1.5">
                <label class="text-xs font-bold text-slate-800">SEO Title</label>
                <input
                    type="text"
                    name="seo_title"
                    x-model="form.seoTitle"
                    placeholder="Meta title"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <div class="sm:col-span-2 space-y-1.5">
                <label class="text-xs font-bold text-slate-800">SEO Description</label>
                <textarea
                    name="seo_description"
                    x-model="form.seoDescription"
                    rows="2"
                    placeholder="Meta description"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                ></textarea>
            </div>

            <div class="sm:col-span-2 space-y-1.5">
                <label class="text-xs font-bold text-slate-800">SEO Keywords</label>
                <input
                    type="text"
                    name="seo_keywords"
                    x-model="form.seoKeywords"
                    placeholder="comma, separated, keywords"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <div class="sm:col-span-2 space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Canonical URL</label>
                <input
                    type="text"
                    name="canonical_url"
                    x-model="form.canonicalUrl"
                    placeholder="https://example.com/products/…"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">OG Image URL</label>
                <input
                    type="text"
                    name="og_image"
                    x-model="form.ogImage"
                    placeholder="https://…"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-800">Twitter Image URL</label>
                <input
                    type="text"
                    name="twitter_image"
                    x-model="form.twitterImage"
                    placeholder="https://…"
                    class="w-full px-3 py-2 text-xs border border-slate-300 rounded-lg"
                />
            </div>

            <!-- Structured Data JSON -->
            <div class="sm:col-span-2 space-y-1.5" x-data="{ jsonError: null }">
                <label class="text-xs font-bold text-slate-800">Structured Data (JSON-LD)</label>
                <textarea
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
                    class="w-full px-3 py-2 text-xs font-mono border border-slate-300 rounded-lg"
                ></textarea>
                <p x-show="jsonError" x-text="'Invalid JSON: ' + jsonError" class="text-[11px] text-red-600 font-semibold"></p>
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
    // Rich editor mini helper
    Alpine.data('richEditor', (config) => ({
        sourceMode: false,
        init() {
            this.$nextTick(() => {
                if (this.$refs.editor && config.initial) {
                    this.$refs.editor.innerHTML = config.initial;
                }
            });
        },
        format(cmd, val = null) {
            document.execCommand(cmd, false, val);
            this.syncToForm();
        },
        formatBlock(tag) {
            document.execCommand('formatBlock', false, tag);
            this.syncToForm();
        },
        syncToForm() {
            if (this.$refs.editor) {
                this.form.description = this.$refs.editor.innerHTML;
            }
        },
        toggleSource() {
            this.sourceMode = !this.sourceMode;
            if (!this.sourceMode) {
                this.$nextTick(() => {
                    if (this.$refs.editor) {
                        this.$refs.editor.innerHTML = this.form.description;
                    }
                });
            }
        }
    }));

    // Image uploader
    Alpine.data('imageUploader', () => ({
        isDragging: false,
        uploadFiles(files) {
            if (!files || files.length === 0) return;
            const formData = new FormData();
            Array.from(files).forEach(f => formData.append('files[]', f));
            formData.append('_token', this.csrfToken);

            fetch(this.uploadUrl, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(res => {
                if (res.urls && res.urls.length > 0) {
                    res.urls.forEach(url => {
                        this.form.images.push({
                            id: Math.random().toString(36).substring(7),
                            url: url,
                            status: 'done',
                            progress: 100
                        });
                    });
                }
            })
            .catch(err => alert('Image upload failed'));
        },
        moveImage(from, to) {
            if (to < 0 || to >= this.form.images.length) return;
            const item = this.form.images.splice(from, 1)[0];
            this.form.images.splice(to, 0, item);
        },
        makeMain(idx) {
            const item = this.form.images.splice(idx, 1)[0];
            this.form.images.unshift(item);
        },
        removeImage(idx) {
            this.form.images.splice(idx, 1);
        }
    }));

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
                    return `${parsed.color || ''}::${parsed.size || ''}`.toLowerCase();
                } catch(e) {
                    return v.name.toLowerCase();
                }
            }));

            let added = 0;
            colors.forEach(color => {
                sizes.forEach(size => {
                    const parts = [];
                    const opts = {};
                    if (color) { parts.push(color); opts.color = color; }
                    if (size) { parts.push(size); opts.size = size; }
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

        // ---------- Variant image upload ----------
        openVariantFilePicker(event, varKey) {
            const input = event.target.closest('tr').querySelector(`input[data-varkey="${varKey}"]`);
            if (input) input.click();
        },
        async uploadVariantImage(event, variant) {
            const file = event.target.files[0];
            if (!file) return;

            // Validate client-side: type and size (5 MB max)
            const allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowed.includes(file.type)) {
                alert('Only JPG, PNG, and WEBP images are allowed.');
                event.target.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('Image must be 5 MB or smaller.');
                event.target.value = '';
                return;
            }

            // Instant browser preview while uploading
            const previewUrl = URL.createObjectURL(file);
            variant.thumbnail = previewUrl;
            variant._uploading = true;

            const fd = new FormData();
            fd.append('file', file);
            fd.append('folder', 'products/variants');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                           || document.querySelector('input[name="_token"]')?.value
                           || '';

            try {
                const res = await fetch('{{ route('admin.products.upload-image') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: fd,
                });
                const json = await res.json();
                if (json.success && json.url) {
                    variant.thumbnail = json.url;   // replace blob preview with real storage URL
                } else {
                    variant.thumbnail = '';          // upload failed — clear preview
                    alert('Image upload failed. Please try again.');
                }
            } catch (err) {
                variant.thumbnail = '';
                alert('Upload error: ' + err.message);
            } finally {
                variant._uploading = false;
                URL.revokeObjectURL(previewUrl);
                event.target.value = ''; // reset so the same file can be re-selected
            }
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
            if (this.form.structuredData.trim()) {
                try { JSON.parse(this.form.structuredData); }
                catch(e) { this.validationErrors.push('Structured Data (JSON-LD) contains invalid JSON.'); }
            }
            return this.validationErrors.length === 0;
        },

        submitForm() {
            if (!this.validate()) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            this.submitting = true;
            document.getElementById('productMainForm').submit();
        }
    }));
});
</script>
