<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;

class CouponController extends Controller
{
    public function validateCoupon(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric',
        ]);

        $code = strtoupper(trim($validated['code']));
        $subtotal = (float) $validated['subtotal'];

        $coupon = Coupon::where('code', $code)->where('status', 'active')->first();
        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'Invalid coupon code'], 400);
        }

        if ($coupon->expiry_date && $coupon->expiry_date->isPast()) {
            return response()->json(['success' => false, 'message' => 'Coupon has expired'], 400);
        }

        if ($coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
            return response()->json(['success' => false, 'message' => "Minimum order amount is {$coupon->min_order_amount} Tk"], 400);
        }

        $discount = ($coupon->discount_type === 'percentage')
            ? ($subtotal * (float) $coupon->discount_value) / 100
            : (float) $coupon->discount_value;

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => true,
                'discount' => $discount,
                'discountType' => $coupon->discount_type,
                'discountValue' => (string) $coupon->discount_value,
            ],
        ]);
    }

    public function getAll()
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $coupons]);
    }

    public function getById($id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'Coupon not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $coupon]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'discountType' => 'required|in:percentage,fixed',
            'discountValue' => 'required|numeric',
        ]);

        $code = strtoupper(trim($validated['code']));
        if (Coupon::where('code', $code)->exists()) {
            return response()->json(['success' => false, 'message' => 'Coupon code already exists'], 409);
        }

        $coupon = Coupon::create([
            'code' => $code,
            'discount_type' => $validated['discountType'],
            'discount_value' => (float) $validated['discountValue'],
            'min_order_amount' => (float) $request->input('minOrderAmount', 0),
            'expiry_date' => $request->input('expiryDate'),
            'status' => $request->input('status', 'active'),
        ]);

        return response()->json(['success' => true, 'data' => $coupon], 201);
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'Coupon not found'], 404);
        }

        $updateData = [];
        if ($request->has('code')) $updateData['code'] = strtoupper(trim($request->input('code')));
        if ($request->has('discountType')) $updateData['discount_type'] = $request->input('discountType');
        if ($request->has('discountValue')) $updateData['discount_value'] = (float) $request->input('discountValue');
        if ($request->has('minOrderAmount')) $updateData['min_order_amount'] = (float) $request->input('minOrderAmount');
        if ($request->has('expiryDate')) $updateData['expiry_date'] = $request->input('expiryDate');
        if ($request->has('status')) $updateData['status'] = $request->input('status');

        $coupon->update($updateData);
        return response()->json(['success' => true, 'data' => $coupon]);
    }

    public function remove($id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'Coupon not found'], 404);
        }

        $coupon->delete();
        return response()->json(['success' => true, 'message' => 'Coupon deleted']);
    }
}
