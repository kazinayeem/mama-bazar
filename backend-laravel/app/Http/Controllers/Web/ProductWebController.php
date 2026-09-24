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
            ->limit(50)
            ->get();

        return view('web.products.show', compact('product', 'relatedProducts', 'reviews'));
    }

    public function storeReview(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'comment' => 'required|string|max:5000',
        ]);

        $user = $request->user();
        if ($user) {
            $existing = Review::where('product_id', $product->id)
                ->where('user_id', $user->id)
                ->first();
            if ($existing) {
                return back()->with('error', 'You have already reviewed this product.');
            }
        }

        Review::create([
            'product_id' => $product->id,
            'user_id' => $user?->id,
            'customer_name' => $user?->name ?? $request->input('customerName'),
            'rating' => (int) $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Review submitted! It will appear after approval.');
    }
}
