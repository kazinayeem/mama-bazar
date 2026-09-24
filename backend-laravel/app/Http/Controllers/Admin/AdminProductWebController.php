<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminProductRequest;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Collection;
use App\Models\Vendor;
use App\Models\Supplier;
use App\Models\Color;
use App\Models\Size;
use App\Services\ProductService;
use App\Services\MediaStorageService;
use App\Services\SlugService;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;

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
        $payload = $this->normalizePayload($request);
        $payload = $this->processVariantImages($request, $payload);

        $product = ProductService::create($payload);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product,
            ], 201);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function show($id)
    {
        $product = ProductService::getById((int) $id);
        if (!$product) {
            abort(404, 'Product not found');
        }

        return view('admin.products.show', compact('product'));
    }

    public function edit($id)
    {
        $product = ProductService::getById((int) $id);
        if (!$product) {
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
        $payload = $this->normalizePayload($request);
        $payload = $this->processVariantImages($request, $payload);

        $product = ProductService::update((int) $id, $payload);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product,
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy($id, Request $request)
    {
        ProductService::remove((int) $id);

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

        return redirect()->route('admin.products.index')->with('success', 'Product duplicated as "' . $copy['title'] . '".');
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
        if (!$product) {
            abort(404, 'Product not found');
        }

        // Toggle between active and inactive
        $currentStatus = $product['status'] ?? 'inactive';
        $newAction = ($currentStatus === 'active') ? 'deactivate' : 'activate';

        $result = ProductService::bulkAction([(int) $id], $newAction);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'   => true,
                'newStatus' => $newAction === 'activate' ? 'active' : 'inactive',
                'affected'  => $result['affected'],
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
        $filename = 'products-' . date('Y-m-d') . '.csv';

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
        } elseif (!empty($request->getContent())) {
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
        $request->validate([
            'file'    => 'nullable|file|mimes:jpeg,jpg,png,webp,gif,svg|max:20480',
            'files'   => 'nullable|array',
            'files.*' => 'file|mimes:jpeg,jpg,png,webp,gif,svg|max:20480',
            'folder'  => 'nullable|string|max:80',
        ]);

        // Resolve folder — sanitise to prevent path traversal
        $rawFolder = $request->input('folder', 'products');
        $folder = preg_replace('/[^a-zA-Z0-9\/\-_]/', '', $rawFolder) ?: 'products';

        $uploaded = [];

        if ($request->hasFile('file')) {
            $res = MediaStorageService::uploadFile($request->file('file'), $folder);
            $uploaded[] = $res['url'];
        }

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $f) {
                $res = MediaStorageService::uploadFile($f, $folder);
                $uploaded[] = $res['url'];
            }
        }

        return response()->json([
            'success' => true,
            'url'     => $uploaded[0] ?? null,
            'urls'    => $uploaded,
        ]);
    }

    /**
     * Rich-text editor image upload — local storage only, jpg/png/webp.
     */
    public function uploadEditorImage(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,webp|max:5120',
            'alt'  => 'nullable|string|max:255',
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
            'url'     => $url,
            'alt'     => $alt,
            'path'    => $res['path'] ?? null,
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
            if (isset($data[$camel]) && !isset($data[$snake])) {
                $data[$snake] = $data[$camel];
            }
        }

        // Handle JSON encoded string payloads from Blade forms
        foreach (['tags', 'features', 'size_options', 'color_options', 'images', 'variants', 'specs', 'relations'] as $jsonField) {
            if (isset($data[$jsonField]) && is_string($data[$jsonField])) {
                $decoded = json_decode($data[$jsonField], true);
                if (is_array($decoded)) {
                    $data[$jsonField] = $decoded;
                }
            }
        }

        // Cast boolean flags
        foreach ([
            'unlimited_stock', 'backorder', 'track_inventory', 'emi_available',
            'is_featured', 'is_trending', 'is_flash_sale', 'is_new_arrival',
            'is_best_seller', 'is_limited_edition', 'is_official', 'is_hot_deal'
        ] as $boolField) {
            if (isset($data[$boolField])) {
                $data[$boolField] = filter_var($data[$boolField], FILTER_VALIDATE_BOOLEAN);
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
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = SlugService::toAsciiSlug($data['title']);
        }
        if (!empty($data['slug'])) {
            $data['slug'] = trim(strtolower($data['slug']));
        }

        // Fallback parent price when variants exist
        if (!empty($data['variants']) && is_array($data['variants'])) {
            $vPrices = array_filter(array_map(fn($v) => (float)($v['price'] ?? 0), $data['variants']), fn($p) => $p > 0);
            if ((empty($data['price']) || (float)$data['price'] <= 0) && !empty($vPrices)) {
                $data['price'] = min($vPrices);
            }
        }

        // When variants are disabled, clear the list so sync removes old rows
        if (isset($data['has_variants']) && !filter_var($data['has_variants'], FILTER_VALIDATE_BOOLEAN)) {
            $data['variants'] = [];
        } elseif (
            filter_var($data['has_variants'] ?? false, FILTER_VALIDATE_BOOLEAN)
            && (!isset($data['variants']) || !is_array($data['variants']))
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
        if (empty($data['variants']) || !is_array($data['variants'])) {
            return $data;
        }

        foreach ($data['variants'] as $i => &$variant) {
            if (!is_array($variant)) {
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
