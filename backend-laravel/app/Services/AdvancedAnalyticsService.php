<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdvancedAnalyticsService
{
    public const VALID_ORDER_STATUSES = [
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

    public const EXCLUDED_ORDER_STATUSES = [
        'cancelled',
        'refunded',
        'returned',
    ];

    /**
     * Resolve filter parameters from incoming request.
     *
     * @return array<string, mixed>
     */
    public function parseFilters(Request $request): array
    {
        $preset = (string) $request->input('preset', '30d');
        $now = Carbon::now();

        $startDate = null;
        $endDate = null;
        $month = (int) $request->input('month', $now->month);
        $year = (int) $request->input('year', $now->year);

        if ($preset === 'today') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($preset === 'yesterday') {
            $startDate = Carbon::yesterday()->startOfDay();
            $endDate = Carbon::yesterday()->endOfDay();
        } elseif ($preset === '7d') {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif ($preset === '30d') {
            $startDate = Carbon::now()->subDays(29)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif ($preset === 'this_month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfDay();
        } elseif ($preset === 'last_month') {
            $startDate = Carbon::now()->subMonth()->startOfMonth();
            $endDate = Carbon::now()->subMonth()->endOfMonth();
        } elseif ($preset === 'this_quarter') {
            $startDate = Carbon::now()->startOfQuarter();
            $endDate = Carbon::now()->endOfDay();
        } elseif ($preset === 'this_year') {
            $startDate = Carbon::now()->startOfYear();
            $endDate = Carbon::now()->endOfDay();
        } elseif ($preset === 'month_year') {
            $month = max(1, min(12, $month));
            $year = max(2020, min(2035, $year));
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        } elseif ($preset === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
            try {
                $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                if ($startDate->gt($endDate)) {
                    [$startDate, $endDate] = [$endDate, $startDate];
                }
            } catch (\Throwable $e) {
                $startDate = Carbon::now()->subDays(29)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
            }
        } else {
            $preset = '30d';
            $startDate = Carbon::now()->subDays(29)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        // Calculate equivalent previous comparison period
        $daysDiff = max(1, $startDate->diffInDays($endDate) + 1);
        $prevEndDate = $startDate->copy()->subSecond();
        $prevStartDate = $prevEndDate->copy()->subDays($daysDiff - 1)->startOfDay();

        return [
            'preset' => $preset,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'prev_start_date' => $prevStartDate,
            'prev_end_date' => $prevEndDate,
            'month' => $month,
            'year' => $year,
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : null,
            'brand_id' => $request->filled('brand_id') ? (int) $request->input('brand_id') : null,
            'product_id' => $request->filled('product_id') ? (int) $request->input('product_id') : null,
            'product_status' => $request->input('product_status', 'all'),
            'stock_status' => $request->input('stock_status', 'all'),
            'search' => trim((string) $request->input('search', '')),
            'sort_by' => (string) $request->input('sort_by', 'revenue_desc'),
            'per_page' => max(10, min(100, (int) $request->input('per_page', 15))),
            'page' => max(1, (int) $request->input('page', 1)),
        ];
    }

    /**
     * Compute Inventory KPIs.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getInventoryKpis(array $filters): array
    {
        $baseQuery = Product::query();
        $this->applyProductFilters($baseQuery, $filters);

        $totalProducts = (clone $baseQuery)->count();
        $activeProducts = (clone $baseQuery)->where('status', 'active')->where('product_status', 'published')->count();
        $totalUnitsInStock = (int) (clone $baseQuery)->sum('stock');

        $lowStockCount = (clone $baseQuery)
            ->where('stock', '>', 0)
            ->whereRaw('stock <= COALESCE(low_stock_alert, 10)')
            ->count();

        $outOfStockCount = (clone $baseQuery)
            ->where('stock', '<=', 0)
            ->count();

        $overstockedCount = (clone $baseQuery)
            ->where('stock', '>=', 100)
            ->count();

        // Potential retail valuation = sum(stock * (sale_price ?: price))
        $retailValuation = (float) (clone $baseQuery)
            ->where('stock', '>', 0)
            ->selectRaw('COALESCE(SUM(stock * CASE WHEN sale_price IS NOT NULL AND sale_price > 0 THEN sale_price ELSE price END), 0) as val')
            ->value('val');

        // Cost valuation = sum(stock * cost_price) where cost_price > 0
        $productsWithCost = (clone $baseQuery)->where('cost_price', '>', 0)->count();
        $costValuation = (float) (clone $baseQuery)
            ->where('stock', '>', 0)
            ->where('cost_price', '>', 0)
            ->selectRaw('COALESCE(SUM(stock * cost_price), 0) as val')
            ->value('val');

        $costDataStatus = 'unavailable';
        if ($totalProducts > 0) {
            if ($productsWithCost === 0) {
                $costDataStatus = 'unavailable';
            } elseif ($productsWithCost >= $totalProducts) {
                $costDataStatus = 'exact';
            } else {
                $costDataStatus = 'partial';
            }
        }

        $costCoveragePct = $totalProducts > 0 ? round(($productsWithCost / $totalProducts) * 100, 1) : 0;

        // Unrealized gross margin: only calculable when reliable cost data exists
        $unrealizedMargin = null;
        if ($costDataStatus !== 'unavailable' && $retailValuation > 0 && $costValuation > 0) {
            $unrealizedMargin = round((($retailValuation - $costValuation) / $retailValuation) * 100, 1);
        }

        // Slow-moving products valuation: products with stock > 0 and 0 units sold in the period
        $soldProductIds = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$filters['start_date'], $filters['end_date']])
            ->whereNotIn('orders.status', self::EXCLUDED_ORDER_STATUSES)
            ->distinct()
            ->pluck('order_items.product_id')
            ->all();

        $slowMovingRetail = (float) (clone $baseQuery)
            ->where('stock', '>', 0)
            ->whereNotIn('id', $soldProductIds)
            ->selectRaw('COALESCE(SUM(stock * CASE WHEN sale_price IS NOT NULL AND sale_price > 0 THEN sale_price ELSE price END), 0) as val')
            ->value('val');

        $slowMovingCost = (float) (clone $baseQuery)
            ->where('stock', '>', 0)
            ->where('cost_price', '>', 0)
            ->whereNotIn('id', $soldProductIds)
            ->selectRaw('COALESCE(SUM(stock * cost_price), 0) as val')
            ->value('val');

        $slowMovingCount = (clone $baseQuery)
            ->where('stock', '>', 0)
            ->whereNotIn('id', $soldProductIds)
            ->count();

        // Active variants count
        $activeVariantsCount = ProductVariant::where('status', 'active')->where('availability', 1)->count();
        $totalVariantsCount = ProductVariant::count();

        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'active_variants' => $activeVariantsCount,
            'total_variants' => $totalVariantsCount,
            'total_units_in_stock' => $totalUnitsInStock,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'overstocked_count' => $overstockedCount,
            'in_stock_count' => max(0, $totalProducts - $outOfStockCount - $lowStockCount),
            'cost_valuation' => $costDataStatus === 'unavailable' ? null : $costValuation,
            'cost_data_status' => $costDataStatus,
            'products_with_cost' => $productsWithCost,
            'cost_coverage_pct' => $costCoveragePct,
            'retail_valuation' => $retailValuation,
            'unrealized_margin' => $unrealizedMargin,
            'slow_moving_valuation' => $slowMovingRetail,
            'slow_moving_cost' => $costDataStatus === 'unavailable' ? null : $slowMovingCost,
            'slow_moving_count' => $slowMovingCount,
            'snapshot_date' => Carbon::now()->toFormattedDateString().' '.Carbon::now()->format('h:i A'),
        ];
    }

    /**
     * Compute Sales & Profit KPIs for the selected period and previous equivalent period.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getSalesKpis(array $filters): array
    {
        $currentSales = $this->aggregateSalesForPeriod($filters['start_date'], $filters['end_date'], $filters);
        $prevSales = $this->aggregateSalesForPeriod($filters['prev_start_date'], $filters['prev_end_date'], $filters);

        // Calculate growth percentages
        $salesGrowth = $this->calcGrowthPct($currentSales['net_sales'], $prevSales['net_sales']);
        $ordersGrowth = $this->calcGrowthPct($currentSales['orders_count'], $prevSales['orders_count']);
        $unitsGrowth = $this->calcGrowthPct($currentSales['units_sold'], $prevSales['units_sold']);
        $grossSalesGrowth = $this->calcGrowthPct($currentSales['gross_sales'], $prevSales['gross_sales']);

        return array_merge($currentSales, [
            'prev_net_sales' => $prevSales['net_sales'],
            'prev_orders_count' => $prevSales['orders_count'],
            'prev_units_sold' => $prevSales['units_sold'],
            'sales_growth' => $salesGrowth,
            'orders_growth' => $ordersGrowth,
            'units_growth' => $unitsGrowth,
            'gross_sales_growth' => $grossSalesGrowth,
        ]);
    }

    /**
     * Aggregate orders and items for a specific date window.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function aggregateSalesForPeriod(Carbon $startDate, Carbon $endDate, array $filters): array
    {
        $ordersQuery = Order::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        // If category or brand or product filter is active, restrict orders to those matching items
        $productFilterIds = $this->getMatchingProductIds($filters);
        if ($productFilterIds !== null) {
            $ordersQuery->whereExists(function ($q) use ($productFilterIds) {
                $q->select(DB::raw(1))
                    ->from('order_items')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->whereIn('order_items.product_id', $productFilterIds);
            });
        }

        // Gross sales: all placed orders in the period
        $grossSales = (float) (clone $ordersQuery)->sum('total_price');
        $allOrdersCount = (clone $ordersQuery)->count();

        // Net sales: valid orders (non-cancelled, non-refunded, non-returned)
        $validOrdersQuery = (clone $ordersQuery)
            ->whereNotIn('status', self::EXCLUDED_ORDER_STATUSES)
            ->where(function ($q) {
                $q->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'refunded');
            });

        $netSales = (float) (clone $validOrdersQuery)->sum('total_price');
        $validOrdersCount = (clone $validOrdersQuery)->count();
        $discountsTotal = (float) (clone $validOrdersQuery)->sum('discount');

        // Cancelled / Refunded orders
        $cancelledSales = (float) (clone $ordersQuery)->where('status', 'cancelled')->sum('total_price');
        $cancelledCount = (clone $ordersQuery)->where('status', 'cancelled')->count();
        $refundedSales = (float) (clone $ordersQuery)->where(function ($q) {
            $q->where('status', 'refunded')->orWhere('payment_status', 'refunded');
        })->sum('total_price');
        $refundedCount = (clone $ordersQuery)->where(function ($q) {
            $q->where('status', 'refunded')->orWhere('payment_status', 'refunded');
        })->count();

        // Units sold & COGS from order_items
        $itemsQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereNotIn('orders.status', self::EXCLUDED_ORDER_STATUSES)
            ->where(function ($q) {
                $q->whereNull('orders.payment_status')
                    ->orWhere('orders.payment_status', '!=', 'refunded');
            });

        if ($productFilterIds !== null) {
            $itemsQuery->whereIn('order_items.product_id', $productFilterIds);
        }

        $unitsSold = (int) (clone $itemsQuery)->sum('order_items.quantity');

        // Profit calculations: ONLY where product cost_price > 0
        $cogsRow = (clone $itemsQuery)
            ->where('products.cost_price', '>', 0)
            ->selectRaw('
                COUNT(order_items.id) as items_with_cost,
                COALESCE(SUM(order_items.quantity * order_items.price), 0) as revenue_with_cost,
                COALESCE(SUM(order_items.quantity * products.cost_price), 0) as cost_total
            ')
            ->first();

        $totalItemsCount = (clone $itemsQuery)->count();
        $itemsWithCost = (int) ($cogsRow->items_with_cost ?? 0);
        $revenueWithCost = (float) ($cogsRow->revenue_with_cost ?? 0);
        $costTotal = (float) ($cogsRow->cost_total ?? 0);

        $profitStatus = 'unavailable';
        $grossProfit = null;
        $grossMarginPct = null;

        if ($totalItemsCount > 0 && $itemsWithCost > 0) {
            $grossProfit = round($revenueWithCost - $costTotal, 2);
            $grossMarginPct = $revenueWithCost > 0 ? round(($grossProfit / $revenueWithCost) * 100, 1) : 0;
            $profitStatus = ($itemsWithCost >= $totalItemsCount) ? 'exact' : 'partial';
        }

        $avgOrderValue = $validOrdersCount > 0 ? round($netSales / $validOrdersCount, 2) : 0.0;

        return [
            'gross_sales' => $grossSales,
            'all_orders_count' => $allOrdersCount,
            'net_sales' => $netSales,
            'orders_count' => $validOrdersCount,
            'units_sold' => $unitsSold,
            'discounts_total' => $discountsTotal,
            'cancelled_sales' => $cancelledSales,
            'cancelled_count' => $cancelledCount,
            'refunded_sales' => $refundedSales,
            'refunded_count' => $refundedCount,
            'avg_order_value' => $avgOrderValue,
            'gross_profit' => $grossProfit,
            'gross_margin_pct' => $grossMarginPct,
            'profit_status' => $profitStatus,
            'items_with_cost' => $itemsWithCost,
            'total_items_sold_count' => $totalItemsCount,
            'cogs_total' => $itemsWithCost > 0 ? $costTotal : null,
        ];
    }

    /**
     * Compute Interactive Charts Data.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getChartsData(array $filters): array
    {
        $productFilterIds = $this->getMatchingProductIds($filters);

        // A. Sales & Revenue Trends (Daily or Monthly series)
        $salesTrend = $this->buildSalesTrendChart($filters['start_date'], $filters['end_date'], $filters['prev_start_date'], $productFilterIds);

        // B. Inventory Analysis: Stock & Retail Valuation by Category
        $inventoryByCategory = $this->buildCategoryInventoryChart($filters);

        // C. Stock Distribution by Status
        $stockDistribution = $this->buildStockDistributionChart($filters);

        // D. Pricing Analysis
        $pricingAnalysis = $this->buildPricingAnalysis($filters);

        // E. Product Performance
        $productPerformance = $this->buildProductPerformance($filters, $productFilterIds);

        // F. Variant Analytics
        $variantAnalytics = $this->buildVariantAnalytics($filters);

        return [
            'sales_trend' => $salesTrend,
            'category_inventory' => $inventoryByCategory,
            'stock_distribution' => $stockDistribution,
            'pricing_analysis' => $pricingAnalysis,
            'product_performance' => $productPerformance,
            'variant_analytics' => $variantAnalytics,
        ];
    }

    /**
     * Generate daily or aggregated time-series trend data for sales charts.
     */
    private function buildSalesTrendChart(Carbon $startDate, Carbon $endDate, Carbon $prevStartDate, ?array $productFilterIds): array
    {
        $days = $startDate->diffInDays($endDate) + 1;
        $isLargeRange = $days > 90;

        // Fetch current period daily aggregate
        $currentRowsQuery = DB::table('orders')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($productFilterIds !== null) {
            $currentRowsQuery->whereExists(function ($q) use ($productFilterIds) {
                $q->select(DB::raw(1))
                    ->from('order_items')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->whereIn('order_items.product_id', $productFilterIds);
            });
        }

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $dateSql = $isLargeRange
            ? ($isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')")
            : ($isSqlite ? 'date(created_at)' : 'DATE(created_at)');

        $currentRows = (clone $currentRowsQuery)
            ->selectRaw("
                {$dateSql} as period_key,
                COALESCE(SUM(total_price), 0) as gross_sales,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled', 'refunded', 'returned') AND (payment_status IS NULL OR payment_status != 'refunded') THEN total_price ELSE 0 END), 0) as net_sales,
                COUNT(CASE WHEN status NOT IN ('cancelled', 'refunded', 'returned') AND (payment_status IS NULL OR payment_status != 'refunded') THEN 1 END) as order_count
            ")
            ->groupBy(DB::raw($dateSql))
            ->orderBy('period_key')
            ->get()
            ->keyBy('period_key');

        // Fetch items count per date
        $itemsDateRowsQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereNotIn('orders.status', self::EXCLUDED_ORDER_STATUSES);

        if ($productFilterIds !== null) {
            $itemsDateRowsQuery->whereIn('order_items.product_id', $productFilterIds);
        }

        $itemsDateRows = (clone $itemsDateRowsQuery)
            ->selectRaw("{$dateSql} as period_key, COALESCE(SUM(order_items.quantity), 0) as units")
            ->groupBy(DB::raw($dateSql))
            ->get()
            ->keyBy('period_key');

        // Assemble continuous points
        $points = [];
        $cursor = $startDate->copy();

        if (! $isLargeRange) {
            while ($cursor->lte($endDate)) {
                $key = $cursor->format('Y-m-d');
                $cur = $currentRows->get($key);
                $itm = $itemsDateRows->get($key);

                $points[] = [
                    'date' => $key,
                    'label' => $cursor->format('M d'),
                    'gross_sales' => (float) ($cur->gross_sales ?? 0),
                    'net_sales' => (float) ($cur->net_sales ?? 0),
                    'orders' => (int) ($cur->order_count ?? 0),
                    'units' => (int) ($itm->units ?? 0),
                ];
                $cursor->addDay();
            }
        } else {
            // Group by Month
            while ($cursor->lte($endDate)) {
                $key = $cursor->format('Y-m');
                $cur = $currentRows->get($key);
                $itm = $itemsDateRows->get($key);

                $points[] = [
                    'date' => $key,
                    'label' => $cursor->format('M Y'),
                    'gross_sales' => (float) ($cur->gross_sales ?? 0),
                    'net_sales' => (float) ($cur->net_sales ?? 0),
                    'orders' => (int) ($cur->order_count ?? 0),
                    'units' => (int) ($itm->units ?? 0),
                ];
                $cursor->addMonth()->startOfMonth();
            }
        }

        return $points;
    }

    /**
     * Category inventory breakdown chart data.
     *
     * @param  array<string, mixed>  $filters
     */
    private function buildCategoryInventoryChart(array $filters): array
    {
        $base = Product::query();
        $this->applyProductFilters($base, $filters);

        $results = (clone $base)
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('
                COALESCE(categories.name, "Uncategorized") as category_name,
                COUNT(products.id) as product_count,
                COALESCE(SUM(products.stock), 0) as stock_qty,
                COALESCE(SUM(products.stock * CASE WHEN products.sale_price IS NOT NULL AND products.sale_price > 0 THEN products.sale_price ELSE products.price END), 0) as retail_valuation,
                COALESCE(SUM(CASE WHEN products.cost_price > 0 THEN products.stock * products.cost_price ELSE 0 END), 0) as cost_valuation
            ')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('stock_qty')
            ->limit(10)
            ->get();

        return $results->map(fn ($r) => [
            'category' => $r->category_name,
            'products' => (int) $r->product_count,
            'stock' => (int) $r->stock_qty,
            'retail_value' => (float) $r->retail_valuation,
            'cost_value' => (float) $r->cost_valuation,
        ])->all();
    }

    /**
     * Stock distribution breakdown chart data.
     *
     * @param  array<string, mixed>  $filters
     */
    private function buildStockDistributionChart(array $filters): array
    {
        $base = Product::query();
        $this->applyProductFilters($base, $filters);

        $total = (clone $base)->count();
        $out = (clone $base)->where('stock', '<=', 0)->count();
        $low = (clone $base)->where('stock', '>', 0)->whereRaw('stock <= COALESCE(low_stock_alert, 10)')->count();
        $over = (clone $base)->where('stock', '>=', 100)->count();
        $normal = max(0, $total - $out - $low - $over);

        return [
            'total' => $total,
            'in_stock' => $normal,
            'low_stock' => $low,
            'out_of_stock' => $out,
            'overstock' => $over,
            'in_stock_pct' => $total > 0 ? round(($normal / $total) * 100, 1) : 0,
            'low_stock_pct' => $total > 0 ? round(($low / $total) * 100, 1) : 0,
            'out_of_stock_pct' => $total > 0 ? round(($out / $total) * 100, 1) : 0,
            'overstock_pct' => $total > 0 ? round(($over / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Pricing and discount analysis.
     *
     * @param  array<string, mixed>  $filters
     */
    private function buildPricingAnalysis(array $filters): array
    {
        $base = Product::query();
        $this->applyProductFilters($base, $filters);

        $tiers = [
            '< ৳500' => (clone $base)->whereRaw('COALESCE(sale_price, price) < 500')->count(),
            '৳500–৳1,000' => (clone $base)->whereRaw('COALESCE(sale_price, price) >= 500 AND COALESCE(sale_price, price) < 1000')->count(),
            '৳1,000–৳2,500' => (clone $base)->whereRaw('COALESCE(sale_price, price) >= 1000 AND COALESCE(sale_price, price) < 2500')->count(),
            '৳2,500–৳5,000' => (clone $base)->whereRaw('COALESCE(sale_price, price) >= 2500 AND COALESCE(sale_price, price) < 5000')->count(),
            '৳5,000+' => (clone $base)->whereRaw('COALESCE(sale_price, price) >= 5000')->count(),
        ];

        // Discount percentage distribution
        $discounts = [
            'No Discount' => (clone $base)->where(fn ($q) => $q->whereNull('sale_price')->orWhereRaw('sale_price >= price'))->count(),
            '1%–10%' => (clone $base)->whereRaw('sale_price < price AND ((price - sale_price)/price)*100 <= 10')->count(),
            '11%–25%' => (clone $base)->whereRaw('sale_price < price AND ((price - sale_price)/price)*100 > 10 AND ((price - sale_price)/price)*100 <= 25')->count(),
            '26%–50%' => (clone $base)->whereRaw('sale_price < price AND ((price - sale_price)/price)*100 > 25 AND ((price - sale_price)/price)*100 <= 50')->count(),
            '> 50%' => (clone $base)->whereRaw('sale_price < price AND ((price - sale_price)/price)*100 > 50')->count(),
        ];

        // Highest discount products
        $highestDiscounts = (clone $base)
            ->whereNotNull('sale_price')
            ->whereRaw('sale_price < price AND price > 0')
            ->selectRaw('id, title, price, sale_price, ROUND(((price - sale_price)/price)*100, 1) as discount_pct')
            ->orderByDesc('discount_pct')
            ->limit(5)
            ->get()
            ->toArray();

        return [
            'price_tiers' => $tiers,
            'discount_tiers' => $discounts,
            'highest_discounts' => $highestDiscounts,
        ];
    }

    /**
     * Product performance insights: best-sellers, highest revenue, slow movers, etc.
     *
     * @param  array<string, mixed>  $filters
     */
    private function buildProductPerformance(array $filters, ?array $productFilterIds): array
    {
        $salesSubquery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$filters['start_date'], $filters['end_date']])
            ->whereNotIn('orders.status', self::EXCLUDED_ORDER_STATUSES)
            ->where(function ($q) {
                $q->whereNull('orders.payment_status')
                    ->orWhere('orders.payment_status', '!=', 'refunded');
            });

        if ($productFilterIds !== null) {
            $salesSubquery->whereIn('order_items.product_id', $productFilterIds);
        }

        // Top 5 best sellers by units sold
        $bestSellers = (clone $salesSubquery)
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->selectRaw('
                products.id,
                products.title,
                products.stock,
                products.price,
                products.sale_price,
                SUM(order_items.quantity) as units_sold,
                SUM(order_items.quantity * order_items.price) as revenue
            ')
            ->groupBy('products.id', 'products.title', 'products.stock', 'products.price', 'products.sale_price')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get();

        // Top 5 highest revenue
        $highestRevenue = (clone $salesSubquery)
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->selectRaw('
                products.id,
                products.title,
                products.stock,
                SUM(order_items.quantity) as units_sold,
                SUM(order_items.quantity * order_items.price) as revenue
            ')
            ->groupBy('products.id', 'products.title', 'products.stock')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Slow-moving products (stock > 0 and 0 units sold in the period)
        $soldIds = (clone $salesSubquery)->distinct()->pluck('order_items.product_id')->all();

        $slowMoving = Product::query()
            ->where('stock', '>', 5)
            ->whereNotIn('id', $soldIds);
        $this->applyProductFilters($slowMoving, $filters);

        $slowMovingProducts = $slowMoving
            ->selectRaw('id, title, stock, price, sale_price, stock * COALESCE(sale_price, price) as tied_capital')
            ->orderByDesc('stock')
            ->limit(5)
            ->get();

        // Low stock with high demand: stock <= 10 and sold in period
        $lowStockHighDemand = (clone $salesSubquery)
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.stock', '<=', 10)
            ->selectRaw('
                products.id,
                products.title,
                products.stock,
                SUM(order_items.quantity) as units_sold
            ')
            ->groupBy('products.id', 'products.title', 'products.stock')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get();

        return [
            'best_sellers' => $bestSellers,
            'highest_revenue' => $highestRevenue,
            'slow_moving' => $slowMovingProducts,
            'low_stock_high_demand' => $lowStockHighDemand,
        ];
    }

    /**
     * Variant level analytics.
     *
     * @param  array<string, mixed>  $filters
     */
    private function buildVariantAnalytics(array $filters): array
    {
        $totalVariants = ProductVariant::count();
        $inStockVariants = ProductVariant::where('stock', '>', 0)->count();
        $outOfStockVariants = ProductVariant::where('stock', '<=', 0)->count();
        $lowStockVariants = ProductVariant::where('stock', '>', 0)->where('stock', '<=', 5)->count();

        // Top selling variants in period
        $topVariants = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.variant_id', '=', 'product_variants.id')
            ->whereBetween('orders.created_at', [$filters['start_date'], $filters['end_date']])
            ->whereNotIn('orders.status', self::EXCLUDED_ORDER_STATUSES)
            ->selectRaw('
                product_variants.id,
                product_variants.name,
                product_variants.stock,
                SUM(order_items.quantity) as units_sold,
                SUM(order_items.quantity * order_items.price) as revenue
            ')
            ->groupBy('product_variants.id', 'product_variants.name', 'product_variants.stock')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get();

        return [
            'total_variants' => $totalVariants,
            'in_stock' => $inStockVariants,
            'out_of_stock' => $outOfStockVariants,
            'low_stock' => $lowStockVariants,
            'top_variants' => $topVariants,
        ];
    }

    /**
     * Paginated and sorted Product and Stock Table data.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getProductStockTable(array $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['category:id,name', 'brandRel:id,name', 'variants:id,product_id,name,stock,price,discount_price,sku']);

        $this->applyProductFilters($query, $filters);

        // Subquery for units sold and revenue in the period
        $startDate = $filters['start_date']->toDateTimeString();
        $endDate = $filters['end_date']->toDateTimeString();

        $excludedStatuses = "'".implode("','", self::EXCLUDED_ORDER_STATUSES)."'";

        $unitsSoldSql = "(
            SELECT COALESCE(SUM(oi.quantity), 0)
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE oi.product_id = products.id
              AND o.created_at BETWEEN '{$startDate}' AND '{$endDate}'
              AND o.status NOT IN ({$excludedStatuses})
              AND (o.payment_status IS NULL OR o.payment_status != 'refunded')
        )";

        $revenueSql = "(
            SELECT COALESCE(SUM(oi.quantity * oi.price), 0)
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE oi.product_id = products.id
              AND o.created_at BETWEEN '{$startDate}' AND '{$endDate}'
              AND o.status NOT IN ({$excludedStatuses})
              AND (o.payment_status IS NULL OR o.payment_status != 'refunded')
        )";

        $lastSaleSql = "(
            SELECT MAX(o.created_at)
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.id
            WHERE oi.product_id = products.id
              AND o.status NOT IN ({$excludedStatuses})
        )";

        $query->select('products.*')
            ->selectRaw("{$unitsSoldSql} as period_units_sold")
            ->selectRaw("{$revenueSql} as period_revenue")
            ->selectRaw("{$lastSaleSql} as last_sale_at")
            ->selectRaw('(products.stock * CASE WHEN products.sale_price IS NOT NULL AND products.sale_price > 0 THEN products.sale_price ELSE products.price END) as retail_valuation')
            ->selectRaw('CASE WHEN products.cost_price > 0 THEN (products.stock * products.cost_price) ELSE NULL END as cost_valuation');

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'revenue_desc';
        match ($sortBy) {
            'stock_asc' => $query->orderBy('stock', 'asc'),
            'stock_desc' => $query->orderBy('stock', 'desc'),
            'price_asc' => $query->orderByRaw('COALESCE(sale_price, price) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, price) desc'),
            'units_desc' => $query->orderByDesc('period_units_sold'),
            'units_asc' => $query->orderBy('period_units_sold', 'asc'),
            'retail_val_desc' => $query->orderByDesc('retail_valuation'),
            'title_asc' => $query->orderBy('title', 'asc'),
            default => $query->orderByDesc('period_revenue')->orderByDesc('stock'),
        };

        return $query->paginate($filters['per_page'])->withQueryString();
    }

    /**
     * Monthly and Historical Analysis.
     */
    public function getMonthlyHistoricalAnalysis(int $year, int $month): array
    {
        $year = max(2020, min(2035, $year));
        $now = Carbon::now();

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthSql = $isSqlite ? 'CAST(strftime(\'%m\', created_at) AS INTEGER)' : 'MONTH(created_at)';

        // 12-month summary for the year
        $monthlyRows = DB::table('orders')
            ->whereYear('created_at', $year)
            ->selectRaw("
                {$monthSql} as month_num,
                COALESCE(SUM(total_price), 0) as gross_sales,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled', 'refunded', 'returned') AND (payment_status IS NULL OR payment_status != 'refunded') THEN total_price ELSE 0 END), 0) as net_sales,
                COUNT(CASE WHEN status NOT IN ('cancelled', 'refunded', 'returned') AND (payment_status IS NULL OR payment_status != 'refunded') THEN 1 END) as order_count
            ")
            ->groupBy(DB::raw($monthSql))
            ->get()
            ->keyBy('month_num');

        // Monthly units sold
        $orderCreatedMonthSql = $isSqlite ? 'CAST(strftime(\'%m\', orders.created_at) AS INTEGER)' : 'MONTH(orders.created_at)';
        $monthlyUnits = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereYear('orders.created_at', $year)
            ->whereNotIn('orders.status', self::EXCLUDED_ORDER_STATUSES)
            ->selectRaw("{$orderCreatedMonthSql} as month_num, COALESCE(SUM(order_items.quantity), 0) as units")
            ->groupBy(DB::raw($orderCreatedMonthSql))
            ->get()
            ->keyBy('month_num');

        $monthsData = [];
        $prevNet = null;

        for ($m = 1; $m <= 12; $m++) {
            $row = $monthlyRows->get($m);
            $unt = $monthlyUnits->get($m);

            $net = (float) ($row->net_sales ?? 0);
            $momGrowth = $prevNet !== null ? $this->calcGrowthPct($net, $prevNet) : null;
            $prevNet = $net;

            $dateObj = Carbon::createFromDate($year, $m, 1);

            $monthsData[] = [
                'month_num' => $m,
                'month_name' => $dateObj->format('F'),
                'short_name' => $dateObj->format('M'),
                'gross_sales' => (float) ($row->gross_sales ?? 0),
                'net_sales' => $net,
                'orders_count' => (int) ($row->order_count ?? 0),
                'units_sold' => (int) ($unt->units ?? 0),
                'mom_growth' => $momGrowth,
                'is_current' => ($year === $now->year && $m === $now->month),
            ];
        }

        return [
            'year' => $year,
            'selected_month' => $month,
            'months' => $monthsData,
        ];
    }

    /**
     * Generate secure, UTF-8 CSV Stream of filtered products.
     *
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $filename = 'MamaBazar_Analytics_'.date('Ymd_His').'.csv';

        $query = Product::query()
            ->with(['category:id,name', 'brandRel:id,name']);
        $this->applyProductFilters($query, $filters);

        $startDate = $filters['start_date']->toDateTimeString();
        $endDate = $filters['end_date']->toDateTimeString();
        $excludedStatuses = "'".implode("','", self::EXCLUDED_ORDER_STATUSES)."'";

        $query->select('products.*')
            ->selectRaw("(
                SELECT COALESCE(SUM(oi.quantity), 0)
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE oi.product_id = products.id
                  AND o.created_at BETWEEN '{$startDate}' AND '{$endDate}'
                  AND o.status NOT IN ({$excludedStatuses})
            ) as period_units_sold")
            ->selectRaw("(
                SELECT COALESCE(SUM(oi.quantity * oi.price), 0)
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE oi.product_id = products.id
                  AND o.created_at BETWEEN '{$startDate}' AND '{$endDate}'
                  AND o.status NOT IN ({$excludedStatuses})
            ) as period_revenue");

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens Bengali text seamlessly
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($handle, [
                'Product ID',
                'Product Name',
                'SKU',
                'Category',
                'Brand',
                'Status',
                'Current Stock',
                'Stock Status',
                'Regular Price (BDT)',
                'Selling Price (BDT)',
                'Discount %',
                'Cost Price (BDT)',
                'Stock Cost Value (BDT)',
                'Potential Retail Value (BDT)',
                'Period Units Sold',
                'Period Revenue (BDT)',
                'Period Profit (BDT)',
            ]);

            $query->chunk(200, function ($products) use ($handle) {
                foreach ($products as $p) {
                    $stock = (int) $p->stock;
                    $regPrice = (float) $p->price;
                    $sellPrice = (float) ($p->sale_price ?: $p->price);
                    $costPrice = (float) $p->cost_price;
                    $unitsSold = (int) $p->period_units_sold;
                    $revenue = (float) $p->period_revenue;

                    $discountPct = ($regPrice > 0 && $sellPrice < $regPrice)
                        ? round((($regPrice - $sellPrice) / $regPrice) * 100, 1)
                        : 0;

                    $stockCostVal = ($costPrice > 0) ? round($stock * $costPrice, 2) : 'Unavailable';
                    $retailVal = round($stock * $sellPrice, 2);

                    $profit = ($costPrice > 0) ? round($revenue - ($unitsSold * $costPrice), 2) : 'Unavailable';

                    // Stock status
                    $stockStatus = 'In Stock';
                    if ($stock <= 0) {
                        $stockStatus = 'Out of Stock';
                    } elseif ($stock <= (int) ($p->low_stock_alert ?? 10)) {
                        $stockStatus = 'Low Stock';
                    } elseif ($stock >= 100) {
                        $stockStatus = 'Overstocked';
                    }

                    fputcsv($handle, [
                        $p->id,
                        $this->sanitizeCsvCell($p->title),
                        $this->sanitizeCsvCell($p->sku ?? 'N/A'),
                        $this->sanitizeCsvCell($p->category?->name ?? 'Uncategorized'),
                        $this->sanitizeCsvCell($p->brandRel?->name ?? $p->brand ?? 'N/A'),
                        ucfirst($p->status),
                        $stock,
                        $stockStatus,
                        $regPrice,
                        $sellPrice,
                        $discountPct.'%',
                        $costPrice > 0 ? $costPrice : 'Unavailable',
                        $stockCostVal,
                        $retailVal,
                        $unitsSold,
                        $revenue,
                        $profit,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Prevent spreadsheet formula injection by prepending apostrophe to risky symbols.
     */
    private function sanitizeCsvCell(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        $firstChar = substr($value, 0, 1);
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Apply common product filters to a query.
     */
    private function applyProductFilters($query, array $filters): void
    {
        if (! empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        if (! empty($filters['brand_id'])) {
            $query->where('products.brand_id', $filters['brand_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('products.id', $filters['product_id']);
        }

        if (! empty($filters['product_status']) && $filters['product_status'] !== 'all') {
            $query->where('products.product_status', $filters['product_status']);
        }

        if (! empty($filters['stock_status']) && $filters['stock_status'] !== 'all') {
            match ($filters['stock_status']) {
                'in_stock' => $query->where('products.stock', '>', 10)->where('products.stock', '<', 100),
                'low_stock' => $query->where('products.stock', '>', 0)->whereRaw('products.stock <= COALESCE(products.low_stock_alert, 10)'),
                'out_of_stock' => $query->where('products.stock', '<=', 0),
                'overstock' => $query->where('products.stock', '>=', 100),
                default => null,
            };
        }

        if (! empty($filters['search'])) {
            $term = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('products.title', 'like', $term)
                    ->orWhere('products.sku', 'like', $term)
                    ->orWhere('products.barcode', 'like', $term);
            });
        }
    }

    /**
     * Get product IDs that match category/brand/search filters, or null if no product filter is active.
     */
    private function getMatchingProductIds(array $filters): ?array
    {
        if (empty($filters['category_id']) && empty($filters['brand_id']) && empty($filters['product_id']) && empty($filters['search'])) {
            return null;
        }

        $q = Product::query();
        $this->applyProductFilters($q, $filters);

        return $q->pluck('id')->all();
    }

    /**
     * Helper to compute growth percentage safely.
     */
    private function calcGrowthPct(float $current, float $previous): ?float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
