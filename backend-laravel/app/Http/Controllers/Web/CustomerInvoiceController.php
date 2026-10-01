<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;

/**
 * Customer invoice download, reachable from order emails. Access requires
 * the order's secret access token or being the signed-in owner.
 */
class CustomerInvoiceController extends Controller
{
    public function download(Request $request, string $orderId)
    {
        $order = Order::where('order_id', $orderId)->first();
        abort_unless($order, 404);

        $token = (string) $request->query('token', '');
        $ownsOrder = $request->user() && $order->user_id && (int) $order->user_id === (int) $request->user()->id;
        $tokenValid = $token !== '' && $order->access_token && hash_equals((string) $order->access_token, $token);
        abort_unless($ownsOrder || $tokenValid, 404);

        if (! InvoicePdfService::isInvoiceReady($order)) {
            return redirect()->route('track', ['order_id' => $order->order_id, 'token' => $order->access_token])
                ->with('error', 'The invoice will be available once your payment has been verified.');
        }

        $pdf = InvoicePdfService::make($order);

        return $pdf->download(InvoicePdfService::filename($order));
    }
}
