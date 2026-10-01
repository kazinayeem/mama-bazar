<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Services\ProductService;
use App\Services\ReviewService;
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

        // Approved-only review data (pending/rejected never leave the server).
        $reviewSummary = ReviewService::summary((int) $product['id']);
        $reviewPage = max(1, (int) request()->query('review_page', 1));
        $reviews = ReviewService::approvedForProduct((int) $product['id'], $reviewPage);

        $userReview = null;
        $canReview = false;
        $isVerifiedBuyer = false;
        if (auth()->check()) {
            $userReview = Review::where('product_id', $product['id'])
                ->where('user_id', auth()->id())
                ->first();
            $canReview = $userReview === null;
            $isVerifiedBuyer = ReviewService::hasVerifiedPurchase(auth()->id(), (int) $product['id']);
        }

        return view('web.products.show', compact(
            'product', 'relatedProducts', 'reviews',
            'reviewSummary', 'userReview', 'canReview', 'isVerifiedBuyer'
        ));
    }

    public function storeReview(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'comment' => 'required|string|max:5000',
        ]);

        try {
            ReviewService::submit(
                (int) $product->id,
                $request->user(),
                (int) $validated['rating'],
                $validated['title'] ?? null,
                (string) $validated['comment']
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Review submitted! It will appear after approval.');
    }

    public function updateReview(Request $request, string $slug, int $id)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $review = Review::where('id', $id)->where('product_id', $product->id)->firstOrFail();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'comment' => 'required|string|max:5000',
        ]);

        try {
            ReviewService::updateOwn(
                $review,
                $request->user(),
                (int) $validated['rating'],
                $validated['title'] ?? null,
                (string) $validated['comment']
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Review updated! It will re-appear after approval.');
    }
}
