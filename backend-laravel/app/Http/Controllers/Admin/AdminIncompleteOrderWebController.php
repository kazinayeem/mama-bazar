<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\IncompleteOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminIncompleteOrderWebController extends Controller
{
    /**
     * Display the Incomplete Orders and Abandoned Checkout Analytics dashboard.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['date_range', 'status', 'device', 'progress_range', 'search']);

        IncompleteOrderService::evaluateInactivity();

        $metrics = IncompleteOrderService::getDashboardMetrics($filters);
        $sessions = IncompleteOrderService::getTableSessions($filters, 20);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewIp = $user ? $user->hasPermission('incomplete_orders.view_ip') : false;
        $canExport = $user ? $user->hasPermission('incomplete_orders.export') : false;
        $canManageRetention = $user ? $user->hasPermission('incomplete_orders.manage_retention') : false;

        return view('admin.incomplete-orders.index', compact(
            'metrics',
            'sessions',
            'filters',
            'canViewIp',
            'canExport',
            'canManageRetention'
        ));
    }

    /**
     * Export incomplete checkout session data as a CSV stream.
     */
    public function export(Request $request): StreamedResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewIp = $user ? $user->hasPermission('incomplete_orders.view_ip') : false;

        $filters = $request->only(['date_range', 'status', 'device', 'progress_range', 'search']);

        return IncompleteOrderService::exportCsv($filters, $canViewIp);
    }

    /**
     * Prune stale checkout sessions older than the configured retention threshold.
     */
    public function prune(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'retention_days' => 'required|integer|min:1|max:365',
        ]);

        $days = (int) $validated['retention_days'];
        $count = IncompleteOrderService::pruneOldSessions($days);

        return redirect()
            ->route('admin.incomplete-orders.index')
            ->with('success', "Retention cleanup completed. Removed {$count} sessions older than {$days} days.");
    }
}
