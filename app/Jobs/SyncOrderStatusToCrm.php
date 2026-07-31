<?php

namespace App\Jobs;

use App\Http\Resources\Crm\OrderResource;
use App\Http\Resources\Crm\OrderStatusResource;
use App\Models\OrderStatus;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncOrderStatusToCrm implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct( public int $orderId,
                                 public string $event)
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            if (!config('services.crm.enabled') || !env('CRM_SYNC_ORDER_STATUS', true)) {
                return;
            }

            $os = OrderStatus::query()->find($this->orderId);
            if (!$os) return;

            $payload = [
                'event'  => $this->event,
                'orderstatus'  => (new OrderStatusResource($os))->resolve(),
            ];

            $raw     = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $secret  = config('services.crm.webhook_secret');
            $sig     = hash_hmac('sha256', $raw, $secret);
            $key     = $os->name;

            $url = config('services.crm.webhook_url').'/webhooks/order/statuses';
            $url_ =  "http://localhost:8008/api/webhooks/order/statuses";

            $resp = Http::timeout(10)
                ->retry(5, 500)
                ->acceptJson()
                ->withHeaders([
                    'X-Signature'       => "sha256={$sig}",
                    'X-Idempotency-Key' => $key,
                ])
                ->withBody($raw, 'application/json')
                ->post($url);

            if (!$resp->successful()) {
                Log::error('SyncOrderStatusToCrm failed', [
                    'order_id' => $os->id,
                    'status'   => $resp->status(),
                    'body'     => $resp->body(),
                ]);
                $resp->throw();
            }
        }Catch (Exception $e) {
            Log::error('SyncOrderStatusToCrm failed', [$e->getMessage()]);
        }
    }

}
