<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Services\ActivityLoggerService;
use App\Services\BusinessSettingService;
use App\Services\InvoicePdfService;
use App\Services\IpLocationService;
use App\Services\OrderEditService;
use App\Services\OrderEmailService;
use App\Services\OrderFilterService;
use App\Support\EmailQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOrderWebController extends Controller
{
    public function index(Request $request)
    {
        $params = $request->query();

        // Save active filter to session so detail view can retain it even on direct clicks
        if (OrderFilterService::hasActiveFilters($params) || ! empty($params['sort'])) {
            session(['admin_orders_filter_query' => OrderFilterService::cleanParams($params)]);
        } elseif ($request->has('clear')) {
            session()->forget('admin_orders_filter_query');
        }

        $query = Order::query()->with(['items.product', 'items.variant', 'user']);

        // Apply filters & search
        $query = OrderFilterService::applyFilters($query, $params);

        // Apply deterministic sorting
        $sort = $request->input('sort', 'newest');
        $query = OrderFilterService::applySorting($query, $sort);

        $orders = $query->paginate(20)->withQueryString();

        // Calculate summary cards & active chips
        $summaryStats = OrderFilterService::getSummaryStats($params);
        $activeChips = OrderFilterService::getActiveFilterChips($params);

        // Options for filter selects
        $paymentMethods = PaymentMethod::where('enabled', true)->pluck('name', 'code')->toArray();
        if (empty($paymentMethods)) {
            $paymentMethods = OrderFilterService::VALID_PAYMENT_METHODS;
        }

        $shippingMethods = ShippingMethod::where('status', 'active')->get();

        return view('admin.orders.index', compact(
            'orders',
            'summaryStats',
            'activeChips',
            'params',
            'paymentMethods',
            'shippingMethods'
        ));
    }

    public function show(Request $request, $id)
    {
        $order = Order::with(['items.product', 'items.variant', 'statusHistory.user', 'user'])->findOrFail($id);
        $store = self::storeInfo();
        $emailLogs = EmailLog::where('order_id', $order->id)->latest()->take(15)->get();
        $invoiceReady = InvoicePdfService::isInvoiceReady($order);

        $ipGeolocation = IpLocationService::lookup($order->ip_address);
        $locationComparison = IpLocationService::compareLocation($ipGeolocation, $order);

        // Resolve active filter params from query string or session fallback
        $filterParams = $request->query();
        if (empty(OrderFilterService::cleanParams($filterParams))) {
            $saved = session('admin_orders_filter_query', []);
            if (is_array($saved) && ! empty($saved)) {
                $filterParams = $saved;
            }
        }

        $navigation = OrderFilterService::getNavigation($order, $filterParams);

        return view('admin.orders.show', compact(
            'order',
            'store',
            'emailLogs',
            'invoiceReady',
            'ipGeolocation',
            'locationComparison',
            'navigation'
        ));
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

        if (empty($order->email) || ! filter_var($order->email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'This order has no valid customer email address.');
        }

        if (! InvoicePdfService::isInvoiceReady($order)) {
            return back()->with('error', 'Invoice is not available yet — payment is unverified or the order is cancelled/refunded.');
        }

        if (! EmailQueue::isBackground()) {
            $dedupeKey = OrderEmailService::dedupeKey($order, OrderEmailService::TRIGGER_INVOICE, true);
            $result = OrderEmailService::send($order, OrderEmailService::TRIGGER_INVOICE, $dedupeKey);

            if ($result['success'] ?? false) {
                return back()->with('success', "Invoice email for #{$order->order_id} was successfully sent via SMTP to {$order->email}.");
            }

            return back()->with('error', 'Invoice email delivery failed: '.($result['error'] ?? 'Check SMTP logs.'));
        }

        $queued = OrderEmailService::queue($order, OrderEmailService::TRIGGER_INVOICE, true);

        if (! $queued) {
            return back()->with('error', "Could not queue invoice email for #{$order->order_id}. Duplicate or delivery disabled.");
        }

        ActivityLoggerService::logOrder(
            'order.invoice_emailed',
            $order,
            "Invoice email for #{$order->order_id} queued to {$order->email}",
            ['actor' => Auth::user(), 'source' => 'admin']
        );

        return back()->with('success', "Invoice email for #{$order->order_id} has been queued for delivery to {$order->email}. Status: Queued (awaiting queue worker).");
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

        $oldStatus = $order->status;
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

        ActivityLoggerService::logOrder(
            'order.status_changed',
            $order,
            "Order #{$order->order_id} status updated from '{$oldStatus}' to '{$newStatus}'",
            [
                'actor' => Auth::user(),
                'source' => 'admin',
                'oldValues' => ['status' => $oldStatus],
                'newValues' => ['status' => $newStatus],
            ]
        );

        return back()->with('success', "Order #{$order->order_id} status updated to {$newStatus}.");
    }

    public function updatePayment(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'payment_status' => 'required|in:pending,payment_pending,payment_verification,verified,success,failed,rejected,refunded',
            'note' => 'nullable|string|max:500',
        ]);

        $oldPaymentStatus = $order->payment_status;
        $order->payment_status = $request->input('payment_status');
        $order->save();

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'note' => 'Payment: '.$request->input('payment_status').($request->input('note') ? ' — '.$request->input('note') : ''),
            'created_by_user_id' => Auth::id(),
        ]);

        ActivityLoggerService::logOrder(
            'order.payment_status_changed',
            $order,
            "Order #{$order->order_id} payment status changed from '{$oldPaymentStatus}' to '{$order->payment_status}'",
            [
                'actor' => Auth::user(),
                'source' => 'admin',
                'oldValues' => ['payment_status' => $oldPaymentStatus],
                'newValues' => ['payment_status' => $order->payment_status],
            ]
        );

        return back()->with('success', "Payment status updated to {$order->payment_status}.");
    }

    public function addNote(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate(['admin_notes' => 'required|string|max:2000']);

        $order->admin_notes = trim(($order->admin_notes ? $order->admin_notes."\n" : '').'['.now()->format('Y-m-d H:i').' | '.(Auth::user()->name ?? 'Admin').'] '.$request->input('admin_notes'));
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
        return BusinessSettingService::forInvoice();
    }

    public static function logoBase64(): ?string
    {
        return BusinessSettingService::logoBase64();
    }
}
