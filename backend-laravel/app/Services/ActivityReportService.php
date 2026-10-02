<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\MemberLoginHistory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityReportService
{
    /**
     * Build base query from filter criteria.
     */
    public static function buildQuery(array $filters = []): Builder
    {
        $query = ActivityLog::query();

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['module'])) {
            $query->module($filters['module']);
        }

        if (! empty($filters['event'])) {
            $query->where('event_name', $filters['event']);
        }

        if (! empty($filters['status'])) {
            $query->status($filters['status']);
        }

        if (! empty($filters['source'])) {
            $query->source($filters['source']);
        }

        if (! empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (! empty($filters['actor_type'])) {
            $query->actorType($filters['actor_type']);
        }

        if (! empty($filters['from']) || ! empty($filters['to'])) {
            $query->dateRange($filters['from'] ?? null, $filters['to'] ?? null);
        }

        if (! empty($filters['failed_only'])) {
            $query->failedOnly();
        }

        if (! empty($filters['security_only'])) {
            $query->securityOnly();
        }

        $sort = ($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc';
        $query->orderBy('occurred_at', $sort)->orderBy('id', $sort);

        return $query;
    }

    /**
     * Compute real dashboard summary statistics for the selected time range.
     */
    public static function getStatistics(?string $from = null, ?string $to = null): array
    {
        $fromTs = $from ? Carbon::parse($from)->startOfDay() : null;
        $toTs = $to ? Carbon::parse($to)->endOfDay() : null;

        // Base scoped queries
        $actQuery = ActivityLog::query();
        $orderQuery = Order::query();
        $userQuery = User::query();
        $prodQuery = Product::query();

        if ($fromTs && $toTs) {
            $actQuery->whereBetween('occurred_at', [$fromTs, $toTs]);
            $orderQuery->whereBetween('created_at', [$fromTs, $toTs]);
            $userQuery->whereBetween('created_at', [$fromTs, $toTs]);
            $prodQuery->whereBetween('created_at', [$fromTs, $toTs]);
        }

        $totalActivities = (clone $actQuery)->count();
        $ordersReceived = (clone $orderQuery)->count();
        $ordersToday = Order::whereDate('created_at', today())->count();
        $productsCreated = (clone $prodQuery)->count();
        $productsUpdated = (clone $actQuery)->where('event_name', 'product.updated')->count();
        $customersRegistered = (clone $userQuery)->where('role', 'user')->count();
        $teamMembersAdded = (clone $userQuery)->whereIn('role', ['admin', 'manager', 'editor', 'staff', 'super_admin'])->count();
        $paymentsReceived = (clone $orderQuery)->where('payment_status', 'success')->sum('total_amount');
        $emailOperations = (clone $actQuery)->where('module', 'email')->count();
        $failedOperations = (clone $actQuery)->where('status', 'failure')->count();
        $securityEvents = (clone $actQuery)->securityOnly()->count();
        $activeTeamMembers = User::whereIn('role', ['admin', 'manager', 'editor', 'staff', 'super_admin'])->where('status', 'active')->count();
        $loginSessions = MemberLoginHistory::count();

        return [
            'total_activities' => $totalActivities,
            'orders_received' => $ordersReceived,
            'orders_today' => $ordersToday,
            'products_created' => $productsCreated,
            'products_updated' => $productsUpdated,
            'customers_registered' => $customersRegistered,
            'team_members_added' => $teamMembersAdded,
            'payments_received' => (float) $paymentsReceived,
            'email_operations' => $emailOperations,
            'failed_operations' => $failedOperations,
            'security_events' => $securityEvents,
            'active_team_members' => $activeTeamMembers,
            'login_sessions' => $loginSessions,
        ];
    }

    /**
     * Compute analytics trends and distributions.
     */
    public static function getAnalytics(array $filters = []): array
    {
        $baseQuery = self::buildQuery($filters);

        // 1. Module breakdown
        $modules = (clone $baseQuery)
            ->selectRaw('module, count(*) as count')
            ->groupBy('module')
            ->pluck('count', 'module')
            ->toArray();

        // 2. Status breakdown
        $statuses = (clone $baseQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 3. Top events
        $topEvents = (clone $baseQuery)
            ->selectRaw('event_name, count(*) as count')
            ->groupBy('event_name')
            ->orderByDesc('count')
            ->limit(8)
            ->pluck('count', 'event_name')
            ->toArray();

        // 4. Daily volume (last 7 days or date range)
        $dailyQuery = (clone $baseQuery)
            ->selectRaw('DATE(occurred_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->limit(30)
            ->pluck('count', 'date')
            ->toArray();

        return [
            'modules' => $modules,
            'statuses' => $statuses,
            'top_events' => $topEvents,
            'daily_volume' => $dailyQuery,
        ];
    }

    /**
     * Build PDF document instance for activities.
     */
    public static function generatePdf(array $filters = [], string $reportType = 'full')
    {
        // Scope by report type
        if ($reportType !== 'full') {
            $filters['module'] = match ($reportType) {
                'order', 'orders' => 'orders',
                'product', 'products' => 'products',
                'customer', 'customers' => 'customers',
                'security' => 'security',
                'email' => 'email',
                default => $filters['module'] ?? null,
            };
        }

        $query = self::buildQuery($filters);
        $totalMatching = $query->count();
        $activities = $query->limit(500)->get();

        $titles = [
            'full' => 'Comprehensive Super Admin Activity & Audit Report',
            'orders' => 'Order Operations & Sales Activity Report',
            'products' => 'Product Catalog & Inventory Activity Report',
            'customers' => 'Customer Operations & Account Activity Report',
            'security' => 'Team Security, Authentication & Access Audit Report',
            'email' => 'Email Infrastructure & Automation Operations Report',
        ];

        $reportTitle = $titles[$reportType] ?? 'Platform Activity Audit Report';

        return Pdf::loadView('admin.reports.activity-pdf', [
            'title' => $reportTitle,
            'reportType' => $reportType,
            'activities' => $activities,
            'totalMatching' => $totalMatching,
            'filters' => $filters,
            'generatedAt' => now()->format('Y-m-d H:i:s T'),
            'generatedBy' => auth()->user()?->name ?? 'Super Administrator',
        ])->setPaper('a4', 'landscape');
    }

    /**
     * Generate branded PDF report for download.
     */
    public static function exportPdf(array $filters = [], string $reportType = 'full')
    {
        $filename = 'mama_bazar_activity_'.$reportType.'_'.date('Ymd_His').'.pdf';

        return self::generatePdf($filters, $reportType)->download($filename);
    }

    /**
     * Generate secure, UTF-8 CSV stream.
     */
    public static function exportCsv(array $filters = []): StreamedResponse
    {
        $filename = 'mama_bazar_audit_export_'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'UUID',
            'Date & Time',
            'Actor',
            'Actor Role',
            'Actor Type',
            'Event Name',
            'Module',
            'Target Type',
            'Target ID',
            'Status',
            'Source',
            'IP Address',
            'Location',
            'Description',
        ];

        $callback = function () use ($filters, $columns) {
            $out = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens with proper encoding
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns);

            $query = self::buildQuery($filters);

            $query->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        self::sanitizeCsvCell($log->uuid),
                        optional($log->occurred_at)->format('Y-m-d H:i:s'),
                        self::sanitizeCsvCell($log->actor_name ?: 'System'),
                        self::sanitizeCsvCell($log->actor_role ?: '—'),
                        self::sanitizeCsvCell($log->actor_type),
                        self::sanitizeCsvCell($log->event_name),
                        self::sanitizeCsvCell($log->module),
                        self::sanitizeCsvCell($log->subject_type ?: '—'),
                        self::sanitizeCsvCell($log->subject_id ?: '—'),
                        self::sanitizeCsvCell($log->status),
                        self::sanitizeCsvCell($log->source),
                        self::sanitizeCsvCell($log->ip_address ?: '—'),
                        self::sanitizeCsvCell($log->location ?: 'Location unavailable'),
                        self::sanitizeCsvCell($log->description ?: '—'),
                    ]);
                }
            });

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Prevent CSV formula injection by prepending apostrophe to dangerous leading characters.
     */
    protected static function sanitizeCsvCell(?string $value): string
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
     * Export Excel-friendly spreadsheet format.
     */
    public static function exportExcel(array $filters = []): Response
    {
        $filename = 'mama_bazar_audit_export_'.date('Ymd_His').'.xls';
        $query = self::buildQuery($filters)->limit(5000);
        $logs = $query->get();

        $content = view('admin.reports.activity-excel', [
            'logs' => $logs,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ])->render();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
