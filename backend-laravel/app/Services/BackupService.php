<?php

namespace App\Services;

use App\Models\AdminBackup;
use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use DateTime;
use DateTimeZone;
use ZipArchive;
use Exception;

class BackupService
{
    const TABLE_RESTORE_ORDER = [
        "site_settings",
        "admin_roles",
        "admin_permissions",
        "role_permissions",
        "categories",
        "brands",
        "collections",
        "colors",
        "sizes",
        "vendors",
        "suppliers",
        "shipping_methods",
        "payment_methods",
        "checkout_notices",
        "policy_pages",
        "banners",
        "media_assets",
        "expense_categories",
        "users",
        "user_addresses",
        "user_permissions",
        "products",
        "product_variants",
        "product_specs",
        "product_relations",
        "reviews",
        "coupons",
        "orders",
        "order_items",
        "order_status_history",
        "marketing_integrations",
        "tracking_logs",
        "newsletters",
        "contact_messages",
        "expenses",
        "costs",
        "bookings",
        "rentals",
        "memos",
        "admin_audit_logs",
    ];

    public static function getExpectedBackupPins(): array
    {
        $now = new DateTime();

        $format = function (DateTime $dt, ?string $tzName = null): string {
            $cloned = clone $dt;
            if ($tzName) {
                $cloned->setTimezone(new DateTimeZone($tzName));
            }
            return $cloned->format('dmY');
        };

        $pins = array_unique([
            $format($now),
            $format($now, 'Asia/Dhaka'),
            $format($now, 'UTC'),
        ]);

        return array_values(array_filter($pins, fn($p) => strlen($p) === 8));
    }

    public static function validateBackupPin(?string $inputPin): bool
    {
        if (!$inputPin) return false;
        $cleanPin = trim($inputPin);
        return in_array($cleanPin, self::getExpectedBackupPins(), true);
    }

    public static function validatePin(?string $inputPin): bool
    {
        return self::validateBackupPin($inputPin);
    }

    public static function getValidPins(): array
    {
        return self::getExpectedBackupPins();
    }

    public static function listBackups()
    {
        return AdminBackup::orderBy('created_at', 'desc')->get();
    }

    /**
     * Alias for listBackups() — returns collection as array-accessible items.
     */
    public static function getBackupList(): \Illuminate\Database\Eloquent\Collection
    {
        return self::listBackups();
    }

    /**
     * Returns a hint string for the PIN challenge shown in the backup UI.
     */
    public static function getPinChallenge(): string
    {
        $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
        return 'Today\'s date in DDMMYYYY format (' . $now->format('d/m/Y') . ')';
    }

    /**
     * Alias for validateBackupPin().
     */
    public static function verifyPin(?string $inputPin): bool
    {
        return self::validateBackupPin($inputPin);
    }

    public static function getBackupById(int $id): ?AdminBackup
    {
        return AdminBackup::find($id);
    }

    public static function createBackup(array $options = []): AdminBackup
    {
        $type = $options['type'] ?? 'manual';
        $timestamp = (new DateTime())->format('Y-m-d\TH-i-s');
        $filename = "mamabazar-backup-{$type}-{$timestamp}.zip";

        $storageDir = storage_path('app/backups');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        $zipPath = $storageDir . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to create ZIP file at {$zipPath}");
        }

        $manifest = [
            'formatVersion' => '1.0',
            'application' => 'MamaBazar',
            'createdAt' => (new DateTime())->format(DateTime::ATOM),
            'type' => $type,
            'tableCount' => 0,
            'totalRecords' => 0,
            'tables' => [],
        ];

        $tableCount = 0;
        $totalRecords = 0;

        foreach (self::TABLE_RESTORE_ORDER as $table) {
            if (!Schema::hasTable($table)) continue;

            $rows = DB::table($table)->get()->map(function ($row) use ($table) {
                $item = (array) $row;
                if ($table === 'users') {
                    unset($item['reset_token_hash'], $item['reset_token_expires_at']);
                }
                return $item;
            })->toArray();

            $count = count($rows);
            $manifest['tables'][$table] = $count;
            $tableCount++;
            $totalRecords += $count;

            $zip->addFromString(
                "database/{$table}.json",
                json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        $manifest['tableCount'] = $tableCount;
        $manifest['totalRecords'] = $totalRecords;

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->addFromString('metadata.json', json_encode([
            'version' => '1.0.0',
            'app' => 'MamaBazar E-Commerce',
            'engine' => 'Laravel 12 / MySQL',
            'environment' => config('app.env'),
            'createdByName' => $options['actorName'] ?? 'System',
            'storage' => 'local',
        ], JSON_PRETTY_PRINT));

        $zip->close();

        $size = file_exists($zipPath) ? filesize($zipPath) : 0;

        $backup = AdminBackup::create([
            'filename' => $filename,
            'filepath' => $zipPath,
            'size' => $size,
            'type' => $type,
            'table_count' => $tableCount,
            'record_count' => $totalRecords,
            'created_by_id' => $options['createdById'] ?? null,
        ]);

        if (($options['actorName'] ?? null)) {
            AdminAuditLog::create([
                'actor_id' => $options['createdById'] ?? null,
                'actor_name' => $options['actorName'] ?? 'Super Admin',
                'actor_email' => $options['actorEmail'] ?? null,
                'action' => 'BACKUP_CREATED',
                'target_type' => 'Database',
                'target_id' => (string) $backup->id,
                'details' => json_encode(['filename' => $filename, 'type' => $type, 'size' => $size, 'tableCount' => $tableCount, 'recordCount' => $totalRecords]),
                'ip_address' => $options['ip'] ?? null,
                'user_agent' => $options['userAgent'] ?? null,
                'status' => 'success',
            ]);
        }

        return $backup;
    }

    public static function restoreBackup(string $zipPath, array $actor = []): array
    {
        if (!file_exists($zipPath)) {
            throw new Exception("Backup archive file not found");
        }

        // Safety backup first
        $safetyBackup = self::createBackup([
            'type' => 'safety_auto',
            'createdById' => $actor['id'] ?? null,
            'actorName' => $actor['name'] ?? 'Super Admin',
            'actorEmail' => $actor['email'] ?? null,
            'ip' => $actor['ip'] ?? null,
            'userAgent' => $actor['userAgent'] ?? null,
        ]);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new Exception("Failed to open backup archive");
        }

        $manifestJson = $zip->getFromName('manifest.json');
        if (!$manifestJson) {
            $zip->close();
            throw new Exception("manifest.json missing from backup archive");
        }

        $isMysql = DB::connection()->getDriverName() === 'mysql';

        try {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            }

            $restoredTablesCount = 0;
            $restoredRecordsCount = 0;

            foreach (self::TABLE_RESTORE_ORDER as $table) {
                $tableJson = $zip->getFromName("database/{$table}.json");
                if ($tableJson === false) continue;

                $rows = json_decode($tableJson, true);
                if (!is_array($rows)) continue;

                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();

                    foreach (array_chunk($rows, 100) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }

                    $restoredTablesCount++;
                    $restoredRecordsCount += count($rows);
                }
            }

            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            }

            $zip->close();

            AdminAuditLog::create([
                'actor_id' => $actor['id'] ?? null,
                'actor_name' => $actor['name'] ?? 'Super Admin',
                'actor_email' => $actor['email'] ?? null,
                'action' => 'RESTORE_SUCCESS',
                'target_type' => 'Database',
                'details' => json_encode([
                    'restoredTables' => $restoredTablesCount,
                    'restoredRecords' => $restoredRecordsCount,
                    'safetyBackupId' => $safetyBackup->id,
                    'safetyBackupFilename' => $safetyBackup->filename,
                ]),
                'ip_address' => $actor['ip'] ?? null,
                'user_agent' => $actor['userAgent'] ?? null,
                'status' => 'success',
            ]);

            return [
                'success' => true,
                'restoredTablesCount' => $restoredTablesCount,
                'restoredRecordsCount' => $restoredRecordsCount,
                'safetyBackupFilename' => $safetyBackup->filename,
            ];
        } catch (Exception $e) {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            }
            $zip->close();

            AdminAuditLog::create([
                'actor_id' => $actor['id'] ?? null,
                'actor_name' => $actor['name'] ?? 'Super Admin',
                'actor_email' => $actor['email'] ?? null,
                'action' => 'RESTORE_FAILED',
                'target_type' => 'Database',
                'details' => json_encode(['error' => $e->getMessage(), 'safetyBackupId' => $safetyBackup->id]),
                'ip_address' => $actor['ip'] ?? null,
                'user_agent' => $actor['userAgent'] ?? null,
                'status' => 'failure',
            ]);

            throw new Exception("Database restore failed: {$e->getMessage()}. Current state preserved via safety backup '{$safetyBackup->filename}'.");
        }
    }

    public static function deleteBackup(int $id, array $actor = []): bool
    {
        $backup = AdminBackup::find($id);
        if (!$backup) {
            throw new Exception("Backup not found", 404);
        }

        if (file_exists($backup->filepath)) {
            @unlink($backup->filepath);
        }

        $backup->delete();

        AdminAuditLog::create([
            'actor_id' => $actor['id'] ?? null,
            'actor_name' => $actor['name'] ?? 'Super Admin',
            'actorEmail' => $actor['email'] ?? null,
            'action' => 'BACKUP_DELETED',
            'target_type' => 'Backup',
            'target_id' => (string) $id,
            'details' => json_encode(['filename' => $backup->filename]),
            'ip_address' => $actor['ip'] ?? null,
            'user_agent' => $actor['userAgent'] ?? null,
        ]);

        return true;
    }
}
