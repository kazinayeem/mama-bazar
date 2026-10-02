<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
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
                'reviews.is_verified_purchase as isVerifiedPurchase',
                'reviews.is_featured as isFeatured',
                'reviews.created_at as createdAt',
                'products.title as productTitle',
                'products.slug as productSlug',
                'users.phone as customerPhone',
                'users.role as customerRole',
            ])
            ->first();

        if (! $review) {
            return response()->json(['success' => false, 'message' => 'Review not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $review]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'productId' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'comment' => 'required|string|max:5000',
            'customerName' => 'nullable|string|max:100',
        ]);

        try {
            $review = ReviewService::submit(
                (int) $validated['productId'],
                $request->user(),
                (int) $validated['rating'],
                $validated['title'] ?? null,
                (string) $validated['comment']
            );
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [400, 403, 404, 409, 422], true) ? $e->getCode() : 400;

            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }

        return response()->json([
            'success' => true,
            'data' => $this->getReviewRecord($review->id),
            'message' => 'Review submitted and pending approval',
        ], 201);
    }

    /**
     * Owner-only edit (JWT users). Returns the review to pending re-moderation.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $review = Review::find($id);
        if (! $review) {
            return response()->json(['success' => false, 'message' => 'Review not found'], 404);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'comment' => 'required|string|max:5000',
        ]);

        try {
            $updated = ReviewService::updateOwn(
                $review,
                $request->user(),
                (int) $validated['rating'],
                $validated['title'] ?? null,
                (string) $validated['comment']
            );
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [400, 403, 404, 409, 422], true) ? $e->getCode() : 400;

            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }

        return response()->json(['success' => true, 'data' => $this->getReviewRecord($updated->id)]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $review = Review::find($id);
        if (! $review) {
            return response()->json(['success' => false, 'message' => 'Review not found'], 404);
        }

        $updated = ReviewService::setStatus(
            $review,
            $validated['status'],
            $request->user(),
            $validated['admin_note'] ?? null
        );

        return response()->json(['success' => true, 'data' => $this->getReviewRecord($updated->id)]);
    }

    public function remove(int $id): JsonResponse
    {
        $review = Review::find($id);
        if (! $review) {
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
                'reviews.is_verified_purchase as isVerifiedPurchase',
                'reviews.is_featured as isFeatured',
                'reviews.created_at as createdAt',
                'products.title as productTitle',
                'products.slug as productSlug',
                'users.phone as customerPhone',
                'users.role as customerRole',
            ])
            ->first();
    }
}
