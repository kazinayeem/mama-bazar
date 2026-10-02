<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminReviewWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['product', 'user', 'approver'])->orderBy('created_at', 'desc');

        if ($request->filled('status') && in_array($request->input('status'), ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('rating') && in_array((int) $request->input('rating'), [1, 2, 3, 4, 5], true)) {
            $query->where('rating', (int) $request->input('rating'));
        }
        if ($request->input('verified') === '1') {
            $query->where('is_verified_purchase', true);
        }
        if ($request->input('featured') === '1') {
            $query->where('is_featured', true);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('comment', 'like', "%{$s}%")
                    ->orWhere('title', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('title', 'like', "%{$s}%"))
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
                if (is_numeric($s)) {
                    $q->orWhere('id', (int) $s);
                }
            });
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'rating_high' => $query->orderBy('rating', 'desc'),
            'rating_low' => $query->orderBy('rating', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $reviews = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Review::count(),
            'pending' => Review::where('status', 'pending')->count(),
            'approved' => Review::where('status', 'approved')->count(),
            'rejected' => Review::where('status', 'rejected')->count(),
            'average' => round((float) (Review::where('status', 'approved')->avg('rating') ?? 0), 1),
        ];

        $products = Product::orderBy('title')->get(['id', 'title']);

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'stats' => $stats,
            'products' => $products,
            'filters' => $request->all(),
            'headerTitle' => 'Reviews',
        ]);
    }

    public function show($id)
    {
        $review = Review::with(['product', 'user', 'approver'])->findOrFail($id);

        return view('admin.reviews.show', compact('review') + ['headerTitle' => 'Review Details']);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $review = Review::findOrFail($id);
        ReviewService::setStatus($review, $validated['status'], Auth::user(), $validated['admin_note'] ?? null);

        return back()->with('success', "Review {$validated['status']}.");
    }

    public function toggleFeatured(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $featured = $request->has('featured') ? (bool) $request->input('featured') : ! $review->is_featured;
        ReviewService::setFeatured($review, $featured);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'isFeatured' => $featured]);
        }

        return back()->with('success', $featured ? 'Review featured on homepage.' : 'Review removed from homepage features.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'comment' => 'required|string|max:5000',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $review = Review::findOrFail($id);
        $review->update([
            'rating' => (int) $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
            'admin_note' => $validated['admin_note'] ?? $review->admin_note,
        ]);

        return back()->with('success', 'Review updated.');
    }

    public function destroy(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Review deleted.']);
        }

        return redirect()->route('admin.reviews.index')->with('success', 'Review deleted.');
    }
}
