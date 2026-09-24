<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductWebController extends Controller
{
    public function show($slug)
    {
        $product = ProductService::getBySlug($slug);

        if (!$product) {
            abort(404, 'Product not found');
        }

        $catId = (int) ($product['categoryId'] ?? $product['category_id'] ?? 0);
        $relatedProducts = $catId ? ProductService::getRelated($catId, (int) $product['id']) : [];

        $reviews = Review::where('product_id', $product['id'])
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('web.products.show', compact('product', 'relatedProducts', 'reviews'));
    }
}
