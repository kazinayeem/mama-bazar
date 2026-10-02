<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class SqliteToMysqlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sqlite-to-mysql {--sqlite= : Path to SQLite file} {--force : Overwrite existing MySQL data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate schema and copy all data directly from SQLite to MySQL database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sqlitePath = $this->option('sqlite') ?: database_path('database.sqlite');

        if (! file_exists($sqlitePath)) {
            $this->error("SQLite database file not found at: {$sqlitePath}");

            return self::FAILURE;
        }

        $this->info('--> Step 1: Testing MySQL database connection...');
        try {
            DB::connection('mysql')->getPdo();
            $dbName = DB::connection('mysql')->getDatabaseName();
            $this->info("  [OK] Connected to MySQL database: '{$dbName}'");
        } catch (\Throwable $e) {
            $this->error('  [!] MySQL connection failed: '.$e->getMessage());
            $this->line('      Please verify DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env');

            return self::FAILURE;
        }

        $this->info('--> Step 2: Running migrations on MySQL...');
        $this->call('migrate', [
            '--database' => 'mysql',
            '--force' => true,
        ]);

        $this->info('--> Step 3: Copying all data from SQLite to MySQL...');
        $sqlitePdo = new PDO("sqlite:{$sqlitePath}");
        $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tables = $sqlitePdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

        $mysql = DB::connection('mysql');
        $mysql->statement('SET FOREIGN_KEY_CHECKS=0;');

        $totalCopied = 0;

        foreach ($tables as $table) {
            $stmt = $sqlitePdo->query("SELECT * FROM \"{$table}\"");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($rows);

            if ($count === 0) {
                continue;
            }

            // Clean existing table in MySQL
            $mysql->table($table)->truncate();

            // Insert in chunks of 100
            $chunks = array_chunk($rows, 100);
            foreach ($chunks as $chunk) {
                $mysql->table($table)->insert($chunk);
            }

            $totalCopied += $count;
            $this->line("  [+] Copied `{$table}`: {$count} records");
        }

        $mysql->statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->newLine();
        $this->info('--> Successfully migrated from SQLite to MySQL!');
        $this->line("    - Total records copied: {$totalCopied}");
        $this->line("    - Active MySQL connection: '{$dbName}'");

        return self::SUCCESS;
    }
}
