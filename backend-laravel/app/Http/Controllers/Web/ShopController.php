<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\Review;
use App\Models\Size;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) ($request->input('search', $request->input('q', ''))));
        $categorySlug = $request->input('category', '');
        $subcategorySlug = $request->input('subcategory', '');
        $brandSlug = $request->input('brand', '');
        $sort = $request->input('sort', 'newest');
        $minPrice = $request->filled('minPrice') ? (float) $request->input('minPrice') : ($request->filled('min_price') ? (float) $request->input('min_price') : null);
        $maxPrice = $request->filled('maxPrice') ? (float) $request->input('maxPrice') : ($request->filled('max_price') ? (float) $request->input('max_price') : null);
        $availability = $request->input('availability', $request->input('stock', ''));
        $onSale = $request->boolean('sale') || $request->input('sale') === '1';
        $rating = $request->filled('rating') ? (float) $request->input('rating') : null;
        $color = $request->input('color', '');
        $size = $request->input('size', '');
        $view = in_array($request->input('view'), ['grid', 'list'], true) ? $request->input('view') : 'grid';
        $page = max(1, (int) $request->input('page', 1));
        $limit = 12;

        // Normalize React-style stock=1 → availability
        if ($availability === '1') {
            $availability = 'in_stock';
        }

        $params = array_filter([
            'page' => $page,
            'limit' => $limit,
            'search' => $search !== '' ? $search : null,
            'category' => $categorySlug !== '' ? $categorySlug : null,
            'subcategory' => $subcategorySlug !== '' ? $subcategorySlug : null,
            'brand' => $brandSlug !== '' ? $brandSlug : null,
            'sort' => $sort,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'stock' => in_array($availability, ['in_stock', 'low_stock', 'out_of_stock'], true) ? $availability : null,
            'sale' => $onSale ? '1' : null,
            'rating' => $rating,
            'color' => $color !== '' ? $color : null,
            'size' => $size !== '' ? $size : null,
            'productStatus' => 'published',
            'status' => 'active',
        ], fn ($v) => $v !== null && $v !== '');

        $result = ProductService::getAll($params);

        $ids = collect($result['data'])->pluck('id')->all();
        $productsById = Product::with(['brandRel'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
        $products = collect($ids)->map(fn ($id) => $productsById->get($id))->filter()->values();

        // Attach ratings from formatted payload
        $ratings = collect($result['data'])->keyBy('id');
        foreach ($products as $product) {
            $meta = $ratings->get($product->id);
            $product->shop_rating = $meta['rating'] ?? null;
            $product->shop_review_count = $meta['reviewCount'] ?? 0;
        }

        $selectedCategory = $categorySlug
            ? Category::where('slug', $categorySlug)->first()
            : null;
        $selectedSubcategory = $subcategorySlug
            ? Category::where('slug', $subcategorySlug)->first()
            : null;
        $selectedBrand = $brandSlug
            ? Brand::where('slug', $brandSlug)->first()
            : null;

        $categories = Category::with(['children' => fn ($q) => $q->where('status', 'active')->orderBy('name')])
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Product counts per category (parent includes children)
        $categoryCounts = Product::query()
            ->where('status', 'active')
            ->where('product_status', 'published')
            ->selectRaw('category_id, COUNT(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');
        $subCounts = Product::query()
            ->where('status', 'active')
            ->where('product_status', 'published')
            ->selectRaw('sub_category_id, COUNT(*) as aggregate')
            ->groupBy('sub_category_id')
            ->pluck('aggregate', 'sub_category_id');

        foreach ($categories as $cat) {
            $childSum = $cat->children->sum(fn ($c) => (int) ($subCounts[$c->id] ?? $categoryCounts[$c->id] ?? 0));
            $own = (int) ($categoryCounts[$cat->id] ?? 0);
            $cat->product_count = $own + $childSum;
            foreach ($cat->children as $child) {
                $child->product_count = (int) ($subCounts[$child->id] ?? $categoryCounts[$child->id] ?? 0);
            }
        }

        $brands = Brand::where('status', 'active')
            ->withCount(['products' => fn ($q) => $q->where('status', 'active')->where('product_status', 'published')])
            ->orderBy('name')
            ->get()
            ->filter(fn ($b) => $b->products_count > 0)
            ->values();

        $priceBounds = Product::query()
            ->where('status', 'active')
            ->where('product_status', 'published')
            ->selectRaw('MIN(price) as min_p, MAX(price) as max_p')
            ->first();

        $hasReviews = Review::where('status', 'approved')->exists();

        $colorOptions = Color::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get();
        $sizeOptions = Size::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get();
        // Only expose attribute filters when products actually use them
        $hasColorData = Product::where('status', 'active')
            ->whereNotNull('color_options')
            ->where('color_options', '!=', '[]')
            ->where('color_options', '!=', '')
            ->exists();
        $hasSizeData = Product::where('status', 'active')
            ->whereNotNull('size_options')
            ->where('size_options', '!=', '[]')
            ->where('size_options', '!=', '')
            ->exists();

        $stockCounts = [
            'in_stock' => Product::where('status', 'active')->where('product_status', 'published')
                ->where(fn ($q) => $q->where('stock', '>', 0)->orWhere('unlimited_stock', true))->count(),
            'low_stock' => Product::where('status', 'active')->where('product_status', 'published')
                ->where('stock', '>', 0)->whereRaw('stock <= COALESCE(low_stock_alert, 10)')->count(),
            'out_of_stock' => Product::where('status', 'active')->where('product_status', 'published')
                ->where('stock', '<=', 0)->where('unlimited_stock', false)->count(),
        ];

        // Autocomplete suggestions (server-rendered for current search term when present)
        $suggestions = ['products' => collect(), 'brands' => collect(), 'categories' => collect()];
        if (mb_strlen($search) >= 2) {
            $like = '%' . $search . '%';
            $suggestions['products'] = Product::where('status', 'active')
                ->where('product_status', 'published')
                ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('sku', 'like', $like))
                ->orderBy('title')
                ->limit(5)
                ->get(['id', 'title', 'slug']);
            $suggestions['brands'] = Brand::where('status', 'active')
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name', 'slug']);
            $suggestions['categories'] = Category::where('status', 'active')
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name', 'slug']);
        }

        $pagination = $result['pagination'];
        $from = $pagination['total'] === 0 ? 0 : (($pagination['page'] - 1) * $pagination['limit']) + 1;
        $to = min($pagination['page'] * $pagination['limit'], $pagination['total']);

        $activeFilters = [];
        if ($search !== '') {
            $activeFilters[] = ['key' => 'search', 'label' => "Search: {$search}", 'remove' => ['search' => null, 'q' => null]];
        }
        if ($selectedCategory) {
            $activeFilters[] = ['key' => 'category', 'label' => $selectedCategory->name, 'remove' => ['category' => null, 'subcategory' => null]];
        }
        if ($selectedSubcategory) {
            $activeFilters[] = ['key' => 'subcategory', 'label' => $selectedSubcategory->name, 'remove' => ['subcategory' => null]];
        }
        if ($selectedBrand) {
            $activeFilters[] = ['key' => 'brand', 'label' => $selectedBrand->name, 'remove' => ['brand' => null]];
        }
        if ($minPrice !== null || $maxPrice !== null) {
            $label = '৳' . number_format($minPrice ?? 0) . ' – ৳' . number_format($maxPrice ?? (float) ($priceBounds->max_p ?? 0));
            $activeFilters[] = ['key' => 'price', 'label' => $label, 'remove' => ['minPrice' => null, 'maxPrice' => null, 'min_price' => null, 'max_price' => null]];
        }
        if (in_array($availability, ['in_stock', 'low_stock', 'out_of_stock'], true)) {
            $labels = ['in_stock' => 'In Stock', 'low_stock' => 'Low Stock', 'out_of_stock' => 'Out of Stock'];
            $activeFilters[] = ['key' => 'availability', 'label' => $labels[$availability], 'remove' => ['availability' => null, 'stock' => null]];
        }
        if ($onSale) {
            $activeFilters[] = ['key' => 'sale', 'label' => 'On Sale', 'remove' => ['sale' => null]];
        }
        if ($rating) {
            $activeFilters[] = ['key' => 'rating', 'label' => "{$rating}+ stars", 'remove' => ['rating' => null]];
        }
        if ($color !== '') {
            $activeFilters[] = ['key' => 'color', 'label' => "Color: {$color}", 'remove' => ['color' => null]];
        }
        if ($size !== '') {
            $activeFilters[] = ['key' => 'size', 'label' => "Size: {$size}", 'remove' => ['size' => null]];
        }

        $seoTitle = 'Shop All Products';
        $seoDescription = 'Browse all products at Mama Bazar. Find the best deals on premium products.';
        if ($search !== '') {
            $seoTitle = "Search results for \"{$search}\"";
            $seoDescription = "Find products matching \"{$search}\" on Mama Bazar.";
        } elseif ($selectedCategory && $selectedBrand) {
            $seoTitle = "{$selectedCategory->name} - {$selectedBrand->name} Products";
        } elseif ($selectedCategory) {
            $seoTitle = "{$selectedCategory->name} Products";
            $seoDescription = "Shop {$selectedCategory->name} products at Mama Bazar.";
        } elseif ($selectedBrand) {
            $seoTitle = "{$selectedBrand->name} Products";
            $seoDescription = "Shop {$selectedBrand->name} products at Mama Bazar.";
        }

        return view('web.products.index', [
            'products' => $products,
            'pagination' => $pagination,
            'from' => $from,
            'to' => $to,
            'categories' => $categories,
            'brands' => $brands,
            'selectedCategory' => $selectedCategory,
            'selectedSubcategory' => $selectedSubcategory,
            'selectedBrand' => $selectedBrand,
            'search' => $search,
            'currentSort' => $sort,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'availability' => $availability,
            'onSale' => $onSale,
            'rating' => $rating,
            'color' => $color,
            'size' => $size,
            'viewMode' => $view,
            'activeFilters' => $activeFilters,
            'priceBounds' => $priceBounds,
            'hasReviews' => $hasReviews,
            'hasColorData' => $hasColorData,
            'hasSizeData' => $hasSizeData,
            'colorOptions' => $hasColorData ? $colorOptions : collect(),
            'sizeOptions' => $hasSizeData ? $sizeOptions : collect(),
            'stockCounts' => $stockCounts,
            'suggestions' => $suggestions,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
        ]);
    }

    /**
     * JSON autocomplete endpoint for shop search.
     */
    public function suggest(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['products' => [], 'brands' => [], 'categories' => []]);
        }

        $like = '%' . $term . '%';

        return response()->json([
            'products' => Product::where('status', 'active')
                ->where('product_status', 'published')
                ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('sku', 'like', $like))
                ->orderBy('title')
                ->limit(6)
                ->get(['id', 'title', 'slug'])
                ->map(fn ($p) => ['title' => $p->title, 'url' => route('products.show', $p->slug), 'type' => 'product']),
            'brands' => Brand::where('status', 'active')
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name', 'slug'])
                ->map(fn ($b) => ['title' => $b->name, 'url' => route('shop', ['brand' => $b->slug]), 'type' => 'brand']),
            'categories' => Category::where('status', 'active')
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name', 'slug'])
                ->map(fn ($c) => [
                    'title' => $c->name,
                    'url' => route('shop', [$c->parent_id ? 'subcategory' : 'category' => $c->slug]),
                    'type' => 'category',
                ]),
        ]);
    }
}
