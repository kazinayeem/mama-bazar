<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class ActivityLoggerService
{
    protected const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'invitation_token',
        'invitation_token_hash',
        'otp',
        'pin',
        'secret',
        'smtp_password',
        'api_key',
        'access_token',
        'refresh_token',
        'authorization',
        'cvv',
        'card_number',
    ];

    /**
     * Log a business or operational activity into the unified activity logs table.
     *
     * @param array{
     *     event: string,
     *     module: string,
     *     description?: string|null,
     *     subjectType?: string|null,
     *     subjectId?: string|int|null,
     *     actor?: User|array|null,
     *     actorType?: string|null,
     *     actorName?: string|null,
     *     actorRole?: string|null,
     *     oldValues?: array|null,
     *     newValues?: array|null,
     *     metadata?: array|null,
     *     source?: string|null,
     *     status?: string|null,
     *     ip?: string|null,
     *     userAgent?: string|null,
     *     correlationId?: string|null,
     *     occurredAt?: \DateTimeInterface|string|null,
     * } $data
     */
    public static function log(array $data): ?ActivityLog
    {
        try {
            $request = request();

            // 1. Resolve Actor
            $actor = $data['actor'] ?? Auth::user();
            $actorId = null;
            $actorName = $data['actorName'] ?? null;
            $actorRole = $data['actorRole'] ?? null;
            $actorType = $data['actorType'] ?? null;

            if ($actor instanceof User) {
                $actorId = $actor->id;
                $actorName = $actorName ?: $actor->name;
                $actorRole = $actorRole ?: ($actor->custom_role ?: $actor->role);
                $actorType = $actorType ?: ($actor->isStaff() ? 'admin' : 'customer');
            } elseif (is_array($actor)) {
                $actorId = $actor['id'] ?? null;
                $actorName = $actorName ?: ($actor['name'] ?? null);
                $actorRole = $actorRole ?: ($actor['role'] ?? null);
                $actorType = $actorType ?: ($actor['type'] ?? 'system');
            } else {
                $actorType = $actorType ?: 'system';
                $actorName = $actorName ?: 'System';
                $actorRole = $actorRole ?: 'system';
            }

            // 2. Resolve IP, User Agent, and approximate location
            $ip = $data['ip'] ?? ($request ? $request->ip() : null);
            $userAgent = $data['userAgent'] ?? ($request ? substr((string) $request->userAgent(), 0, 500) : null);
            $location = null;
            if ($ip) {
                $locData = IpLocationService::lookup($ip);
                $location = $locData['formatted'] ?? null;
            }

            // 3. Resolve Source
            $source = $data['source'] ?? null;
            if (! $source) {
                if ($request && $request->is('admin*')) {
                    $source = 'admin';
                } elseif ($request && $request->is('api*')) {
                    $source = 'api';
                } elseif ($request) {
                    $source = 'storefront';
                } else {
                    $source = 'system';
                }
            }

            // 4. Sanitize and redact values
            $oldValues = isset($data['oldValues']) ? self::redactSensitiveData($data['oldValues']) : null;
            $newValues = isset($data['newValues']) ? self::redactSensitiveData($data['newValues']) : null;
            $metadata = isset($data['metadata']) ? self::redactSensitiveData($data['metadata']) : null;

            // 5. Generate UUID and correlation ID
            $uuid = (string) Str::uuid();
            $correlationId = $data['correlationId'] ?? ($request ? $request->header('X-Correlation-ID', Str::random(16)) : Str::random(16));
            $occurredAt = $data['occurredAt'] ?? now();

            $activityLog = ActivityLog::create([
                'uuid' => $uuid,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'actor_name' => $actorName,
                'actor_role' => $actorRole,
                'event_name' => $data['event'],
                'module' => $data['module'] ?? 'system',
                'subject_type' => $data['subjectType'] ?? null,
                'subject_id' => isset($data['subjectId']) ? (string) $data['subjectId'] : null,
                'description' => $data['description'] ?? null,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'metadata' => $metadata,
                'source' => $source,
                'status' => $data['status'] ?? 'success',
                'ip_address' => $ip,
                'location' => $location,
                'user_agent' => $userAgent,
                'correlation_id' => $correlationId,
                'occurred_at' => $occurredAt,
            ]);

            // 6. Bridge to AdminAuditLog for backwards compatibility with legacy modules
            self::bridgeToAdminAuditLog($activityLog, $data);

            return $activityLog;
        } catch (Throwable $e) {
            logger()->error('ActivityLoggerService failed to write log: '.$e->getMessage(), [
                'event' => $data['event'] ?? 'unknown',
            ]);

            return null;
        }
    }

    /**
     * Recursively redact sensitive keys.
     */
    public static function redactSensitiveData(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = false;

            foreach (self::SENSITIVE_KEYS as $sensitivePattern) {
                if (str_contains($lowerKey, $sensitivePattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::redactSensitiveData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Bridge entry to admin_audit_logs if it represents an administrative or security event.
     */
    protected static function bridgeToAdminAuditLog(ActivityLog $log, array $data): void
    {
        try {
            if ($log->actor_type === 'admin' || $log->module === 'security' || in_array($log->module, ['team', 'system'], true)) {
                AdminAuditLog::create([
                    'actor_id' => $log->actor_id,
                    'actor_name' => $log->actor_name ?? 'System',
                    'actor_email' => $data['actorEmail'] ?? ($log->actor?->email),
                    'action' => $log->event_name,
                    'target_type' => $log->subject_type,
                    'target_id' => $log->subject_id,
                    'details' => json_encode([
                        'uuid' => $log->uuid,
                        'description' => $log->description,
                        'module' => $log->module,
                        'metadata' => $log->metadata,
                    ]),
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'status' => $log->status === 'failure' ? 'failure' : 'success',
                ]);
            }
        } catch (Throwable) {
            // Silently ignore bridge failures
        }
    }

    /* ── Helper Shortcuts for Common Operations ──────────────────────── */

    public static function logOrder(
        string $event,
        $order,
        string $description,
        array $extra = []
    ): ?ActivityLog {
        return self::log(array_merge([
            'event' => $event,
            'module' => 'orders',
            'subjectType' => 'Order',
            'subjectId' => is_object($order) ? ($order->id ?? null) : $order,
            'description' => $description,
            'metadata' => is_object($order) ? [
                'order_number' => $order->order_id ?? null,
                'customer_name' => $order->customer_name ?? null,
                'total_amount' => $order->total_amount ?? null,
                'status' => $order->status ?? null,
            ] : [],
        ], $extra));
    }

    public static function logProduct(
        string $event,
        $product,
        string $description,
        array $extra = []
    ): ?ActivityLog {
        return self::log(array_merge([
            'event' => $event,
            'module' => 'products',
            'subjectType' => 'Product',
            'subjectId' => is_object($product) ? ($product->id ?? null) : $product,
            'description' => $description,
            'metadata' => is_object($product) ? [
                'title' => $product->title ?? null,
                'sku' => $product->sku ?? null,
                'price' => $product->price ?? null,
                'stock' => $product->stock ?? null,
            ] : [],
        ], $extra));
    }

    public static function logCustomer(
        string $event,
        $customer,
        string $description,
        array $extra = []
    ): ?ActivityLog {
        return self::log(array_merge([
            'event' => $event,
            'module' => 'customers',
            'subjectType' => 'User',
            'subjectId' => is_object($customer) ? ($customer->id ?? null) : $customer,
            'description' => $description,
            'actorType' => 'customer',
            'metadata' => is_object($customer) ? [
                'name' => $customer->name ?? null,
                'email' => $customer->email ?? null,
                'phone' => $customer->phone ?? null,
            ] : [],
        ], $extra));
    }

    public static function logSecurity(
        string $event,
        string $description,
        array $extra = []
    ): ?ActivityLog {
        return self::log(array_merge([
            'event' => $event,
            'module' => 'security',
            'description' => $description,
        ], $extra));
    }

    public static function logEmail(
        string $event,
        string $description,
        array $extra = []
    ): ?ActivityLog {
        return self::log(array_merge([
            'event' => $event,
            'module' => 'email',
            'description' => $description,
        ], $extra));
    }

    public static function logSystem(
        string $event,
        string $description,
        array $extra = []
    ): ?ActivityLog {
        return self::log(array_merge([
            'event' => $event,
            'module' => 'system',
            'actorType' => 'system',
            'description' => $description,
        ], $extra));
    }
}
