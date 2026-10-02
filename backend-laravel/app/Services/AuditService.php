<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AdminAuditLog;
use Illuminate\Support\Str;

class AuditService
{
    public static function log(array $input): void
    {
        try {
            $details = $input['details'] ?? null;
            if (is_array($details) || is_object($details)) {
                $details = json_encode($details);
            }

            AdminAuditLog::create([
                'actor_id' => $input['actorId'] ?? null,
                'actor_name' => $input['actorName'] ?? 'System',
                'actor_email' => $input['actorEmail'] ?? null,
                'action' => $input['action'],
                'target_type' => $input['targetType'] ?? null,
                'target_id' => isset($input['targetId']) ? (string) $input['targetId'] : null,
                'details' => $details,
                'ip_address' => $input['ipAddress'] ?? null,
                'user_agent' => isset($input['userAgent']) ? substr($input['userAgent'], 0, 500) : null,
                'status' => $input['status'] ?? 'success',
            ]);

            // Sync with unified ActivityLog
            $action = $input['action'] ?? 'system.event';
            $module = 'system';
            if (str_starts_with($action, 'member.') || str_starts_with($action, 'team.')) {
                $module = 'team';
            } elseif (str_starts_with($action, 'login.') || str_contains($action, 'password') || str_contains($action, 'security')) {
                $module = 'security';
            } elseif (str_starts_with($action, 'order.')) {
                $module = 'orders';
            } elseif (str_starts_with($action, 'product.')) {
                $module = 'products';
            } elseif (str_starts_with($action, 'customer.')) {
                $module = 'customers';
            } elseif (str_starts_with($action, 'email.')) {
                $module = 'email';
            }

            $rawDetails = $input['details'] ?? [];
            if (is_string($rawDetails)) {
                $rawDetails = json_decode($rawDetails, true) ?: ['raw' => $rawDetails];
            }

            ActivityLog::create([
                'uuid' => (string) Str::uuid(),
                'actor_type' => 'admin',
                'actor_id' => $input['actorId'] ?? null,
                'actor_name' => $input['actorName'] ?? 'System',
                'actor_role' => 'admin',
                'event_name' => $action,
                'module' => $module,
                'subject_type' => $input['targetType'] ?? null,
                'subject_id' => isset($input['targetId']) ? (string) $input['targetId'] : null,
                'description' => ucwords(str_replace(['.', '_'], ' ', $action)),
                'metadata' => is_array($rawDetails) ? ActivityLoggerService::redactSensitiveData($rawDetails) : null,
                'source' => 'admin',
                'status' => $input['status'] ?? 'success',
                'ip_address' => $input['ipAddress'] ?? null,
                'location' => isset($input['ipAddress']) ? IpLocationService::lookup($input['ipAddress'])['formatted'] : null,
                'user_agent' => $input['userAgent'] ?? null,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silently log or ignore failure to write audit log
            logger()->error('Failed to write audit log: '.$e->getMessage());
        }
    }

    public static function getLogs(int $limit = 100)
    {
        return AdminAuditLog::orderBy('created_at', 'desc')->limit($limit)->get();
    }
}
