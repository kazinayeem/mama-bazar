<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Sends one order lifecycle email. Retries with backoff on SMTP or PDF
 * failures; the email log row is reused so attempts are counted once.
 */
class SendTransactionalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $orderId,
        public string $triggerType,
        public ?string $dedupeKey = null
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $order = Order::find($this->orderId);
        if (! $order) {
            return;
        }

        $finalAttempt = $this->job === null || $this->attempts() >= $this->tries;
        $result = OrderEmailService::send($order, $this->triggerType, $this->dedupeKey, $finalAttempt);

        $failed = ! ($result['success'] ?? false) && ! ($result['skipped'] ?? false);
        if (($result['retry'] ?? false) || ($failed && ! $finalAttempt)) {
            throw new RuntimeException((string) ($result['error'] ?? 'Order email failed.'));
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error("Order email '{$this->triggerType}' failed for order id {$this->orderId}: ".$exception->getMessage());
    }
}
