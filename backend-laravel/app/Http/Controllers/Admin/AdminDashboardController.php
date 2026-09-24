<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request, AdminDashboardService $dashboard)
    {
        $range = $dashboard->resolveRange($request->query('range'));
        $payload = $dashboard->gather($range);
        $expenses = $dashboard->expenseSummary();

        return view('admin.dashboard', [
            'range' => $range,
            'kpis' => $payload['kpis'],
            'revenueChart' => $payload['revenueChart'],
            'statusBreakdown' => $payload['statusBreakdown'],
            'statusChart' => $payload['statusChart'],
            'recentOrders' => $payload['recentOrders'],
            'topProducts' => $payload['topProducts'],
            'lowStockProducts' => $payload['lowStockProducts'],
            'hasOrderItems' => $payload['hasOrderItems'],
            'expenses' => $expenses,
            'statusLabels' => AdminDashboardService::statusLabels(),
            'statusBadges' => AdminDashboardService::statusBadgeClasses(),
        ]);
    }
}
