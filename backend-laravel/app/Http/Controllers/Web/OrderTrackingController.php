<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function index(Request $request)
    {
        $order = null;
        $searched = false;
        $error = null;

        if ($request->filled('order_id') || $request->filled('phone')) {
            $searched = true;
            $order = OrderService::trackOrder($request->input('order_id'), $request->input('phone'));
            if (!$order) {
                $error = 'No order found matching the provided details.';
            }
        }

        return view('web.track', compact('order', 'searched', 'error'));
    }
}
