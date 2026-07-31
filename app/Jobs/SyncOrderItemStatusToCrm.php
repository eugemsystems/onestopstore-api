<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncOrderItemStatusToCrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = [10, 30, 60];

    protected $orderId;
    protected $productId;
    protected $variationId;
    protected $itemStatus;
    protected $cancellationReason;
    protected $eta;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $orderId,
        int $productId,
        ?int $variationId,
        string $itemStatus,
        ?string $cancellationReason,
        ?string $eta
    ) {
        $this->orderId = $orderId;
        $this->productId = $productId;
        $this->variationId = $variationId;
        $this->itemStatus = $itemStatus;
        $this->cancellationReason = $cancellationReason;
        $this->eta = $eta;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $crmBaseUrl = rtrim(config('services.crm.base_url'), '/');

        if (!$crmBaseUrl) {
            return;
        }

        $webhookPayload = [
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'variation_id' => $this->variationId,
            'item_status' => $this->itemStatus,
            'cancellation_reason' => $this->cancellationReason,
            'eta' => $this->eta,
        ];

        try {;

            $response = Http::timeout(5)
                ->retry(2, 100)
                ->post($crmBaseUrl . '/api/webhooks/order/item-status', $webhookPayload);

            if (!$response->successful()) {


                // Retry the job if it's not a client error (4xx)
                if ($response->status() >= 500) {
                    throw new \Exception('CRM returned server error: ' . $response->status());
                }
            }
        } catch (\Throwable $e) {


            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CRM sync job failed after all retries', [
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'error' => $exception->getMessage(),
        ]);
    }
}

