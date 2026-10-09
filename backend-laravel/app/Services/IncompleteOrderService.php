<?php

namespace App\Services;

use App\Models\CheckoutSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncompleteOrderService
{
    public const INACTIVITY_THRESHOLD_MINUTES = 30;

    public const EXPIRATION_THRESHOLD_DAYS = 7;

    /**
     * Record or update checkout progress idempotently.
     *
     * @param  array<string, mixed>  $data
     */
    public static function recordProgress(array $data, Request $request): CheckoutSession
    {
        $sessionId = trim((string) ($data['checkout_session_id'] ?? $data['session_id'] ?? ''));
        if ($sessionId === '') {
            throw new \InvalidArgumentException('Session ID is required.');
        }

        // Evaluate inactivity first so statuses stay fresh
        self::evaluateInactivity();

        $clientIp = $request->ip();
        $userAgent = (string) $request->userAgent();
        $deviceCategory = self::detectDeviceCategory($data['device_category'] ?? $data['device_type'] ?? null, $userAgent);
        $browser = self::detectBrowser($userAgent);
        $os = self::detectOs($userAgent);

        // Resolve approximate geolocation
        $geo = IpLocationService::lookup($clientIp);
        $country = $geo['country'] ?? ($geo['is_private'] ? 'Local Network' : null);
        $region = $geo['region'] ?? null;
        $city = $geo['city'] ?? null;

        $progressPercent = min(max((int) ($data['progress_percent'] ?? 10), 0), 100);
        $currentStep = (string) ($data['current_step'] ?? 'opened');
        $completedFields = array_values(array_unique(array_filter((array) ($data['completed_fields'] ?? []))));
        $cartItemCount = max((int) ($data['cart_item_count'] ?? 0), 0);
        $cartSubtotal = max((float) ($data['cart_subtotal'] ?? $data['cart_total'] ?? 0), 0);
        $shippingMethodName = ! empty($data['shipping_method_name'])
            ? (string) $data['shipping_method_name']
            : (! empty($data['selected_shipping_method']) ? (string) $data['selected_shipping_method'] : null);
        $paymentMethodCode = ! empty($data['payment_method_code'])
            ? (string) $data['payment_method_code']
            : (! empty($data['selected_payment_method']) ? (string) $data['selected_payment_method'] : null);
        $district = ! empty($data['district']) ? (string) $data['district'] : null;
        $referrer = ! empty($data['referrer']) ? mb_substr((string) $data['referrer'], 0, 500) : mb_substr((string) $request->headers->get('referer'), 0, 500);
        $landingPage = ! empty($data['landing_page']) ? mb_substr((string) $data['landing_page'], 0, 500) : session()->get('landing_page');

        return DB::transaction(function () use (
            $sessionId, $clientIp, $deviceCategory, $browser, $os, $country, $region, $city,
            $progressPercent, $currentStep, $completedFields, $cartItemCount, $cartSubtotal,
            $shippingMethodName, $paymentMethodCode, $district, $referrer, $landingPage
        ) {
            $session = CheckoutSession::where('session_id', $sessionId)->lockForUpdate()->first();

            $now = Carbon::now();
            $milestones = [];

            if ($session) {
                // If already converted, do not revert state
                if ($session->isConverted()) {
                    return $session;
                }

                $milestones = is_array($session->milestones) ? $session->milestones : [];
                $existingCompleted = is_array($session->completed_fields) ? $session->completed_fields : [];
                $completedFields = array_values(array_unique(array_merge($existingCompleted, $completedFields)));

                // Calculate monotonically non-decreasing progress
                $progressPercent = max($session->progress_percent, $progressPercent);

                self::appendMilestone($milestones, $progressPercent, $currentStep, $now);

                $session->update([
                    'progress_percent' => $progressPercent,
                    'current_step' => $currentStep,
                    'completed_fields' => $completedFields,
                    'milestones' => $milestones,
                    'cart_item_count' => $cartItemCount ?: $session->cart_item_count,
                    'cart_subtotal' => $cartSubtotal ?: $session->cart_subtotal,
                    'shipping_method_name' => $shippingMethodName ?: $session->shipping_method_name,
                    'payment_method_code' => $paymentMethodCode ?: $session->payment_method_code,
                    'district' => $district ?: $session->district,
                    'last_active_at' => $now,
                    'status' => 'active', // Active upon new interaction
                ]);

                return $session;
            }

            // Brand new checkout session
            self::appendMilestone($milestones, $progressPercent, $currentStep, $now);

            return CheckoutSession::create([
                'session_id' => $sessionId,
                'user_id' => auth()->id(),
                'status' => 'active',
                'progress_percent' => $progressPercent,
                'current_step' => $currentStep,
                'completed_fields' => $completedFields,
                'milestones' => $milestones,
                'cart_item_count' => $cartItemCount,
                'cart_subtotal' => $cartSubtotal,
                'shipping_method_name' => $shippingMethodName,
                'payment_method_code' => $paymentMethodCode,
                'district' => $district,
                'device_category' => $deviceCategory,
                'browser' => $browser,
                'os' => $os,
                'ip_address' => $clientIp,
                'country' => $country,
                'region' => $region,
                'city' => $city,
                'referrer' => $referrer,
                'landing_page' => $landingPage,
                'first_active_at' => $now,
                'last_active_at' => $now,
            ]);
        });
    }

    /**
     * Mark a session as successfully converted when an order is created.
     */
    public static function markConverted(?string $sessionId, int $orderId): ?CheckoutSession
    {
        if (empty($sessionId)) {
            return null;
        }

        return DB::transaction(function () use ($sessionId, $orderId) {
            $session = CheckoutSession::where('session_id', $sessionId)->lockForUpdate()->first();
            if (! $session) {
                return null;
            }

            $now = Carbon::now();
            $milestones = is_array($session->milestones) ? $session->milestones : [];
            $milestones[] = [
                'milestone' => 'converted',
                'progress' => 100,
                'step' => 'completed',
                'reached_at' => $now->toIso8601String(),
            ];

            $session->update([
                'order_id' => $orderId,
                'status' => 'converted',
                'progress_percent' => 100,
                'current_step' => 'completed',
                'milestones' => $milestones,
                'converted_at' => $now,
                'last_active_at' => $now,
            ]);

            return $session;
        });
    }

    /**
     * Transition active sessions past the inactivity threshold to incomplete,
     * and old incomplete sessions past the expiration threshold to expired.
     */
    public static function evaluateInactivity(int $inactivityMinutes = self::INACTIVITY_THRESHOLD_MINUTES): int
    {
        $cutoff = Carbon::now()->subMinutes($inactivityMinutes);
        $expireCutoff = Carbon::now()->subDays(self::EXPIRATION_THRESHOLD_DAYS);

        $abandonedCount = CheckoutSession::where('status', 'active')
            ->where('last_active_at', '<', $cutoff)
            ->update([
                'status' => 'incomplete',
                'abandoned_at' => Carbon::now(),
            ]);

        CheckoutSession::where('status', 'incomplete')
            ->where('last_active_at', '<', $expireCutoff)
            ->update([
                'status' => 'expired',
            ]);

        return $abandonedCount;
    }

    /**
     * Prune checkout sessions older than a retention threshold.
     */
    public static function pruneOldSessions(int $days = 30): int
    {
        $cutoff = Carbon::now()->subDays($days);

        // Keep converted sessions or prune all older than threshold
        return CheckoutSession::where('last_active_at', '<', $cutoff)->delete();
    }

    /**
     * Compile comprehensive dashboard metrics.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function getDashboardMetrics(array $filters = []): array
    {
        self::evaluateInactivity();

        $query = CheckoutSession::query();
        self::applyDateFilters($query, $filters);

        $totalSessions = (clone $query)->count();
        $activeSessions = (clone $query)->where('status', 'active')->count();
        $incompleteSessions = (clone $query)->where('status', 'incomplete')->count();
        $convertedSessions = (clone $query)->where('status', 'converted')->count();
        $expiredSessions = (clone $query)->where('status', 'expired')->count();

        // Abandonment rate: Incomplete checkouts out of total sessions that concluded (incomplete + converted + expired)
        $concluded = $incompleteSessions + $convertedSessions + $expiredSessions;
        $abandonmentRate = $concluded > 0 ? round(($incompleteSessions / $concluded) * 100, 1) : 0.0;
        $conversionRate = $totalSessions > 0 ? round(($convertedSessions / $totalSessions) * 100, 1) : 0.0;

        $avgCompletion = (clone $query)->whereIn('status', ['incomplete', 'active'])->avg('progress_percent');
        $avgCompletionPercent = round((float) ($avgCompletion ?? 0), 1);

        // Funnel metrics
        $funnel = [
            'opened' => (clone $query)->where('progress_percent', '>=', 10)->count(),
            'info_entered' => (clone $query)->where('progress_percent', '>=', 25)->count(),
            'shipping' => (clone $query)->where('progress_percent', '>=', 50)->count(),
            'payment' => (clone $query)->where('progress_percent', '>=', 75)->count(),
            'converted' => $convertedSessions,
        ];

        // Abandonment by Progress Brackets (only incomplete sessions)
        $incompleteQuery = (clone $query)->where('status', 'incomplete');
        $progressBrackets = [
            '0_25' => (clone $incompleteQuery)->whereBetween('progress_percent', [0, 25])->count(),
            '26_50' => (clone $incompleteQuery)->whereBetween('progress_percent', [26, 50])->count(),
            '51_75' => (clone $incompleteQuery)->whereBetween('progress_percent', [51, 75])->count(),
            '76_99' => (clone $incompleteQuery)->whereBetween('progress_percent', [76, 99])->count(),
        ];

        // Device Category Breakdown
        $devices = [
            'desktop' => (clone $query)->where('device_category', 'desktop')->count(),
            'mobile' => (clone $query)->where('device_category', 'mobile')->count(),
            'tablet' => (clone $query)->where('device_category', 'tablet')->count(),
        ];

        // Payment Method Abandonment (Top 5)
        $paymentMethods = (clone $incompleteQuery)
            ->whereNotNull('payment_method_code')
            ->select('payment_method_code', DB::raw('count(*) as count'))
            ->groupBy('payment_method_code')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // 7-day Trend
        $trends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayTotal = (clone $query)->whereDate('first_active_at', $date)->count();
            $dayIncomplete = (clone $query)->where('status', 'incomplete')->whereDate('first_active_at', $date)->count();
            $dayConverted = (clone $query)->where('status', 'converted')->whereDate('first_active_at', $date)->count();

            $trends[] = [
                'date' => $date->format('M d'),
                'total' => $dayTotal,
                'incomplete' => $dayIncomplete,
                'converted' => $dayConverted,
            ];
        }

        return [
            'total_sessions' => $totalSessions,
            'active_sessions' => $activeSessions,
            'incomplete_sessions' => $incompleteSessions,
            'converted_sessions' => $convertedSessions,
            'expired_sessions' => $expiredSessions,
            'abandonment_rate' => $abandonmentRate,
            'conversion_rate' => $conversionRate,
            'avg_completion_percent' => $avgCompletionPercent,
            'funnel' => $funnel,
            'progress_brackets' => $progressBrackets,
            'devices' => $devices,
            'payment_methods' => $paymentMethods,
            'trends' => $trends,
        ];
    }

    /**
     * Get paginated checkout sessions with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function getTableSessions(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        self::evaluateInactivity();

        $query = CheckoutSession::query()->with('order');

        self::applyDateFilters($query, $filters);

        if (! empty($filters['status']) && in_array($filters['status'], ['active', 'incomplete', 'converted', 'expired'], true)) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['device']) && in_array($filters['device'], ['desktop', 'mobile', 'tablet'], true)) {
            $query->where('device_category', $filters['device']);
        }

        if (! empty($filters['progress_range'])) {
            match ($filters['progress_range']) {
                '0-25' => $query->whereBetween('progress_percent', [0, 25]),
                '26-50' => $query->whereBetween('progress_percent', [26, 50]),
                '51-75' => $query->whereBetween('progress_percent', [51, 75]),
                '76-99' => $query->whereBetween('progress_percent', [76, 99]),
                '100' => $query->where('progress_percent', 100),
                default => null,
            };
        }

        if (! empty($filters['search'])) {
            $s = trim((string) $filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('session_id', 'like', "%{$s}%")
                    ->orWhere('city', 'like', "%{$s}%")
                    ->orWhere('country', 'like', "%{$s}%")
                    ->orWhere('district', 'like', "%{$s}%");
            });
        }

        return $query->orderByDesc('last_active_at')->paginate($perPage)->withQueryString();
    }

    /**
     * Stream CSV export for authorized administrators.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function exportCsv(array $filters = [], bool $includeIp = false): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="incomplete_orders_export_'.date('Y_m_d_His').'.csv"',
        ];

        return response()->stream(function () use ($filters, $includeIp) {
            $handle = fopen('php://output', 'w');

            $columns = [
                'Session ID',
                'Status',
                'Progress %',
                'Current Step',
                'Cart Items',
                'Cart Subtotal (BDT)',
                'Device',
                'Location',
            ];
            if ($includeIp) {
                $columns[] = 'IP Address';
            }
            $columns = array_merge($columns, [
                'Shipping Method',
                'Payment Method',
                'First Active At',
                'Last Active At',
                'Converted Order ID',
            ]);

            fputcsv($handle, $columns);

            $query = CheckoutSession::query();
            self::applyDateFilters($query, $filters);

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $query->orderByDesc('last_active_at')->chunk(200, function ($sessions) use ($handle, $includeIp) {
                foreach ($sessions as $s) {
                    $location = trim(($s->city ? $s->city.', ' : '').($s->country ?: ''));
                    $row = [
                        $s->session_id,
                        ucfirst($s->status),
                        $s->progress_percent.'%',
                        ucfirst($s->current_step),
                        $s->cart_item_count,
                        number_format($s->cart_subtotal, 2),
                        ucfirst($s->device_category),
                        $location ?: 'Location unavailable',
                    ];
                    if ($includeIp) {
                        $row[] = $s->ip_address ?: '—';
                    }
                    $row = array_merge($row, [
                        $s->shipping_method_name ?: '—',
                        strtoupper($s->payment_method_code ?: '—'),
                        $s->first_active_at?->format('Y-m-d H:i:s'),
                        $s->last_active_at?->format('Y-m-d H:i:s'),
                        $s->order_id ?: '—',
                    ]);

                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    protected static function applyDateFilters($query, array $filters): void
    {
        $range = $filters['date_range'] ?? '30_days';

        if ($range === 'today') {
            $query->whereDate('first_active_at', Carbon::today());
        } elseif ($range === '7_days') {
            $query->where('first_active_at', '>=', Carbon::now()->subDays(7));
        } elseif ($range === '30_days') {
            $query->where('first_active_at', '>=', Carbon::now()->subDays(30));
        } elseif ($range === 'custom' && ! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('first_active_at', [
                Carbon::parse($filters['start_date'])->startOfDay(),
                Carbon::parse($filters['end_date'])->endOfDay(),
            ]);
        }
    }

    protected static function appendMilestone(array &$milestones, int $progress, string $step, Carbon $time): void
    {
        $milestoneKey = match (true) {
            $progress >= 95 => 'submitted',
            $progress >= 75 => 'payment_selected',
            $progress >= 50 => 'shipping_completed',
            $progress >= 25 => 'info_entered',
            default => 'opened',
        };

        $alreadyReached = false;
        foreach ($milestones as $m) {
            if (($m['milestone'] ?? '') === $milestoneKey) {
                $alreadyReached = true;
                break;
            }
        }

        if (! $alreadyReached) {
            $milestones[] = [
                'milestone' => $milestoneKey,
                'progress' => $progress,
                'step' => $step,
                'reached_at' => $time->toIso8601String(),
            ];
        }
    }

    protected static function detectDeviceCategory(?string $declared, string $userAgent): string
    {
        if ($declared && in_array(strtolower($declared), ['mobile', 'tablet', 'desktop'], true)) {
            return strtolower($declared);
        }

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile|mobile)/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    protected static function detectBrowser(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg') => 'Edge',
            str_contains($userAgent, 'Chrome') => 'Chrome',
            str_contains($userAgent, 'Safari') && ! str_contains($userAgent, 'Chrome') => 'Safari',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident/') => 'Internet Explorer',
            default => 'Other Browser',
        };
    }

    protected static function detectOs(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Other OS',
        };
    }
}
