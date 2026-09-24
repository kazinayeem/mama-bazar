<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;

class ExportMysqlDumpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:export-mysql {--output= : Destination sql filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export full SQLite database to a MySQL-compatible .sql dump file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $outputFile = $this->option('output') ?: database_path('mama_bazar_dump.sql');
        $sqlitePath = database_path('database.sqlite');

        if (! file_exists($sqlitePath)) {
            $this->error("SQLite database not found at: {$sqlitePath}");
            return self::FAILURE;
        }

        $this->info("--> Reading SQLite database from: {$sqlitePath}");
        $sqlitePdo = new PDO("sqlite:{$sqlitePath}");
        $sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Prepare MySQL DDL collector
        $this->info('--> Generating MySQL schema from migrations...');
        $ddlQueries = $this->generateMysqlSchema($sqlitePdo);

        $fh = fopen($outputFile, 'w');
        if (! $fh) {
            $this->error("Failed to open output file: {$outputFile}");
            return self::FAILURE;
        }

        // Header
        fwrite($fh, "-- Mama Bazar MySQL Database Dump\n");
        fwrite($fh, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($fh, "-- Target Server: MySQL 5.7+ / MariaDB 10.3+ (cPanel / phpMyAdmin compatible)\n\n");
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($fh, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($fh, "SET AUTOCOMMIT = 0;\n");
        fwrite($fh, "START TRANSACTION;\n");
        fwrite($fh, "SET time_zone = \"+00:00\";\n\n");
        fwrite($fh, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
        fwrite($fh, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
        fwrite($fh, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
        fwrite($fh, "/*!40101 SET NAMES utf8mb4 */;\n\n");

        // Migrations table DDL
        fwrite($fh, "-- Table structure for `migrations`\n");
        fwrite($fh, "DROP TABLE IF EXISTS `migrations`;\n");
        fwrite($fh, "CREATE TABLE `migrations` (\n");
        fwrite($fh, "  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,\n");
        fwrite($fh, "  `migration` varchar(191) NOT NULL,\n");
        fwrite($fh, "  `batch` int(11) NOT NULL,\n");
        fwrite($fh, "  PRIMARY KEY (`id`)\n");
        fwrite($fh, ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n");

        // Schema DDL
        foreach ($ddlQueries as $table => $queries) {
            fwrite($fh, "-- Table structure for `{$table}`\n");
            fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
            foreach ($queries as $q) {
                fwrite($fh, $q . ";\n");
            }
            fwrite($fh, "\n");
        }

        // 2. Export Data
        $this->info('--> Exporting table data...');
        $tables = $sqlitePdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

        $totalRowsExported = 0;

        foreach ($tables as $table) {
            $stmt = $sqlitePdo->query("SELECT * FROM \"{$table}\"");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rowCount = count($rows);

            if ($rowCount === 0) {
                continue;
            }

            $totalRowsExported += $rowCount;
            $this->line("  [+] Table `{$table}`: {$rowCount} rows");

            fwrite($fh, "-- Dumping data for table `{$table}`\n");

            // Chunk inserts in batches of 50
            $chunks = array_chunk($rows, 50);
            foreach ($chunks as $chunk) {
                $columns = array_keys($chunk[0]);
                $colList = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

                $valueSets = [];
                foreach ($chunk as $row) {
                    $vals = [];
                    foreach ($columns as $col) {
                        $val = $row[$col] ?? null;
                        if ($val === null) {
                            $vals[] = 'NULL';
                        } elseif (is_numeric($val) && ! str_starts_with((string) $val, '0') || $val === '0') {
                            $vals[] = $val;
                        } else {
                            // Escape string safely for MySQL
                            $escaped = str_replace(
                                ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
                                ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
                                (string) $val
                            );
                            $vals[] = "'" . $escaped . "'";
                        }
                    }
                    $valueSets[] = '(' . implode(', ', $vals) . ')';
                }

                fwrite($fh, "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $valueSets) . ";\n");
            }

            fwrite($fh, "\n");
        }

        // Footer
        fwrite($fh, "COMMIT;\n");
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n\n");
        fwrite($fh, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
        fwrite($fh, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n");
        fwrite($fh, "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");

        fclose($fh);

        // Also create a copy named mama_bazar_mysql.sql for convenience
        $altOutput = database_path('mama_bazar_mysql.sql');
        @copy($outputFile, $altOutput);

        $fileSizeMB = round(filesize($outputFile) / (1024 * 1024), 2);
        $this->newLine();
        $this->info("--> Successfully exported MySQL dump!");
        $this->line("    - File: {$outputFile} ({$fileSizeMB} MB)");
        $this->line("    - Copy: {$altOutput}");
        $this->line("    - Total records exported: {$totalRowsExported}");
        $this->line("    - Ready for direct import into phpMyAdmin on cPanel!");

        return self::SUCCESS;
    }

    /**
     * Generate MySQL DDL queries for all tables using Laravel migration Blueprints.
     */
    protected function generateMysqlSchema(PDO $sqlitePdo): array
    {
        $myConn = new class($sqlitePdo, 'mama_bazar') extends MySqlConnection {
            public array $collectedQueries = [];

            public function __construct($pdo, $database)
            {
                $this->pdo = $pdo;
                $this->database = $database;
                $this->useDefaultPostProcessor();
                $this->useDefaultSchemaGrammar();
                $this->useDefaultQueryGrammar();
            }

            public function statement($query, $bindings = [])
            {
                $this->collectedQueries[] = $query;
                return true;
            }

            public function hasTable($table)
            {
                return false;
            }

            public function hasColumn($table, $column)
            {
                return true;
            }
        };

        $originalSchema = Schema::getFacadeRoot();
        Schema::swap($myConn->getSchemaBuilder());

        $migrationFiles = [
            database_path('migrations/2024_01_01_000001_create_users_table.php'),
            database_path('migrations/2024_01_01_000002_create_rbac_tables.php'),
            database_path('migrations/2024_01_01_000003_create_catalog_base_tables.php'),
            database_path('migrations/2024_01_01_000004_create_products_tables.php'),
            database_path('migrations/2024_01_01_000005_create_commerce_support_tables.php'),
            database_path('migrations/2024_01_01_000006_create_orders_tables.php'),
            database_path('migrations/2024_01_01_000007_create_site_marketing_tables.php'),
            database_path('migrations/2024_01_01_000008_create_operations_tables.php'),
        ];

        foreach ($migrationFiles as $file) {
            if (file_exists($file)) {
                $migration = require $file;
                if (is_object($migration) && method_exists($migration, 'up')) {
                    $migration->up();
                }
            }
        }

        // Restore original schema
        Schema::swap($originalSchema);

        // Group queries by primary table
        $tables = [];
        $currentTable = null;

        foreach ($myConn->collectedQueries as $query) {
            if (preg_match('/create table `([^`]+)`/i', $query, $matches)) {
                $currentTable = $matches[1];
                $tables[$currentTable] = [$query];
            } elseif ($currentTable && preg_match('/alter table `([^`]+)`/i', $query, $matches)) {
                $alterTable = $matches[1];
                if (isset($tables[$alterTable])) {
                    $tables[$alterTable][] = $query;
                } else {
                    $tables[$alterTable] = [$query];
                }
            }
        }

        return $tables;
    }
}
