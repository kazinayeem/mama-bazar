<?php

namespace App\Services;

use App\Jobs\SendTransactionalEmailJob;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Support\EmailQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Order lifecycle emails. Each trigger is sent at most once per order
 * (dedupe key "order:{id}:{trigger}"), except manual invoice sends.
 */
class OrderEmailService
{
    public const TRIGGER_CREATED = 'order_created';

    public const TRIGGER_PAYMENT = 'payment_confirmed';

    public const TRIGGER_INVOICE = 'invoice';

    public const TRIGGER_REVIEW = 'review_invitation';

    /**
     * @var array<string, array{template: string, automation: string|null, attach_invoice: string}>
     */
    public const TRIGGERS = [
        'order_created' => ['template' => 'order_confirmation', 'automation' => 'order_created', 'attach_invoice' => 'auto'],
        'payment_confirmed' => ['template' => 'payment_confirmation', 'automation' => 'payment_confirmed', 'attach_invoice' => 'auto'],
        'status_confirmed' => ['template' => 'order_confirmed', 'automation' => 'order_confirmed', 'attach_invoice' => 'never'],
        'status_processing' => ['template' => 'order_processing', 'automation' => 'order_processing', 'attach_invoice' => 'never'],
        'status_shipped' => ['template' => 'order_shipped', 'automation' => 'order_shipped', 'attach_invoice' => 'never'],
        'status_out_for_delivery' => ['template' => 'out_for_delivery', 'automation' => 'order_out_for_delivery', 'attach_invoice' => 'never'],
        'status_delivered' => ['template' => 'order_delivered', 'automation' => 'order_delivered', 'attach_invoice' => 'never'],
        'status_cancelled' => ['template' => 'order_cancelled', 'automation' => 'order_cancelled', 'attach_invoice' => 'never'],
        'status_refunded' => ['template' => 'refund_notification', 'automation' => 'order_refunded', 'attach_invoice' => 'never'],
        'invoice' => ['template' => 'invoice_email', 'automation' => null, 'attach_invoice' => 'always'],
        'review_invitation' => ['template' => 'review_invitation', 'automation' => 'review_invitation', 'attach_invoice' => 'never'],
    ];

    /** Order statuses that send a customer email when entered. */
    public const STATUS_TRIGGERS = [
        'confirmed' => 'status_confirmed',
        'processing' => 'status_processing',
        'shipped' => 'status_shipped',
        'out_for_delivery' => 'status_out_for_delivery',
        'delivered' => 'status_delivered',
        'cancelled' => 'status_cancelled',
        'refunded' => 'status_refunded',
    ];

    /**
     * Model "created" hook: queue the confirmation once the order transaction commits.
     */
    public static function handleCreated(Order $order): void
    {
        $orderId = $order->id;

        DB::afterCommit(function () use ($orderId) {
            try {
                $fresh = Order::find($orderId);
                if ($fresh) {
                    self::queue($fresh, self::TRIGGER_CREATED);
                }
            } catch (Throwable $e) {
                Log::error("Order confirmation email could not be queued for order id {$orderId}: ".$e->getMessage());
            }
        });
    }

    /**
     * Model "updated" hook: detect real status/payment transitions only.
     */
    public static function handleUpdated(Order $order): void
    {
        try {
            $triggers = self::triggersForChanges(
                $order->wasChanged('status') ? (string) $order->getOriginal('status') : null,
                (string) $order->status,
                $order->wasChanged('payment_status') ? (string) $order->getOriginal('payment_status') : null,
                (string) $order->payment_status,
                (string) $order->payment_method
            );
        } catch (Throwable $e) {
            return;
        }

        if ($triggers === []) {
            return;
        }

        $orderId = $order->id;
        DB::afterCommit(function () use ($orderId, $triggers) {
            try {
                $fresh = Order::find($orderId);
                foreach ($fresh ? $triggers : [] as $trigger) {
                    self::queue($fresh, $trigger);
                }
            } catch (Throwable $e) {
                Log::error("Order status email could not be queued for order id {$orderId}: ".$e->getMessage());
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public static function triggersForChanges(?string $oldStatus, string $newStatus, ?string $oldPayment, string $newPayment, string $paymentMethod): array
    {
        $triggers = [];
        $paid = InvoicePdfService::PAID_STATUSES;

        if ($oldPayment !== null && $oldPayment !== $newPayment) {
            if (in_array($newPayment, $paid, true) && ! in_array($oldPayment, $paid, true) && $paymentMethod !== 'cod') {
                $triggers[] = self::TRIGGER_PAYMENT;
            }
            if ($newPayment === 'refunded') {
                $triggers[] = 'status_refunded';
            }
        }

        if ($oldStatus !== null && $oldStatus !== $newStatus && isset(self::STATUS_TRIGGERS[$newStatus])) {
            $trigger = self::STATUS_TRIGGERS[$newStatus];
            $coveredByPayment = $trigger === 'status_confirmed' && in_array(self::TRIGGER_PAYMENT, $triggers, true);
            if (! $coveredByPayment && ! in_array($trigger, $triggers, true)) {
                $triggers[] = $trigger;
            }
        }

        return $triggers;
    }

    public static function dedupeKey(Order $order, string $trigger, bool $manual = false): string
    {
        return $manual
            ? "order:{$order->id}:{$trigger}:manual:".now()->format('YmdHi')
            : "order:{$order->id}:{$trigger}";
    }

    public static function hasEmail(Order $order): bool
    {
        return ! empty($order->email) && filter_var($order->email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Queue an order email if the trigger is enabled and not already sent.
     */
    public static function queue(Order $order, string $trigger, bool $manual = false): bool
    {
        $config = self::TRIGGERS[$trigger] ?? null;
        if (! $config || ! self::hasEmail($order)) {
            return false;
        }

        if ($config['automation'] && ! $manual && ! EmailSettingService::isAutomationEnabled($config['automation'])) {
            return false;
        }

        $dedupeKey = self::dedupeKey($order, $trigger, $manual);
        if (EmailDispatcherService::alreadySent($dedupeKey)) {
            return false;
        }

        if ($trigger === self::TRIGGER_REVIEW && EmailLog::where('dedupe_key', $dedupeKey)->exists()) {
            return false;
        }

        $subject = match ($trigger) {
            self::TRIGGER_INVOICE => "Invoice #{$order->order_id}",
            self::TRIGGER_CREATED => "Order confirmation — {$order->order_id}",
            self::TRIGGER_PAYMENT => "Payment confirmed — {$order->order_id}",
            default => "Order update — {$order->order_id}",
        };

        EmailDispatcherService::recordQueued(
            $order->email,
            $order->customer_name,
            $subject,
            $trigger === self::TRIGGER_INVOICE ? 'invoice' : 'order',
            $order->id,
            $config['template'],
            $dedupeKey,
            [
                'replay' => ['kind' => 'order', 'order_id' => $order->id, 'trigger' => $trigger],
                'manual' => $manual,
            ]
        );

        return EmailQueue::dispatch(new SendTransactionalEmailJob($order->id, $trigger, $dedupeKey));
    }

    /**
     * Build and send the email. Called from the queued job.
     *
     * @return array{success: bool, skipped?: bool, retry?: bool, log_id?: int|null, error?: string|null}
     */
    public static function send(Order $order, string $trigger, ?string $dedupeKey = null, bool $finalAttempt = true): array
    {
        $config = self::TRIGGERS[$trigger] ?? null;
        if (! $config) {
            return ['success' => false, 'skipped' => true, 'error' => "Unknown trigger {$trigger}"];
        }
        if (! self::hasEmail($order)) {
            return ['success' => false, 'skipped' => true, 'error' => 'Order has no valid email.'];
        }
        if ($trigger === self::TRIGGER_REVIEW && EmailPreferenceService::isSuppressed($order->email)) {
            return ['success' => false, 'skipped' => true, 'error' => 'Recipient opted out.'];
        }

        $dedupeKey = $dedupeKey ?: self::dedupeKey($order, $trigger);
        $order->loadMissing(['items.product', 'items.variant']);

        $attachments = [];
        $metadata = [];
        $wantsInvoice = match ($config['attach_invoice']) {
            'always' => true,
            'auto' => EmailSettingService::isAutomationEnabled('invoice_pdf'),
            default => false,
        };

        if ($wantsInvoice && InvoicePdfService::isInvoiceReady($order)) {
            $pdf = InvoicePdfService::render($order);
            if ($pdf !== null) {
                $attachments[] = ['data' => $pdf, 'name' => InvoicePdfService::filename($order), 'mime' => 'application/pdf'];
            } elseif (! $finalAttempt) {
                return ['success' => false, 'retry' => true, 'error' => 'Invoice PDF generation failed; will retry.'];
            } else {
                $metadata['invoice_attachment_failed'] = true;
            }
        } elseif ($trigger === self::TRIGGER_INVOICE) {
            return ['success' => false, 'skipped' => true, 'error' => 'Order is not invoice-ready.'];
        }

        return EmailDispatcherService::sendTemplate(
            $config['template'],
            $order->email,
            $order->customer_name,
            self::orderData($order),
            $trigger === self::TRIGGER_INVOICE ? 'invoice' : 'order',
            [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'attachments' => $attachments,
                'dedupe_key' => $dedupeKey,
                'replay' => ['kind' => 'order', 'order_id' => $order->id, 'trigger' => $trigger],
                'metadata' => $metadata,
            ]
        );
    }

    public static function money(float|int|string|null $amount): string
    {
        $value = (float) $amount;
        $decimals = abs($value - round($value)) > 0.001 ? 2 : 0;

        return '৳'.number_format($value, $decimals);
    }

    public static function trackingUrl(Order $order): string
    {
        $params = ['order_id' => $order->order_id];
        if ($order->access_token) {
            $params['token'] = $order->access_token;
        }

        return route('track', $params);
    }

    public static function invoiceUrl(Order $order): string
    {
        return route('order.invoice', ['orderId' => $order->order_id, 'token' => $order->access_token]);
    }

    /**
     * @return array<string, string>
     */
    public static function orderData(Order $order): array
    {
        $label = fn (?string $v) => ucwords(str_replace('_', ' ', (string) $v));
        $firstProduct = $order->items->first()?->product;

        return [
            'customer_name' => $order->customer_name ?: 'Valued Customer',
            'customer_email' => (string) $order->email,
            'order_number' => (string) $order->order_id,
            'order_date' => $order->created_at ? $order->created_at->format('M d, Y · h:i A') : now()->format('M d, Y'),
            'order_status' => $label($order->status),
            'order_subtotal' => self::money($order->subtotal),
            'order_discount' => self::money($order->discount),
            'order_shipping' => self::money($order->shipping_cost),
            'order_total' => self::money($order->total_price),
            'payment_method' => self::paymentMethodLabel($order),
            'payment_status' => $label($order->payment_status === 'success' && $order->payment_method === 'cod' ? 'due_on_delivery' : $order->payment_status),
            'paid_amount' => self::money($order->amount_sent ?: $order->total_price),
            'payment_date' => ($order->payment_date ?? now())->format('M d, Y · h:i A'),
            'transaction_reference' => $order->transaction_id ?: '—',
            'shipping_address' => implode(', ', array_filter([$order->address, $order->apartment, $order->area, $order->upazila, $order->district])),
            'courier_tracking_number' => $order->courier_tracking_number ?: 'Will be shared by the courier',
            'items_table' => self::itemsTable($order),
            'order_summary_table' => self::summaryTable($order),
            'tracking_url' => self::trackingUrl($order),
            'invoice_url' => self::invoiceUrl($order),
            'review_url' => $firstProduct?->slug ? route('products.show', $firstProduct->slug).'#reviews' : url('/shop'),
        ];
    }

    protected static function paymentMethodLabel(Order $order): string
    {
        if ($order->payment_method === 'cod') {
            return 'Cash on Delivery';
        }

        $name = PaymentMethod::where('code', $order->payment_method)->value('name');

        return $name ?: strtoupper((string) $order->payment_method);
    }

    protected static function itemsTable(Order $order): string
    {
        if ($order->items->isEmpty()) {
            return '';
        }

        $rows = '';
        foreach ($order->items as $item) {
            $title = e($item->product_title ?: ($item->product?->title ?: 'Item'));
            $variantParts = array_filter([$item->variant_name, $item->size, $item->color]);
            $variant = $variantParts ? '<br><span style="font-size:11px;color:#64748b;">'.e(implode(' · ', array_unique($variantParts))).'</span>' : '';
            $qty = (int) $item->quantity;

            $rows .= '<tr>'
                .'<td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;">'.$title.$variant.'</td>'
                .'<td align="center" style="padding:8px 10px;border-bottom:1px solid #f1f5f9;">'.$qty.'</td>'
                .'<td align="right" style="padding:8px 10px;border-bottom:1px solid #f1f5f9;">'.e(self::money($item->price)).'</td>'
                .'<td align="right" style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-weight:bold;">'.e(self::money((float) $item->price * $qty)).'</td>'
                .'</tr>';
        }

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0 0 0;border:1px solid #e2e8f0;font-size:12px;">'
            .'<tr style="background:#f1f5f9;color:#475569;font-size:11px;">'
            .'<th align="left" style="padding:8px 10px;">Item</th><th align="center" style="padding:8px 10px;">Qty</th>'
            .'<th align="right" style="padding:8px 10px;">Unit price</th><th align="right" style="padding:8px 10px;">Total</th></tr>'
            .$rows.'</table>';
    }

    protected static function summaryTable(Order $order): string
    {
        $rows = [['Subtotal', self::money($order->subtotal)]];
        if ((float) $order->discount > 0) {
            $rows[] = ['Discount'.($order->coupon_code ? ' ('.$order->coupon_code.')' : ''), '− '.self::money($order->discount)];
        }
        $rows[] = ['Shipping'.($order->shipping_method_name ? ' ('.$order->shipping_method_name.')' : ''), self::money($order->shipping_cost)];
        if ((float) $order->tax > 0) {
            $rows[] = ['Tax', self::money($order->tax)];
        }

        $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px 0;font-size:13px;">';
        foreach ($rows as [$label, $value]) {
            $html .= '<tr><td align="right" style="padding:6px 10px;color:#64748b;">'.e($label).'</td><td align="right" width="120" style="padding:6px 10px;">'.e($value).'</td></tr>';
        }

        return $html.'<tr><td align="right" style="padding:8px 10px;font-weight:bold;border-top:2px solid #0f4d2c;">Total</td>'
            .'<td align="right" style="padding:8px 10px;font-weight:bold;font-size:15px;color:#0f4d2c;border-top:2px solid #0f4d2c;">'.e(self::money($order->total_price)).'</td></tr></table>';
    }
}
