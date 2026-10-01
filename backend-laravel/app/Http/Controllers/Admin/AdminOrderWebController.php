<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
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
        return view('admin.orders.show', compact('order', 'store'));
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
        $store = self::storeInfo();
        $store['logo_base64'] = self::logoBase64();

        $pdf = Pdf::loadView('admin.orders.invoice-pdf', compact('order', 'store'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

        self::registerBengaliFont($pdf->getDomPDF());

        $filename = ($order->invoice_number ?: $order->order_id) . '.pdf';

        return $pdf->download($filename);
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

        $order->status = $newStatus;
        if ($newStatus === 'delivered') {
            $order->payment_status = 'success';
        }
        if (in_array($newStatus, ['cancelled', 'refunded'], true)) {
            // Keep payment_status truthful; admin adjusts separately below.
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

    /**
     * Register Hind Siliguri (OFL Bengali font) with dompdf.
     * registerFont() generates metrics but its URL-based resolution breaks
     * on special chars in the project path, so entries are then pinned to
     * the generated extension-less cache paths (the format dompdf resolves).
     * Falls back silently to DejaVu Sans.
     */
    protected static function registerBengaliFont($dompdf): void
    {
        try {
            $regular = public_path('fonts/HindSiliguri-Regular.ttf');
            $bold = public_path('fonts/HindSiliguri-Bold.ttf');
            if (!is_file($regular) || !is_readable($regular)) {
                return;
            }
            if (!is_file($bold) || !is_readable($bold)) {
                $bold = $regular;
            }
            // dompdf needs a writable dir for font metrics (absent on fresh deploys).
            $fontDir = rtrim($dompdf->getOptions()->getFontDir(), '/');
            if (!is_dir($fontDir)) {
                @mkdir($fontDir, 0775, true);
            }
            if (!is_dir($fontDir) || !is_writable($fontDir)) {
                return;
            }
            $metrics = $dompdf->getFontMetrics();
            $weights = ['normal' => $regular, 'bold' => $bold, 'italic' => $regular, 'bold_italic' => $bold];
            foreach (['normal' => $regular, 'bold' => $bold] as $weight => $file) {
                $metrics->registerFont(
                    ['family' => 'Hind Siliguri', 'style' => 'normal', 'weight' => $weight],
                    $file
                );
            }
            // Pin entries to deterministic cache paths (prefix + md5 of source path).
            $dir = $fontDir;
            $pinned = [];
            foreach ($weights as $subtype => $file) {
                $style = $subtype === 'bold_italic' ? 'bold_italic' : ($subtype === 'italic' ? 'italic' : $subtype);
                $prefix = 'hind_siliguri_' . $style . '_' . md5($file);
                if (is_file($dir . '/' . $prefix . '.ufm') || is_file($dir . '/' . $prefix . '.ttf')) {
                    $pinned[$subtype] = $dir . '/' . $prefix;
                }
            }
            if (isset($pinned['normal'])) {
                $metrics->setFontFamily('hind siliguri', $pinned);
            }
        } catch (\Throwable $e) {
            // Non-fatal: invoice still renders with the default font.
        }
    }
}
