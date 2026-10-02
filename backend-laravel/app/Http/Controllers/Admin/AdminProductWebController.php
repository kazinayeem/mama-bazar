<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
use App\Models\Supplier;
use App\Models\Vendor;
use App\Services\ActivityLoggerService;
use App\Services\HtmlSanitizer;
use App\Services\MediaStorageService;
use App\Services\ProductService;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminProductWebController extends Controller
{
    public function index(Request $request)
    {
        $params = $request->all();
        $params['limit'] = $params['limit'] ?? 20;
        $params['status'] = $params['status'] ?? 'all';

        $result = ProductService::getAll($params);

        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $collections = Collection::orderBy('name')->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination'],
                'total' => $result['total'],
                'totalPages' => $result['totalPages'],
                'page' => $result['page'],
            ]);
        }

        return view('admin.products.index', [
            'products' => $result['data'],
            'pagination' => $result['pagination'],
            'total' => $result['total'],
            'totalPages' => $result['totalPages'],
            'page' => $result['page'],
            'filters' => $params,
            'categories' => $categories,
            'brands' => $brands,
            'suppliers' => $suppliers,
            'vendors' => $vendors,
            'collections' => $collections,
        ]);
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $collections = Collection::orderBy('name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $colors = Color::orderBy('sort_order')->orderBy('name')->get();
        $sizes = Size::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.products.create', compact(
            'categories', 'brands', 'collections', 'vendors', 'suppliers', 'colors', 'sizes'
        ));
    }

    public function store(AdminProductRequest $request)
    {
        try {
            $payload = $this->normalizePayload($request);
            $payload = $this->processVariantImages($request, $payload);

            $product = ProductService::create($payload);

            ActivityLoggerService::logProduct(
                'product.created',
                $product,
                'Product created: '.($product['title'] ?? 'Product #'.($product['id'] ?? '')),
                [
                    'actor' => Auth::user(),
                    'source' => 'admin',
                    'newValues' => [
                        'title' => $product['title'] ?? null,
                        'price' => $product['price'] ?? null,
                        'stock' => $product['stock'] ?? null,
                        'sku' => $product['sku'] ?? null,
                    ],
                ]
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product created successfully',
                    'data' => $product,
                ], 201);
            }

            return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Product creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to create product. Please check your inputs and try again.',
                ], 422);
            }

            return back()->withInput()->withErrors([
                'general' => 'Unable to create product. An unexpected error occurred. Please try again.',
            ]);
        }
    }

    public function show($id)
    {
        $product = ProductService::getById((int) $id);
        if (! $product) {
            abort(404, 'Product not found');
        }

        return view('admin.products.show', compact('product'));
    }

    public function edit($id)
    {
        $product = ProductService::getById((int) $id);
        if (! $product) {
            abort(404, 'Product not found');
        }

        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $collections = Collection::orderBy('name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $colors = Color::orderBy('sort_order')->orderBy('name')->get();
        $sizes = Size::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.products.edit', compact(
            'product', 'categories', 'brands', 'collections', 'vendors', 'suppliers', 'colors', 'sizes'
        ));
    }

    public function update(AdminProductRequest $request, $id)
    {
        try {
            $payload = $this->normalizePayload($request);
            $payload = $this->processVariantImages($request, $payload);

            $product = ProductService::update((int) $id, $payload);

            ActivityLoggerService::logProduct(
                'product.updated',
                $product,
                'Product updated: '.($product['title'] ?? 'Product #'.$id),
                [
                    'actor' => Auth::user(),
                    'source' => 'admin',
                    'newValues' => [
                        'title' => $product['title'] ?? null,
                        'price' => $product['price'] ?? null,
                        'stock' => $product['stock'] ?? null,
                    ],
                ]
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product updated successfully',
                    'data' => $product,
                ]);
            }

            return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Product update failed', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to update product. Please check your inputs and try again.',
                ], 422);
            }

            return back()->withInput()->withErrors([
                'general' => 'Unable to update product. An unexpected error occurred. Please try again.',
            ]);
        }
    }

    public function destroy($id, Request $request)
    {
        ProductService::remove((int) $id);

        ActivityLoggerService::logProduct(
            'product.deleted',
            $id,
            "Product #{$id} deleted",
            ['actor' => Auth::user(), 'source' => 'admin']
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    public function duplicate($id, Request $request)
    {
        $copy = ProductService::duplicate((int) $id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product duplicated successfully',
                'data' => $copy,
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product duplicated as "'.$copy['title'].'".');
    }

    public function toggleFeatured($id, Request $request)
    {
        $featured = $request->has('featured') ? (bool) $request->input('featured') : null;
        $status = ProductService::toggleFeatured((int) $id, $featured);

        return response()->json([
            'success' => true,
            'isFeatured' => $status,
        ]);
    }

    public function toggleStatus($id, Request $request)
    {
        $product = ProductService::getById((int) $id);
        if (! $product) {
            abort(404, 'Product not found');
        }

        // Toggle between active and inactive
        $currentStatus = $product['status'] ?? 'inactive';
        $newAction = ($currentStatus === 'active') ? 'deactivate' : 'activate';

        $result = ProductService::bulkAction([(int) $id], $newAction);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'newStatus' => $newAction === 'activate' ? 'active' : 'inactive',
                'affected' => $result['affected'],
            ]);
        }

        return back()->with('success', 'Product status toggled.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:publish,draft,hide,archive,delete,activate,deactivate',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $result = ProductService::bulkAction($request->input('ids'), $request->input('action'));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'affected' => $result['affected'],
                'affectedCount' => $result['affectedCount'],
            ]);
        }

        return back()->with('success', "{$result['affected']} product(s) updated.");
    }

    public function exportCsv(Request $request)
    {
        $csv = ProductService::exportCsv($request->all());
        $filename = 'products-'.date('Y-m-d').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function importCsv(Request $request)
    {
        $content = '';
        if ($request->hasFile('file')) {
            $content = file_get_contents($request->file('file')->getRealPath());
        } elseif ($request->filled('csv')) {
            $content = $request->input('csv');
        } elseif (! empty($request->getContent())) {
            $content = $request->getContent();
        }

        if (empty($content)) {
            return response()->json(['error' => 'No CSV content provided.'], 422);
        }

        $result = ProductService::importCsv($content);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'imported' => $result['imported'],
            ]);
        }

        return back()->with('success', "{$result['imported']} product(s) imported.");
    }

    public function uploadImage(Request $request)
    {
        try {
            $request->validate([
                'file' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:20480',
                'files' => 'nullable|array',
                'files.*' => 'file|mimes:jpeg,jpg,png,webp,gif,svg|max:20480',
                'folder' => 'nullable|string|max:80',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Invalid image file provided.',
                'errors' => $e->errors(),
            ], 422);
        }

        // Resolve folder — sanitise to prevent path traversal
        $rawFolder = $request->input('folder', 'products');
        $folder = preg_replace('/[^a-zA-Z0-9\/\-_]/', '', $rawFolder) ?: 'products';

        $uploaded = [];
        $data = [];

        try {
            if ($request->hasFile('file')) {
                $f = $request->file('file');
                if (! $f->isValid()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Uploaded file is invalid or corrupted.',
                    ], 422);
                }
                $res = MediaStorageService::uploadFile($f, $folder);
                $uploaded[] = $res['url'];
                $data[] = [
                    'url' => $res['url'],
                    'path' => $res['path'] ?? null,
                ];
            }

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $f) {
                    if (! $f->isValid()) {
                        continue;
                    }
                    $res = MediaStorageService::uploadFile($f, $folder);
                    $uploaded[] = $res['url'];
                    $data[] = [
                        'url' => $res['url'],
                        'path' => $res['path'] ?? null,
                    ];
                }
            }

            if (empty($uploaded)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid image files received.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => count($uploaded) > 1 ? count($uploaded).' images uploaded successfully.' : 'Image uploaded successfully.',
                'url' => $uploaded[0] ?? null,
                'urls' => $uploaded,
                'data' => count($data) === 1 ? $data[0] : $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store image: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an individual image from a product.
     * Enforces ownership to prevent modifying other products' images.
     */
    public function deleteImage(Request $request, $id)
    {
        $product = Product::findOrFail((int) $id);

        $targetUrl = trim((string) ($request->input('image') ?: $request->input('url')));
        if ($targetUrl === '') {
            return response()->json([
                'success' => false,
                'message' => 'The image or url parameter is required.',
            ], 422);
        }

        $targetRel = MediaStorageService::toRelativePath($targetUrl);
        $images = is_array($product->images) ? $product->images : [];

        $found = false;
        $updatedImages = [];
        foreach ($images as $img) {
            $imgRel = MediaStorageService::toRelativePath($img);
            if ($img === $targetUrl || ($targetRel && $imgRel === $targetRel)) {
                $found = true;
            } else {
                $updatedImages[] = $img;
            }
        }

        if (! $found) {
            return response()->json([
                'success' => false,
                'message' => 'Image does not belong to this product.',
            ], 404);
        }

        $product->images = array_values($updatedImages);
        $product->save();

        MediaStorageService::deleteIfUnreferenced($targetUrl);

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.',
            'data' => [
                'images' => $product->images,
            ],
        ]);
    }

    /**
     * Rich-text editor image upload — local storage only, jpg/png/webp.
     */
    public function uploadEditorImage(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,webp|max:5120',
            'alt' => 'nullable|string|max:255',
        ]);

        $res = MediaStorageService::uploadFile($request->file('file'), 'products/descriptions');
        $url = $res['url'];

        if (! HtmlSanitizer::isAllowedImageSrc($url)) {
            return response()->json([
                'success' => false,
                'message' => 'Only local storage image URLs are allowed.',
            ], 422);
        }

        $alt = trim((string) $request->input('alt', ''));

        return response()->json([
            'success' => true,
            'url' => $url,
            'alt' => $alt,
            'path' => $res['path'] ?? null,
        ]);
    }

    protected function normalizePayload(Request $request): array
    {
        $data = $request->all();

        $mappings = [
            'shortDescription' => 'short_description',
            'returnPolicy' => 'return_policy',
            'categoryId' => 'category_id',
            'subCategoryId' => 'sub_category_id',
            'childCategoryId' => 'child_category_id',
            'brandId' => 'brand_id',
            'collectionId' => 'collection_id',
            'vendorId' => 'vendor_id',
            'supplierId' => 'supplier_id',
            'countryOfOrigin' => 'country_of_origin',
            'videoUrl' => 'video_url',
            'paymentPhoneNumber' => 'payment_phone_number',
            'salePrice' => 'sale_price',
            'costPrice' => 'cost_price',
            'profitMargin' => 'profit_margin',
            'shippingCharge' => 'shipping_charge',
            'codFee' => 'cod_fee',
            'flashSalePrice' => 'flash_sale_price',
            'wholesalePrice' => 'wholesale_price',
            'dealerPrice' => 'dealer_price',
            'lowStockAlert' => 'low_stock_alert',
            'minOrder' => 'min_order',
            'maxOrder' => 'max_order',
            'stockStatus' => 'stock_status',
            'unlimitedStock' => 'unlimited_stock',
            'trackInventory' => 'track_inventory',
            'productStatus' => 'product_status',
            'emiAvailable' => 'emi_available',
            'isFeatured' => 'is_featured',
            'isTrending' => 'is_trending',
            'isFlashSale' => 'is_flash_sale',
            'isNewArrival' => 'is_new_arrival',
            'isBestSeller' => 'is_best_seller',
            'isLimitedEdition' => 'is_limited_edition',
            'isOfficial' => 'is_official',
            'isHotDeal' => 'is_hot_deal',
            'seoTitle' => 'seo_title',
            'seoDescription' => 'seo_description',
            'seoKeywords' => 'seo_keywords',
            'canonicalUrl' => 'canonical_url',
            'ogImage' => 'og_image',
            'twitterImage' => 'twitter_image',
            'structuredData' => 'structured_data',
            'sizeOptions' => 'size_options',
            'colorOptions' => 'color_options',
        ];

        foreach ($mappings as $camel => $snake) {
            if (isset($data[$camel]) && ! isset($data[$snake])) {
                $data[$snake] = $data[$camel];
            }
        }

        // Handle JSON encoded string payloads from Blade forms
        foreach (['tags', 'features', 'size_options', 'color_options', 'images', 'variants', 'specs', 'relations', 'deleted_images'] as $jsonField) {
            if (isset($data[$jsonField]) && is_string($data[$jsonField])) {
                $decoded = json_decode($data[$jsonField], true);
                if (is_array($decoded)) {
                    $data[$jsonField] = $decoded;
                }
            }
        }

        // Clean and sanitize images array: reject blob and data URLs, preserve order, remove duplicates
        if (isset($data['images']) && is_array($data['images'])) {
            $cleanedImages = [];
            foreach ($data['images'] as $img) {
                if (is_string($img)) {
                    $img = trim($img);
                    if ($img !== '' && ! str_starts_with($img, 'blob:') && ! str_starts_with($img, 'data:')) {
                        $cleanedImages[] = $img;
                    }
                }
            }
            $data['images'] = array_values(array_unique($cleanedImages));
        }

        // Convert empty strings to null for integer and foreign key columns
        foreach ([
            'category_id', 'sub_category_id', 'child_category_id', 'brand_id',
            'collection_id', 'vendor_id', 'supplier_id', 'low_stock_alert',
            'min_order', 'max_order',
        ] as $intField) {
            if (array_key_exists($intField, $data)) {
                $v = $data[$intField];
                $data[$intField] = ($v === '' || $v === null || $v === 'null') ? null : (int) $v;
            }
        }
        if (array_key_exists('stock', $data)) {
            $v = $data['stock'];
            $data['stock'] = ($v === '' || $v === null || $v === 'null') ? 0 : (int) $v;
        }

        // Convert empty strings to null for truly-nullable float columns.
        // NOT NULL columns (cost_price, profit_margin, tax, vat, shipping_charge,
        // cod_fee) default to 0 so saves never hit SQL NOT NULL violations.
        foreach ([
            'sale_price', 'flash_sale_price',
            'wholesale_price', 'dealer_price',
        ] as $floatField) {
            if (array_key_exists($floatField, $data)) {
                $v = $data[$floatField];
                $data[$floatField] = ($v === '' || $v === null || $v === 'null') ? null : (float) $v;
            }
        }
        foreach (['cost_price', 'profit_margin', 'tax', 'vat', 'shipping_charge', 'cod_fee'] as $zeroFloat) {
            if (array_key_exists($zeroFloat, $data)) {
                $v = $data[$zeroFloat];
                $data[$zeroFloat] = ($v === '' || $v === null || $v === 'null') ? 0.0 : (float) $v;
            }
        }
        if (array_key_exists('price', $data)) {
            $v = $data['price'];
            $data['price'] = ($v === '' || $v === null || $v === 'null') ? 0.0 : (float) $v;
        }
        if (array_key_exists('discount', $data)) {
            $v = $data['discount'];
            $data['discount'] = ($v === '' || $v === null || $v === 'null') ? 0.0 : (float) $v;
        }

        // Convert empty strings to null for optional string columns
        foreach ([
            'sku', 'barcode', 'country_of_origin', 'warranty', 'weight',
            'dimensions', 'warehouse', 'video_url', 'payment_phone_number',
            'seo_title', 'seo_description', 'seo_keywords', 'canonical_url',
            'og_image', 'twitter_image', 'return_policy', 'short_description',
        ] as $strField) {
            if (array_key_exists($strField, $data) && is_string($data[$strField])) {
                $trimmed = trim($data[$strField]);
                $data[$strField] = $trimmed === '' ? null : $trimmed;
            }
        }

        // Cast boolean flags safely
        foreach ([
            'unlimited_stock', 'backorder', 'track_inventory', 'emi_available',
            'is_featured', 'is_trending', 'is_flash_sale', 'is_new_arrival',
            'is_best_seller', 'is_limited_edition', 'is_official', 'is_hot_deal',
        ] as $boolField) {
            if (array_key_exists($boolField, $data)) {
                $val = $data[$boolField];
                if (is_bool($val)) {
                    $data[$boolField] = $val;
                } else {
                    $filtered = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    $data[$boolField] = $filtered !== null ? $filtered : false;
                }
            }
        }

        // Save mode handling
        $saveMode = $request->input('save_mode', $request->input('saveMode'));
        if ($saveMode === 'draft') {
            $data['product_status'] = 'draft';
            $data['status'] = 'inactive';
        } elseif ($saveMode === 'publish') {
            $data['product_status'] = 'published';
            $data['status'] = 'active';
        } else {
            if (empty($data['product_status'])) {
                $data['product_status'] = 'published';
            }
            if (empty($data['status'])) {
                $data['status'] = ($data['product_status'] === 'published') ? 'active' : 'inactive';
            }
        }

        // Slug derivation
        if (empty($data['slug']) && ! empty($data['title'])) {
            $data['slug'] = SlugService::toAsciiSlug($data['title']);
        }
        if (! empty($data['slug'])) {
            $data['slug'] = trim(strtolower($data['slug']));
        }

        // Fallback parent price when variants exist
        if (! empty($data['variants']) && is_array($data['variants'])) {
            $vPrices = array_filter(array_map(fn ($v) => (float) ($v['price'] ?? 0), $data['variants']), fn ($p) => $p > 0);
            if ((empty($data['price']) || (float) $data['price'] <= 0) && ! empty($vPrices)) {
                $data['price'] = min($vPrices);
            }
        }

        // When variants are disabled, clear the list so sync removes old rows
        if (isset($data['has_variants']) && ! filter_var($data['has_variants'], FILTER_VALIDATE_BOOLEAN)) {
            $data['variants'] = [];
        } elseif (
            filter_var($data['has_variants'] ?? false, FILTER_VALIDATE_BOOLEAN)
            && (! isset($data['variants']) || ! is_array($data['variants']))
        ) {
            $data['variants'] = [];
        }

        return $data;
    }

    /**
     * Process multipart variants[n][image] uploads into local storage paths.
     * Saves relative paths (products/variants/…) in the payload thumbnail field.
     */
    protected function processVariantImages(Request $request, array $data): array
    {
        if (empty($data['variants']) || ! is_array($data['variants'])) {
            return $data;
        }

        foreach ($data['variants'] as $i => &$variant) {
            if (! is_array($variant)) {
                continue;
            }

            $oldThumbnail = $variant['thumbnail'] ?? null;
            $remove = filter_var($variant['remove_image'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $variantId = isset($variant['id']) ? (int) $variant['id'] : null;

            if ($request->hasFile("variants.$i.image")) {
                $upload = MediaStorageService::storeUploaded(
                    $request->file("variants.$i.image"),
                    'products/variants'
                );
                $variant['thumbnail'] = $upload['path'];
                $variant['images'] = [$upload['url']];

                if ($oldThumbnail) {
                    MediaStorageService::deleteIfUnreferenced($oldThumbnail, $variantId ?: null);
                }
            } elseif ($remove) {
                if ($oldThumbnail) {
                    MediaStorageService::deleteIfUnreferenced($oldThumbnail, $variantId ?: null);
                }
                $variant['thumbnail'] = null;
                $variant['images'] = [];
            } else {
                // Keep existing path; normalize to relative when local
                if (is_string($oldThumbnail) && $oldThumbnail !== '') {
                    $relative = MediaStorageService::toRelativePath($oldThumbnail);
                    $variant['thumbnail'] = $relative ?: $oldThumbnail;
                } else {
                    $variant['thumbnail'] = null;
                }
            }

            unset($variant['remove_image'], $variant['image'], $variant['key'], $variant['_preview'], $variant['_savedPath'], $variant['_uploading']);

            if (array_key_exists('availability', $variant)) {
                $variant['availability'] = filter_var($variant['availability'], FILTER_VALIDATE_BOOLEAN);
            }
        }
        unset($variant);

        $data['variants'] = array_values($data['variants']);

        return $data;
    }
}
