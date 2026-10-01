<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\InvoicePdfService;
use App\Services\OrderEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOrderWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->with('items.product');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('order_id', 'like', "%{$s}%")
                  ->orWhere('invoice_number', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['items.product', 'items.variant', 'statusHistory.user', 'user'])->findOrFail($id);
        $store = self::storeInfo();
        $emailLogs = EmailLog::where('order_id', $order->id)->latest()->take(15)->get();
        $invoiceReady = InvoicePdfService::isInvoiceReady($order);

        return view('admin.orders.show', compact('order', 'store', 'emailLogs', 'invoiceReady'));
    }

    public function invoice($id)
    {
        $order = Order::with(['items.product', 'items.variant'])->findOrFail($id);
        $store = self::storeInfo();
        return view('admin.orders.invoice', compact('order', 'store'));
    }

    public function downloadInvoice($id)
    {
        $order = Order::with(['items.product', 'items.variant'])->findOrFail($id);

        return InvoicePdfService::make($order)->download(InvoicePdfService::filename($order));
    }

    /**
     * Manually email the invoice PDF to the order's own email address.
     */
    public function emailInvoice($id)
    {
        $order = Order::findOrFail($id);

        if (empty($order->email) || !filter_var($order->email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'This order has no valid customer email address.');
        }

        if (!InvoicePdfService::isInvoiceReady($order)) {
            return back()->with('error', 'Invoice is not available yet — payment is unverified or the order is cancelled/refunded.');
        }

        OrderEmailService::queue($order, OrderEmailService::TRIGGER_INVOICE, true);

        return back()->with('success', "Invoice email for #{$order->order_id} has been queued to {$order->email}.");
    }

    public function packingSlip($id)
    {
        $order = Order::with(['items.product', 'items.variant'])->findOrFail($id);
        $store = self::storeInfo();
        return view('admin.orders.packing-slip', compact('order', 'store'));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,payment_pending,payment_verification,confirmed,processing,packed,shipped,out_for_delivery,delivered,cancelled,returned,refunded',
            'note' => 'nullable|string|max:500',
        ]);

        $newStatus = $request->input('status');
        $note = $request->input('note');

        if ($order->status === $newStatus) {
            return back()->with('success', "Order #{$order->order_id} is already {$newStatus}. No notification sent.");
        }

        $order->status = $newStatus;
        if ($newStatus === 'delivered') {
            $order->payment_status = 'success';
        }
        $order->save();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $newStatus,
            'note' => $note ?: "Status updated to {$newStatus}",
            'created_by_user_id' => Auth::id(),
        ]);

        return back()->with('success', "Order #{$order->order_id} status updated to {$newStatus}.");
    }

    public function updatePayment(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'payment_status' => 'required|in:pending,payment_pending,payment_verification,verified,success,failed,rejected,refunded',
            'note' => 'nullable|string|max:500',
        ]);

        $order->payment_status = $request->input('payment_status');
        $order->save();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => 'Payment: ' . $request->input('payment_status') . ($request->input('note') ? ' — ' . $request->input('note') : ''),
            'created_by_user_id' => Auth::id(),
        ]);

        return back()->with('success', "Payment status updated to {$order->payment_status}.");
    }

    public function addNote(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate(['admin_notes' => 'required|string|max:2000']);

        $order->admin_notes = trim(($order->admin_notes ? $order->admin_notes . "\n" : '') . '[' . now()->format('Y-m-d H:i') . ' | ' . (Auth::user()->name ?? 'Admin') . '] ' . $request->input('admin_notes'));
        $order->save();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => 'Internal note added',
            'created_by_user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Internal note added.');
    }

    public static function storeInfo(): array
    {
        return \App\Services\BusinessSettingService::forInvoice();
    }

    public static function logoBase64(): ?string
    {
        return \App\Services\BusinessSettingService::logoBase64();
    }
}
