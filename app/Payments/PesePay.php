<?php

namespace App\Payments;

use App\Models\PesepayTransaction; // align to app model
use Exception;
use App\Models\Order;
use App\Helpers\Helpers;
use App\Enums\PaymentStatus;
use App\Http\Traits\PaymentTrait;
use App\Http\Traits\TransactionsTrait;
use App\GraphQL\Exceptions\ExceptionHandler;
use Codevirtus\Payments\Pesepay as PesepayGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PesePay
{
    use TransactionsTrait, PaymentTrait;

    public static function webhookHandler($request)
    {
        try {
            $data = $request->all();

//            Log::info('Pesepay webhook: incoming', [
//                'data' => $data,
//                'headers' => $request->headers->all(),
//                'ip' => $request->ip(),
//            ]);

            // Normalize common fields from various possible payloads
            $statusRaw = strtolower((string)($data['status'] ?? $data['transactionStatus'] ?? $data['state'] ?? $data['payment_status'] ?? ''));
            $successFlag = null;
            if (array_key_exists('success', $data)) {
                $val = $data['success'];
                $successFlag = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($successFlag === null && is_numeric($val)) {
                    $successFlag = ((int)$val) === 1;
                }
            }

            $reference = $data['referenceNumber']
                ?? $data['reference']
                ?? $data['transactionReference']
                ?? $data['txn_id']
                ?? $data['id']
                ?? null;
            $amount   = (float)($data['amount'] ?? $data['transactionAmount'] ?? $data['total'] ?? 0);
            $currency = (string)($data['currency'] ?? $data['transactionCurrency'] ?? 'USD');

            // Attempt to resolve Order
            $order = null;
            $orderId = $data['order_id'] ?? $data['orderId'] ?? null;
            if ($orderId) {
                $order = Order::find($orderId);
            }

            if (!$order) {
                $orderNumber = $data['order_number'] ?? $data['orderNumber'] ?? null;

                // Fallback: try parse from description/narrative
                if (!$orderNumber) {
                    $desc = $data['description']
                        ?? $data['reason']
                        ?? $data['reasonForPayment']
                        ?? $data['merchantReference']
                        ?? $data['message']
                        ?? $data['narrative']
                        ?? null;
                    if ($desc && preg_match('/order\s*(number)?\s*[:#-]?\s*(\d+)/i', (string)$desc, $m)) {
                        $orderNumber = $m[2] ?? null;
                    }
                }

                if ($orderNumber) {
                    $order = Order::where('order_number', $orderNumber)->first();
                }
            }

            // Persist raw transaction for audit/ops visibility into vendor schema (unique reference_number)
            try {
                if ($reference) {
                    $reason = $data['reasonForPayment'] ?? ($data['description'] ?? null);
                    $redirectUrl = $data['redirectUrl'] ?? null;
                    $resultUrl   = $data['resultUrl'] ?? null;
                    $pollUrl     = $data['pollUrl'] ?? null;
                    $returnUrl   = $data['returnUrl'] ?? null;

                    DB::table('pesepay_transactions')->upsert([
                        [
                            'reference_number'   => (string) $reference,
                            'transaction_status' => $statusRaw ?: null,
                            'amount'             => $amount ?: null,
                            'currency_code'      => $currency ?: null,
                            'reason_for_payment' => $reason,
                            'redirect_url'       => $redirectUrl,
                            'result_url'         => $resultUrl,
                            'poll_url'           => $pollUrl,
                            'return_url'         => $returnUrl,
                            'response'           => json_encode($data),
                            'updated_at'         => now(),
                            'created_at'         => now(),
                        ],
                    ], ['reference_number'], ['transaction_status','amount','currency_code','reason_for_payment','redirect_url','result_url','poll_url','return_url','response','updated_at']);
                } else {
                    // Fallback when no reference provided: store minimal log into our app model table
                    PesepayTransaction::create([
                        'order_id' => $order?->id,
                        'reference_number' => 'NO-REF-' . Str::uuid(),
                        'gateway_transaction_id' => null,
                        'status' => $statusRaw ?: ($successFlag === true ? 'paid' : ($successFlag === false ? 'failed' : null)),
                        'amount' => $amount ?: null,
                        'currency' => $currency ?: null,
                        'raw_response' => $data,
                        'other_fields' => [
                            'headers' => $request->headers->all(),
                            'ip' => $request->ip(),
                        ],
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Pesepay webhook: failed to persist transaction', ['error' => $e->getMessage()]);
            }

            // Determine outcome
            $successValues = ['paid','success','successful','completed','approved','ok'];
            $failedValues  = ['failed','declined','error','cancelled','canceled'];
            $isSuccess = in_array($statusRaw, $successValues, true) || $successFlag === true;
            $isFailed  = in_array($statusRaw, $failedValues, true) || $successFlag === false;

            if ($order) {
                // Ensure payment method is set
                self::updateOrderPaymentMethod($order, 'pesepay');

                // Check if this is a layby payment before updating order
                if ($order->note && preg_match('/TEMP_LAYBY_PAYMENT:(\d+)/', $order->note, $laybyMatches)) {

                    // Determine payment status
                    $paymentStatus = 'pending';
                    if ($isSuccess) {
                        $paymentStatus = 'completed';
                    } elseif ($isFailed) {
                        $paymentStatus = 'failed';
                    }

                    // Call layby webhook handler with the actual LaybyPayment ID (not order ID)
                    $laybyPaymentId = $laybyMatches[1];
                    \App\Http\Controllers\LaybyController::handleWebhookCallback(
                        $laybyPaymentId,
                        $reference,
                        $paymentStatus
                    );

                    return response()->json(['ok' => true]);
                }

                // Regular order payment - continue with existing logic
                if ($isSuccess) {
                    self::updateOrderPaymentStatus($order, PaymentStatus::COMPLETED);
                    //Log::info('Pesepay webhook: order marked paid', ['order' => $order->order_number, 'reference' => $reference]);
                } elseif ($isFailed) {
                    self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
                    //Log::warning('Pesepay webhook: order marked failed', ['order' => $order->order_number, 'reference' => $reference]);
                }
            }

            return response()->json(['ok' => true]);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public static function getIntent(Order $order, $request)
    {
        try {

            $transactionId = $order->id . '-' . Str::random(10);

            if (!self::verifyOrderTransaction($order->id, $transactionId)) {
                self::storeOrderTransaction($order, $transactionId, $request->payment_method);
            }

            // Use order->total directly - it's already updated with wallet/points deductions
            // ALWAYS add delivery_price to the total sent to payment gateway
            $computedTotal = (float) ($order->total ?? 0);
            $deliveryPrice = (float) ($order->delivery_price ?? 0);
            $computedTotal = $computedTotal + $deliveryPrice;

            // Fallback only if total is somehow zero or negative
            if ($computedTotal <= 0) {
                $sub  = (float) ($order->amount ?? 0);
                $ship = (float) ($order->shipping_total ?? 0);
                $fast = (float) ($order->fast_shipping_total ?? 0);
                $del  = (float) ($order->delivery_price ?? 0);
                $tax  = (float) ($order->tax_total ?? 0);
                $disc = abs((float) ($order->coupon_total_discount ?? 0));
                $pts  = abs((float) ($order->points_amount ?? 0));
                $wal  = abs((float) ($order->wallet_balance ?? 0));
                $computedTotal = max(0.01, ($sub + $ship + $fast + $del + $tax) - $disc - $pts - $wal);
            }


            $amountUsd = (float) (method_exists(Helpers::class, 'convertToUSD')
                ? Helpers::convertToUSD($computedTotal)
                : $computedTotal);
            $amountUsd = max(0.01, (float) number_format((float) $amountUsd, 2, '.', ''));

            // URLs
            $returnUrl = ($request->return_url ?? config('pesepay.return_url'));
            $returnUrl = rtrim((string) $returnUrl, '/') . '/' . $order->order_number;
            $resultUrl = env('PESEPAY_RESULT_URL') ?: (config('pesepay.result_url') ?: route('pesepay.webhook'));

            // Initialize gateway
            $pesepay = new PesepayGateway(
                config('pesepay.integration_key'),
                config('pesepay.encryption_key')
            );
            if (method_exists($pesepay, 'setReturnUrl')) $pesepay->setReturnUrl($returnUrl); else $pesepay->returnUrl = $returnUrl;
            if (method_exists($pesepay, 'setResultUrl')) $pesepay->setResultUrl($resultUrl); else $pesepay->resultUrl = $resultUrl;

            $description = 'Payment for order number:' . $order->order_number;
//            Log::info('PesePay init: start', [
//                'order'      => $order->order_number,
//                'amount_usd' => $amountUsd,
//                'returnUrl'  => $returnUrl,
//                'resultUrl'  => $resultUrl,
//            ]);

            // Create and initiate transaction
            $tx = method_exists($pesepay, 'createTransaction')
                ? $pesepay->createTransaction($amountUsd, 'USD', $description)
                : null;

            $resp = $tx && method_exists($pesepay, 'initiateTransaction')
                ? $pesepay->initiateTransaction($tx)
                : (method_exists($pesepay, 'initiatePayment')
                    ? $pesepay->initiatePayment($amountUsd, 'USD', $description)
                    : null);

            // Normalize response
            $success = false; $redirectUrl = null; $reference = null; $message = null;
            if (is_object($resp)) {
                if (method_exists($resp, 'success'))          $success     = (bool) $resp->success();
                if (method_exists($resp, 'redirectUrl'))      $redirectUrl = $resp->redirectUrl();
                if (method_exists($resp, 'referenceNumber'))  $reference   = $resp->referenceNumber();
                if (method_exists($resp, 'message'))          $message     = $resp->message();
            } elseif (is_array($resp)) {
                $success     = (bool) ($resp['success'] ?? false);
                $redirectUrl = $resp['redirectUrl'] ?? $resp['redirect_url'] ?? null;
                $reference   = $resp['referenceNumber'] ?? $resp['reference'] ?? null;
                $message     = $resp['message'] ?? null;
            } elseif (is_string($resp)) {
                $json = json_decode($resp, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $success     = (bool) ($json['success'] ?? false);
                    $redirectUrl = $json['redirectUrl'] ?? $json['redirect_url'] ?? null;
                    $reference   = $json['referenceNumber'] ?? $json['reference'] ?? null;
                    $message     = $json['message'] ?? null;
                }
            }

            if ($success && $redirectUrl) {
//                Log::info('PesePay init: success', [
//                    'order'     => $order->order_number,
//                    'reference' => $reference,
//                    'redirect'  => $redirectUrl,
//                ]);

                return [
                    'order_number'   => $order->order_number,
                    'transaction_id' => $transactionId,
                    'url'            => (string) $redirectUrl,
                    'is_redirect'    => true,
                ];
            }

            Log::error('PesePay init: failure', [
                'order'   => $order->order_number,
                'message' => $message ?? 'Unknown error',
            ]);
            throw new Exception($message ?: 'PesePay init failed');

        } catch (Exception $e) {

            //self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create a PesePay payment intent and return the redirect URL.
     *
     * @param  \App\Models\Order  $order
     * @param  \Illuminate\Http\Request  $request
     * @return array{payment_method:string,url:string,reference:string}
     *
     * @throws \RuntimeException
     */
    public static function getIntent_(Order $order, $request): array
    {
        // --- amount (USD) ---
        $baseAmount = $request->input('amount', $request->input('grand_total', $order->amount ?? 0));
        $baseAmount = is_numeric($baseAmount)
            ? (float) $baseAmount
            : (float) preg_replace('/[^\d.\-]/', '', (string) $baseAmount);

        if ($baseAmount <= 0) {
            throw new \RuntimeException('Invalid amount for PesePay init.');
        }

        if (class_exists('\\App\\Helpers\\Helpers') && method_exists(\App\Helpers\Helpers::class, 'convertToUSD')) {
            try {
                $amountUsd = (float) \App\Helpers\Helpers::convertToUSD($baseAmount);
            } catch (\Throwable $e) {
                Log::warning('PesePay convertToUSD failed, using raw amount', ['e' => $e->getMessage()]);
                $amountUsd = $baseAmount;
            }
        } else {
            $amountUsd = $baseAmount;
        }
        $amountUsd = max(0.01, (float) number_format($amountUsd, 2, '.', ''));

        // --- URLs ---
        $returnUrl = config('app.url') . '/api/pesepay/return?order=' . urlencode($order->order_number);
        $resultUrl = config('app.url') . '/api/pesepay/notify';

        if (!app()->isProduction()) {
            $returnUrl = env('PESEPAY_RETURN_URL', $returnUrl); // e.g. https://<ngrok>/api/pesepay/return?order=...
            $resultUrl = env('PESEPAY_RESULT_URL', $resultUrl); // e.g. https://<ngrok>/api/pesepay/notify
        }

        // --- SDK ---
        $pesepay = new PesepayGateway(
            config('pesepay.integration_key'),
            config('pesepay.encryption_key')
        );

        // set URLs (support setters or public props)
        if (method_exists($pesepay, 'setReturnUrl')) $pesepay->setReturnUrl($returnUrl); else $pesepay->returnUrl = $returnUrl;
        if (method_exists($pesepay, 'setResultUrl')) $pesepay->setResultUrl($resultUrl); else $pesepay->resultUrl = $resultUrl;

        $description = 'Payment for order number:' . $order->order_number;

//        Log::info('PesePay init: pre-flight', [
//            'order'      => $order->order_number,
//            'amount_usd' => $amountUsd,
//            'returnUrl'  => $returnUrl,
//            'resultUrl'  => $resultUrl,
//        ]);

        // --- initiate ---
        $tx = method_exists($pesepay, 'createTransaction')
            ? $pesepay->createTransaction($amountUsd, 'USD', $description)
            : null;

        $resp = $tx && method_exists($pesepay, 'initiateTransaction')
            ? $pesepay->initiateTransaction($tx)
            : (method_exists($pesepay, 'initiatePayment')
                ? $pesepay->initiatePayment($amountUsd, 'USD', $description)
                : null);

        // --- log raw response shape (safe) ---
        $respType = gettype($resp);
        $respForLog = $respType === 'object'
            ? (method_exists($resp, 'toArray') ? $resp->toArray() : ['class' => get_class($resp)])
            : ($respType === 'string' ? (mb_strimwidth($resp, 0, 400, '…')) : $resp);

        //Log::info('PesePay init: raw response', ['type' => $respType, 'resp' => $respForLog]);

        // --- normalize response ---
        $success = false;
        $reference = $redirectUrl = $pollUrl = null;
        $message = null;

        if (is_object($resp)) {
            if (method_exists($resp, 'success'))          $success     = (bool) $resp->success();
            if (method_exists($resp, 'referenceNumber'))  $reference   = $resp->referenceNumber();
            if (method_exists($resp, 'redirectUrl'))      $redirectUrl = $resp->redirectUrl();
            if (method_exists($resp, 'pollUrl'))          $pollUrl     = $resp->pollUrl();
            if (method_exists($resp, 'message'))          $message     = $resp->message();
        } elseif (is_array($resp)) {
            $success     = (bool) ($resp['success'] ?? false);
            $reference   = $resp['referenceNumber'] ?? $resp['reference'] ?? null;
            $redirectUrl = $resp['redirectUrl'] ?? $resp['redirect_url'] ?? null;
            $pollUrl     = $resp['pollUrl'] ?? null;
            $message     = $resp['message'] ?? null;
        } elseif (is_string($resp)) {
            $json = json_decode($resp, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $success     = (bool) ($json['success'] ?? false);
                $reference   = $json['referenceNumber'] ?? $json['reference'] ?? null;
                $redirectUrl = $json['redirectUrl'] ?? $json['redirect_url'] ?? null;
                $pollUrl     = $json['pollUrl'] ?? null;
                $message     = $json['message'] ?? null;
            }
        } else {
            // null/unknown
            $message = 'Null response from PesePay SDK.';
        }

        if ($success && $redirectUrl) {
            // persist for later verification
            $order->update([
                'pesepay_reference' => $reference,
                'pesepay_poll_url'  => $pollUrl,
            ]);

//            Log::info('PesePay init: success', [
//                'order'     => $order->order_number,
//                'reference' => $reference,
//                'redirect'  => $redirectUrl,
//                'poll'      => $pollUrl,
//            ]);

            return [
                'payment_method' => 'pesepay',
                'url'            => (string) $redirectUrl,
                'reference'      => (string) ($reference ?? ''),
            ];
        }

//        Log::error('PesePay init: failure', [
//            'order'   => $order->order_number,
//            'message' => $message ?? 'Unknown error',
//        ]);

        throw new \RuntimeException($message ?: 'PesePay init failed');
    }

    public static function status(Order $order, $transaction_id)
    {
        try {
            return $order;
        } catch (Exception $e) {
            //throw new ExceptionHandler($e->getMessage(), $e->getCode());
            throw new Exception('Pesepay status check failed: ' . $e->getMessage(), $e->getCode());
        }
    }
}
