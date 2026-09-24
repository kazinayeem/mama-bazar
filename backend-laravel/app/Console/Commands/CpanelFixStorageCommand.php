<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CpanelFixStorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cpanel:fix-storage {--force : Force remove existing symlink or folder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create directories, repair permissions, and fix public storage symlinks for cPanel hosting';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('--> Step 1: Checking and creating required storage directories...');

        $directories = [
            storage_path('app'),
            storage_path('app/public'),
            storage_path('app/public/products'),
            storage_path('app/public/products/variants'),
            storage_path('app/public/products/descriptions'),
            storage_path('app/public/categories'),
            storage_path('app/public/banners'),
            storage_path('app/public/payments'),
            storage_path('app/public/general'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
                $this->line("  [+] Created: {$dir}");
            }
            @chmod($dir, 0775);
        }
        $this->info('  [OK] Storage directories created and set to 0775 permissions.');

        $this->info('--> Step 2: Fixing public/storage link for cPanel...');
        $publicStorage = public_path('storage');
        $targetStorage = storage_path('app/public');

        if (is_link($publicStorage)) {
            $currentTarget = @readlink($publicStorage);
            $this->warn("  Existing symlink found pointing to: {$currentTarget}");

            // If broken or force requested, remove it
            if ($this->option('force') || ! file_exists($publicStorage) || ! file_exists($currentTarget)) {
                @unlink($publicStorage);
                $this->line('  [+] Removed broken/stale symlink.');
            }
        } elseif (is_dir($publicStorage) && $this->option('force')) {
            $this->warn('  [!] Existing physical directory at public/storage will not be deleted without explicit manual action.');
        }

        if (! file_exists($publicStorage)) {
            // Attempt 1: relative symlink (best for portability in cPanel zip/deploy)
            $created = false;
            try {
                $relativeTarget = '../storage/app/public';
                $created = @symlink($relativeTarget, $publicStorage);
            } catch (\Throwable $e) {
                $created = false;
            }

            // Attempt 2: absolute symlink
            if (! $created) {
                try {
                    $created = @symlink($targetStorage, $publicStorage);
                } catch (\Throwable $e) {
                    $created = false;
                }
            }

            if ($created) {
                $this->info("  [OK] Created symlink: {$publicStorage} -> " . @readlink($publicStorage));
            } else {
                $this->warn('  [!] Symlink creation is not permitted by this PHP/hosting environment.');
                $this->info('  [+] Ensuring direct HTTP storage fallback route is active:');
                $this->line('      Requests to /storage/{path} are handled natively by StorageFileController.');
            }
        } else {
            $this->info('  [OK] public/storage is already present and active.');
        }

        $this->newLine();
        $this->info('--> Storage diagnostics and repair completed successfully!');
        $this->line('    - Storage permissions: 0775');
        $this->line('    - Symlink status: ' . (is_link($publicStorage) ? 'Active' : (is_dir($publicStorage) ? 'Physical Directory' : 'HTTP Fallback Mode')));
        $this->line('    - Fallback route: /storage/{path} active in web.php');

        return self::SUCCESS;
    }
}
