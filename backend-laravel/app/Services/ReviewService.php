<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Single shared backend for customer reviews (web + API funnel here).
 *
 * Approval rule (enforced at query level, never in the browser):
 * only status = approved is ever exposed publicly.
 */
class ReviewService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const PER_PAGE = 10;

    /**
     * Server-side determination of verified purchase. Never trust client input.
     */
    public static function hasVerifiedPurchase(?int $userId, int $productId): bool
    {
        if (!$userId || !$productId) {
            return false;
        }

        return OrderItem::where('order_items.product_id', $productId)
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $userId)
            ->where('orders.status', '<>', 'cancelled')
            ->exists();
    }

    /**
     * Submit a review. Always created as pending; duplicates rejected at
     * application level AND by the unique(product_id, user_id) index.
     *
     * @throws Exception with HTTP-ish code in $e->getCode()
     */
    public static function submit(int $productId, ?User $user, int $rating, ?string $title, string $comment): Review
    {
        $product = Product::find($productId);
        if (!$product) {
            throw new Exception('Product not found.', 404);
        }
        if ($rating < 1 || $rating > 5) {
            throw new Exception('Rating must be between 1 and 5.', 422);
        }
        $comment = trim(strip_tags($comment));
        if ($comment === '') {
            throw new Exception('Review text is required.', 422);
        }

        try {
            return DB::transaction(function () use ($product, $user, $rating, $title, $comment) {
                if ($user) {
                    $exists = Review::where('product_id', $product->id)
                        ->where('user_id', $user->id)
                        ->lockForUpdate()
                        ->exists();
                    if ($exists) {
                        throw new Exception('You have already reviewed this product.', 409);
                    }
                }

                return Review::create([
                    'product_id' => $product->id,
                    'user_id' => $user?->id,
                    'customer_name' => $user?->name ?? 'Guest',
                    'rating' => $rating,
                    'title' => $title ? trim(strip_tags($title)) : null,
                    'comment' => mb_substr($comment, 0, 5000),
                    'status' => self::STATUS_PENDING,
                    'is_verified_purchase' => self::hasVerifiedPurchase($user?->id, $product->id),
                ]);
            });
        } catch (Exception $e) {
            // Unique-index race (double-click / double submit): report as duplicate.
            if ($e->getCode() === 409) {
                throw $e;
            }
            if (str_contains($e->getMessage(), 'reviews_product_user_unique')
                || str_contains($e->getMessage(), 'Duplicate entry')) {
                throw new Exception('You have already reviewed this product.', 409);
            }
            throw $e;
        }
    }

    /**
     * Owner-only edit. Returns the review to pending for re-moderation and
     * recomputes the verified flag (a purchase may have happened since).
     */
    public static function updateOwn(Review $review, User $user, int $rating, ?string $title, string $comment): Review
    {
        if ((int) $review->user_id !== (int) $user->id) {
            throw new Exception('You can only edit your own review.', 403);
        }
        if ($rating < 1 || $rating > 5) {
            throw new Exception('Rating must be between 1 and 5.', 422);
        }
        $comment = trim(strip_tags($comment));
        if ($comment === '') {
            throw new Exception('Review text is required.', 422);
        }

        $review->update([
            'rating' => $rating,
            'title' => $title ? trim(strip_tags($title)) : null,
            'comment' => mb_substr($comment, 0, 5000),
            'status' => self::STATUS_PENDING,
            'approved_at' => null,
            'approved_by' => null,
            'is_verified_purchase' => self::hasVerifiedPurchase($user->id, (int) $review->product_id),
        ]);

        return $review->fresh();
    }

    /**
     * Approved-only rating summary. Single aggregate query.
     */
    public static function summary(int $productId): array
    {
        $rows = Review::where('product_id', $productId)
            ->where('status', self::STATUS_APPROVED)
            ->selectRaw('rating, COUNT(*) as c')
            ->groupBy('rating')
            ->pluck('c', 'rating')
            ->toArray();

        $breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $total = 0;
        $sum = 0;
        foreach ($breakdown as $stars => $_) {
            $c = (int) ($rows[$stars] ?? 0);
            $breakdown[$stars] = $c;
            $total += $c;
            $sum += $stars * $c;
        }

        return [
            'average' => $total > 0 ? round($sum / $total, 1) : 0,
            'count' => $total,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Approved-only paginated reviews with authors eager-loaded.
     */
    public static function approvedForProduct(int $productId, int $page = 1, int $perPage = self::PER_PAGE)
    {
        return Review::with('user')
            ->where('product_id', $productId)
            ->where('status', self::STATUS_APPROVED)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'review_page', $page);
    }

    /**
     * Homepage features: featured AND approved only. Falls back to latest
     * approved when nothing is featured. Never exposes pending/rejected.
     */
    public static function featuredForHomepage(int $limit = 8)
    {
        $featured = Review::with(['user', 'product'])
            ->where('status', self::STATUS_APPROVED)
            ->where('is_featured', true)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        if ($featured->count() >= min(3, $limit) || $featured->isEmpty()) {
            if ($featured->isEmpty()) {
                return Review::with(['user', 'product'])
                    ->where('status', self::STATUS_APPROVED)
                    ->orderBy('created_at', 'desc')
                    ->take($limit)
                    ->get();
            }
            return $featured;
        }

        $more = Review::with(['user', 'product'])
            ->where('status', self::STATUS_APPROVED)
            ->where('is_featured', false)
            ->orderBy('created_at', 'desc')
            ->take($limit - $featured->count())
            ->get();

        return $featured->concat($more);
    }

    /**
     * Admin moderation. Approving stamps approved_at/by; rejecting keeps the
     * row hidden; featured flag is only ever honored together with approved.
     */
    public static function setStatus(Review $review, string $status, ?User $admin = null, ?string $note = null): Review
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
            throw new Exception('Invalid review status.', 422);
        }

        $review->update([
            'status' => $status,
            'admin_note' => $note !== null ? mb_substr(trim($note), 0, 2000) : $review->admin_note,
            'approved_at' => $status === self::STATUS_APPROVED ? now() : null,
            'approved_by' => $status === self::STATUS_APPROVED ? $admin?->id : null,
        ]);

        return $review->fresh();
    }

    public static function setFeatured(Review $review, bool $featured): Review
    {
        $review->update(['is_featured' => $featured]);
        return $review->fresh();
    }
}
