<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductSpec;
use App\Models\ProductRelation;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Vendor;
use App\Models\Supplier;
use App\Models\Collection;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductService
{
    /** Relations needed for a full product detail / API payload (avoids N+1). */
    public const DETAIL_RELATIONS = [
        'brandRel',
        'category',
        'subCategory',
        'childCategory',
        'collection',
        'vendor',
        'supplierRel',
        'variants',
        'specs',
        'productRelations',
    ];

    public static function ensureUniqueSlug(string $slug, array $opts = []): string
    {
        $excludeId = $opts['excludeId'] ?? null;
        $autoSuffix = $opts['autoSuffix'] ?? false;

        $candidate = $slug;
        $suffix = 2;

        while (true) {
            $query = Product::where('slug', $candidate);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                return $candidate;
            }

            if (!$autoSuffix) {
                throw new Exception("Slug \"{$slug}\" is already in use by another product. Please choose a different slug.", 409);
            }

            $candidate = "{$slug}-{$suffix}";
            $suffix++;
            if ($suffix > 1000) return $candidate;
        }
    }

    public static function formatProduct(Product $product, ?array $ratingInfo = null, bool $withChildren = false): array
    {
        $brandInfo = null;
        if ($product->brand_id && $product->brandRel) {
            $brandInfo = [
                'id' => $product->brand_id,
                'name' => $product->brandRel->name,
                'logo' => $product->brandRel->logo,
                'slug' => $product->brandRel->slug,
            ];
        }

        $category = null;
        if ($product->category) {
            $category = [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ];
        }

        $subCategory = null;
        if ($product->subCategory) {
            $subCategory = [
                'id' => $product->subCategory->id,
                'name' => $product->subCategory->name,
                'slug' => $product->subCategory->slug,
            ];
        }

        $childCategory = null;
        if ($product->childCategory) {
            $childCategory = [
                'id' => $product->childCategory->id,
                'name' => $product->childCategory->name,
                'slug' => $product->childCategory->slug,
            ];
        }

        $collection = null;
        if ($product->collection) {
            $collection = [
                'id' => $product->collection->id,
                'name' => $product->collection->name,
                'slug' => $product->collection->slug,
            ];
        }

        $vendor = null;
        if ($product->vendor) {
            $vendor = [
                'id' => $product->vendor->id,
                'name' => $product->vendor->name,
                'slug' => $product->vendor->slug,
            ];
        }

        $supplierInfo = null;
        if ($product->supplierRel) {
            $supplierInfo = [
                'id' => $product->supplierRel->id,
                'name' => $product->supplierRel->name,
                'slug' => $product->supplierRel->slug,
            ];
        }

        $formatted = [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'description' => $product->description,
            'shortDescription' => $product->short_description,
            'price' => (string) $product->price,
            'salePrice' => $product->sale_price !== null ? (string) $product->sale_price : null,
            'discount' => (string) $product->discount,
            'costPrice' => (string) $product->cost_price,
            'profitMargin' => (string) $product->profit_margin,
            'tax' => (string) $product->tax,
            'vat' => (string) $product->vat,
            'shippingCharge' => (string) $product->shipping_charge,
            'codFee' => (string) $product->cod_fee,
            'flashSalePrice' => $product->flash_sale_price !== null ? (string) $product->flash_sale_price : null,
            'wholesalePrice' => $product->wholesale_price !== null ? (string) $product->wholesale_price : null,
            'dealerPrice' => $product->dealer_price !== null ? (string) $product->dealer_price : null,
            'categoryId' => $product->category_id,
            'subCategoryId' => $product->sub_category_id,
            'childCategoryId' => $product->child_category_id,
            'collectionId' => $product->collection_id,
            'brandId' => $product->brand_id,
            'brand' => $product->brand,
            'brandInfo' => $brandInfo,
            'category' => $category,
            'subCategory' => $subCategory,
            'childCategory' => $childCategory,
            'collection' => $collection,
            'vendorId' => $product->vendor_id,
            'vendor' => $vendor,
            'supplierId' => $product->supplier_id,
            'supplier' => $product->supplier,
            'supplierInfo' => $supplierInfo,
            'countryOfOrigin' => $product->country_of_origin,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'tags' => $product->tags ?: [],
            'warranty' => $product->warranty,
            'weight' => $product->weight,
            'dimensions' => $product->dimensions,
            'features' => $product->features ?: [],
            'returnPolicy' => $product->return_policy,
            'warehouse' => $product->warehouse,
            'videoUrl' => $product->video_url,
            'seoTitle' => $product->seo_title,
            'seoDescription' => $product->seo_description,
            'seoKeywords' => $product->seo_keywords,
            'canonicalUrl' => $product->canonical_url,
            'ogImage' => $product->og_image,
            'twitterImage' => $product->twitter_image,
            'structuredData' => $product->structured_data,
            'emiAvailable' => (bool) $product->emi_available,
            'isFeatured' => (bool) $product->is_featured,
            'isTrending' => (bool) $product->is_trending,
            'isFlashSale' => (bool) $product->is_flash_sale,
            'isNewArrival' => (bool) $product->is_new_arrival,
            'isBestSeller' => (bool) $product->is_best_seller,
            'isLimitedEdition' => (bool) $product->is_limited_edition,
            'isOfficial' => (bool) $product->is_official,
            'isHotDeal' => (bool) $product->is_hot_deal,
            'isArchived' => (bool) $product->is_archived,
            'stock' => (int) $product->stock,
            'lowStockAlert' => (int) $product->low_stock_alert,
            'minOrder' => (int) $product->min_order,
            'maxOrder' => $product->max_order !== null ? (int) $product->max_order : null,
            'unlimitedStock' => (bool) $product->unlimited_stock,
            'backorder' => (bool) $product->backorder,
            'trackInventory' => (bool) $product->track_inventory,
            'stockStatus' => $product->stock_status,
            'productStatus' => $product->product_status,
            'status' => $product->status,
            'images' => $product->images ?: [],
            'sizeOptions' => $product->size_options ?: [],
            'colorOptions' => $product->color_options ?: [],
            'paymentMethods' => $product->payment_methods ?: ['cod'],
            'paymentPhoneNumber' => $product->payment_phone_number,
            'createdAt' => $product->created_at ? $product->created_at->toIso8601String() : null,
            'rating' => $ratingInfo ? $ratingInfo['rating'] : null,
            'reviewCount' => $ratingInfo ? $ratingInfo['reviewCount'] : 0,
        ];

        if ($withChildren || $product->relationLoaded('variants')) {
            $formatted['variants'] = $product->variants->map(fn($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'options' => $v->options ?: [],
                'price' => $v->price !== null ? (string) $v->price : null,
                'salePrice' => $v->discount_price !== null ? (string) $v->discount_price : ($v->price !== null ? (string) $v->price : null),
                'discountPrice' => $v->discount_price !== null ? (string) $v->discount_price : null,
                'sku' => $v->sku,
                'barcode' => $v->barcode,
                'stock' => (int) $v->stock,
                'weight' => $v->weight,
                'dimensions' => $v->dimensions,
                'images' => $v->images ?: [],
                'thumbnail' => $v->thumbnail,
                'status' => $v->status,
                'shippingCost' => $v->shipping_cost !== null ? (string) $v->shipping_cost : null,
                'warranty' => $v->warranty,
                'availability' => (bool) $v->availability,
            ])->toArray();

            // Generic option axis (Storage/Color/RAM/Size/Strap/…) derived from variant.options
            $formatted['optionGroups'] = self::deriveOptionGroups(
                $formatted['variants'],
                $formatted['colorOptions'] ?? [],
                $formatted['sizeOptions'] ?? []
            );
        }

        if ($withChildren || $product->relationLoaded('specs')) {
            $formatted['specs'] = $product->specs->map(fn($s) => [
                'id' => $s->id,
                'label' => $s->label,
                'value' => $s->value,
                'sortOrder' => (int) $s->sort_order,
            ])->toArray();
        }

        if ($withChildren || $product->relationLoaded('productRelations')) {
            $relatedIds = $product->productRelations->pluck('related_product_id')->filter()->unique()->values()->all();
            $relatedProducts = $relatedIds
                ? Product::whereIn('id', $relatedIds)->get()->keyBy('id')
                : collect();
            $formatted['relations'] = $product->productRelations->map(fn($r) => [
                'id' => $r->id,
                'type' => $r->type,
                'relatedProductId' => $r->related_product_id,
                'relatedProduct' => ($p = $relatedProducts->get($r->related_product_id)) ? [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                    'price' => (string) $p->price,
                    'discount' => (string) $p->discount,
                    'images' => $p->images ?: [],
                ] : null,
            ])->toArray();
        }

        return $formatted;
    }

    /**
     * Build dynamic option groups from variant.options keys (not hardcoded to color/size).
     * Falls back to product-level colorOptions / sizeOptions when variants lack those axes.
     *
     * @param  array<int, array<string, mixed>>  $variants
     * @param  array<int, mixed>  $colorOptions
     * @param  array<int, mixed>  $sizeOptions
     * @return array<int, array{key: string, label: string, type: string, values: array<int, array{name: string, value?: string|null, image?: string|null}>}>
     */
    public static function deriveOptionGroups(array $variants, array $colorOptions = [], array $sizeOptions = []): array
    {
        $active = array_values(array_filter(
            $variants,
            fn($v) => ($v['status'] ?? 'active') !== 'inactive' && ($v['availability'] ?? true) !== false
        ));

        $keyOrder = [];
        $valuesByKey = [];

        foreach ($active as $variant) {
            $opts = $variant['options'] ?? [];
            if (!is_array($opts)) {
                continue;
            }
            foreach ($opts as $key => $value) {
                $key = (string) $key;
                $value = trim((string) $value);
                if ($key === '' || $value === '') {
                    continue;
                }
                if (!isset($valuesByKey[$key])) {
                    $keyOrder[] = $key;
                    $valuesByKey[$key] = [];
                }
                if (!isset($valuesByKey[$key][$value])) {
                    $valuesByKey[$key][$value] = true;
                }
            }
        }

        // Fallback axes from product-level fields when variants don't declare them
        if (empty($valuesByKey['Color']) && empty($valuesByKey['color']) && !empty($colorOptions)) {
            $keyOrder[] = 'Color';
            $valuesByKey['Color'] = [];
            foreach ($colorOptions as $c) {
                $name = is_array($c) ? trim((string) ($c['name'] ?? '')) : trim((string) $c);
                if ($name !== '') {
                    $valuesByKey['Color'][$name] = true;
                }
            }
        }
        if (empty($valuesByKey['Size']) && empty($valuesByKey['size']) && !empty($sizeOptions)) {
            $keyOrder[] = 'Size';
            $valuesByKey['Size'] = [];
            foreach ($sizeOptions as $s) {
                $name = is_array($s) ? trim((string) ($s['name'] ?? '')) : trim((string) $s);
                if ($name !== '') {
                    $valuesByKey['Size'][$name] = true;
                }
            }
        }

        if (empty($keyOrder)) {
            return [];
        }

        $colorMeta = [];
        foreach ($colorOptions as $c) {
            if (!is_array($c) || empty($c['name'])) {
                continue;
            }
            $colorMeta[mb_strtolower((string) $c['name'])] = [
                'value' => $c['value'] ?? $c['hex'] ?? null,
                'image' => $c['image'] ?? null,
            ];
        }

        // Enrich Color-like groups with hex from catalog when available
        $catalogHex = [];
        try {
            if (class_exists(\App\Models\Color::class)) {
                foreach (\App\Models\Color::query()->get(['name', 'hex', 'slug']) as $row) {
                    if (!empty($row->hex)) {
                        $catalogHex[mb_strtolower((string) $row->name)] = $row->hex;
                        if (!empty($row->slug)) {
                            $catalogHex[mb_strtolower((string) $row->slug)] = $row->hex;
                            $catalogHex[str_replace('-', ' ', mb_strtolower((string) $row->slug))] = $row->hex;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // colors table may be absent in some environments
        }

        $groups = [];
        foreach ($keyOrder as $key) {
            $names = array_keys($valuesByKey[$key] ?? []);
            if (empty($names)) {
                continue;
            }
            $isColor = strcasecmp($key, 'Color') === 0 || strcasecmp($key, 'Colour') === 0;
            $type = $isColor ? 'color' : (strcasecmp($key, 'Size') === 0 ? 'size' : 'text');

            $values = [];
            foreach ($names as $name) {
                $entry = ['name' => $name];
                if ($isColor) {
                    $lk = mb_strtolower($name);
                    $meta = $colorMeta[$lk] ?? null;
                    $hex = $meta['value'] ?? $catalogHex[$lk] ?? $catalogHex[str_replace(' ', '-', $lk)] ?? null;
                    if ($hex) {
                        $entry['value'] = $hex;
                    }
                    if (!empty($meta['image'])) {
                        $entry['image'] = $meta['image'];
                    }
                }
                $values[] = $entry;
            }

            $groups[] = [
                'key' => $key,
                'label' => $key,
                'type' => $type,
                'values' => $values,
            ];
        }

        return $groups;
    }

    public static function fetchRatingMap(array $productIds): array
    {
        if (empty($productIds)) return [];

        $ratings = Review::select('product_id', DB::raw('AVG(rating) as avg_rating'), DB::raw('COUNT(*) as total_count'))
            ->whereIn('product_id', $productIds)
            ->where('status', 'approved')
            ->groupBy('product_id')
            ->get();

        $map = [];
        foreach ($ratings as $r) {
            $map[$r->product_id] = [
                'rating' => round((float) $r->avg_rating, 1),
                'reviewCount' => (int) $r->total_count,
            ];
        }
        return $map;
    }

    public static function getAll(array $query): array
    {
        $page = (int) ($query['page'] ?? 1);
        $limit = (int) ($query['limit'] ?? 12);
        $offset = ($page - 1) * $limit;

        $builder = Product::with(['brandRel', 'category', 'subCategory', 'childCategory', 'collection', 'vendor', 'supplierRel', 'variants']);

        // Status
        if (!empty($query['status']) && $query['status'] !== 'all') {
            $builder->where('status', $query['status']);
        } elseif (empty($query['status']) && empty($query['productStatus'])) {
            $builder->where('status', 'active');
        }

        if (!empty($query['productStatus']) && $query['productStatus'] !== 'all') {
            $builder->where('product_status', $query['productStatus']);
        }

        // Search (also accept legacy `q`)
        $searchTerm = $query['search'] ?? $query['q'] ?? null;
        if (!empty($searchTerm)) {
            $term = '%' . trim((string) $searchTerm) . '%';
            $builder->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('sku', 'like', $term)
                  ->orWhere('barcode', 'like', $term)
                  ->orWhere('brand', 'like', $term)
                  ->orWhere('tags', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhere('short_description', 'like', $term)
                  ->orWhereHas('brandRel', fn ($bq) => $bq->where('name', 'like', $term)->orWhere('slug', 'like', $term))
                  ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', $term)->orWhere('slug', 'like', $term))
                  ->orWhereHas('subCategory', fn ($cq) => $cq->where('name', 'like', $term)->orWhere('slug', 'like', $term));
            });
        }

        if (!empty($query['sku'])) $builder->where('sku', 'like', "%{$query['sku']}%");
        if (!empty($query['barcode'])) $builder->where('barcode', 'like', "%{$query['barcode']}%");

        // Category resolution (slug or numeric id) — includes children when parent selected
        if (!empty($query['category']) && $query['category'] !== 'all') {
            if (is_numeric($query['category'])) {
                $catId = (int) $query['category'];
                $childIds = Category::where('parent_id', $catId)->pluck('id')->all();
                $ids = array_values(array_unique(array_merge([$catId], $childIds)));
                $builder->where(function ($q) use ($ids) {
                    $q->whereIn('category_id', $ids)
                      ->orWhereIn('sub_category_id', $ids)
                      ->orWhereIn('child_category_id', $ids);
                });
            } else {
                $cat = Category::where('slug', $query['category'])->first();
                if ($cat) {
                    $childIds = Category::where('parent_id', $cat->id)->pluck('id')->all();
                    $ids = array_values(array_unique(array_merge([$cat->id], $childIds)));
                    $builder->where(function ($q) use ($ids) {
                        $q->whereIn('category_id', $ids)
                          ->orWhereIn('sub_category_id', $ids)
                          ->orWhereIn('child_category_id', $ids);
                    });
                }
            }
        }

        // Explicit subcategory filter
        if (!empty($query['subcategory']) && $query['subcategory'] !== 'all') {
            if (is_numeric($query['subcategory'])) {
                $subId = (int) $query['subcategory'];
                $builder->where(function ($q) use ($subId) {
                    $q->where('sub_category_id', $subId)
                      ->orWhere('child_category_id', $subId)
                      ->orWhere('category_id', $subId);
                });
            } else {
                $sub = Category::where('slug', $query['subcategory'])->first();
                if ($sub) {
                    $builder->where(function ($q) use ($sub) {
                        $q->where('sub_category_id', $sub->id)
                          ->orWhere('child_category_id', $sub->id)
                          ->orWhere('category_id', $sub->id);
                    });
                }
            }
        }

        // Brand resolution (slug or numeric id)
        if (!empty($query['brand']) && $query['brand'] !== 'all') {
            if (is_numeric($query['brand'])) {
                $builder->where('brand_id', (int) $query['brand']);
            } else {
                $brand = Brand::where('slug', $query['brand'])->first();
                if ($brand) {
                    $builder->where('brand_id', $brand->id);
                } else {
                    $builder->where('brand', $query['brand']);
                }
            }
        }

        // Supplier resolution (slug or numeric id)
        if (!empty($query['supplier']) && $query['supplier'] !== 'all') {
            if (is_numeric($query['supplier'])) {
                $builder->where('supplier_id', (int) $query['supplier']);
            } else {
                $supplier = Supplier::where('slug', $query['supplier'])->first();
                if ($supplier) $builder->where('supplier_id', $supplier->id);
            }
        }

        // Vendor resolution (slug or numeric id)
        if (!empty($query['vendor']) && $query['vendor'] !== 'all') {
            if (is_numeric($query['vendor'])) {
                $builder->where('vendor_id', (int) $query['vendor']);
            } else {
                $vendor = Vendor::where('slug', $query['vendor'])->first();
                if ($vendor) $builder->where('vendor_id', $vendor->id);
            }
        }

        // Collection resolution (slug or numeric id)
        if (!empty($query['collection']) && $query['collection'] !== 'all') {
            if (is_numeric($query['collection'])) {
                $builder->where('collection_id', (int) $query['collection']);
            } else {
                $col = Collection::where('slug', $query['collection'])->first();
                if ($col) $builder->where('collection_id', $col->id);
            }
        }

        // Stock filter
        if (!empty($query['stock']) && $query['stock'] !== 'all') {
            if ($query['stock'] === 'in_stock') {
                $builder->where(fn($q) => $q->where('stock', '>', 0)->orWhere('unlimited_stock', true));
            } elseif ($query['stock'] === 'low_stock') {
                $builder->where('stock', '>', 0)->whereRaw('stock <= COALESCE(low_stock_alert, 5)');
            } elseif ($query['stock'] === 'out_of_stock') {
                $builder->where('stock', '<=', 0);
            } elseif ($query['stock'] === 'on_backorder') {
                $builder->where('backorder', true);
            }
        }

        // Min/Max price
        if (isset($query['minPrice']) && $query['minPrice'] !== '') $builder->where('price', '>=', (float) $query['minPrice']);
        if (isset($query['maxPrice']) && $query['maxPrice'] !== '') $builder->where('price', '<=', (float) $query['maxPrice']);

        // Date ranges
        if (!empty($query['dateFrom'])) $builder->where('created_at', '>=', $query['dateFrom']);
        if (!empty($query['dateTo'])) $builder->where('created_at', '<=', $query['dateTo']);

        // In Stock boolean (React uses stock=1)
        if ((!empty($query['inStock']) && ($query['inStock'] === 'true' || $query['inStock'] === '1' || $query['inStock'] === true))
            || (isset($query['stock']) && $query['stock'] === '1')) {
            $builder->where(fn($q) => $q->where('stock', '>', 0)->orWhere('unlimited_stock', true));
        }

        // On sale (React sale=1)
        if ((!empty($query['sale']) && ($query['sale'] === '1' || $query['sale'] === 'true' || $query['sale'] === true))
            || (!empty($query['onSale']) && $query['onSale'])) {
            $builder->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNotNull('sale_price')->where('sale_price', '>', 0)->whereColumn('sale_price', '<', 'price');
                })->orWhere('discount', '>', 0)
                  ->orWhere('is_flash_sale', true)
                  ->orWhere('is_hot_deal', true);
            });
        }

        // Color / size attribute filters (JSON columns when populated)
        if (!empty($query['color'])) {
            $color = trim((string) $query['color']);
            $builder->where(function ($q) use ($color) {
                $q->where('color_options', 'like', '%' . $color . '%');
            });
        }
        if (!empty($query['size'])) {
            $size = trim((string) $query['size']);
            $builder->where(function ($q) use ($size) {
                $q->where('size_options', 'like', '%' . $size . '%');
            });
        }

        // Minimum rating (only meaningful when reviews exist)
        if (!empty($query['rating']) || !empty($query['minRating'])) {
            $minRating = (float) ($query['rating'] ?? $query['minRating']);
            if ($minRating > 0) {
                $builder->whereIn('id', function ($sub) use ($minRating) {
                    $sub->select('product_id')
                        ->from('reviews')
                        ->where('status', 'approved')
                        ->groupBy('product_id')
                        ->havingRaw('AVG(rating) >= ?', [$minRating]);
                });
            }
        }

        // Labels
        if (!empty($query['label']) && $query['label'] !== 'all') {
            $labelField = match ($query['label']) {
                'featured' => 'is_featured',
                'trending' => 'is_trending',
                'flash_sale' => 'is_flash_sale',
                'new_arrival' => 'is_new_arrival',
                'best_seller' => 'is_best_seller',
                'limited_edition' => 'is_limited_edition',
                'official' => 'is_official',
                'hot_deal' => 'is_hot_deal',
                default => null,
            };
            if ($labelField) {
                $builder->where($labelField, true);
            }
        }

        // Sorting
        $sort = $query['sort'] ?? null;
        match ($sort) {
            'oldest' => $builder->orderBy('created_at', 'asc'),
            'price_asc' => $builder->orderBy('price', 'asc'),
            'price_desc' => $builder->orderBy('price', 'desc'),
            'stock_asc' => $builder->orderBy('stock', 'asc'),
            'stock_desc' => $builder->orderBy('stock', 'desc'),
            'title_asc' => $builder->orderBy('title', 'asc'),
            'title_desc' => $builder->orderBy('title', 'desc'),
            'discount' => $builder->orderBy('discount', 'desc'),
            'popular', 'rating_desc' => $builder->orderBy('is_best_seller', 'desc')->orderBy('is_trending', 'desc')->orderBy('created_at', 'desc'),
            default => $builder->orderBy('created_at', 'desc'),
        };

        $total = $builder->count();
        $products = $builder->skip($offset)->take($limit)->get();

        $ratingMap = self::fetchRatingMap($products->pluck('id')->toArray());

        $formatted = $products->map(fn($p) => self::formatProduct($p, $ratingMap[$p->id] ?? null, false))->toArray();

        return [
            'data' => $formatted,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / max(1, $limit)),
            ],
            'total' => $total,
            'totalPages' => (int) ceil($total / max(1, $limit)),
            'page' => $page,
        ];
    }

    public static function getById(int $id): ?array
    {
        $product = Product::with(self::DETAIL_RELATIONS)->find($id);
        if (!$product) {
            return null;
        }

        $ratingMap = self::fetchRatingMap([$id]);
        return self::formatProduct($product, $ratingMap[$id] ?? null, true);
    }

    public static function getBySlug(string $slug): ?array
    {
        $product = Product::with(self::DETAIL_RELATIONS)->where('slug', $slug)->first();
        if (!$product) {
            return null;
        }

        $ratingMap = self::fetchRatingMap([$product->id]);
        return self::formatProduct($product, $ratingMap[$product->id] ?? null, true);
    }

    public static function getRelated(int $categoryId, int $excludeId, int $limit = 8): array
    {
        $products = Product::with(['brandRel', 'variants'])
            ->where('category_id', $categoryId)
            ->where('id', '!=', $excludeId)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        $ratingMap = self::fetchRatingMap($products->pluck('id')->toArray());

        return $products->map(fn($p) => self::formatProduct($p, $ratingMap[$p->id] ?? null, false))->toArray();
    }

    /**
     * Upsert variants by id (preserve cart/checkout variantId) and delete removed ones.
     *
     * @param  array<int, array<string, mixed>>  $variants
     */
    public static function syncVariants(int $productId, array $variants): void
    {
        $existingIds = ProductVariant::where('product_id', $productId)->pluck('id')->map(fn($id) => (int) $id)->all();
        $existingSet = array_flip($existingIds);
        $keptIds = [];

        foreach ($variants as $v) {
            if (!is_array($v) || empty($v['name'])) {
                continue;
            }

            $options = $v['options'] ?? [];
            if (is_string($options)) {
                $options = json_decode($options, true) ?: [];
            }
            if (!is_array($options)) {
                $options = [];
            }

            $payload = [
                'product_id' => $productId,
                'name' => $v['name'],
                'options' => $options,
                'price' => $v['price'] ?? null,
                'discount_price' => $v['discountPrice'] ?? $v['salePrice'] ?? null,
                'sku' => $v['sku'] ?? null,
                'barcode' => $v['barcode'] ?? null,
                'stock' => (int) ($v['stock'] ?? 0),
                'weight' => $v['weight'] ?? null,
                'dimensions' => $v['dimensions'] ?? null,
                'images' => $v['images'] ?? [],
                'thumbnail' => $v['thumbnail'] ?? null,
                'status' => $v['status'] ?? 'active',
                'shipping_cost' => $v['shippingCost'] ?? null,
                'warranty' => $v['warranty'] ?? null,
                'availability' => array_key_exists('availability', $v) ? (bool) $v['availability'] : true,
            ];

            $id = isset($v['id']) ? (int) $v['id'] : 0;
            if ($id > 0 && isset($existingSet[$id])) {
                ProductVariant::where('id', $id)->where('product_id', $productId)->update($payload);
                $keptIds[] = $id;
            } else {
                $created = ProductVariant::create($payload);
                $keptIds[] = (int) $created->id;
            }
        }

        $removed = array_diff($existingIds, $keptIds);
        if (!empty($removed)) {
            ProductVariant::where('product_id', $productId)->whereIn('id', $removed)->delete();
        }
    }

    public static function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $variants = $data['variants'] ?? [];
            $specs = $data['specs'] ?? [];
            $relations = $data['relations'] ?? [];
            unset($data['variants'], $data['specs'], $data['relations']);

            $product = Product::create($data);

            if (!empty($variants)) {
                self::syncVariants($product->id, $variants);
                $variantStock = collect($variants)->sum(fn($v) => (int) ($v['stock'] ?? 0));
                if (!isset($data['stock']) || (int) ($data['stock'] ?? 0) === 0) {
                    $product->update(['stock' => $variantStock]);
                }
            }

            if (!empty($specs)) {
                foreach ($specs as $index => $s) {
                    ProductSpec::create([
                        'product_id' => $product->id,
                        'label' => $s['label'],
                        'value' => $s['value'],
                        'sort_order' => $s['sortOrder'] ?? $index,
                    ]);
                }
            }

            if (!empty($relations)) {
                foreach ($relations as $r) {
                    ProductRelation::create([
                        'product_id' => $product->id,
                        'related_product_id' => $r['relatedProductId'],
                        'type' => $r['type'],
                    ]);
                }
            }

            return self::getById($product->id);
        });
    }

    public static function update(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $product = Product::findOrFail($id);

            $variants = $data['variants'] ?? null;
            $specs = $data['specs'] ?? null;
            $relations = $data['relations'] ?? null;
            unset($data['variants'], $data['specs'], $data['relations']);

            $product->update($data);

            if ($variants !== null) {
                self::syncVariants($id, $variants);
                if (!empty($variants)) {
                    $variantStock = collect($variants)->sum(fn($v) => (int) ($v['stock'] ?? 0));
                    $product->update(['stock' => $variantStock]);
                }
            }

            if ($specs !== null) {
                ProductSpec::where('product_id', $id)->delete();
                foreach ($specs as $index => $s) {
                    ProductSpec::create([
                        'product_id' => $product->id,
                        'label' => $s['label'],
                        'value' => $s['value'],
                        'sort_order' => $s['sortOrder'] ?? $index,
                    ]);
                }
            }

            if ($relations !== null) {
                ProductRelation::where('product_id', $id)->delete();
                foreach ($relations as $r) {
                    ProductRelation::create([
                        'product_id' => $product->id,
                        'related_product_id' => $r['relatedProductId'],
                        'type' => $r['type'],
                    ]);
                }
            }

            return self::getById($id);
        });
    }

    public static function remove(int $id): bool
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return true;
    }

    public static function bulkAction(array $ids, string $action): array
    {
        $count = 0;
        if ($action === 'delete') {
            $count = Product::whereIn('id', $ids)->delete();
        } elseif ($action === 'publish' || $action === 'activate') {
            $count = Product::whereIn('id', $ids)->update(['status' => 'active', 'product_status' => 'published']);
        } elseif ($action === 'draft' || $action === 'deactivate') {
            $count = Product::whereIn('id', $ids)->update(['status' => 'inactive', 'product_status' => 'draft']);
        } elseif ($action === 'hide') {
            $count = Product::whereIn('id', $ids)->update(['status' => 'inactive', 'product_status' => 'hidden']);
        } elseif ($action === 'archive') {
            $count = Product::whereIn('id', $ids)->update(['status' => 'inactive', 'product_status' => 'archived', 'is_archived' => true]);
        }

        return ['affectedCount' => $count, 'affected' => $count];
    }

    public static function toggleFeatured(int $id, ?bool $featured = null): bool
    {
        $product = Product::findOrFail($id);
        $product->is_featured = $featured !== null ? $featured : !$product->is_featured;
        $product->save();
        return (bool) $product->is_featured;
    }

    public static function exportCsv(array $query = []): string
    {
        $query['limit'] = 10000;
        $query['page'] = 1;
        $result = self::getAll($query);
        $products = $result['data'];

        $csvColumns = ['id', 'title', 'slug', 'sku', 'barcode', 'brand', 'category', 'price', 'salePrice', 'discount', 'costPrice', 'stock', 'stockStatus', 'productStatus', 'status', 'isFeatured', 'createdAt'];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $csvColumns);

        foreach ($products as $p) {
            $categoryName = $p['category']['name'] ?? '';
            $brandName = $p['brandInfo']['name'] ?? ($p['brand'] ?? '');
            fputcsv($output, [
                $p['id'],
                $p['title'],
                $p['slug'],
                $p['sku'] ?? '',
                $p['barcode'] ?? '',
                $brandName,
                $categoryName,
                $p['price'],
                $p['salePrice'] ?? '',
                $p['discount'] ?? '0',
                $p['costPrice'] ?? '0',
                $p['stock'] ?? 0,
                $p['stockStatus'] ?? '',
                $p['productStatus'] ?? '',
                $p['status'] ?? '',
                $p['isFeatured'] ? '1' : '0',
                $p['createdAt'] ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }

    public static function importCsv(string $csvContent): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csvContent));
        if (count($lines) < 2) {
            return ['imported' => 0];
        }

        $headers = str_getcsv(array_shift($lines));
        $headers = array_map(fn($h) => strtolower(trim(str_replace(['"', "'"], '', $h))), $headers);

        $imported = 0;
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $row = str_getcsv($line);
            if (count($row) < 2) continue;

            $data = [];
            foreach ($headers as $idx => $header) {
                $data[$header] = $row[$idx] ?? null;
            }

            $title = $data['title'] ?? $data['name'] ?? null;
            $price = $data['price'] ?? null;
            if (!$title || !$price) continue;

            $slug = SlugService::toAsciiSlug($title) . '-' . substr(uniqid(), -5);
            $slug = self::ensureUniqueSlug($slug, ['autoSuffix' => true]);

            Product::create([
                'title' => $title,
                'slug' => $slug,
                'price' => (float) $price,
                'sale_price' => !empty($data['saleprice']) ? (float)$data['saleprice'] : null,
                'discount' => !empty($data['discount']) ? (float)$data['discount'] : 0,
                'cost_price' => !empty($data['costprice']) ? (float)$data['costprice'] : 0,
                'sku' => !empty($data['sku']) ? $data['sku'] : null,
                'barcode' => !empty($data['barcode']) ? $data['barcode'] : null,
                'brand' => !empty($data['brand']) ? $data['brand'] : null,
                'stock' => !empty($data['stock']) ? (int)$data['stock'] : 0,
                'product_status' => $data['productstatus'] ?? 'published',
                'status' => ($data['productstatus'] ?? 'published') === 'published' ? 'active' : 'inactive',
                'stock_status' => (!empty($data['stock']) && (int)$data['stock'] > 0) ? 'in_stock' : 'out_of_stock',
            ]);
            $imported++;
        }

        return ['imported' => $imported];
    }

    public static function duplicate(int $id): array
    {
        $original = Product::with(['variants', 'specs', 'productRelations'])->findOrFail($id);

        $newTitle = "Copy of " . $original->title;
        $newSlug = self::ensureUniqueSlug(SlugService::toAsciiSlug($newTitle), ['autoSuffix' => true]);

        $productData = $original->toArray();
        unset($productData['id'], $productData['created_at']);
        $productData['title'] = $newTitle;
        $productData['slug'] = $newSlug;

        $newProduct = Product::create($productData);

        foreach ($original->variants as $v) {
            $vData = $v->toArray();
            unset($vData['id'], $vData['created_at']);
            $vData['product_id'] = $newProduct->id;
            ProductVariant::create($vData);
        }

        foreach ($original->specs as $s) {
            $sData = $s->toArray();
            unset($sData['id']);
            $sData['product_id'] = $newProduct->id;
            ProductSpec::create($sData);
        }

        return self::getById($newProduct->id);
    }
}
