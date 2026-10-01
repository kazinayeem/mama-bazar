<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single source for invoice PDFs (admin download + email attachments).
 */
class InvoicePdfService
{
    /** Order states that never get an invoice. */
    public const NON_INVOICE_STATUSES = ['payment_pending', 'payment_verification', 'cancelled', 'refunded', 'returned'];

    public const PAID_STATUSES = ['success', 'verified', 'paid'];

    /**
     * An order is invoice-ready once it is a valid order whose payment is
     * either verified or Cash on Delivery (collected on delivery).
     */
    public static function isInvoiceReady(Order $order): bool
    {
        if (in_array($order->status, self::NON_INVOICE_STATUSES, true)) {
            return false;
        }

        if (in_array($order->payment_status, ['failed', 'rejected', 'refunded'], true)) {
            return false;
        }

        return $order->payment_method === 'cod'
            || in_array($order->payment_status, self::PAID_STATUSES, true);
    }

    public static function filename(Order $order): string
    {
        $number = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $order->order_id) ?: (string) $order->id;

        return "Mama-Bazar-Invoice-{$number}.pdf";
    }

    /**
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function make(Order $order)
    {
        $store = BusinessSettingService::forInvoice();
        $store['logo_base64'] = BusinessSettingService::logoBase64();

        $pdf = Pdf::loadView('admin.orders.invoice-pdf', [
            'order' => $order->loadMissing(['items.product', 'items.variant']),
            'store' => $store,
        ])
            ->setPaper('a4', 'portrait')
            ->setOptions(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

        self::registerBengaliFont($pdf->getDomPDF());

        return $pdf;
    }

    /**
     * Render PDF bytes, or null on failure (logged without customer data).
     */
    public static function render(Order $order): ?string
    {
        try {
            return self::make($order)->output();
        } catch (Throwable $e) {
            Log::warning("Invoice PDF generation failed for order {$order->order_id}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Register Hind Siliguri (OFL Bengali font) with dompdf.
     * registerFont() generates metrics but its URL-based resolution breaks
     * on special chars in the project path, so entries are then pinned to
     * the generated extension-less cache paths (the format dompdf resolves).
     * Falls back silently to DejaVu Sans.
     */
    public static function registerBengaliFont($dompdf): void
    {
        try {
            $regular = public_path('fonts/HindSiliguri-Regular.ttf');
            $bold = public_path('fonts/HindSiliguri-Bold.ttf');
            if (! is_file($regular) || ! is_readable($regular)) {
                return;
            }
            if (! is_file($bold) || ! is_readable($bold)) {
                $bold = $regular;
            }
            $fontDir = rtrim($dompdf->getOptions()->getFontDir(), '/');
            if (! is_dir($fontDir)) {
                @mkdir($fontDir, 0775, true);
            }
            if (! is_dir($fontDir) || ! is_writable($fontDir)) {
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
            $pinned = [];
            foreach ($weights as $subtype => $file) {
                $style = $subtype === 'bold_italic' ? 'bold_italic' : ($subtype === 'italic' ? 'italic' : $subtype);
                $prefix = 'hind_siliguri_'.$style.'_'.md5($file);
                if (is_file($fontDir.'/'.$prefix.'.ufm') || is_file($fontDir.'/'.$prefix.'.ttf')) {
                    $pinned[$subtype] = $fontDir.'/'.$prefix;
                }
            }
            if (isset($pinned['normal'])) {
                $metrics->setFontFamily('hind siliguri', $pinned);
            }
        } catch (Throwable $e) {
            // Non-fatal: invoice still renders with the default font.
        }
    }
}
