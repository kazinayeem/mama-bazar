<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardService
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

    public function resolveRange(?string $range): int
    {
        return match ($range) {
            '7' => 7,
            '365' => 365,
            default => 30,
        };
    }

    /**
     * @return array{
     *     kpis: array<string, int|float>,
     *     revenueChart: list<array{date: string, revenue: float, orders: int, label: string}>,
     *     statusBreakdown: array<string, int>,
     *     statusChart: list<array{name: string, value: int}>,
     *     recentOrders: \Illuminate\Support\Collection,
     *     topProducts: list<array<string, mixed>>,
     *     lowStockProducts: list<array<string, mixed>>,
     *     hasOrderItems: bool
     * }
     */
    public function gather(int $days): array
    {
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

        $outStockCount = DB::table('products')
            ->where('stock', '<=', 0)
            ->count();

        $usersCount = (int) DB::table('users')->where('role', 'user')->count();
        $productsCount = (int) DB::table('products')->count();

        $salesRows = DB::table('orders')
            ->where('created_at', '>=', $since)
            ->where('created_at', '<=', $now)
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(total_price), 0) as revenue, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
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
                'revenue' => $row ? $row['revenue'] : 0.0,
                'orders' => $row ? $row['count'] : 0,
                'label' => $d->format('M j'),
            ];
        }

        $statusRows = DB::table('orders')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $statusBreakdown = array_fill_keys(self::ORDER_STATUSES, 0);
        foreach ($statusRows as $sr) {
            if (array_key_exists($sr->status, $statusBreakdown)) {
                $statusBreakdown[$sr->status] = (int) $sr->count;
            } else {
                $statusBreakdown[$sr->status] = (int) $sr->count;
            }
        }

        $statusLabels = self::statusLabels();
        $statusChart = [];
        foreach ($statusBreakdown as $key => $value) {
            if ($value > 0) {
                $statusChart[] = [
                    'name' => $statusLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                    'value' => $value,
                ];
            }
        }

        $recentOrders = DB::table('orders')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $hasOrderItems = Schema::hasTable('order_items');
        $topProducts = [];
        if ($hasOrderItems && DB::table('order_items')->exists()) {
            $topProducts = DB::table('order_items')
                ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
                ->select([
                    'order_items.product_id as id',
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
                ->map(fn ($p) => $this->mapProductRow($p))
                ->all();
        }

        $lowStockProducts = DB::table('products')
            ->where('stock', '<=', 10)
            ->select(['id', 'title', 'slug', 'stock', 'images', 'price'])
            ->orderBy('stock')
            ->limit(10)
            ->get()
            ->map(fn ($p) => $this->mapProductRow($p))
            ->all();

        $totalRevenue = (float) ($totalOrdersRow->revenue ?? 0);
        $periodRevenue = (float) ($revenueRow->revenue ?? 0);
        $deliveredCount = (int) ($totalOrdersRow->count ?? 0);
        $avgOrderValue = $deliveredCount > 0 ? round($totalRevenue / $deliveredCount, 2) : 0.0;
        $periodOrders = array_reduce($revenueChart, fn ($sum, $r) => $sum + $r['orders'], 0);

        return [
            'kpis' => [
                'totalRevenue' => $totalRevenue,
                'totalOrders' => $deliveredCount,
                'avgOrderValue' => $avgOrderValue,
                'totalCustomers' => $usersCount,
                'totalProducts' => $productsCount,
                'todayOrders' => (int) ($todayRow->count ?? 0),
                'periodRevenue' => $periodRevenue,
                'periodOrders' => $periodOrders,
                'outOfStock' => (int) $outStockCount,
            ],
            'revenueChart' => $revenueChart,
            'statusBreakdown' => $statusBreakdown,
            'statusChart' => $statusChart,
            'recentOrders' => $recentOrders,
            'topProducts' => $topProducts,
            'lowStockProducts' => $lowStockProducts,
            'hasOrderItems' => $hasOrderItems,
        ];
    }

    /**
     * @return array<string, int|float>|null
     */
    public function expenseSummary(): ?array
    {
        if (! Schema::hasTable('expenses')) {
            return null;
        }

        $todayStart = Carbon::today()->format('Y-m-d 00:00:00');
        $weekStart = Carbon::now()->startOfWeek()->format('Y-m-d 00:00:00');
        $monthStart = Carbon::now()->startOfMonth()->format('Y-m-d 00:00:00');

        $base = DB::table('expenses');

        $totalRow = (clone $base)->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')->first();
        $monthRow = (clone $base)->where('expense_date', '>=', $monthStart)->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')->first();
        $weekRow = (clone $base)->where('expense_date', '>=', $weekStart)->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')->first();
        $todayRow = (clone $base)->where('expense_date', '>=', $todayStart)->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')->first();

        return [
            'total' => (float) ($totalRow->total ?? 0),
            'totalCount' => (int) ($totalRow->count ?? 0),
            'thisMonth' => (float) ($monthRow->total ?? 0),
            'thisMonthCount' => (int) ($monthRow->count ?? 0),
            'thisWeek' => (float) ($weekRow->total ?? 0),
            'thisWeekCount' => (int) ($weekRow->count ?? 0),
            'today' => (float) ($todayRow->total ?? 0),
            'todayCount' => (int) ($todayRow->count ?? 0),
        ];
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'packed' => 'Packed',
            'shipped' => 'Shipped',
            'out_for_delivery' => 'Out for delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    public static function statusBadgeClasses(): array
    {
        return [
            'pending' => 'bg-amber-50 text-amber-800 ring-amber-200',
            'confirmed' => 'bg-brand-green-50 text-brand-green-800 ring-brand-green-200',
            'processing' => 'bg-brand-green-50 text-brand-green-700 ring-brand-green-200',
            'packed' => 'bg-orange-50 text-orange-800 ring-orange-200',
            'shipped' => 'bg-sky-50 text-sky-800 ring-sky-200',
            'out_for_delivery' => 'bg-orange-50 text-orange-700 ring-orange-200',
            'delivered' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            'cancelled' => 'bg-red-50 text-red-700 ring-red-200',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProductRow(object $p): array
    {
        $images = is_string($p->images ?? null) ? json_decode($p->images, true) : ($p->images ?? null);

        return [
            'id' => $p->id ?? null,
            'title' => $p->title ?? 'Unknown product',
            'slug' => $p->slug ?? null,
            'image' => (is_array($images) && count($images) > 0) ? $images[0] : null,
            'quantity' => isset($p->quantity) ? (int) $p->quantity : null,
            'revenue' => isset($p->revenue) ? (float) $p->revenue : null,
            'stock' => isset($p->stock) ? (int) $p->stock : null,
            'price' => isset($p->price) ? (float) $p->price : null,
        ];
    }
}
