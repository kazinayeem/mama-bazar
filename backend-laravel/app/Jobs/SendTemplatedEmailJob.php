<?php

namespace App\Jobs;

use App\Services\EmailDispatcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sends a stored template to one recipient (welcome, security notices,
 * contact form). Never used for OTPs or reset links — those secrets must
 * not be serialized into the queue.
 */
class SendTemplatedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, scalar|null>  $data
     * @param  array{dedupe_key?: string|null, user_id?: int|null, log_id?: int|null, order_id?: int|null}  $options
     */
    public function __construct(
        public string $templateKey,
        public string $to,
        public ?string $name,
        public array $data,
        public string $emailType = 'transactional',
        public array $options = []
    ) {
        if (empty($this->options['dedupe_key']) && empty($this->options['log_id'])) {
            $this->options['dedupe_key'] = 'tpl:'.Str::uuid()->toString();
        }
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $result = EmailDispatcherService::sendTemplate(
            $this->templateKey,
            $this->to,
            $this->name,
            $this->data,
            $this->emailType,
            array_merge($this->options, [
                'replay' => [
                    'kind' => 'template',
                    'template_key' => $this->templateKey,
                    'name' => $this->name,
                    'data' => $this->data,
                    'type' => $this->emailType,
                ],
            ])
        );

        $finalAttempt = $this->job === null || $this->attempts() >= $this->tries;
        if (! $result['success'] && ! ($result['skipped'] ?? false) && ! $finalAttempt) {
            throw new RuntimeException((string) $result['error']);
        }
    }
}
