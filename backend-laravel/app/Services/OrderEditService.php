<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEditHistory;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderEditService
{
    /**
     * Finalized statuses where items and financials cannot be altered.
     */
    public const LOCKED_STATUSES = ['delivered', 'cancelled', 'returned', 'refunded'];

    /**
     * Check granular edit permissions for an actor.
     *
     * @return array{
     *     canEditGeneral: bool,
     *     canEditCustomer: bool,
     *     canEditShipping: bool,
     *     canEditItems: bool,
     *     canAdjustFinancials: bool
     * }
     */
    public static function checkPermissions(User $user): array
    {
        $isSuperAdmin = RbacService::isSuperAdmin($user);
        $canEditGeneral = $isSuperAdmin || $user->hasPermission('orders.edit') || $user->hasPermission('orders.update');
        $canEditCustomer = $canEditGeneral || $user->hasPermission('orders.edit_customer');
        $canEditShipping = $canEditGeneral || $user->hasPermission('orders.edit_shipping');
        $canEditItems = $isSuperAdmin || $user->hasPermission('orders.edit_items');
        $canAdjustFinancials = $isSuperAdmin || $user->hasPermission('orders.adjust_financials');

        return compact(
            'canEditGeneral',
            'canEditCustomer',
            'canEditShipping',
            'canEditItems',
            'canAdjustFinancials'
        );
    }

    /**
     * Safely update an order with inventory adjustments, financial recalculations,
     * and comprehensive audit logging.
     *
     * @param  array<string, mixed>  $data
     * @return array{order: Order, changes: array<string, mixed>}
     *
     * @throws ValidationException|Exception
     */
    public static function updateOrder(Order $order, array $data, User $actor): array
    {
        $perms = self::checkPermissions($actor);
        $isLocked = in_array($order->status, self::LOCKED_STATUSES, true);

        // Disallow item or financial edits on locked orders
        if ($isLocked) {
            if (! empty($data['items']) || isset($data['discount']) || isset($data['shipping_cost'])) {
                throw ValidationException::withMessages([
                    'order' => ["Order #{$order->order_id} is {$order->status}. Line items and financials cannot be modified on finalized orders."],
                ]);
            }
        }

        return DB::transaction(function () use ($order, $data, $actor, $perms, $isLocked) {
            // Lock order for update
            $order = Order::with('items')->where('id', $order->id)->lockForUpdate()->firstOrFail();

            $changes = [];
            $reason = trim((string) ($data['reason'] ?? ''));

            // 1. Customer Information Updates
            if ($perms['canEditCustomer']) {
                $customerFields = [
                    'customer_name' => 'Customer Name',
                    'phone' => 'Phone',
                    'alternative_phone' => 'Alternative Phone',
                    'email' => 'Email',
                    'address' => 'Address',
                    'district' => 'District',
                    'division' => 'Division',
                    'upazila' => 'Upazila',
                    'postal_code' => 'Postal Code',
                    'order_note' => 'Customer Note',
                    'admin_notes' => 'Internal Notes',
                ];

                foreach ($customerFields as $field => $label) {
                    if (array_key_exists($field, $data)) {
                        $oldVal = (string) ($order->{$field} ?? '');
                        $newVal = (string) ($data[$field] ?? '');

                        if ($oldVal !== $newVal) {
                            $changes[$field] = [
                                'label' => $label,
                                'old' => $oldVal ?: '—',
                                'new' => $newVal ?: '—',
                            ];
                            $order->{$field} = $newVal !== '' ? $newVal : null;
                        }
                    }
                }
            }

            // 2. Shipping Information Updates
            if ($perms['canEditShipping']) {
                if (! empty($data['shipping_method_id']) && (int) $data['shipping_method_id'] !== (int) $order->shipping_method_id) {
                    $shipMethod = ShippingMethod::find($data['shipping_method_id']);
                    if ($shipMethod) {
                        $oldMethodName = $order->shipping_method_name ?: 'Standard Delivery';
                        $changes['shipping_method'] = [
                            'label' => 'Shipping Method',
                            'old' => $oldMethodName,
                            'new' => $shipMethod->name,
                        ];
                        $order->shipping_method_id = $shipMethod->id;
                        $order->shipping_method_name = $shipMethod->name;

                        // Only auto-update shipping cost from method if actor cannot adjust financials
                        if (! $perms['canAdjustFinancials'] && ! isset($data['shipping_cost'])) {
                            if ((float) $order->shipping_cost !== (float) $shipMethod->charge) {
                                $changes['shipping_cost'] = [
                                    'label' => 'Shipping Cost',
                                    'old' => '৳'.number_format($order->shipping_cost, 2),
                                    'new' => '৳'.number_format($shipMethod->charge, 2),
                                ];
                                $order->shipping_cost = (float) $shipMethod->charge;
                            }
                        }
                    }
                }

                if (array_key_exists('courier_tracking_number', $data)) {
                    $oldTracking = (string) ($order->courier_tracking_number ?? '');
                    $newTracking = (string) ($data['courier_tracking_number'] ?? '');
                    if ($oldTracking !== $newTracking) {
                        $changes['courier_tracking_number'] = [
                            'label' => 'Courier Tracking',
                            'old' => $oldTracking ?: '—',
                            'new' => $newTracking ?: '—',
                        ];
                        $order->courier_tracking_number = $newTracking !== '' ? $newTracking : null;
                    }
                }
            }

            // 3. Line Items and Inventory Updates (Subject to orders.edit_items)
            $itemsModified = false;
            if ($perms['canEditItems'] && ! $isLocked && isset($data['items']) && is_array($data['items'])) {
                if (empty($data['items'])) {
                    throw ValidationException::withMessages([
                        'items' => ['An order must have at least one line item.'],
                    ]);
                }

                $existingItems = $order->items->keyBy('id');
                $retainedItemIds = [];
                $itemChangesSummary = [];

                foreach ($data['items'] as $itemData) {
                    $itemId = ! empty($itemData['item_id']) ? (int) $itemData['item_id'] : null;
                    $productId = (int) ($itemData['product_id'] ?? 0);
                    $variantId = ! empty($itemData['variant_id']) ? (int) $itemData['variant_id'] : null;
                    $newQty = max(1, (int) ($itemData['quantity'] ?? 1));

                    $product = Product::lockForUpdate()->find($productId);
                    if (! $product) {
                        throw ValidationException::withMessages([
                            'items' => ["Product ID #{$productId} could not be found."],
                        ]);
                    }

                    $variant = $variantId ? ProductVariant::lockForUpdate()->find($variantId) : null;
                    $isUnlimited = (bool) $product->unlimited_stock;

                    if ($itemId && $existingItems->has($itemId)) {
                        // Existing item modification
                        /** @var OrderItem $existingItem */
                        $existingItem = $existingItems->get($itemId);
                        $retainedItemIds[] = $itemId;

                        $oldQty = (int) $existingItem->quantity;
                        $oldPrice = (float) $existingItem->price;
                        $newPrice = ($perms['canAdjustFinancials'] && isset($itemData['price']))
                            ? max(0, (float) $itemData['price'])
                            : $oldPrice;

                        // Check stock delta
                        $deltaQty = $newQty - $oldQty;
                        if ($deltaQty !== 0) {
                            $itemsModified = true;
                            if ($deltaQty > 0 && ! $isUnlimited) {
                                // Increasing quantity: ensure enough stock exists
                                if ($product->stock < $deltaQty) {
                                    throw ValidationException::withMessages([
                                        'items' => ["Insufficient stock for {$product->title}. Only {$product->stock} available, but +{$deltaQty} requested."],
                                    ]);
                                }
                                if ($variant && $variant->stock < $deltaQty) {
                                    throw ValidationException::withMessages([
                                        'items' => ["Insufficient variant stock for {$product->title} ({$variant->name}). Only {$variant->stock} available."],
                                    ]);
                                }

                                Product::where('id', $productId)->decrement('stock', $deltaQty);
                                if ($variantId) {
                                    ProductVariant::where('id', $variantId)->decrement('stock', $deltaQty);
                                }
                            } elseif ($deltaQty < 0 && ! $isUnlimited) {
                                // Decreasing quantity: restore released stock
                                $releaseQty = abs($deltaQty);
                                Product::where('id', $productId)->increment('stock', $releaseQty);
                                if ($variantId) {
                                    ProductVariant::where('id', $variantId)->increment('stock', $releaseQty);
                                }
                            }

                            $itemChangesSummary[] = "{$existingItem->product_title}: qty {$oldQty} → {$newQty}";
                        }

                        if ($perms['canAdjustFinancials'] && $newPrice !== $oldPrice) {
                            $itemsModified = true;
                            $itemChangesSummary[] = "{$existingItem->product_title}: price ৳{$oldPrice} → ৳{$newPrice}";
                        }

                        $existingItem->update([
                            'quantity' => $newQty,
                            'price' => $newPrice,
                        ]);
                    } else {
                        // Brand new item added to existing order
                        $itemsModified = true;
                        if (! $isUnlimited) {
                            if ($product->stock < $newQty) {
                                throw ValidationException::withMessages([
                                    'items' => ["Insufficient stock to add {$product->title}. Only {$product->stock} available."],
                                ]);
                            }
                            if ($variant && $variant->stock < $newQty) {
                                throw ValidationException::withMessages([
                                    'items' => ["Insufficient stock to add {$product->title} ({$variant->name}). Only {$variant->stock} available."],
                                ]);
                            }

                            Product::where('id', $productId)->decrement('stock', $newQty);
                            if ($variantId) {
                                ProductVariant::where('id', $variantId)->decrement('stock', $newQty);
                            }
                        }

                        $unitPrice = ($perms['canAdjustFinancials'] && isset($itemData['price']))
                            ? max(0, (float) $itemData['price'])
                            : (float) ($variant?->price ?? ($product->sale_price ?: $product->price));

                        $newItem = OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $productId,
                            'variant_id' => $variantId,
                            'product_title' => $product->title,
                            'product_sku' => $variant?->sku ?: $product->sku,
                            'variant_name' => $variant?->name,
                            'size' => $variant?->size,
                            'color' => $variant?->color,
                            'quantity' => $newQty,
                            'price' => $unitPrice,
                        ]);

                        $retainedItemIds[] = $newItem->id;
                        $itemChangesSummary[] = "Added item: {$product->title} (x{$newQty} @ ৳{$unitPrice})";
                    }
                }

                // Remove deleted items and release stock back to inventory
                foreach ($existingItems as $existingId => $existingItem) {
                    if (! in_array($existingId, $retainedItemIds, true)) {
                        $itemsModified = true;
                        $parentProduct = Product::find($existingItem->product_id);
                        if ($parentProduct && ! $parentProduct->unlimited_stock) {
                            Product::where('id', $existingItem->product_id)->increment('stock', $existingItem->quantity);
                            if ($existingItem->variant_id) {
                                ProductVariant::where('id', $existingItem->variant_id)->increment('stock', $existingItem->quantity);
                            }
                        }

                        $itemChangesSummary[] = "Removed item: {$existingItem->product_title} (x{$existingItem->quantity})";
                        $existingItem->delete();
                    }
                }

                if (! empty($itemChangesSummary)) {
                    $changes['order_items'] = [
                        'label' => 'Order Items',
                        'old' => 'Modified line items',
                        'new' => implode('; ', $itemChangesSummary),
                    ];
                }
            }

            // 4. Financial Calculations & Totals Recalculation
            // Recalculate subtotal from fresh items collection
            $freshSubtotal = (float) OrderItem::where('order_id', $order->id)->sum(DB::raw('quantity * price'));
            if ((float) $order->subtotal !== $freshSubtotal) {
                $changes['subtotal'] = [
                    'label' => 'Subtotal',
                    'old' => '৳'.number_format($order->subtotal, 2),
                    'new' => '৳'.number_format($freshSubtotal, 2),
                ];
                $order->subtotal = $freshSubtotal;
            }

            // Financial overrides (subject to orders.adjust_financials)
            if ($perms['canAdjustFinancials'] && ! $isLocked) {
                if (isset($data['shipping_cost'])) {
                    $newShipCost = max(0, (float) $data['shipping_cost']);
                    if ((float) $order->shipping_cost !== $newShipCost) {
                        $changes['shipping_cost'] = [
                            'label' => 'Shipping Cost',
                            'old' => '৳'.number_format($order->shipping_cost, 2),
                            'new' => '৳'.number_format($newShipCost, 2),
                        ];
                        $order->shipping_cost = $newShipCost;
                    }
                }

                if (isset($data['discount'])) {
                    $newDiscount = min($order->subtotal, max(0, (float) $data['discount']));
                    if ((float) $order->discount !== $newDiscount) {
                        $changes['discount'] = [
                            'label' => 'Discount',
                            'old' => '৳'.number_format($order->discount, 2),
                            'new' => '৳'.number_format($newDiscount, 2),
                        ];
                        $order->discount = $newDiscount;
                    }
                }
            }

            // Total price is calculated strictly from subtotal - discount + shipping_cost + tax
            $newTotalPrice = max(0, $order->subtotal - $order->discount + $order->shipping_cost + $order->tax);
            if ((float) $order->total_price !== (float) $newTotalPrice) {
                $changes['total_price'] = [
                    'label' => 'Total Price',
                    'old' => '৳'.number_format($order->total_price, 2),
                    'new' => '৳'.number_format($newTotalPrice, 2),
                ];
                $order->total_price = $newTotalPrice;

                // Check prepaid discrepancy
                if ($order->payment_method !== 'cod' && $order->amount_sent !== null && (float) $order->amount_sent > 0) {
                    $discrepancy = $newTotalPrice - (float) $order->amount_sent;
                    if (abs($discrepancy) >= 0.01) {
                        $changes['payment_discrepancy'] = [
                            'label' => 'Payment Discrepancy Flag',
                            'old' => 'Paid matches total',
                            'new' => 'Difference of ৳'.number_format($discrepancy, 2).' compared to amount sent',
                        ];
                    }
                }
            }

            // Require reason if items or financials were changed
            if (($itemsModified || isset($changes['discount']) || isset($changes['shipping_cost']) || isset($changes['total_price'])) && empty($reason)) {
                throw ValidationException::withMessages([
                    'reason' => ['A reason is required when modifying order items or financial figures.'],
                ]);
            }

            $order->save();

            // 5. Audit Logging
            if (! empty($changes)) {
                $effectiveReason = $reason ?: 'Order details updated by staff.';

                // Store in dedicated OrderEditHistory table
                OrderEditHistory::create([
                    'order_id' => $order->id,
                    'user_id' => $actor->id,
                    'user_name' => $actor->name,
                    'action' => 'order_edited',
                    'reason' => $effectiveReason,
                    'changes' => $changes,
                    'ip_address' => request()->ip(),
                ]);

                // Bridge to ActivityLoggerService
                ActivityLoggerService::logOrder(
                    'order.edited',
                    $order,
                    "Order #{$order->order_id} edited by {$actor->name}: {$effectiveReason}",
                    [
                        'actor' => $actor,
                        'source' => 'admin',
                        'oldValues' => array_map(fn ($c) => $c['old'], $changes),
                        'newValues' => array_map(fn ($c) => $c['new'], $changes),
                        'metadata' => [
                            'reason' => $effectiveReason,
                            'changedFields' => array_keys($changes),
                        ],
                    ]
                );
            }

            return [
                'order' => $order,
                'changes' => $changes,
            ];
        });
    }
}
