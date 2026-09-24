<?php

namespace App\Services;

use App\Models\AdminAuditLog;

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
        } catch (\Throwable $e) {
            // Silently log or ignore failure to write audit log
            logger()->error('Failed to write audit log: ' . $e->getMessage());
        }
    }

    public static function getLogs(int $limit = 100)
    {
        return AdminAuditLog::orderBy('created_at', 'desc')->limit($limit)->get();
    }
}
