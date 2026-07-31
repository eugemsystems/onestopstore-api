<?php

namespace App\Listeners;

use App\Enums\OrderEnum;
use App\Enums\PaymentStatus;
use App\Helpers\Helpers;
use App\Http\Traits\PaymentTrait;
use App\Models\Order;
use Eugem\Pesepay\Events\PaymentSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandlePesePayPayment
{
    use PaymentTrait;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentSucceeded $event)
    {
        $data = (array) ($event->getData() ?? []);
        //Log::info('Pesepay listener: PaymentSucceeded received', ['payload' => $data]);

        // Resolve order number robustly
        $orderNumber = null;
        if (isset($data['order_number'])) {
            $orderNumber = (int) $data['order_number'];
        } elseif (!empty($data['reasonForPayment'])) {
            if (preg_match('/order\s*(number)?\s*[:#-]?\s*(\d+)/i', (string) $data['reasonForPayment'], $m)) {
                $orderNumber = (int) ($m[2] ?? 0);
            }
        } elseif (!empty($data['merchantReference'])) {
            if (preg_match('/(\d{3,})/', (string) $data['merchantReference'], $m)) {
                $orderNumber = (int) $m[1];
            }
        }

        if (!$orderNumber) {
            Log::warning('Pesepay listener: could not resolve order_number from payload');
            return;
        }

        $order = Order::withTempLaybyOrders()->where('order_number', $orderNumber)->first();
        if (!$order) {
            Log::warning('Pesepay listener: order not found', ['order_number' => $orderNumber]);
            return;
        }

        // Map gateway status to internal PaymentStatus
        $gatewayStatus = strtoupper((string) ($data['transactionStatus'] ?? ''));
        $status = match ($gatewayStatus) {
            'SUCCESS'            => PaymentStatus::COMPLETED,
            'PENDING', 'INITIATED', 'PROCESSING', 'PARTIALLY_PAID' => PaymentStatus::PROCESSING,
            'DECLINED', 'CANCELLED', 'ERROR', 'FAILED', 'AUTHORIZATION_FAILED', 'INSUFFICIENT_FUNDS', 'REVERSED', 'TERMINATED', 'TIME_OUT', 'CLOSED', 'CLOSED_PERIOD_ELAPSED', 'SERVICE_UNAVAILABLE' => PaymentStatus::FAILED,
            default              => PaymentStatus::FAILED,
        };

        // Update order in a transaction
        DB::transaction(function () use ($order, $status) {
            if ($status === PaymentStatus::COMPLETED) {
                // Don't overwrite a cancelled order
                $currentName = (string) ($order->order_status->name ?? '');
                if (strtolower(trim($currentName)) !== strtolower(trim(OrderEnum::CANCELLED))) {
                    $order->update([
                        'order_status_id' => Helpers::getOrderStatusIdByName(OrderEnum::PROCESSING),
                    ]);
                }

                // Mark any linked auction deposit as paid
                // (covers AUC_DEPOSIT orders; no-op for regular orders)
                \App\Models\AuctionBidDeposit::where('order_id', $order->id)
                    ->whereNull('paid_at')
                    ->update(['paid_at' => now()]);
            }
            self::updateOrderPaymentStatus($order, $status);
        });
    }
}
