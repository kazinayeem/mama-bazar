<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\SeoService;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function index(Request $request)
    {
        $order = null;
        $searched = false;
        $error = null;

        if ($request->filled('order_id') || $request->filled('phone') || $request->filled('token')) {
            $searched = true;
            $request->validate([
                'order_id' => 'nullable|string|max:30',
                'phone' => 'nullable|string|max:20',
                'token' => 'nullable|string|max:64',
            ]);
            // Both order reference + phone are required (token links exempt).
            if (! $request->filled('token') && (! $request->filled('order_id') || ! $request->filled('phone'))) {
                $error = 'Please enter both your Order ID and phone number.';
            } else {
                $order = OrderService::trackOrder(
                    $request->input('order_id'),
                    $request->input('phone'),
                    $request->input('token')
                );
                if (! $order) {
                    // Generic message — never reveal which field mismatched.
                    $error = 'No order found matching the provided details.';
                }
            }
        }

        $seo = SeoService::getForPrivate('Track Your Order');

        return view('web.track', compact('order', 'searched', 'error', 'seo'));
    }
}
