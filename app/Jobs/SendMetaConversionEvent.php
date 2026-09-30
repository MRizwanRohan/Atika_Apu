<?php

namespace App\Jobs;

use App\Models\MetaEventLog;
use App\Models\Order;
use App\Services\MetaConversion\MetaConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMetaConversionEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum attempts before moving to failed_jobs.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds between retries: 1min → 5min → 15min.
     */
    public array $backoff = [60, 300, 900];

    /**
     * Timeout for each attempt in seconds.
     */
    public int $timeout = 30;

    public function __construct(
        public readonly Order $order,
        public readonly string $trigger = 'system',
        public readonly bool $forceResend = false
    ) {}

    public function handle(MetaConversionService $service): void
    {
        $result = $service->sendPurchaseEvent(
            $this->order,
            $this->trigger,
            $this->forceResend
        );

        if (!$result['success']) {
            if (($result['retryable'] ?? true) === false) {
                return;
            }
            // Only re-throw (and thus retry) if it wasn't a "already sent" or
            // "disabled" scenario — those don't need retrying.
            $nonRetryable = [
                'CAPI is disabled',
                'already sent',
                'not configured',
            ];

            foreach ($nonRetryable as $phrase) {
                if (str_contains($result['message'], $phrase)) {
                    Log::info('MetaConversionEvent: skipped (non-retryable)', [
                        'order_id' => $this->order->id,
                        'reason'   => $result['message'],
                    ]);
                    return; // Don't retry
                }
            }

            // Retryable failure — throw to trigger backoff retry
            throw new \RuntimeException('MetaConversionEvent failed: ' . $result['message']);
        }

        Log::info('MetaConversionEvent: sent successfully', [
            'order_id' => $this->order->id,
            'event_id' => $result['event_id'],
        ]);
    }

    /**
     * Handle a job that has exhausted all retries.
     */
    public function failed(\Throwable $exception): void
    {
        // Update the log entry status to 'failed' if it exists
        $log = MetaEventLog::where('order_id', $this->order->id)
            ->where('event_name', 'Purchase')
            ->where('status', 'pending')
            ->latest()
            ->first();

        $log?->markFailed('Max attempts reached: ' . $exception->getMessage());

        Log::error('MetaConversionEvent: permanently failed', [
            'order_id' => $this->order->id,
            'error'    => $exception->getMessage(),
        ]);
    }
}
