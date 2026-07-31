<?php

namespace App\Listeners;

use App\Enums\OrderEnum;
use App\Enums\PayfastStatus;
use App\Enums\PaymentStatus;
use App\Helpers\Helpers;
use App\Http\Traits\PaymentTrait;
use App\Models\Order;
use Eugem\Payfast\Events\PaymentSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandlePayfastPayment
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
        $ipnData = $event->getData();

        $order = Order::withTempLaybyOrders()->find($ipnData['custom_int1']);

        if (!$order) {
            Log::warning('HandlePayfastPayment: order not found', ['custom_int1' => $ipnData['custom_int1']]);
            return;
        }

        switch ($ipnData['payment_status']) {
            case 'COMPLETE':
                $status = PayfastStatus::COMPLETED;
                break;
            default:
                $status = PayfastStatus::CANCELLED;
        }

        //Update order status
        DB::Transaction(function () use ($order, $status, $ipnData) {
            if($status === PayfastStatus::COMPLETED) {
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
            return self::updateOrderPaymentStatus($order, $status);
        });
    }
}
