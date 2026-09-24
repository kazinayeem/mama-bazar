<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function getAll(Request $request): JsonResponse
    {
        $page = (int) ($request->query('page') ?: 1);
        $limit = (int) ($request->query('limit') ?: 12);
        $productId = $request->query('productId');
        $search = $request->query('search');

        $query = DB::table('reviews')
            ->leftJoin('products', 'reviews.product_id', '=', 'products.id')
            ->where('reviews.status', 'approved');

        if ($productId) {
            $query->where('reviews.product_id', (int) $productId);
        }
        if ($search) {
            $query->where('reviews.comment', 'like', "%{$search}%");
        }

        $total = $query->count();
        $offset = ($page - 1) * $limit;

        $rows = $query->select([
            'reviews.id',
            'reviews.product_id as productId',
            'reviews.user_id as userId',
            'reviews.customer_name as customerName',
            'reviews.rating',
            'reviews.title',
            'reviews.comment',
            'reviews.status',
            'reviews.created_at as createdAt',
            'products.title as productTitle',
            'products.slug as productSlug',
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(products.images, '$[0]')) as productImage"),
        ])
            ->orderBy('reviews.created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function getAllAdmin(Request $request): JsonResponse
    {
        $page = (int) ($request->query('page') ?: 1);
        $limit = (int) ($request->query('limit') ?: 12);
        $status = $request->query('status');
        $search = $request->query('search');

        $query = DB::table('reviews')
            ->leftJoin('products', 'reviews.product_id', '=', 'products.id');

        if ($status) {
            $query->where('reviews.status', $status);
        }
        if ($search) {
            $query->where('reviews.comment', 'like', "%{$search}%");
        }

        $total = $query->count();
        $offset = ($page - 1) * $limit;

        $rows = $query->select([
            'reviews.id',
            'reviews.product_id as productId',
            'reviews.user_id as userId',
            'reviews.customer_name as customerName',
            'reviews.rating',
            'reviews.title',
            'reviews.comment',
            'reviews.status',
            'reviews.created_at as createdAt',
            'products.title as productTitle',
            'products.slug as productSlug',
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(products.images, '$[0]')) as productImage"),
        ])
            ->orderBy('reviews.created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function getById(int $id): JsonResponse
    {
        $review = DB::table('reviews')
            ->leftJoin('products', 'reviews.product_id', '=', 'products.id')
            ->leftJoin('users', 'reviews.user_id', '=', 'users.id')
            ->where('reviews.id', $id)
            ->select([
                'reviews.id',
                'reviews.product_id as productId',
                'reviews.user_id as userId',
                'reviews.customer_name as customerName',
                'reviews.rating',
                'reviews.title',
                'reviews.comment',
                'reviews.status',
                'reviews.created_at as createdAt',
                'products.title as productTitle',
                'products.slug as productSlug',
                'users.phone as customerPhone',
                'users.role as customerRole',
            ])
            ->first();

        if (!$review) {
            return response()->json(['success' => false, 'message' => 'Review not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $review]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'productId' => 'required|integer',
            'rating' => 'required|numeric|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'comment' => 'required|string',
            'customerName' => 'nullable|string|max:100',
        ]);

        $product = Product::find($validated['productId']);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $user = $request->user();
        if ($user) {
            $existing = Review::where('product_id', $validated['productId'])
                ->where('user_id', $user->id)
                ->first();
            if ($existing) {
                return response()->json(['success' => false, 'message' => 'You have already reviewed this product'], 400);
            }
        }

        $review = Review::create([
            'product_id' => $validated['productId'],
            'user_id' => $user ? $user->id : null,
            'customer_name' => $user ? ($user->name ?? $request->input('customerName')) : $request->input('customerName'),
            'rating' => max(1, min(5, (int) $validated['rating'])),
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
            'status' => 'pending',
        ]);

        $fullReview = $this->getReviewRecord($review->id);

        return response()->json([
            'success' => true,
            'data' => $fullReview,
            'message' => 'Review submitted and pending approval',
        ], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $review = Review::find($id);
        if (!$review) {
            return response()->json(['success' => false, 'message' => 'Review not found'], 404);
        }

        $review->update(['status' => $validated['status']]);

        return response()->json(['success' => true, 'data' => $this->getReviewRecord($id)]);
    }

    public function remove(int $id): JsonResponse
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json(['success' => false, 'message' => 'Review not found'], 404);
        }

        $review->delete();
        return response()->json(['success' => true, 'message' => 'Review deleted']);
    }

    private function getReviewRecord(int $id)
    {
        return DB::table('reviews')
            ->leftJoin('products', 'reviews.product_id', '=', 'products.id')
            ->leftJoin('users', 'reviews.user_id', '=', 'users.id')
            ->where('reviews.id', $id)
            ->select([
                'reviews.id',
                'reviews.product_id as productId',
                'reviews.user_id as userId',
                'reviews.customer_name as customerName',
                'reviews.rating',
                'reviews.title',
                'reviews.comment',
                'reviews.status',
                'reviews.created_at as createdAt',
                'products.title as productTitle',
                'products.slug as productSlug',
                'users.phone as customerPhone',
                'users.role as customerRole',
            ])
            ->first();
    }
}
