<?php

namespace App\Listeners;

use App\Helpers\Helpers;
use Eugem\Pesepay\Events\PaymentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandlePesepayFailedPayments
{
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
    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        try {
            $ipnData = $event->getData();

            $data = [
                'data' => $ipnData,
                'user_id' => Helpers::getCurrentUserId(),
                'transaction_purpose' => $ipnData['referenceNumber'],
                'amount' => $ipnData['amountDetails']['amount'],
                'transaction_date' => now(),
                'payment_method' => 'Pesepay',
                'currency' => $ipnData['amountDetails']['currencyCode'],
                'reference' => $ipnData['referenceNumber'],
                'gateway_reference' => $ipnData['referenceNumber'],
                'status' => $ipnData['transactionStatus'],
                'remarks' => $ipnData['reasonForPayment'],
                'processed_by' =>  Helpers::getCurrentUserId(),
                'processed_at' => now(),
                'gateway_response' => json_encode($ipnData),
            ];

            Log::error('Failed to log Pesepay payment failure', [
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling Pesepay failed payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
