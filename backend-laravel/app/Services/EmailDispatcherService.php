<?php

namespace App\Services;

use App\Http\Controllers\Admin\AdminOrderWebController;
use App\Models\EmailLog;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailDispatcherService
{
    /**
     * Send an email immediately and record in email_logs.
     *
     * @param string $to Recipient email address
     * @param string $recipientName Recipient full name
     * @param string $subject Email subject line
     * @param string $htmlContent Rendered HTML message
     * @param string|null $plainContent Plain text fallback
     * @param string $emailType e.g. 'otp', 'transactional', 'order', 'invoice', 'campaign'
     * @param int|null $orderId Related Order ID
     * @param int|null $campaignId Related Campaign ID
     * @param array $attachments Array of [ 'data' => string, 'name' => string, 'mime' => string ]
     * @return array [ 'success' => bool, 'log_id' => int, 'error' => ?string ]
     */
    public static function send(
        string $to,
        ?string $recipientName,
        string $subject,
        string $htmlContent,
        ?string $plainContent = null,
        string $emailType = 'transactional',
        ?int $orderId = null,
        ?int $campaignId = null,
        array $attachments = []
    ): array {
        $to = strtolower(trim($to));

        // Create log record in 'queued' state
        $log = EmailLog::create([
            'recipient_email' => $to,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'email_type' => $emailType,
            'order_id' => $orderId,
            'campaign_id' => $campaignId,
            'status' => 'queued',
            'attempts' => 1,
            'metadata' => [
                'has_attachments' => !empty($attachments),
                'attachment_names' => array_column($attachments, 'name'),
            ],
        ]);

        // Check if outgoing emails are globally enabled
        if (!EmailSettingService::isSendingEnabled()) {
            $log->update([
                'status' => 'skipped',
                'error_message' => 'Email delivery is currently disabled in Email Settings.',
            ]);

            return [
                'success' => false,
                'skipped' => true,
                'log_id' => $log->id,
                'error' => 'Email delivery is disabled in settings.',
            ];
        }

        try {
            // Apply dynamic runtime SMTP credentials
            EmailSettingService::applyRuntimeConfig();

            $fromAddress = EmailSettingService::get('mail_from_address', 'contact@mama-bazar.com');
            $fromName = EmailSettingService::get('mail_from_name', 'Mama Bazar');
            $replyTo = EmailSettingService::get('mail_reply_to');

            Mail::send([], [], function ($message) use ($to, $recipientName, $subject, $htmlContent, $plainContent, $fromAddress, $fromName, $replyTo, $attachments) {
                $message->to($to, $recipientName ?: null)
                    ->from($fromAddress, $fromName)
                    ->subject($subject);

                if (!empty($replyTo)) {
                    $message->replyTo($replyTo);
                }

                $message->html($htmlContent);

                if (!empty($plainContent)) {
                    $message->text($plainContent);
                }

                // Add in-memory attachments
                foreach ($attachments as $att) {
                    if (!empty($att['data']) && !empty($att['name'])) {
                        $message->attachData(
                            $att['data'],
                            $att['name'],
                            ['mime' => $att['mime'] ?? 'application/pdf']
                        );
                    }
                }
            });

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            return [
                'success' => true,
                'log_id' => $log->id,
                'error' => null,
            ];
        } catch (Throwable $e) {
            $errMsg = $e->getMessage();

            $log->update([
                'status' => 'failed',
                'error_message' => mb_substr($errMsg, 0, 1000),
            ]);

            return [
                'success' => false,
                'log_id' => $log->id,
                'error' => $errMsg,
            ];
        }
    }

    /**
     * Generate PDF invoice content in memory for an Order.
     */
    public static function generateInvoicePdf(Order $order): ?string
    {
        try {
            $store = BusinessSettingService::forInvoice();
            $store['logo_base64'] = BusinessSettingService::logoBase64();

            $pdf = Pdf::loadView('admin.orders.invoice-pdf', [
                'order' => $order->loadMissing(['items.product', 'items.variant']),
                'store' => $store,
            ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

            return $pdf->output();
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Invoice PDF generation failed for order #{$order->order_id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Dispatch an automated order email based on order state.
     */
    public static function dispatchOrderEmail(Order $order, string $triggerType): array
    {
        if (empty($order->email)) {
            return ['success' => false, 'skipped' => true, 'reason' => 'Order has no email address.'];
        }

        // Map trigger type to template key and automation setting
        $map = [
            'order_confirmation' => ['tpl' => 'order_confirmation', 'setting' => 'order_created'],
            'order_created'      => ['tpl' => 'order_confirmation', 'setting' => 'order_created'],
            'payment_confirmation' => ['tpl' => 'payment_confirmation', 'setting' => 'payment_confirmed'],
            'order_processing'   => ['tpl' => 'order_processing', 'setting' => 'order_status'],
            'order_confirmed'    => ['tpl' => 'order_confirmation', 'setting' => 'order_status'],
            'order_shipped'      => ['tpl' => 'order_shipped', 'setting' => 'order_status'],
            'order_out_for_delivery' => ['tpl' => 'out_for_delivery', 'setting' => 'order_status'],
            'order_delivered'    => ['tpl' => 'order_delivered', 'setting' => 'order_status'],
            'order_cancelled'    => ['tpl' => 'order_cancelled', 'setting' => 'order_status'],
            'order_refunded'     => ['tpl' => 'refund_notification', 'setting' => 'order_status'],
            'invoice_email'      => ['tpl' => 'invoice_email', 'setting' => 'invoice_pdf'],
        ];

        $config = $map[$triggerType] ?? null;
        if (!$config) {
            return ['success' => false, 'error' => "Unknown order email trigger: {$triggerType}"];
        }

        // Check automation toggle
        if (!EmailSettingService::isAutomationEnabled($config['setting'])) {
            return ['success' => false, 'skipped' => true, 'reason' => "Automation '{$config['setting']}' is disabled."];
        }

        // Build data payload
        $itemsTableHtml = self::formatOrderItemsTable($order);
        $orderData = [
            'customer_name' => $order->customer_name ?: 'Valued Customer',
            'customer_email' => $order->email,
            'order_number' => $order->order_id,
            'order_total' => '৳' . number_format((float) $order->total_price, 0),
            'order_date' => $order->created_at ? $order->created_at->format('M d, Y · h:i A') : date('M d, Y'),
            'payment_method' => strtoupper($order->payment_method),
            'payment_status' => ucfirst(str_replace('_', ' ', $order->payment_status)),
            'shipping_address' => implode(', ', array_filter([$order->address, $order->area, $order->district])),
            'items_table' => $itemsTableHtml,
            'tracking_url' => url('/track?order_id=' . urlencode($order->order_id) . '&phone=' . urlencode($order->phone)),
            'invoice_url' => url('/admin/orders/' . $order->id . '/invoice'),
        ];

        // Attach PDF invoice if configured or if invoice email
        $attachments = [];
        $shouldAttachInvoice = ($triggerType === 'invoice_email') ||
            (in_array($triggerType, ['order_confirmation', 'order_created', 'payment_confirmation'], true) && EmailSettingService::isAutomationEnabled('invoice_pdf'));

        if ($shouldAttachInvoice) {
            $pdfContent = self::generateInvoicePdf($order);
            if ($pdfContent) {
                $attachments[] = [
                    'data' => $pdfContent,
                    'name' => 'Mama-Bazar-Invoice-' . $order->order_id . '.pdf',
                    'mime' => 'application/pdf',
                ];
            }
        }

        $rendered = EmailTemplateService::render($config['tpl'], $orderData);

        return self::send(
            $order->email,
            $order->customer_name,
            $rendered['subject'],
            $rendered['html'],
            $rendered['plain'],
            'order',
            $order->id,
            null,
            $attachments
        );
    }

    /**
     * Build an email-safe items table HTML snippet.
     */
    protected static function formatOrderItemsTable(Order $order): string
    {
        $items = $order->items;
        if ($items->isEmpty()) {
            return '';
        }

        $rows = '';
        foreach ($items as $it) {
            $title = htmlspecialchars($it->product_title ?: ($it->product?->title ?: 'Item'));
            $variant = htmlspecialchars(trim(($it->variant_name ?: '') . ' ' . ($it->size ?: '') . ' ' . ($it->color ?: '')));
            $varSub = $variant ? "<br><span style=\"font-size:11px; color:#64748b;\">{$variant}</span>" : '';
            $qty = (int) $it->quantity;
            $price = '৳' . number_format((float) $it->price, 0);
            $total = '৳' . number_format((float) ($it->price * $qty), 0);

            $rows .= "<tr>
                <td style=\"padding:8px 10px; border-bottom:1px solid #f1f5f9;\">{$title}{$varSub}</td>
                <td align=\"center\" style=\"padding:8px 10px; border-bottom:1px solid #f1f5f9;\">{$qty}</td>
                <td align=\"right\" style=\"padding:8px 10px; border-bottom:1px solid #f1f5f9;\">{$price}</td>
                <td align=\"right\" style=\"padding:8px 10px; border-bottom:1px solid #f1f5f9; font-weight:600;\">{$total}</td>
            </tr>";
        }

        return <<<HTML
<table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0; border:1px solid #e2e8f0; border-radius:8px; font-size:12px;">
    <thead>
        <tr style="background:#f1f5f9; color:#475569; font-size:11px; text-transform:uppercase;">
            <th align="left" style="padding:8px 10px;">Item</th>
            <th align="center" style="padding:8px 10px;">Qty</th>
            <th align="right" style="padding:8px 10px;">Price</th>
            <th align="right" style="padding:8px 10px;">Total</th>
        </tr>
    </thead>
    <tbody>
        {$rows}
    </tbody>
</table>
HTML;
    }
}
