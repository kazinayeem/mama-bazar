<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\EmailDispatcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendTransactionalEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30];

    public function __construct(
        public int $orderId,
        public string $triggerType
    ) {}

    public function handle(): void
    {
        $order = Order::find($this->orderId);
        if (!$order) {
            return;
        }

        EmailDispatcherService::dispatchOrderEmail($order, $this->triggerType);
    }

    public function failed(Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error("SendTransactionalEmailJob failed for Order #{$this->orderId} ({$this->triggerType}): " . $exception->getMessage());
    }
}
