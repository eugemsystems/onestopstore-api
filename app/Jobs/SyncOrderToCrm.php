<?php

namespace App\Jobs;

use App\Models\Order;
use App\Http\Resources\Crm\OrderResource;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncOrderToCrm implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public int $orderId,
        public string $event   // 'created' | 'updated'
    ) {}

    public function handle(): void
    {

        if (!config('services.crm.enabled')) {
            return;
        }

        if (!env('CRM_SYNC_ORDER', true)) {
            return;
        }

        // Fresh query to ensure we have the latest data from database
        $order = Order::query()->find($this->orderId);
        if (!$order) {
            return;
        }

        // IMPORTANT: Only sync MAIN orders (parent_id = NULL)
        // Sub-orders should NOT be synced separately - their products are included in the main order
        if ($order->parent_id !== null) {

            return;
        }

        // Skip syncing orders with pending status
        $orderStatusName = strtolower(trim($order->order_status?->name ?? ''));

        if ($orderStatusName === 'pending') {
            return;
        }

        if ($orderStatusName === 'pending') {
            return;
        }

        // Skip syncing auto-cancelled orders (cancelled by system, not manually)
        $orderNote = $order->note ?? '';


        if (str_contains($orderNote, '[AUTO_CANCELLED]')) {

            return;
        }

        $payload = [
            'event'  => $this->event,
            'order'  => (new OrderResource($order))->resolve(),
        ];

        $items = $payload['order']['items'] ?? [];
        // Convert Collection to array if needed
        if ($items instanceof \Illuminate\Support\Collection) {
            $items = $items->all();
        }

        $raw     = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $secret  = config('services.crm.webhook_secret');
        $sig     = hash_hmac('sha256', $raw, $secret);
        $key     = 'order-'.$order->id.'-'.$order->updated_at?->timestamp;

        $url = config('services.crm.webhook_url').'/webhooks/orders';

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
            $resp->throw();
        }

    }
}
