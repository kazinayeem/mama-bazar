<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class AdminCouponWebController extends Controller
{
    public function index()
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.coupons.index', compact('coupons'));
    }

    protected function couponRules(?int $ignoreId = null): array
    {
        $unique = $ignoreId ? "unique:coupons,code,{$ignoreId}" : 'unique:coupons,code';
        return [
            'code' => "required|string|max:50|{$unique}",
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0|max:100000',
            'min_order_amount' => 'nullable|numeric|min:0|max:1000000',
            'expiry_date' => 'nullable|date|after:today',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->couponRules());

        Coupon::create([
            'code' => strtoupper(trim($validated['code'])),
            'discount_type' => $validated['discount_type'],
            'discount_value' => (float) $validated['discount_value'],
            'min_order_amount' => isset($validated['min_order_amount']) ? (float) $validated['min_order_amount'] : null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        return back()->with('success', 'Coupon created successfully.');
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);
        $validated = $request->validate($this->couponRules((int) $id));

        $coupon->update([
            'code' => strtoupper(trim($validated['code'])),
            'discount_type' => $validated['discount_type'],
            'discount_value' => (float) $validated['discount_value'],
            'min_order_amount' => isset($validated['min_order_amount']) ? (float) $validated['min_order_amount'] : null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'status' => $validated['status'] ?? $coupon->status,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Coupon updated successfully.']);
        }

        return back()->with('success', 'Coupon updated successfully.');
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return back()->with('success', 'Coupon deleted successfully.');
    }
}
