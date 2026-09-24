<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProductService;
use App\Services\MediaStorageService;
use App\Services\SlugService;
use App\Models\Product;
use Exception;

class ProductController extends Controller
{
    private function parseJson($val)
    {
        if (is_array($val)) return $val;
        if (is_string($val) && !empty($val)) {
            $decoded = json_decode($val, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function getAll(Request $request)
    {
        $result = ProductService::getAll($request->all());
        return response()->json(array_merge(['success' => true], $result));
    }

    public function getById($id)
    {
        $product = ProductService::getById((int) $id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $product]);
    }

    public function getBySlug($slug)
    {
        $product = ProductService::getBySlug($slug);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $product]);
    }

    public function getRelated($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $related = ProductService::getRelated((int) $product->category_id, (int) $product->id);
        return response()->json(['success' => true, 'data' => $related]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $body = $request->all();

        $uploadedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $upload = MediaStorageService::uploadFile($file, 'products');
                $uploadedImages[] = $upload['url'];
            }
        }

        $bodyImages = $this->parseJson($request->input('images'));
        $images = array_merge($bodyImages, $uploadedImages);

        $requestedSlug = $request->filled('slug') ? trim(strtolower($request->input('slug'))) : null;
        $generatedSlug = SlugService::toAsciiSlug($body['title']) ?: 'product';
        $slug = ProductService::ensureUniqueSlug($requestedSlug ?: $generatedSlug, [
            'autoSuffix' => !$requestedSlug,
        ]);

        $data = [
            'title' => $body['title'],
            'slug' => $slug,
            'description' => $body['description'] ?? null,
            'short_description' => $body['shortDescription'] ?? null,
            'price' => (float) ($body['price'] ?? 0),
            'sale_price' => isset($body['salePrice']) && $body['salePrice'] !== '' ? (float) $body['salePrice'] : null,
            'discount' => isset($body['discount']) && $body['discount'] !== '' ? (float) $body['discount'] : 0,
            'cost_price' => isset($body['costPrice']) && $body['costPrice'] !== '' ? (float) $body['costPrice'] : 0,
            'tax' => isset($body['tax']) && $body['tax'] !== '' ? (float) $body['tax'] : 0,
            'vat' => isset($body['vat']) && $body['vat'] !== '' ? (float) $body['vat'] : 0,
            'shipping_charge' => isset($body['shippingCharge']) && $body['shippingCharge'] !== '' ? (float) $body['shippingCharge'] : 0,
            'cod_fee' => isset($body['codFee']) && $body['codFee'] !== '' ? (float) $body['codFee'] : 0,
            'flash_sale_price' => isset($body['flashSalePrice']) && $body['flashSalePrice'] !== '' ? (float) $body['flashSalePrice'] : null,
            'wholesale_price' => isset($body['wholesalePrice']) && $body['wholesalePrice'] !== '' ? (float) $body['wholesalePrice'] : null,
            'dealer_price' => isset($body['dealerPrice']) && $body['dealerPrice'] !== '' ? (float) $body['dealerPrice'] : null,
            'category_id' => !empty($body['categoryId']) ? (int) $body['categoryId'] : null,
            'sub_category_id' => !empty($body['subCategoryId']) ? (int) $body['subCategoryId'] : null,
            'child_category_id' => !empty($body['childCategoryId']) ? (int) $body['childCategoryId'] : null,
            'collection_id' => !empty($body['collectionId']) ? (int) $body['collectionId'] : null,
            'brand_id' => !empty($body['brandId']) ? (int) $body['brandId'] : null,
            'brand' => $body['brand'] ?? null,
            'vendor_id' => !empty($body['vendorId']) ? (int) $body['vendorId'] : null,
            'supplier_id' => !empty($body['supplierId']) ? (int) $body['supplierId'] : null,
            'supplier' => $body['supplier'] ?? null,
            'country_of_origin' => $body['countryOfOrigin'] ?? null,
            'sku' => $body['sku'] ?? null,
            'barcode' => $body['barcode'] ?? null,
            'tags' => $this->parseJson($body['tags'] ?? []),
            'warranty' => $body['warranty'] ?? null,
            'weight' => $body['weight'] ?? null,
            'dimensions' => $body['dimensions'] ?? null,
            'features' => $this->parseJson($body['features'] ?? []),
            'return_policy' => $body['returnPolicy'] ?? null,
            'warehouse' => $body['warehouse'] ?? null,
            'video_url' => $body['videoUrl'] ?? null,
            'seo_title' => $body['seoTitle'] ?? null,
            'seo_description' => $body['seoDescription'] ?? null,
            'seo_keywords' => $body['seoKeywords'] ?? null,
            'canonical_url' => $body['canonicalUrl'] ?? null,
            'og_image' => $body['ogImage'] ?? null,
            'twitter_image' => $body['twitterImage'] ?? null,
            'structured_data' => $this->parseJson($body['structuredData'] ?? null),
            'emi_available' => filter_var($body['emiAvailable'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_featured' => filter_var($body['isFeatured'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_trending' => filter_var($body['isTrending'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_flash_sale' => filter_var($body['isFlashSale'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_new_arrival' => filter_var($body['isNewArrival'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_best_seller' => filter_var($body['isBestSeller'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_limited_edition' => filter_var($body['isLimitedEdition'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_official' => filter_var($body['isOfficial'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_hot_deal' => filter_var($body['isHotDeal'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_archived' => filter_var($body['isArchived'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'stock' => isset($body['stock']) ? (int) $body['stock'] : 0,
            'low_stock_alert' => isset($body['lowStockAlert']) ? (int) $body['lowStockAlert'] : 10,
            'min_order' => isset($body['minOrder']) ? (int) $body['minOrder'] : 1,
            'max_order' => isset($body['maxOrder']) && $body['maxOrder'] !== '' ? (int) $body['maxOrder'] : null,
            'unlimited_stock' => filter_var($body['unlimitedStock'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'backorder' => filter_var($body['backorder'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'track_inventory' => filter_var($body['trackInventory'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'stock_status' => $body['stockStatus'] ?? 'in_stock',
            'product_status' => $body['productStatus'] ?? 'published',
            'images' => $images,
            'size_options' => $this->parseJson($body['sizeOptions'] ?? []),
            'color_options' => $this->parseJson($body['colorOptions'] ?? []),
            'payment_methods' => $this->parseJson($body['paymentMethods'] ?? ['cod']),
            'payment_phone_number' => $body['paymentPhoneNumber'] ?? null,
            'status' => $body['status'] ?? 'active',
            'variants' => $this->parseJson($body['variants'] ?? []),
            'specs' => $this->parseJson($body['specs'] ?? []),
            'relations' => $this->parseJson($body['relations'] ?? []),
        ];

        $product = ProductService::create($data);

        return response()->json(['success' => true, 'data' => $product], 201);
    }

    public function update(Request $request, $id)
    {
        $existing = Product::find($id);
        if (!$existing) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $body = $request->all();

        $uploadedImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $upload = MediaStorageService::uploadFile($file, 'products');
                $uploadedImages[] = $upload['url'];
            }
        }

        $updateData = [];

        if (isset($body['title'])) {
            $updateData['title'] = $body['title'];
            if (!$request->filled('slug')) {
                $updateData['slug'] = ProductService::ensureUniqueSlug(SlugService::toAsciiSlug($body['title']), [
                    'excludeId' => (int) $id,
                    'autoSuffix' => true,
                ]);
            }
        }

        if ($request->filled('slug')) {
            $updateData['slug'] = ProductService::ensureUniqueSlug(trim(strtolower($request->input('slug'))), [
                'excludeId' => (int) $id,
                'autoSuffix' => false,
            ]);
        }

        // Add remaining fields
        foreach ([
            'description', 'shortDescription' => 'short_description', 'price', 'salePrice' => 'sale_price',
            'discount', 'costPrice' => 'cost_price', 'tax', 'vat', 'shippingCharge' => 'shipping_charge',
            'codFee' => 'cod_fee', 'flashSalePrice' => 'flash_sale_price', 'wholesalePrice' => 'wholesale_price',
            'dealerPrice' => 'dealer_price', 'categoryId' => 'category_id', 'subCategoryId' => 'sub_category_id',
            'childCategoryId' => 'child_category_id', 'collectionId' => 'collection_id', 'brandId' => 'brand_id',
            'brand', 'vendorId' => 'vendor_id', 'supplierId' => 'supplier_id', 'supplier', 'countryOfOrigin' => 'country_of_origin',
            'sku', 'barcode', 'warranty', 'weight', 'dimensions', 'returnPolicy' => 'return_policy',
            'warehouse', 'videoUrl' => 'video_url', 'seoTitle' => 'seo_title', 'seoDescription' => 'seo_description',
            'seoKeywords' => 'seo_keywords', 'canonicalUrl' => 'canonical_url', 'ogImage' => 'og_image',
            'twitterImage' => 'twitter_image', 'stock', 'lowStockAlert' => 'low_stock_alert', 'minOrder' => 'min_order',
            'maxOrder' => 'max_order', 'stockStatus' => 'stock_status', 'productStatus' => 'product_status',
            'paymentPhoneNumber' => 'payment_phone_number', 'status'
        ] as $k => $dbCol) {
            $inputKey = is_string($k) ? $k : $dbCol;
            if ($request->has($inputKey)) {
                $updateData[$dbCol] = $request->input($inputKey);
            }
        }

        // Booleans
        foreach (['emiAvailable', 'isFeatured', 'isTrending', 'isFlashSale', 'isNewArrival', 'isBestSeller', 'isLimitedEdition', 'isOfficial', 'isHotDeal', 'isArchived', 'unlimitedStock', 'backorder', 'trackInventory'] as $flag) {
            if ($request->has($flag)) {
                $updateData[\Illuminate\Support\Str::snake($flag)] = filter_var($request->input($flag), FILTER_VALIDATE_BOOLEAN);
            }
        }

        // Arrays / JSON
        foreach (['tags', 'features', 'sizeOptions' => 'size_options', 'colorOptions' => 'color_options', 'paymentMethods' => 'payment_methods', 'structuredData' => 'structured_data'] as $k => $dbCol) {
            $inputKey = is_string($k) ? $k : $dbCol;
            if ($request->has($inputKey)) {
                $updateData[$dbCol] = $this->parseJson($request->input($inputKey));
            }
        }

        if ($request->has('images') || count($uploadedImages) > 0) {
            $bodyImages = $this->parseJson($request->input('images'));
            $existing = $this->parseJson($request->input('existingImages'));
            $combined = count($existing) ? array_merge($existing, $bodyImages) : $bodyImages;
            $updateData['images'] = array_merge($combined, $uploadedImages);
        }

        if ($request->has('variants')) {
            $updateData['variants'] = $this->parseJson($request->input('variants'));
        }
        if ($request->has('specs')) {
            $updateData['specs'] = $this->parseJson($request->input('specs'));
        }
        if ($request->has('relations')) {
            $updateData['relations'] = $this->parseJson($request->input('relations'));
        }

        $product = ProductService::update((int) $id, $updateData);

        return response()->json(['success' => true, 'data' => $product]);
    }

    public function remove($id)
    {
        $existing = Product::find($id);
        if (!$existing) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        ProductService::remove((int) $id);
        return response()->json(['success' => true, 'message' => 'Product deleted']);
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'action' => 'required|string|in:delete,publish,activate,draft,deactivate',
        ]);

        $result = ProductService::bulkAction($validated['ids'], $validated['action']);
        return response()->json(array_merge(['success' => true, 'message' => "Bulk {$validated['action']} complete"], $result));
    }

    public function duplicate($id)
    {
        $product = ProductService::duplicate((int) $id);
        return response()->json(['success' => true, 'data' => $product], 201);
    }

    public function saveDraft(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update(['draft' => $request->input('draft', [])]);
        return response()->json(['success' => true, 'message' => 'Draft saved']);
    }

    public function exportCsv(Request $request)
    {
        $products = Product::all();
        $csv = "id,title,slug,price,stock,status\n";
        foreach ($products as $p) {
            $csv .= "{$p->id},\"{$p->title}\",{$p->slug},{$p->price},{$p->stock},{$p->status}\n";
        }
        return response()->json(['success' => true, 'csv' => $csv]);
    }

    public function search(Request $request)
    {
        $q = $request->query('q') ?? $request->query('search');
        $params = $request->all();
        $params['search'] = $q;
        $result = ProductService::getAll($params);
        return response()->json(array_merge(['success' => true], $result));
    }

    public function getByCategory($categoryId, Request $request)
    {
        $params = $request->all();
        $params['category'] = $categoryId;
        $result = ProductService::getAll($params);
        return response()->json(array_merge(['success' => true], $result));
    }

    public function getByCollection($collectionId, Request $request)
    {
        $params = $request->all();
        $params['collection'] = $collectionId;
        $result = ProductService::getAll($params);
        return response()->json(array_merge(['success' => true], $result));
    }

    public function getByBrand($brandId, Request $request)
    {
        $params = $request->all();
        $params['brand'] = $brandId;
        $result = ProductService::getAll($params);
        return response()->json(array_merge(['success' => true], $result));
    }

    public function getByVendor($vendorId, Request $request)
    {
        $params = $request->all();
        $params['vendor'] = $vendorId;
        $result = ProductService::getAll($params);
        return response()->json(array_merge(['success' => true], $result));
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        $result = ProductService::bulkAction($ids, 'delete');
        return response()->json(array_merge(['success' => true, 'message' => 'Bulk delete complete'], $result));
    }

    public function bulkUpdateStatus(Request $request)
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status', 'active');
        $action = $status === 'active' ? 'activate' : ($status === 'draft' ? 'draft' : 'deactivate');
        $result = ProductService::bulkAction($ids, $action);
        return response()->json(array_merge(['success' => true, 'message' => 'Bulk update status complete'], $result));
    }

    public function importCsv(Request $request)
    {
        return response()->json(['success' => true, 'imported' => 0]);
    }
}
