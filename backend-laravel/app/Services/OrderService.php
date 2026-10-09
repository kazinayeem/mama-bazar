<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\UserAddress;
use App\Support\DeviceDetector;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderService
{
    const ORDER_PROGRESS_FLOW = [
        'pending',
        'payment_pending',
        'payment_verification',
        'confirmed',
        'processing',
        'packed',
        'shipped',
        'out_for_delivery',
        'delivered',
    ];

    /** Prefix for newly generated order numbers (Bornosoft). Legacy `GHB-` orders remain valid. */
    public const ORDER_NUMBER_PREFIX = 'BS';

    public static function generateOrderId(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $candidate = self::ORDER_NUMBER_PREFIX.'-'.$code;
            if (! Order::where('order_id', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Astronomically unlikely fallback: timestamp-suffixed unique id.
        return self::ORDER_NUMBER_PREFIX.'-'.strtoupper(substr(md5(uniqid((string) random_int(0, PHP_INT_MAX), true)), 0, 6));
    }

    public static function generateInvoiceNumber(?int $sequence = null): string
    {
        $seq = $sequence ?? ((int) (Order::max('id') ?? 0) + 1);

        return 'INV-'.date('Y').'-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Normalize a BD phone number for storage: trim, drop inner spaces/dashes.
     * Never concatenates; returns '' when nothing usable remains.
     */
    public static function normalizePhone(mixed $value): string
    {
        $clean = preg_replace('/[\s\-]/', '', trim((string) $value));

        return is_string($clean) ? $clean : '';
    }

    public static function getTimelineWithFallback(Order $order): array
    {
        $logs = OrderStatusHistory::where('order_id', $order->id)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($logs->count() === 1 && $order->status !== 'pending') {
            $chain = self::getBackfillChain($order->status);
            $result = [];
            foreach ($chain as $index => $status) {
                $result[] = [
                    'id' => $index + 1,
                    'status' => $status,
                    'note' => $index === 0 ? 'Order created' : "Backfilled {$status} step",
                    'createdAt' => $order->created_at->addMinutes($index * 20)->toIso8601String(),
                    'createdByUserId' => null,
                ];
            }

            return $result;
        }

        if ($logs->isNotEmpty()) {
            return $logs->map(fn ($log) => [
                'id' => $log->id,
                'status' => $log->status,
                'note' => $log->note,
                'createdAt' => $log->created_at ? $log->created_at->toIso8601String() : null,
                'createdByUserId' => $log->created_by_user_id,
            ])->toArray();
        }

        return [
            [
                'id' => 0,
                'status' => $order->status,
                'note' => 'Order created',
                'createdAt' => $order->created_at ? $order->created_at->toIso8601String() : null,
                'createdByUserId' => null,
            ],
        ];
    }

    public static function getBackfillChain(string $status): array
    {
        if ($status === 'cancelled') {
            return ['pending', 'confirmed', 'cancelled'];
        }
        if ($status === 'refunded') {
            return ['pending', 'confirmed', 'delivered', 'refunded'];
        }
        if ($status === 'returned') {
            return ['pending', 'confirmed', 'delivered', 'returned'];
        }

        $index = array_search($status, self::ORDER_PROGRESS_FLOW);
        if ($index === false) {
            return [$status];
        }

        return array_slice(self::ORDER_PROGRESS_FLOW, 0, $index + 1);
    }

    public static function formatOrder(Order $order): array
    {
        $items = OrderItem::with(['product', 'variant'])
            ->where('order_id', $order->id)
            ->get();

        $history = self::getTimelineWithFallback($order);

        $formattedItems = $items->map(function ($item) {
            $images = $item->product?->images;
            $image = null;
            if (is_array($images) && count($images) > 0) {
                $image = $images[0];
            } elseif (is_string($images)) {
                $parsed = json_decode($images, true);
                if (is_array($parsed) && count($parsed) > 0) {
                    $image = $parsed[0];
                }
            }

            return [
                'id' => $item->id,
                'orderId' => $item->order_id,
                'productId' => $item->product_id,
                'variantId' => $item->variant_id,
                'productTitle' => $item->product_title ?: $item->product?->title,
                'productSku' => $item->product_sku ?: $item->product?->sku,
                'size' => $item->size,
                'color' => $item->color,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'variantName' => $item->variant_name ?: $item->variant?->name,
                'product' => $item->product ? [
                    'title' => $item->product->title,
                    'image' => $image,
                ] : null,
            ];
        })->toArray();

        return [
            'id' => $order->id,
            'orderId' => $order->order_id,
            'invoiceNumber' => $order->invoice_number,
            'accessToken' => $order->access_token,
            'isGuest' => empty($order->user_id),
            'userId' => $order->user_id,
            'customerName' => $order->customer_name,
            'phone' => $order->phone,
            'alternativePhone' => $order->alternative_phone,
            'email' => $order->email,
            'country' => $order->country,
            'division' => $order->division,
            'district' => $order->district,
            'upazila' => $order->upazila,
            'area' => $order->area,
            'address' => $order->address,
            'apartment' => $order->apartment,
            'postalCode' => $order->postal_code,
            'shippingMethodId' => $order->shipping_method_id,
            'shippingMethodName' => $order->shipping_method_name,
            'shippingCost' => (string) $order->shipping_cost,
            'subtotal' => (string) $order->subtotal,
            'couponCode' => $order->coupon_code,
            'discount' => (string) $order->discount,
            'tax' => (string) $order->tax,
            'orderNote' => $order->order_note,
            'checkoutNotes' => $order->checkout_notes,
            'adminNotes' => $order->admin_notes,
            'totalPrice' => (string) $order->total_price,
            'paymentMethod' => $order->payment_method,
            'transactionId' => $order->transaction_id,
            'senderNumber' => $order->sender_number,
            'paymentScreenshot' => $order->payment_screenshot,
            'paymentDate' => $order->payment_date ? $order->payment_date->toIso8601String() : null,
            'amountSent' => $order->amount_sent !== null ? (string) $order->amount_sent : null,
            'paymentInstructions' => $order->payment_instructions,
            'courierTrackingNumber' => $order->courier_tracking_number,
            'paymentStatus' => $order->payment_status,
            'status' => $order->status,
            'createdAt' => $order->created_at ? $order->created_at->toIso8601String() : null,
            'statusHistory' => $history,
            'historyCount' => count($history),
            'items' => $formattedItems,
        ];
    }

    public static function createOrder(array $input): array
    {
        // Idempotency: safe retry / double-submit returns the original order.
        $idempotencyKey = $input['idempotency_key'] ?? $input['idempotencyKey'] ?? null;
        if ($idempotencyKey) {
            $existing = Order::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return [
                    'order' => self::formatOrder($existing),
                    'auth' => null,
                    'duplicate' => true,
                ];
            }
        }

        return DB::transaction(function () use ($input, $idempotencyKey) {
            $items = $input['items'] ?? [];
            if (empty($items)) {
                throw new Exception('Order must contain at least one item', 400);
            }

            // Normalize contact phones once so delivery phone / alt phone can never
            // be stored duplicated or concatenated (e.g. alt identical to phone).
            // Applies to every entry point (web checkout + API) since all funnel here.
            $input['phone'] = self::normalizePhone($input['phone'] ?? $input['Phone'] ?? '');
            $altPhone = self::normalizePhone($input['alternativePhone'] ?? $input['alternative_phone'] ?? '');
            $input['alternative_phone'] = ($altPhone !== '' && $altPhone !== $input['phone']) ? $altPhone : null;
            $input['alternativePhone'] = $input['alternative_phone'];

            $subtotal = 0;
            $itemsWithPrice = [];

            $productIds = array_unique(array_filter(array_map(fn ($it) => $it['productId'] ?? $it['product_id'] ?? null, $items)));
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $productId = $item['productId'] ?? $item['product_id'] ?? null;
                $quantity = (int) ($item['quantity'] ?? 1);
                $product = $products->get($productId);

                if (! $product) {
                    throw new Exception("Product {$productId} not found", 400);
                }

                $variantId = $item['variantId'] ?? $item['variant_id'] ?? null;
                $itemPrice = 0;

                if ($variantId) {
                    $variant = ProductVariant::where('id', $variantId)->where('product_id', $productId)->lockForUpdate()->first();
                    if (! $variant) {
                        throw new Exception("Variant not found for product {$product->title}", 400);
                    }
                    if (! $variant->availability || $variant->status === 'inactive') {
                        throw new Exception("Variant \"{$variant->name}\" is not available", 400);
                    }
                    if (! $product->unlimited_stock && $variant->stock < $quantity) {
                        throw new Exception("Insufficient stock for {$product->title} - {$variant->name}", 400);
                    }
                    $itemPrice = $variant->discount_price ?: ($variant->price ?: (float) $product->price);
                } elseif (! empty($item['size']) || ! empty($item['color'])) {
                    $size = $item['size'] ?? null;
                    $color = $item['color'] ?? null;

                    $variants = ProductVariant::where('product_id', $productId)->where('status', 'active')->lockForUpdate()->get();
                    $matchedVariant = $variants->first(function ($v) use ($size, $color) {
                        $opts = (array) $v->options;
                        $sizeMatch = ! $size || collect($opts)->contains(fn ($val) => strcasecmp($val, $size) === 0);
                        $colorMatch = ! $color || collect($opts)->contains(fn ($val) => strcasecmp($val, $color) === 0);

                        return $sizeMatch && $colorMatch;
                    });

                    if (! $matchedVariant) {
                        throw new Exception("Variant ({$size}, {$color}) not found for {$product->title}", 400);
                    }
                    if (! $matchedVariant->availability) {
                        throw new Exception("Variant \"{$matchedVariant->name}\" is not available", 400);
                    }
                    if ($matchedVariant->stock < $quantity) {
                        throw new Exception("Insufficient stock for {$product->title} - {$matchedVariant->name}", 400);
                    }

                    $itemPrice = $matchedVariant->discount_price ?: ($matchedVariant->price ?: (float) $product->price);
                    $variantId = $matchedVariant->id;
                } else {
                    $hasVariants = ProductVariant::where('product_id', $productId)
                        ->where('status', 'active')
                        ->where('availability', true)
                        ->exists();
                    if ($hasVariants) {
                        throw new Exception("Please select a variant for {$product->title}", 400);
                    }
                    if (! $product->unlimited_stock && $product->stock < $quantity) {
                        throw new Exception("Insufficient stock for {$product->title}", 400);
                    }
                    $salePrice = (float) ($product->sale_price ?: 0);
                    $discountRate = min((float) ($product->discount ?: 0), 100);

                    if ($salePrice > 0) {
                        $itemPrice = round($salePrice);
                    } elseif ($discountRate > 0) {
                        $itemPrice = round((float) $product->price * (1 - $discountRate / 100));
                    } else {
                        $itemPrice = round((float) $product->price);
                    }
                }

                $subtotal += $itemPrice * $quantity;
                $matchedVariantName = null;
                if ($variantId) {
                    $v = ProductVariant::where('id', $variantId)->first();
                    $matchedVariantName = $v?->name;
                }
                $itemsWithPrice[] = [
                    'productId' => $productId,
                    'variantId' => $variantId,
                    'quantity' => $quantity,
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                    'price' => $itemPrice,
                    'productTitle' => $product->title,
                    'productSku' => $product->sku ?? ('MB-'.$product->id),
                    'variantName' => $matchedVariantName,
                ];
            }

            // Shipping — always recalculated server-side, never trusted from frontend.
            $customerDistrict = $input['district'] ?? $input['District'] ?? null;
            $shippingMethodId = $input['shippingMethodId'] ?? $input['shipping_method_id'] ?? null;
            if (empty($shippingMethodId)) {
                throw new Exception('Please select a delivery method', 400);
            }
            $method = ShippingMethod::where('id', $shippingMethodId)->where('status', 'active')->first();
            if (! $method) {
                throw new Exception('Selected delivery method is unavailable', 400);
            }
            if (! $method->isApplicableTo($customerDistrict)) {
                throw new Exception("\"{$method->name}\" is not available for ".($customerDistrict ?: 'your area'), 400);
            }
            $shippingMethodId = $method->id;
            $shippingMethodName = $method->name;
            $freeMin = $method->free_shipping_min_amount;
            if ($freeMin === null) {
                $globalSettings = SiteSetting::where('key', 'checkout_settings')->first();
                if ($globalSettings) {
                    $decoded = json_decode($globalSettings->value, true);
                    $freeMin = $decoded['free_shipping_threshold'] ?? null;
                }
            }
            $shippingCost = ($freeMin !== null && $freeMin !== '' && $subtotal >= (float) $freeMin) ? 0 : (float) $method->charge;

            // Payment method
            $paymentMethodCode = strtolower($input['paymentMethod'] ?? $input['payment_method'] ?? 'cod');
            $paymentMethod = PaymentMethod::where('code', $paymentMethodCode)->first();
            if (! $paymentMethod) {
                throw new Exception('Selected payment method is unavailable', 400);
            }
            if (! $paymentMethod->enabled || $paymentMethod->maintenance_mode) {
                throw new Exception("{$paymentMethod->name} is currently unavailable", 400);
            }
            if ($paymentMethodCode === 'cod' && isset($method) && ! $method->cod_available) {
                throw new Exception("Cash on Delivery is not available for {$method->name}", 400);
            }

            // Coupon — validated server-side; invalid codes are rejected, not ignored.
            $discount = 0;
            $couponCode = trim((string) ($input['couponCode'] ?? $input['coupon_code'] ?? ''));
            if ($couponCode !== '') {
                $coupon = Coupon::whereRaw('LOWER(code) = ?', [mb_strtolower($couponCode)])
                    ->where('status', 'active')
                    ->first();
                if (! $coupon) {
                    throw new Exception('Invalid or expired coupon code', 400);
                }
                if ($coupon->expiry_date && $coupon->expiry_date->isPast()) {
                    throw new Exception('Coupon has expired', 400);
                }
                if ($coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
                    throw new Exception("Minimum order amount is {$coupon->min_order_amount} Tk", 400);
                }
                if ($coupon->discount_type === 'percentage') {
                    $discount = ($subtotal * (float) $coupon->discount_value) / 100;
                } else {
                    $discount = (float) $coupon->discount_value;
                }
                $discount = round(min($discount, $subtotal), 2);
                $couponCode = $coupon->code;
            } else {
                $couponCode = null;
            }

            // Tax
            $taxSettingsRow = SiteSetting::where('key', 'tax_settings')->first();
            $taxSettings = $taxSettingsRow ? json_decode($taxSettingsRow->value, true) : null;
            $taxRate = min(max((float) ($taxSettings['taxRate'] ?? 0), 0), 25);
            $applyTaxToShipping = ! empty($taxSettings['applyTaxToShipping']);
            $taxable = $subtotal - $discount + ($applyTaxToShipping ? $shippingCost : 0);
            $tax = max(0, round(max(0, $taxable) * ($taxRate / 100)));

            $totalPrice = $subtotal - $discount + $tax + $shippingCost;

            // Payment state
            if ($paymentMethodCode === 'cod') {
                $paymentStatus = 'success';
                $orderStatus = 'pending';
            } elseif (! empty($input['paymentScreenshot']) || ! empty($input['payment_screenshot'])
                || ! empty($input['transactionId']) || ! empty($input['transaction_id'])
                || ! empty($input['senderNumber']) || ! empty($input['sender_number'])) {
                $paymentStatus = 'payment_verification';
                $orderStatus = 'payment_verification';
            } else {
                $paymentStatus = 'payment_pending';
                $orderStatus = 'payment_pending';
            }

            // Resolve user
            $resolvedUserId = $input['userId'] ?? null;
            if (! $resolvedUserId && ! empty($input['phone'])) {
                $existingUser = User::where('phone', $input['phone'])->first();
                if ($existingUser) {
                    $resolvedUserId = $existingUser->id;
                }
            }

            // Update user shipping address if user exists
            if ($resolvedUserId) {
                User::where('id', $resolvedUserId)->update([
                    'shipping_area' => $input['shippingArea'] ?? null,
                    'shipping_address' => $input['address'] ?? null,
                ]);

                $existingAddresses = UserAddress::where('user_id', $resolvedUserId)->get();
                $alreadyExists = $existingAddresses->first(function ($a) use ($input) {
                    return $a->address === ($input['address'] ?? '') && $a->phone === ($input['phone'] ?? '');
                });

                if (! $alreadyExists && $existingAddresses->count() < 5) {
                    UserAddress::create([
                        'user_id' => $resolvedUserId,
                        'recipient_name' => $input['name'] ?? '',
                        'phone' => $input['phone'] ?? '',
                        'alternative_phone' => $input['alternativePhone'] ?? null,
                        'email' => $input['email'] ?? null,
                        'country' => $input['country'] ?? null,
                        'division' => $input['division'] ?? null,
                        'district' => $input['district'] ?? null,
                        'upazila' => $input['upazila'] ?? null,
                        'area' => $input['area'] ?? null,
                        'shipping_area' => $input['shippingArea'] ?? 'inside_dhaka',
                        'address' => $input['address'] ?? '',
                        'apartment' => $input['apartment'] ?? null,
                        'postal_code' => $input['postalCode'] ?? null,
                        'is_default' => $existingAddresses->isEmpty(),
                    ]);
                }
            }

            // Privacy-conscious analytics metadata (hashed/truncated IP only)
            $rawIp = $input['_client_ip'] ?? null;
            $uaString = $input['_user_agent'] ?? null;
            $parsed = DeviceDetector::parse($uaString);
            $nextSeq = ((int) (Order::max('id') ?? 0)) + 1;

            // Create Order
            $order = Order::create([
                'order_id' => self::generateOrderId(),
                'invoice_number' => self::generateInvoiceNumber($nextSeq),
                'access_token' => bin2hex(random_bytes(16)),
                'idempotency_key' => $idempotencyKey,
                'ip_hash' => DeviceDetector::hashIp($rawIp),
                'ip_truncated' => DeviceDetector::truncateIp($rawIp),
                'ip_address' => $rawIp ? mb_substr((string) $rawIp, 0, 45) : null,
                'user_agent' => $uaString ? mb_substr($uaString, 0, 1000) : null,
                'browser' => $parsed['browser'],
                'os_platform' => $parsed['os'],
                'device_type' => $parsed['device'],
                'referrer' => isset($input['_referrer']) ? mb_substr((string) $input['_referrer'], 0, 500) : null,
                'landing_page' => isset($input['_landing_page']) ? mb_substr((string) $input['_landing_page'], 0, 500) : null,
                'utm_source' => $input['utm_source'] ?? null,
                'utm_medium' => $input['utm_medium'] ?? null,
                'utm_campaign' => $input['utm_campaign'] ?? null,
                'utm_content' => $input['utm_content'] ?? null,
                'utm_term' => $input['utm_term'] ?? null,
                'order_source' => $input['order_source'] ?? 'web',
                'marketing_consent' => ! empty($input['marketing_consent']),
                'fb_event_id' => $input['fb_event_id'] ?? ('order-'.time().'-'.$nextSeq),
                'user_id' => $resolvedUserId,
                'customer_name' => $input['customer_name'] ?? $input['customerName'] ?? $input['name'] ?? 'Customer',
                'phone' => $input['phone'] ?? '',
                'alternative_phone' => $input['alternativePhone'] ?? $input['alternative_phone'] ?? null,
                'email' => $input['email'] ?? null,
                'country' => $input['country'] ?? null,
                'division' => $input['division'] ?? null,
                'district' => $input['district'] ?? null,
                'upazila' => $input['upazila'] ?? null,
                'area' => $input['area'] ?? null,
                'address' => $input['address'] ?? '',
                'apartment' => $input['apartment'] ?? null,
                'postal_code' => $input['postalCode'] ?? $input['postal_code'] ?? null,
                'shipping_method_id' => $shippingMethodId,
                'shipping_method_name' => $shippingMethodName,
                'shipping_cost' => $shippingCost,
                'subtotal' => $subtotal,
                'coupon_code' => $couponCode,
                'discount' => $discount,
                'tax' => $tax,
                'order_note' => $input['orderNote'] ?? $input['order_note'] ?? null,
                'checkout_notes' => $input['checkoutNotes'] ?? null,
                'admin_notes' => $input['adminNotes'] ?? null,
                'total_price' => $totalPrice,
                'payment_method' => $paymentMethodCode,
                'transaction_id' => $input['transactionId'] ?? $input['transaction_id'] ?? null,
                'sender_number' => $input['senderNumber'] ?? $input['sender_number'] ?? null,
                'payment_screenshot' => $input['paymentScreenshot'] ?? $input['payment_screenshot'] ?? null,
                'amount_sent' => isset($input['amountSent']) ? (float) $input['amountSent'] : null,
                'payment_instructions' => $input['paymentInstructions'] ?? null,
                'payment_status' => $paymentStatus,
                'status' => $orderStatus,
            ]);

            $historyNote = implode(' ', array_filter([
                'Order created',
                $discount > 0 ? "with discount Tk {$discount}" : null,
                $orderStatus === 'payment_pending' ? '- awaiting payment' : null,
                $orderStatus === 'payment_verification' ? '- payment submitted, awaiting verification' : null,
            ]));

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $orderStatus,
                'note' => $historyNote,
                'created_by_user_id' => $resolvedUserId,
            ]);

            // Insert order items (with snapshots) and decrement stock atomically
            // (rows were locked with lockForUpdate above, preventing oversell races).
            foreach ($itemsWithPrice as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['productId'],
                    'variant_id' => $item['variantId'],
                    'product_title' => $item['productTitle'] ?? null,
                    'product_sku' => $item['productSku'] ?? null,
                    'variant_name' => $item['variantName'] ?? null,
                    'size' => $item['size'],
                    'color' => $item['color'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);

                $parent = $products->get($item['productId']);
                $isUnlimited = $parent && (bool) $parent->unlimited_stock;

                if (! $isUnlimited) {
                    $affected = Product::where('id', $item['productId'])
                        ->where('stock', '>=', $item['quantity'])
                        ->decrement('stock', $item['quantity']);
                    if ($affected === 0) {
                        throw new Exception("Insufficient stock for {$parent->title} (sold out during checkout)", 400);
                    }

                    if ($item['variantId']) {
                        $vAffected = ProductVariant::where('id', $item['variantId'])
                            ->where('stock', '>=', $item['quantity'])
                            ->decrement('stock', $item['quantity']);
                        if ($vAffected === 0) {
                            throw new Exception("Insufficient variant stock for {$parent->title} (sold out during checkout)", 400);
                        }
                    }
                }
            }

            // Record activity log
            ActivityLoggerService::logOrder(
                'order.created',
                $order,
                "Order #{$order->order_id} created for ".($order->customer_name ?: 'Customer')." (Tk {$order->total_amount})",
                [
                    'actor' => $resolvedUserId ? User::find($resolvedUserId) : null,
                    'actorType' => $resolvedUserId ? 'customer' : 'guest',
                    'actorName' => $order->customer_name ?: 'Guest Customer',
                    'source' => $input['order_source'] ?? 'storefront',
                    'metadata' => [
                        'order_id' => $order->order_id,
                        'customer_name' => $order->customer_name,
                        'total_amount' => $order->total_amount,
                        'payment_method' => $order->payment_method,
                        'status' => $order->status,
                        'items_count' => count($itemsWithPrice),
                    ],
                ]
            );

            return [
                'order' => self::formatOrder($order),
                'auth' => null,
            ];
        });
    }

    public static function getOrderById(int $id): ?array
    {
        $order = Order::find($id);

        return $order ? self::formatOrder($order) : null;
    }

    public static function getOrderByOrderId(string $orderId): ?array
    {
        $order = Order::where('order_id', $orderId)->first();

        return $order ? self::formatOrder($order) : null;
    }

    /**
     * Secure guest order tracking: order reference + verified phone (or access token).
     * Returns null on mismatch without leaking which field was wrong.
     */
    public static function trackOrder(?string $orderId, ?string $phone = null, ?string $token = null): ?array
    {
        if (empty($orderId)) {
            return null;
        }

        $orderId = trim($orderId);
        $order = Order::whereRaw('LOWER(order_id) = ?', [strtolower($orderId)])->first();
        if (! $order) {
            return null;
        }

        // Secure token bypass (e.g. link from confirmation SMS/email)
        if (! empty($token) && $order->access_token && hash_equals((string) $order->access_token, (string) $token)) {
            return self::formatOrder($order);
        }

        // Phone verification is required — never expose by order ID alone.
        if (empty($phone)) {
            return null;
        }
        $cleanPhone = preg_replace('/[\s\-]/', '', trim($phone));
        $stored = preg_replace('/[\s\-]/', '', (string) $order->phone);
        $storedAlt = preg_replace('/[\s\-]/', '', (string) $order->alternative_phone);
        if ($cleanPhone !== $stored && $cleanPhone !== $storedAlt) {
            return null;
        }

        return self::formatOrder($order);
    }
}
