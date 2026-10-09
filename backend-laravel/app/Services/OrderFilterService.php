<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderFilterService
{
    public const VALID_STATUSES = [
        'pending' => 'Pending',
        'payment_pending' => 'Payment Pending',
        'payment_verification' => 'Payment Verification',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'packed' => 'Packed',
        'shipped' => 'Shipped',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
        'refunded' => 'Refunded',
    ];

    public const VALID_PAYMENT_STATUSES = [
        'pending' => 'Pending',
        'payment_pending' => 'Payment Pending',
        'payment_verification' => 'Payment Verification',
        'verified' => 'Verified',
        'success' => 'Success',
        'failed' => 'Failed',
        'rejected' => 'Rejected',
        'refunded' => 'Refunded',
    ];

    public const VALID_PAYMENT_METHODS = [
        'cod' => 'Cash on Delivery',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'rocket' => 'Rocket',
        'bank' => 'Bank Transfer',
        'sslcommerz' => 'Card / Online Gateway',
    ];

    public const SORTS = [
        'newest' => 'Newest First',
        'oldest' => 'Oldest First',
        'highest_amount' => 'Highest Amount',
        'lowest_amount' => 'Lowest Amount',
        'recently_updated' => 'Recently Updated',
        'customer_name' => 'Customer Name (A-Z)',
        'payment_status' => 'Payment Status',
        'order_status' => 'Order Status',
    ];

    public const DATE_PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'custom' => 'Custom Date Range',
        'month_year' => 'Specific Month & Year',
    ];

    public const QUICK_PRESETS = [
        'all' => 'All Orders',
        'today' => "Today's Orders",
        'pending' => 'Pending Orders',
        'unpaid' => 'Unpaid Orders',
        'ready_to_ship' => 'Ready to Ship',
        'delivered' => 'Delivered Orders',
        'cancelled' => 'Cancelled Orders',
        'failed_emails' => 'Failed Emails',
    ];

    /**
     * Sanitize and extract only recognized filter parameters.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function cleanParams(array $params): array
    {
        $allowedKeys = [
            'search', 'status', 'payment_status', 'payment_method',
            'date_range', 'start_date', 'end_date', 'month', 'year',
            'customer_type', 'customer_name', 'email', 'phone',
            'min_amount', 'max_amount', 'shipping_method', 'delivery_area',
            'order_id', 'invoice_number', 'product_search', 'sku',
            'failed_emails', 'missing_info', 'attention_required',
            'preset', 'sort',
        ];

        $cleaned = [];
        foreach ($allowedKeys as $key) {
            if (isset($params[$key]) && $params[$key] !== '' && $params[$key] !== null) {
                $cleaned[$key] = is_string($params[$key]) ? trim($params[$key]) : $params[$key];
            }
        }

        return $cleaned;
    }

    /**
     * Check if non-default filter parameters are present.
     *
     * @param  array<string, mixed>  $params
     */
    public static function hasActiveFilters(array $params): bool
    {
        $clean = self::cleanParams($params);
        unset($clean['sort'], $clean['page']);

        if (isset($clean['preset']) && $clean['preset'] === 'all') {
            unset($clean['preset']);
        }

        return ! empty($clean);
    }

    /**
     * Apply all search and filter conditions to the query.
     *
     * @param  Builder<Order>  $query
     * @param  array<string, mixed>  $params
     * @return Builder<Order>
     */
    public static function applyFilters(Builder $query, array $params): Builder
    {
        $clean = self::cleanParams($params);

        // 1. Quick Presets (takes precedence or supplies defaults)
        if (! empty($clean['preset'])) {
            match ($clean['preset']) {
                'today' => $clean['date_range'] = $clean['date_range'] ?? 'today',
                'pending' => $clean['status'] = $clean['status'] ?? 'pending',
                'unpaid' => $query->whereIn('payment_status', ['pending', 'payment_pending', 'payment_verification', 'failed', 'rejected']),
                'ready_to_ship' => $query->whereIn('status', ['confirmed', 'processing', 'packed'])
                    ->where(function (Builder $q) {
                        $q->whereIn('payment_status', ['success', 'verified'])
                            ->orWhere('payment_method', 'cod');
                    }),
                'delivered' => $clean['status'] = $clean['status'] ?? 'delivered',
                'cancelled' => $query->whereIn('status', ['cancelled', 'refunded']),
                'failed_emails' => $query->whereIn('orders.id', function ($sub) {
                    $sub->select('order_id')
                        ->from('email_logs')
                        ->where('status', 'failed')
                        ->whereNotNull('order_id');
                }),
                default => null,
            };
        }

        // 2. Universal Search
        if (! empty($clean['search'])) {
            $s = $clean['search'];
            $query->where(function (Builder $q) use ($s) {
                $q->where('order_id', 'like', "%{$s}%")
                    ->orWhere('invoice_number', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('transaction_id', 'like', "%{$s}%")
                    ->orWhereHas('items', function (Builder $iq) use ($s) {
                        $iq->where('product_title', 'like', "%{$s}%")
                            ->orWhere('product_sku', 'like', "%{$s}%");
                    });
            });
        }

        // 3. Status
        if (! empty($clean['status'])) {
            if (is_array($clean['status'])) {
                $query->whereIn('status', $clean['status']);
            } else {
                $query->where('status', $clean['status']);
            }
        }

        // 4. Payment Status
        if (! empty($clean['payment_status'])) {
            if (is_array($clean['payment_status'])) {
                $query->whereIn('payment_status', $clean['payment_status']);
            } else {
                $query->where('payment_status', $clean['payment_status']);
            }
        }

        // 5. Payment Method
        if (! empty($clean['payment_method'])) {
            $query->where('payment_method', $clean['payment_method']);
        }

        // 6. Customer Filters
        if (! empty($clean['customer_type'])) {
            if ($clean['customer_type'] === 'registered') {
                $query->whereNotNull('user_id');
            } elseif ($clean['customer_type'] === 'guest') {
                $query->whereNull('user_id');
            }
        }

        if (! empty($clean['customer_name'])) {
            $query->where('customer_name', 'like', "%{$clean['customer_name']}%");
        }

        if (! empty($clean['email'])) {
            $query->where('email', 'like', "%{$clean['email']}%");
        }

        if (! empty($clean['phone'])) {
            $p = $clean['phone'];
            $query->where(function (Builder $q) use ($p) {
                $q->where('phone', 'like', "%{$p}%")
                    ->orWhere('alternative_phone', 'like', "%{$p}%");
            });
        }

        // 7. Order Amount
        if (isset($clean['min_amount']) && is_numeric($clean['min_amount'])) {
            $query->where('total_price', '>=', (float) $clean['min_amount']);
        }

        if (isset($clean['max_amount']) && is_numeric($clean['max_amount'])) {
            $query->where('total_price', '<=', (float) $clean['max_amount']);
        }

        // 8. Shipping & Delivery
        if (! empty($clean['shipping_method'])) {
            $sm = $clean['shipping_method'];
            $query->where(function (Builder $q) use ($sm) {
                if (is_numeric($sm)) {
                    $q->where('shipping_method_id', (int) $sm);
                } else {
                    $q->where('shipping_method_name', 'like', "%{$sm}%");
                }
            });
        }

        if (! empty($clean['delivery_area'])) {
            $area = $clean['delivery_area'];
            $query->where(function (Builder $q) use ($area) {
                $q->where('district', 'like', "%{$area}%")
                    ->orWhere('division', 'like', "%{$area}%")
                    ->orWhere('area', 'like', "%{$area}%")
                    ->orWhere('address', 'like', "%{$area}%");
            });
        }

        // 9. Exact identifiers
        if (! empty($clean['order_id'])) {
            $query->where('order_id', 'like', "%{$clean['order_id']}%");
        }

        if (! empty($clean['invoice_number'])) {
            $query->where('invoice_number', 'like', "%{$clean['invoice_number']}%");
        }

        // 10. Product / SKU
        if (! empty($clean['product_search'])) {
            $ps = $clean['product_search'];
            $query->whereHas('items', function (Builder $iq) use ($ps) {
                $iq->where('product_title', 'like', "%{$ps}%")
                    ->orWhere('product_sku', 'like', "%{$ps}%");
            });
        }

        if (! empty($clean['sku'])) {
            $sku = $clean['sku'];
            $query->whereHas('items', fn (Builder $iq) => $iq->where('product_sku', 'like', "%{$sku}%"));
        }

        // 11. Flag Filters
        if (! empty($clean['failed_emails'])) {
            $query->whereIn('orders.id', function ($sub) {
                $sub->select('order_id')
                    ->from('email_logs')
                    ->where('status', 'failed')
                    ->whereNotNull('order_id');
            });
        }

        if (! empty($clean['missing_info'])) {
            $query->where(function (Builder $q) {
                $q->whereNull('email')->orWhere('email', '')
                    ->orWhereNull('phone')->orWhere('phone', '')
                    ->orWhereNull('address')->orWhere('address', '');
            });
        }

        if (! empty($clean['attention_required'])) {
            $query->where(function (Builder $q) {
                $q->whereIn('payment_status', ['payment_verification', 'payment_pending'])
                    ->orWhereIn('orders.id', function ($sub) {
                        $sub->select('order_id')
                            ->from('email_logs')
                            ->where('status', 'failed')
                            ->whereNotNull('order_id');
                    })
                    ->orWhere(function (Builder $sub) {
                        $sub->where('status', 'pending')
                            ->where('created_at', '<', Carbon::now()->subDays(2));
                    });
            });
        }

        // 12. Date and Time
        if (! empty($clean['date_range'])) {
            match ($clean['date_range']) {
                'today' => $query->whereDate('created_at', Carbon::today()),
                'yesterday' => $query->whereDate('created_at', Carbon::yesterday()),
                'last_7_days' => $query->where('created_at', '>=', Carbon::now()->subDays(7)->startOfDay()),
                'last_30_days' => $query->where('created_at', '>=', Carbon::now()->subDays(30)->startOfDay()),
                'this_month' => $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]),
                'last_month' => $query->whereBetween('created_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]),
                'custom' => self::applyCustomDateRange($query, $clean),
                'month_year' => self::applyMonthYear($query, $clean),
                default => null,
            };
        }

        return $query;
    }

    /**
     * Apply custom date range.
     *
     * @param  Builder<Order>  $query
     * @param  array<string, mixed>  $clean
     */
    protected static function applyCustomDateRange(Builder $query, array $clean): void
    {
        if (! empty($clean['start_date'])) {
            try {
                $start = Carbon::parse($clean['start_date'])->startOfDay();
                $query->where('created_at', '>=', $start);
            } catch (\Throwable) {
            }
        }
        if (! empty($clean['end_date'])) {
            try {
                $end = Carbon::parse($clean['end_date'])->endOfDay();
                $query->where('created_at', '<=', $end);
            } catch (\Throwable) {
            }
        }
    }

    /**
     * Apply month and year filter.
     *
     * @param  Builder<Order>  $query
     * @param  array<string, mixed>  $clean
     */
    protected static function applyMonthYear(Builder $query, array $clean): void
    {
        if (! empty($clean['month']) && is_numeric($clean['month'])) {
            $query->whereMonth('created_at', (int) $clean['month']);
        }
        if (! empty($clean['year']) && is_numeric($clean['year'])) {
            $query->whereYear('created_at', (int) $clean['year']);
        }
    }

    /**
     * Apply deterministic sorting to query.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public static function applySorting(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            'highest_amount' => $query->orderBy('total_price', 'desc')->orderBy('id', 'desc'),
            'lowest_amount' => $query->orderBy('total_price', 'asc')->orderBy('id', 'asc'),
            'recently_updated' => $query->select('orders.*')
                ->selectSub(
                    DB::table('order_status_history')
                        ->selectRaw('MAX(created_at)')
                        ->whereColumn('order_status_history.order_id', 'orders.id'),
                    'last_history_at'
                )
                ->orderByRaw('COALESCE(last_history_at, orders.created_at) desc')
                ->orderBy('orders.id', 'desc'),
            'customer_name' => $query->orderBy('customer_name', 'asc')->orderBy('id', 'asc'),
            'payment_status' => $query->orderBy('payment_status', 'asc')->orderBy('id', 'desc'),
            'order_status' => $query->orderBy('status', 'asc')->orderBy('id', 'desc'),
            default => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };
    }

    /**
     * Compute summary metrics for the given filter parameters.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function getSummaryStats(array $params): array
    {
        $query = self::applyFilters(Order::query(), $params);

        $stats = (clone $query)->selectRaw("
            COUNT(*) as total_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN payment_status IN ('success', 'verified') THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN payment_status IN ('pending', 'payment_pending', 'payment_verification', 'failed', 'rejected') THEN 1 ELSE 0 END) as unpaid_count,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
            SUM(CASE WHEN status IN ('cancelled', 'refunded') THEN 1 ELSE 0 END) as cancelled_count,
            SUM(CASE WHEN (payment_status IN ('success', 'verified') OR status = 'delivered') AND status NOT IN ('cancelled', 'refunded') THEN total_price ELSE 0 END) as completed_revenue,
            SUM(CASE WHEN status NOT IN ('cancelled', 'refunded') THEN total_price ELSE 0 END) as active_total_value
        ")->first();

        return [
            'total' => (int) ($stats->total_count ?? 0),
            'pending' => (int) ($stats->pending_count ?? 0),
            'paid' => (int) ($stats->paid_count ?? 0),
            'unpaid' => (int) ($stats->unpaid_count ?? 0),
            'delivered' => (int) ($stats->delivered_count ?? 0),
            'cancelled' => (int) ($stats->cancelled_count ?? 0),
            'completed_revenue' => (float) ($stats->completed_revenue ?? 0),
            'active_total_value' => (float) ($stats->active_total_value ?? 0),
            'is_filtered' => self::hasActiveFilters($params),
        ];
    }

    /**
     * Generate human-readable filter chips with remove URLs.
     *
     * @param  array<string, mixed>  $params
     * @return array<int, array{key: string, label: string, remove_url: string}>
     */
    public static function getActiveFilterChips(array $params): array
    {
        $clean = self::cleanParams($params);
        $chips = [];

        foreach ($clean as $key => $value) {
            if (in_array($key, ['sort', 'page'], true)) {
                continue;
            }

            if ($key === 'preset' && $value === 'all') {
                continue;
            }

            $label = match ($key) {
                'search' => 'Search: "'.$value.'"',
                'status' => 'Status: '.(self::VALID_STATUSES[$value] ?? ucfirst(str_replace('_', ' ', $value))),
                'payment_status' => 'Payment: '.(self::VALID_PAYMENT_STATUSES[$value] ?? ucfirst(str_replace('_', ' ', $value))),
                'payment_method' => 'Method: '.(self::VALID_PAYMENT_METHODS[$value] ?? strtoupper($value)),
                'preset' => 'Preset: '.(self::QUICK_PRESETS[$value] ?? ucfirst(str_replace('_', ' ', $value))),
                'date_range' => 'Date: '.(self::DATE_PRESETS[$value] ?? ucfirst(str_replace('_', ' ', $value))),
                'start_date' => 'From: '.$value,
                'end_date' => 'To: '.$value,
                'month' => 'Month: '.date('F', mktime(0, 0, 0, (int) $value, 1)),
                'year' => 'Year: '.$value,
                'customer_type' => 'Customer: '.ucfirst($value),
                'customer_name' => 'Name: '.$value,
                'email' => 'Email: '.$value,
                'phone' => 'Phone: '.$value,
                'min_amount' => 'Min: ৳'.number_format((float) $value),
                'max_amount' => 'Max: ৳'.number_format((float) $value),
                'shipping_method' => 'Shipping: '.$value,
                'delivery_area' => 'Area: '.$value,
                'order_id' => 'Order: '.$value,
                'invoice_number' => 'Invoice: '.$value,
                'product_search' => 'Product: '.$value,
                'sku' => 'SKU: '.$value,
                'failed_emails' => 'Failed Emails Only',
                'missing_info' => 'Missing Contact Info',
                'attention_required' => 'Attention Required',
                default => ucfirst(str_replace('_', ' ', $key)).': '.$value,
            };

            // Remove this key
            $remaining = $clean;
            unset($remaining[$key]);

            $chips[] = [
                'key' => $key,
                'label' => $label,
                'remove_url' => route('admin.orders.index', $remaining),
            ];
        }

        return $chips;
    }

    /**
     * Compute neighbor navigation (previous, next, position, total)
     * based on active filters and sorting.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function getNavigation(Order $order, array $params): array
    {
        $cleanParams = self::cleanParams($params);
        $sort = $cleanParams['sort'] ?? 'newest';

        $filterQuery = self::applyFilters(Order::query(), $cleanParams);
        $total = (clone $filterQuery)->count();

        // Check if the current order is part of the active filter
        $inFilter = (clone $filterQuery)->where('orders.id', $order->id)->exists();

        // If current order doesn't match the active filter, fall back to default order list navigation
        if (! $inFilter && $total > 0) {
            $effectiveQuery = self::applySorting(Order::query(), $sort);
            $effectiveTotal = Order::count();
        } else {
            $effectiveQuery = self::applySorting($filterQuery, $sort);
            $effectiveTotal = $total;
        }

        $prev = null;
        $next = null;
        $position = 1;

        // Use fast in-memory search for result sets <= 1500, or cursor query for massive sets
        if ($effectiveTotal <= 1500) {
            $list = (clone $effectiveQuery)->select('orders.id', 'orders.order_id')->get();
            $index = $list->search(fn ($item) => $item->id === $order->id);

            if ($index !== false) {
                $position = $index + 1;
                $prev = $index > 0 ? $list[$index - 1] : null;
                $next = $index < ($list->count() - 1) ? $list[$index + 1] : null;
            }
        } else {
            // High-scale deterministic cursor lookup for newest/oldest
            $isDesc = ! in_array($sort, ['oldest', 'lowest_amount', 'customer_name'], true);

            $qBefore = (clone $filterQuery)->where(function ($q) use ($order, $isDesc) {
                if ($isDesc) {
                    $q->where('created_at', '>', $order->created_at)
                        ->orWhere(function ($sub) use ($order) {
                            $sub->where('created_at', '=', $order->created_at)->where('orders.id', '>', $order->id);
                        });
                } else {
                    $q->where('created_at', '<', $order->created_at)
                        ->orWhere(function ($sub) use ($order) {
                            $sub->where('created_at', '=', $order->created_at)->where('orders.id', '<', $order->id);
                        });
                }
            });

            $position = $qBefore->count() + 1;
            $prev = (clone $qBefore)->orderBy('created_at', $isDesc ? 'asc' : 'desc')
                ->orderBy('orders.id', $isDesc ? 'asc' : 'desc')
                ->select('orders.id', 'orders.order_id')
                ->first();

            $qAfter = (clone $filterQuery)->where(function ($q) use ($order, $isDesc) {
                if ($isDesc) {
                    $q->where('created_at', '<', $order->created_at)
                        ->orWhere(function ($sub) use ($order) {
                            $sub->where('created_at', '=', $order->created_at)->where('orders.id', '<', $order->id);
                        });
                } else {
                    $q->where('created_at', '>', $order->created_at)
                        ->orWhere(function ($sub) use ($order) {
                            $sub->where('created_at', '=', $order->created_at)->where('orders.id', '>', $order->id);
                        });
                }
            });

            $next = (clone $qAfter)->orderBy('created_at', $isDesc ? 'desc' : 'asc')
                ->orderBy('orders.id', $isDesc ? 'desc' : 'asc')
                ->select('orders.id', 'orders.order_id')
                ->first();
        }

        $chips = self::getActiveFilterChips($cleanParams);
        $summaryParts = array_map(fn ($c) => $c['label'], array_slice($chips, 0, 3));
        $filterSummary = ! empty($summaryParts) ? implode(' · ', $summaryParts) : null;

        return [
            'previous' => $prev ? [
                'id' => $prev->id,
                'order_id' => $prev->order_id,
                'url' => route('admin.orders.show', array_merge(['id' => $prev->id], $cleanParams)),
            ] : null,
            'next' => $next ? [
                'id' => $next->id,
                'order_id' => $next->order_id,
                'url' => route('admin.orders.show', array_merge(['id' => $next->id], $cleanParams)),
            ] : null,
            'position' => $position,
            'total' => $effectiveTotal,
            'has_active_filters' => self::hasActiveFilters($cleanParams),
            'filter_summary' => $filterSummary,
            'back_to_list_url' => route('admin.orders.index', $cleanParams),
            'matches_filter' => $inFilter,
            'params' => $cleanParams,
        ];
    }
}
