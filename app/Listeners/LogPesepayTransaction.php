<?php

namespace App\Listeners;

use App\Models\Order;
use App\Models\PesepayTransaction as AppPesepayTransaction;
use Eugem\Pesepay\Events\PaymentSucceeded;
use Eugem\Pesepay\Events\PaymentFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogPesepayTransaction
{
    /**
     * Handle both success and failure Pesepay events.
     * Accepts a generic $event to support multiple event classes.
     */
    public function handle($event): void
    {
        // Extract data from event, normalize to associative array
        $data = $event->getData() ?? [];

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        } elseif (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        } elseif (!is_array($data)) {
            $data = [];
        }

        // Unwrap if wrapped as [ 'data' => [ ... ] ]
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        // Derive order number from reasonForPayment or merchantReference
        $orderNumber = null;
        $reason = (string) ($data['reasonForPayment'] ?? '');
        if (preg_match('/order\s*(number)?\s*[:#-]?\s*(\d+)/i', $reason, $m)) {
            $orderNumber = (int)($m[2] ?? 0);
        } elseif (!empty($data['merchantReference'])) {
            if (preg_match('/(\d{3,})/', (string) $data['merchantReference'], $m2)) {
                $orderNumber = (int)($m2[1] ?? 0);
            }
        }

        $orderId = null;
        if ($orderNumber) {
            $order = Order::withTempLaybyOrders()->where('order_number', $orderNumber)->first();
            $orderId = $order?->id;
        }

        $status = strtoupper((string)($data['transactionStatus'] ?? ($event instanceof PaymentSucceeded ? 'SUCCESS' : 'FAILED')));

        $amount = null;
        $currency = null;
        if (isset($data['amountDetails'])) {
            $amountDetails = $data['amountDetails'];
            if (is_array($amountDetails)) {
                $amount = $amountDetails['amount'] ?? null;
                $currency = $amountDetails['currencyCode'] ?? null;
            } elseif (is_object($amountDetails)) {
                $amount = $amountDetails->amount ?? null;
                $currency = $amountDetails->currencyCode ?? null;
            }
        }

        // reference_number is NOT NULL in the DB — always supply a value
        $referenceNumber = (string)($data['referenceNumber']
            ?? $data['internalReference']
            ?? $data['merchantReference']
            ?? null);

        if (empty($referenceNumber)) {
            $referenceNumber = 'NO-REF-' . Str::uuid();
        }

        $gatewayTransactionId = $referenceNumber;

        $other = [
            'referenceNumber'            => $data['referenceNumber'] ?? null,
            'internalReference'          => $data['internalReference'] ?? null,
            'merchantReference'          => $data['merchantReference'] ?? null,
            'applicationId'              => $data['applicationId'] ?? null,
            'applicationName'            => $data['applicationName'] ?? null,
            'resultUrl'                  => $data['resultUrl'] ?? null,
            'returnUrl'                  => $data['returnUrl'] ?? null,
            'pollUrl'                    => $data['pollUrl'] ?? null,
            'transactionStatusCode'      => $data['transactionStatusCode'] ?? null,
            'transactionStatusDescription' => $data['transactionStatusDescription'] ?? null,
            'transactionMetadata'        => $data['transactionMetadata'] ?? null,
        ];

        try {
            // Use updateOrCreate to avoid unique constraint violations on reference_number.
            // The vendor's PesepayController::callback() may also log the same reference.
            AppPesepayTransaction::updateOrCreate(
                ['reference_number' => $referenceNumber],
                [
                    'order_id'               => $orderId,
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'status'                 => $status,
                    'amount'                 => is_numeric($amount) ? (float)$amount : null,
                    'currency'               => $currency,
                    'raw_response'           => $data,
                    'other_fields'           => $other,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Pesepay: failed to log transaction', [
                'error'   => $e->getMessage(),
                'payload' => $data,
            ]);
        }

        // ---------------------------------------------------------------
        // Handle layby payment completion via the vendor callback route.
        // Our /pesepay/webhook route is NOT hit — Pesepay calls /pesepay/callback
        // (vendor route) which fires this event. So we resolve the layby here.
        // ---------------------------------------------------------------
        try {
            $isSuccess = $event instanceof PaymentSucceeded;

            // Find the temp order by order number to detect TEMP_LAYBY_PAYMENT note
            if ($orderNumber) {
                $tempOrder = Order::withTempLaybyOrders()->where('order_number', $orderNumber)->first();

                if ($tempOrder && $isSuccess) {
                    // Mark auction deposit as paid if this is an AUC_DEPOSIT order
                    if ($tempOrder->isAuctionDepositOrder()) {
                        \App\Models\AuctionBidDeposit::where('order_id', $tempOrder->id)
                            ->whereNull('paid_at')
                            ->update(['paid_at' => now()]);

                        Log::info('Pesepay listener: auction deposit marked paid', [
                            'order_id'     => $tempOrder->id,
                            'order_number' => $orderNumber,
                        ]);
                    }
                }

                if ($tempOrder && $tempOrder->note && preg_match('/TEMP_LAYBY_PAYMENT:(\d+)/', $tempOrder->note, $m)) {
                    $laybyPaymentId = (int) $m[1];
                    $paymentStatus  = $isSuccess ? 'completed' : 'failed';

                    \App\Http\Controllers\LaybyController::handleWebhookCallback(
                        $laybyPaymentId,
                        $referenceNumber,
                        $paymentStatus
                    );

                    Log::info('Pesepay listener: layby callback handled', [
                        'layby_payment_id' => $laybyPaymentId,
                        'payment_status'   => $paymentStatus,
                        'reference'        => $referenceNumber,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Pesepay listener: failed to handle layby callback', [
                'error'        => $e->getMessage(),
                'order_number' => $orderNumber ?? null,
                'reference'    => $referenceNumber,
            ]);
        }
    }
}
