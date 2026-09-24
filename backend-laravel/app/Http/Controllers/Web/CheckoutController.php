<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use App\Models\PaymentMethod;
use App\Models\CheckoutNotice;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Exception;

class CheckoutController extends Controller
{
    public function index()
    {
        $shippingMethods = ShippingMethod::where('status', 'active')
            ->orderBy('priority', 'asc')
            ->get();

        $paymentMethods = PaymentMethod::where('enabled', true)
            ->where('maintenance_mode', false)
            ->orderBy('sort_order', 'asc')
            ->get();

        $checkoutNotice = CheckoutNotice::where('status', 'active')
            ->orderBy('priority', 'desc')
            ->first();

        return view('web.checkout', compact('shippingMethods', 'paymentMethods', 'checkoutNotice'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'payment_method' => 'required|string',
            'shipping_method_id' => 'required|integer',
        ]);

        try {
            $order = OrderService::createOrder($request->all());
            $orderId = is_array($order) ? ($order['orderId'] ?? $order['order_id'] ?? '') : $order->order_id;

            return redirect()->route('order.success', ['orderId' => $orderId])
                ->with('success', 'Order placed successfully!');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function success(Request $request)
    {
        $orderId = $request->query('orderId');
        return view('web.success', compact('orderId'));
    }
}
