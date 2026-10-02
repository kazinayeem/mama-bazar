<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ScheduledReport;
use App\Services\ActivityLoggerService;
use App\Services\ActivityReportService;
use App\Services\RbacService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class AdminActivityController extends Controller
{
    /**
     * Ensure the user holds Super Admin or explicit activity monitoring permissions.
     */
    protected function checkSuperAdminPermission(): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        if ($user->role === 'super_admin' || $user->custom_role === 'SUPER_ADMIN' || $user->role === 'admin') {
            return;
        }

        $resolved = RbacService::resolveUserPermissions((int) $user->id, (string) $user->role, $user->custom_role);
        $context = $resolved + ['id' => (int) $user->id, 'role' => (string) $user->role];

        if (RbacService::hasPermission($context, 'activity.view')) {
            return;
        }

        abort(403, 'Access denied. The Activity Monitor is reserved for Super Administrators.');
    }

    /**
     * Dashboard, Analytics, and Activity Timeline.
     */
    public function index(Request $request)
    {
        $this->checkSuperAdminPermission();

        // 1. Resolve Time Period Preset
        $preset = $request->query('preset', 'last_7_days');
        $from = $request->query('from');
        $to = $request->query('to');

        if (! $from || ! $to) {
            switch ($preset) {
                case 'today':
                    $from = Carbon::today()->format('Y-m-d');
                    $to = Carbon::today()->format('Y-m-d');
                    break;
                case 'yesterday':
                    $from = Carbon::yesterday()->format('Y-m-d');
                    $to = Carbon::yesterday()->format('Y-m-d');
                    break;
                case 'last_30_days':
                    $from = Carbon::now()->subDays(29)->format('Y-m-d');
                    $to = Carbon::now()->format('Y-m-d');
                    break;
                case 'this_month':
                    $from = Carbon::now()->startOfMonth()->format('Y-m-d');
                    $to = Carbon::now()->format('Y-m-d');
                    break;
                case 'prev_month':
                    $from = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                    $to = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                    break;
                case 'last_7_days':
                default:
                    $preset = 'last_7_days';
                    $from = Carbon::now()->subDays(6)->format('Y-m-d');
                    $to = Carbon::now()->format('Y-m-d');
                    break;
            }
        }

        $filters = [
            'search' => $request->query('search'),
            'module' => $request->query('module'),
            'event' => $request->query('event'),
            'status' => $request->query('status'),
            'source' => $request->query('source'),
            'role' => $request->query('role'),
            'actor_type' => $request->query('actor_type'),
            'failed_only' => $request->boolean('failed_only'),
            'security_only' => $request->boolean('security_only'),
            'sort' => $request->query('sort', 'newest'),
            'from' => $from,
            'to' => $to,
        ];

        // 2. Fetch real data
        $statistics = ActivityReportService::getStatistics($from, $to);
        $analytics = ActivityReportService::getAnalytics($filters);
        $activities = ActivityReportService::buildQuery($filters)->paginate(25)->withQueryString();
        $scheduledReports = ScheduledReport::orderByDesc('id')->get();

        return view('admin.activity.index', [
            'activities' => $activities,
            'statistics' => $statistics,
            'analytics' => $analytics,
            'scheduledReports' => $scheduledReports,
            'filters' => $filters,
            'preset' => $preset,
            'from' => $from,
            'to' => $to,
            'headerTitle' => 'Super Admin Activity Monitor',
        ]);
    }

    /**
     * Get detailed activity entry (JSON / Modal).
     */
    public function show(string $uuid): JsonResponse
    {
        $this->checkSuperAdminPermission();

        $activity = ActivityLog::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'success' => true,
            'activity' => [
                'uuid' => $activity->uuid,
                'event_name' => $activity->event_name,
                'human_action' => $activity->human_action,
                'module' => $activity->module,
                'actor_type' => $activity->actor_type,
                'actor_name' => $activity->actor_name ?: 'System',
                'actor_role' => $activity->actor_role ?: '—',
                'actor_id' => $activity->actor_id,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'description' => $activity->description,
                'status' => $activity->status,
                'source' => $activity->source,
                'ip_address' => $activity->ip_address ?: '—',
                'location' => $activity->location ?: 'Location unavailable',
                'user_agent' => $activity->user_agent,
                'correlation_id' => $activity->correlation_id,
                'old_values' => $activity->old_values,
                'new_values' => $activity->new_values,
                'metadata' => $activity->metadata,
                'occurred_at' => optional($activity->occurred_at)->format('Y-m-d H:i:s T'),
            ],
        ]);
    }

    /**
     * Export reports in PDF, CSV, or Excel formats.
     */
    public function export(Request $request)
    {
        $this->checkSuperAdminPermission();

        $format = strtolower($request->query('format', 'pdf'));
        $reportType = strtolower($request->query('type', 'full'));

        $filters = [
            'search' => $request->query('search'),
            'module' => $request->query('module'),
            'event' => $request->query('event'),
            'status' => $request->query('status'),
            'source' => $request->query('source'),
            'role' => $request->query('role'),
            'actor_type' => $request->query('actor_type'),
            'failed_only' => $request->boolean('failed_only'),
            'security_only' => $request->boolean('security_only'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'sort' => $request->query('sort', 'newest'),
        ];

        ActivityLoggerService::logSystem(
            'system.report_exported',
            "Administrator exported {$reportType} report in ".strtoupper($format).' format',
            ['actor' => Auth::user(), 'metadata' => ['format' => $format, 'type' => $reportType, 'filters' => $filters]]
        );

        if ($format === 'csv') {
            return ActivityReportService::exportCsv($filters);
        }

        if ($format === 'xlsx' || $format === 'excel') {
            return ActivityReportService::exportExcel($filters);
        }

        return ActivityReportService::exportPdf($filters, $reportType);
    }

    /**
     * Store a new scheduled report configuration.
     */
    public function storeScheduledReport(Request $request)
    {
        $this->checkSuperAdminPermission();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'report_type' => 'required|in:full,orders,products,customers,security,email',
            'frequency' => 'required|in:daily,weekly,monthly',
            'recipients' => 'required|string',
            'format' => 'required|in:pdf,csv,xlsx',
        ]);

        $nextRun = match ($data['frequency']) {
            'daily' => Carbon::now()->addDay()->startOfDay()->addHours(6),
            'monthly' => Carbon::now()->addMonth()->startOfDay()->addHours(6),
            default => Carbon::now()->addWeek()->startOfDay()->addHours(6),
        };

        ScheduledReport::create([
            'title' => $data['title'],
            'report_type' => $data['report_type'],
            'frequency' => $data['frequency'],
            'recipients' => $data['recipients'],
            'format' => $data['format'],
            'is_active' => true,
            'next_run_at' => $nextRun,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Scheduled activity report created successfully.');
    }

    /**
     * Toggle pause / active state on a scheduled report.
     */
    public function toggleScheduledReport(int $id)
    {
        $this->checkSuperAdminPermission();

        $report = ScheduledReport::findOrFail($id);
        $report->is_active = ! $report->is_active;
        $report->save();

        $statusStr = $report->is_active ? 'resumed' : 'paused';

        return back()->with('success', "Scheduled report #{$id} has been {$statusStr}.");
    }

    /**
     * Delete a scheduled report.
     */
    public function destroyScheduledReport(int $id)
    {
        $this->checkSuperAdminPermission();

        $report = ScheduledReport::findOrFail($id);
        $report->delete();

        return back()->with('success', 'Scheduled report deleted.');
    }

    /**
     * Execute a scheduled report immediately.
     */
    public function runScheduledReportNow(int $id)
    {
        $this->checkSuperAdminPermission();

        $report = ScheduledReport::findOrFail($id);
        Artisan::call('reports:send-scheduled', ['--id' => $report->id]);

        return back()->with('success', "Report '{$report->title}' execution triggered.");
    }
}
