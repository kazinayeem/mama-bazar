<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Detects whether the email system migrations have been applied, so admin
 * pages can show setup instructions instead of failing on missing tables.
 */
class EmailSchema
{
    /**
     * @var array<string, list<string>>
     */
    public const REQUIRED = [
        'email_suppressions' => ['email', 'reason'],
        'email_campaign_recipients' => ['campaign_id', 'email', 'status'],
        'email_logs' => ['dedupe_key', 'template_key', 'last_attempt_at'],
        'email_campaigns' => ['content_json', 'audience_params', 'confirmed_at'],
        'users' => ['email_verification_required', 'marketing_opt_in_at'],
    ];

    protected static ?bool $ready = null;

    public static function isReady(): bool
    {
        if (static::$ready !== null) {
            return static::$ready;
        }

        if (Cache::get('mamabazar:email_schema_ready') === true) {
            return static::$ready = true;
        }

        static::$ready = static::missing() === [];

        if (static::$ready) {
            Cache::put('mamabazar:email_schema_ready', true, now()->addHour());
        }

        return static::$ready;
    }

    /**
     * @return list<string>
     */
    public static function missing(): array
    {
        $missing = [];

        try {
            foreach (self::REQUIRED as $table => $columns) {
                if (! Schema::hasTable($table)) {
                    $missing[] = "table {$table}";

                    continue;
                }
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        $missing[] = "{$table}.{$column}";
                    }
                }
            }
        } catch (Throwable $e) {
            $missing[] = 'database unavailable';
        }

        return $missing;
    }

    public static function flush(): void
    {
        static::$ready = null;
        Cache::forget('mamabazar:email_schema_ready');
    }
}
