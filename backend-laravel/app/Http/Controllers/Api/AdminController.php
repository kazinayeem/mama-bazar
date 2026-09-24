<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private const ORDER_STATUSES = [
        'pending',
        'confirmed',
        'processing',
        'packed',
        'shipped',
        'out_for_delivery',
        'delivered',
        'cancelled',
    ];

    public function getDashboard(Request $request): JsonResponse
    {
        $range = $request->query('range');
        $days = ($range === '7') ? 7 : (($range === '365') ? 365 : 30);

        $now = Carbon::now();
        $since = Carbon::now()->subDays($days);
        $todayStart = Carbon::today();

        $totalOrdersRow = DB::table('orders')
            ->where('status', 'delivered')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_price), 0) as revenue')
            ->first();

        $revenueRow = DB::table('orders')
            ->where('created_at', '>=', $since)
            ->where('created_at', '<=', $now)
            ->selectRaw('COALESCE(SUM(total_price), 0) as revenue')
            ->first();

        $todayRow = DB::table('orders')
            ->where('created_at', '>=', $todayStart)
            ->where('created_at', '<=', $now)
            ->selectRaw('COUNT(*) as count')
            ->first();

        $deliveredPeriodCount = DB::table('orders')
            ->where('status', 'delivered')
            ->where('created_at', '>=', $since)
            ->count();

        $cancelledPeriodCount = DB::table('orders')
            ->where('status', 'cancelled')
            ->where('created_at', '>=', $since)
            ->count();

        $lowStockCount = DB::table('products')
            ->where('stock', '>', 0)
            ->where('stock', '<=', 10)
            ->count();

        $outStockCount = DB::table('products')
            ->where('stock', '<=', 0)
            ->count();

        $usersCount = DB::table('users')
            ->where('role', 'user')
            ->count();

        $productsCount = DB::table('products')->count();

        // Sales by day
        $salesRows = DB::table('orders')
            ->where('created_at', '>=', $since)
            ->where('created_at', '<=', $now)
            ->selectRaw("DATE(created_at) as day, COALESCE(SUM(total_price), 0) as revenue, COUNT(*) as count")
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy(DB::raw("DATE(created_at)"))
            ->get();

        $salesByDay = [];
        foreach ($salesRows as $sr) {
            $salesByDay[$sr->day] = [
                'revenue' => (float) $sr->revenue,
                'count' => (int) $sr->count,
            ];
        }

        $revenueChart = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i);
            $key = $d->format('Y-m-d');
            $row = $salesByDay[$key] ?? null;
            $revenueChart[] = [
                'date' => $key,
                'revenue' => $row ? $row['revenue'] : 0,
                'orders' => $row ? $row['count'] : 0,
            ];
        }

        // Status breakdown
        $statusRows = DB::table('orders')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $statusCounts = [];
        foreach (self::ORDER_STATUSES as $st) {
            $statusCounts[$st] = 0;
        }
        foreach ($statusRows as $sr) {
            $statusCounts[$sr->status] = (int) $sr->count;
        }

        // Payment breakdown
        $paymentRows = DB::table('orders')
            ->selectRaw('payment_method as method, COUNT(*) as count, COALESCE(SUM(total_price), 0) as revenue')
            ->groupBy('payment_method')
            ->get()
            ->map(function ($r) {
                return [
                    'method' => $r->method,
                    'count' => (int) $r->count,
                    'revenue' => (float) $r->revenue,
                ];
            });

        // Recent orders
        $recentOrders = DB::table('orders')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Top products
        $topProducts = DB::table('order_items')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->select([
                'order_items.product_id as productId',
                'products.title',
                'products.slug',
                'products.images',
                DB::raw('SUM(order_items.quantity) as quantity'),
                DB::raw('SUM(order_items.quantity * order_items.price) as revenue'),
            ])
            ->groupBy('order_items.product_id', 'products.title', 'products.slug', 'products.images')
            ->orderByRaw('SUM(order_items.quantity) DESC')
            ->limit(5)
            ->get()
            ->map(function ($p) {
                $images = is_string($p->images) ? json_decode($p->images, true) : $p->images;
                return [
                    'id' => $p->productId,
                    'title' => $p->title,
                    'slug' => $p->slug,
                    'image' => (is_array($images) && count($images) > 0) ? $images[0] : null,
                    'quantity' => (int) $p->quantity,
                    'revenue' => (float) $p->revenue,
                ];
            });

        // Top categories
        $topCategories = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereNotNull('products.category_id')
            ->select([
                'products.category_id as categoryId',
                'categories.name',
                DB::raw('COUNT(products.id) as count'),
            ])
            ->groupBy('products.category_id', 'categories.name')
            ->orderByRaw('COUNT(products.id) DESC')
            ->limit(6)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->categoryId,
                    'name' => $c->name,
                    'count' => (int) $c->count,
                ];
            });

        // Low stock products
        $lowStock = DB::table('products')
            ->where('stock', '<=', 10)
            ->select(['id', 'title', 'slug', 'stock', 'images', 'price'])
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                $images = is_string($p->images) ? json_decode($p->images, true) : $p->images;
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                    'stock' => (int) $p->stock,
                    'image' => (is_array($images) && count($images) > 0) ? $images[0] : null,
                    'price' => (float) $p->price,
                ];
            });

        $totalRevenue = (float) ($totalOrdersRow->revenue ?? 0);
        $periodRevenue = (float) ($revenueRow->revenue ?? 0);
        $deliveredCount = (int) ($totalOrdersRow->count ?? 0);
        $avgOrderValue = $deliveredCount > 0 ? round($totalRevenue / $deliveredCount, 2) : 0;
        $periodOrders = array_reduce($revenueChart, fn($sum, $r) => $sum + $r['orders'], 0);

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'totalRevenue' => $totalRevenue,
                    'totalOrders' => $deliveredCount,
                    'avgOrderValue' => $avgOrderValue,
                    'totalCustomers' => $usersCount,
                    'totalProducts' => $productsCount,
                    'todayOrders' => (int) ($todayRow->count ?? 0),
                    'periodRevenue' => $periodRevenue,
                    'periodOrders' => $periodOrders,
                    'deliveredThisPeriod' => $deliveredPeriodCount,
                    'cancelledThisPeriod' => $cancelledPeriodCount,
                    'lowStock' => $lowStockCount,
                    'outOfStock' => $outStockCount,
                    'conversionRate' => 0,
                    'periodVisitors' => 0,
                ],
                'revenueChart' => $revenueChart,
                'statusBreakdown' => $statusCounts,
                'paymentBreakdown' => $paymentRows,
                'recentOrders' => $recentOrders,
                'topProducts' => $topProducts,
                'topCategories' => $topCategories,
                'lowStockProducts' => $lowStock,
            ],
        ]);
    }
}
