<?php

namespace App\Console\Commands;

use App\Services\IncompleteOrderService;
use Illuminate\Console\Command;

class PruneCheckoutSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkout-sessions:prune 
                            {--days=30 : Retention threshold in days for stale session cleanup}
                            {--evaluate-only : Only evaluate inactivity transitions without purging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate inactivity and prune stale incomplete checkout sessions according to the data retention policy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Evaluating checkout session inactivity...');
        $abandonedCount = IncompleteOrderService::evaluateInactivity();
        $this->info("Marked {$abandonedCount} inactive sessions as incomplete/expired.");

        if ($this->option('evaluate-only')) {
            return self::SUCCESS;
        }

        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $this->info("Pruning checkout sessions older than {$days} days...");
        $prunedCount = IncompleteOrderService::pruneOldSessions($days);
        $this->info("Pruned {$prunedCount} stale checkout sessions successfully.");

        return self::SUCCESS;
    }
}
