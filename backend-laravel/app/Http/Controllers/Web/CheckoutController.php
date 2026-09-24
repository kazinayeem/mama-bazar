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
        PaymentMethod::ensureDefaults();

        $shippingMethods = ShippingMethod::where('status', 'active')
            ->orderBy('priority', 'asc')
            ->get();

        $paymentMethods = PaymentMethod::activeCheckout()->get();

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
            'sender_number' => 'nullable|string|max:30',
            'transaction_id' => 'nullable|string|max:100',
        ]);

        $paymentMethodCode = strtolower($request->input('payment_method'));
        $isCod = $paymentMethodCode === 'cod';

        $activeMethod = PaymentMethod::activeCheckout()->where('code', $paymentMethodCode)->first();
        if (!$activeMethod) {
            return back()->withInput()->with('error', 'Selected payment method is unavailable.');
        }

        if (!$isCod) {
            $request->validate([
                'sender_number' => 'required|string|max:30',
                'transaction_id' => 'required|string|max:100',
            ]);
        }

        try {
            $payload = $request->all();
            $payload['payment_method'] = $paymentMethodCode;
            $payload['transaction_id'] = $request->input('transaction_id');
            $payload['sender_number'] = $request->input('sender_number');
            $payload['transactionId'] = $request->input('transaction_id');
            $payload['senderNumber'] = $request->input('sender_number');

            $order = OrderService::createOrder($payload);
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
