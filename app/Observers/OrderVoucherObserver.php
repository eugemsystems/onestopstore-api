<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\VoucherService;
use App\Mail\GiftVoucherMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderVoucherObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "updated" event.
     * Generate vouchers when order payment is completed
     */
    public function updated(Order $order): void
    {
        // Check if payment_status just changed to 'completed'
        if ($order->isDirty('payment_status') && strtolower($order->payment_status) === 'completed') {
            $this->generateVouchersForOrder($order);
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }

    /**
     * Generate vouchers for gift card products in the order
     */
    protected function generateVouchersForOrder(Order $order)
    {
        try {
            // Load products if not already loaded
            if (!$order->relationLoaded('products')) {
                $order->load('products');
            }

            $vouchers = VoucherService::generateVouchersForOrder($order);

            if (count($vouchers) > 0) {


                // Send email with voucher details
                try {
                    if ($order->consumer && $order->consumer->email) {
                        Mail::to($order->consumer->email)
                            ->send(new GiftVoucherMail($order, $vouchers));
                    } else {
                        Log::warning("No customer email found for order #{$order->order_number}, voucher email not sent");
                    }
                } catch (\Exception $emailError) {
                    Log::error('Failed to send gift voucher email', [
                        'order_id' => $order->id,
                        'error' => $emailError->getMessage(),
                    ]);
                    // Don't throw - vouchers are generated, email failure shouldn't break the process
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate vouchers for order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

