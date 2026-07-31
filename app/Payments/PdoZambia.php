<?php

namespace App\Payments;

use Exception;
use App\Models\Order;
use App\Helpers\Helpers;
use App\Enums\PaymentStatus;
use App\Http\Traits\PaymentTrait;
use App\Http\Traits\TransactionsTrait;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\DpoZambiaTransaction;

class PdoZambia
{
    use TransactionsTrait, PaymentTrait;

    private static function strFrom($data, $key)
    {
        $v = is_array($data) ? ($data[$key] ?? null) : null;
        if (is_array($v) || is_object($v)) return json_encode($v);
        return isset($v) ? (string) $v : '';
    }

    private static function floatFrom($data, $key)
    {
        $v = is_array($data) ? ($data[$key] ?? null) : null;
        if (is_array($v) || is_object($v) || $v === null) return null;
        return (float) $v;
    }

    private static function boolFrom($data, $key)
    {
        $v = is_array($data) ? ($data[$key] ?? null) : null;
        if (is_string($v)) return in_array(strtolower($v), ['1','true','yes','y','t','on'], true);
        return (bool) $v;
    }

    /**
     * Handle DPO (Zambia) webhook callbacks.
     */
    public static function webhookHandler($request)
    {
        try {
//            Log::info('DPO Zambia WebhookHandler', [
//                'headers' => $request->headers->all(),
//                'request' => $request->all(),
//            ]);

            // Attempt to resolve order by a common reference field
            $reference = $request->input('order_number')
                ?? $request->input('CompanyRef')
                ?? $request->input('Reference')
                ?? $request->input('OrderID')
                ?? $request->input('PnrID');

            if ($reference) {
                // Use withTempLaybyOrders() to also find AUC_DEPOSIT and TEMP_LAYBY orders
                // (ExcludeTempLaybyScope global scope hides these from Helpers::getOrderByOrderNumber)
                $order = Order::withTempLaybyOrders()
                    ->with(config('enums.order.with', []))
                    ->where('order_number', $reference)
                    ->first();
                if ($order) {
                    // Normalize various DPO response indicators
                    $rawStatus   = (string) ($request->input('TransactionStatus')
                        ?? $request->input('Result')
                        ?? $request->input('status')
                        ?? '');
                    $resultCode  = (string) ($request->input('ResultCode') ?? '');
                    $ccApproval  = trim((string) ($request->input('CCDapproval') ?? ''));
                    $transId     = (string) ($request->input('TransID')
                        ?? $request->input('TransactionToken')
                        ?? $request->input('TransToken')
                        ?? $request->input('Token')
                        ?? '');

                    // Persist the real gateway transaction id if provided
                    if ($transId !== '') {
                        $order->order_transactions()->updateOrCreate(
                            ['order_id' => $order->id],
                            ['transaction_id' => $transId]
                        );
                    }

                    // Determine status
                    $raw = strtolower($rawStatus);
                    $isSuccess = false;

                    // If we have a token/id, verify with DPO first as the source of truth
                    if ($transId !== '') {
                        $verify = self::verifyWithDpo($transId);
                        if (is_array($verify)) {
                            $vStatus  = strtolower((string) ($verify['TransactionStatus'] ?? $verify['Result'] ?? ''));
                            $vCode    = (string) ($verify['ResultCode'] ?? $verify['Result'] ?? '');
                            $vApprove = trim((string) ($verify['CCDapproval'] ?? ''));

                            if (in_array($vStatus, ['paid', 'success', 'completed', 'approved'], true)) {
                                $isSuccess = true;
                            }
                            if (!$isSuccess && in_array($vCode, ['0', '000'], true)) {
                                $isSuccess = true;
                            }
                            if (!$isSuccess && $vApprove !== '') {
                                $isSuccess = true;
                            }
                        }
                    }

                    // Explicit textual success (fallback)
                    if (!$isSuccess && in_array($raw, ['paid', 'success', 'completed', 'approved'], true)) {
                        $isSuccess = true;
                    }

                    // Approval/auth code present is commonly success (browser redirect case)
                    if (!$isSuccess && $ccApproval !== '') {
                        $isSuccess = true;
                    }

                    // Result codes: treat 0/000 as success when present
                    if (!$isSuccess && $resultCode !== '') {
                        $isSuccess = in_array($resultCode, ['0', '000'], true);
                    }

                    // Map final status
                    if ($isSuccess) {
                        $status = PaymentStatus::COMPLETED;
                    } else {
                        $status = match ($raw) {
                            'failed', 'declined', 'error', 'expired'   => PaymentStatus::FAILED,
                            'cancelled', 'canceled'                     => PaymentStatus::CANCELLED,
                            default                                     => PaymentStatus::PENDING,
                        };
                    }

                    // Persist successful DPO transaction details to dedicated table
                    if ($status === PaymentStatus::COMPLETED) {
                        try {
                            $verifyData = null;
                            if (isset($verify) && is_array($verify)) {
                                $verifyData = $verify;
                            } else {
                                $verifyData = self::verifyWithDpo($transId);
                            }
                            if (is_array($verifyData)) {
                                //Log::info('DPO verifyToken response', ['order_id' => $order->id, 'verify' => $verifyData]);
                                $tx = DpoZambiaTransaction::create([
                                    'order_id'             => $order->id,
                                    'raw_response'         => $verifyData,
                                    'trans_id'             => self::strFrom($verifyData, 'TransID'),
                                    'transaction_token'    => self::strFrom($verifyData, 'TransactionToken') ?: self::strFrom($verifyData, 'TransToken') ?: $transId,
                                    'result'               => self::strFrom($verifyData, 'Result'),
                                    'result_code'          => self::strFrom($verifyData, 'ResultCode'),
                                    'result_explanation'   => self::strFrom($verifyData, 'ResultExplanation'),
                                    'transaction_status'   => self::strFrom($verifyData, 'TransactionStatus'),
                                    'ccd_approval'         => self::strFrom($verifyData, 'CCDapproval'),
                                    'company_ref'          => self::strFrom($verifyData, 'CompanyRef'),
                                    'transaction_currency' => self::strFrom($verifyData, 'TransactionCurrency'),
                                    'payment_amount'       => self::floatFrom($verifyData, 'PaymentAmount'),
                                    'customer_name'        => self::strFrom($verifyData, 'CustomerName'),
                                    'customer_phone'       => self::strFrom($verifyData, 'CustomerPhone'),
                                    'customer_email'       => self::strFrom($verifyData, 'CustomerEmail'),
                                    'customer_country'     => self::strFrom($verifyData, 'CustomerCountry'),
                                    'fraud_alert'          => self::boolFrom($verifyData, 'FraudAlert'),
                                    'fraud_explanation'    => self::strFrom($verifyData, 'FraudExplanation'),
                                    'date_created'         => self::strFrom($verifyData, 'DateCreated') ? date('Y-m-d H:i:s', strtotime(self::strFrom($verifyData, 'DateCreated'))) : null,
                                    'date_approved'        => self::strFrom($verifyData, 'DateApproved') ? date('Y-m-d H:i:s', strtotime(self::strFrom($verifyData, 'DateApproved'))) : null,
                                    'other_fields'         => [
                                        'IP' => self::strFrom($verifyData, 'IP') ?: null,
                                        'Country' => self::strFrom($verifyData, 'Country') ?: null,
                                        'callback' => $request->all(),
                                    ],
                                ]);
                                //Log::info('DPO Zambia transaction stored', ['tx_id' => $tx->id, 'order_id' => $order->id]);
                            }
                        } catch (\Throwable $e) {
                            report($e);
                        }
                    }

                    // Check if this is a layby payment before updating order
                    if ($order->note && preg_match('/TEMP_LAYBY_PAYMENT:(\d+)/', $order->note, $laybyMatches)) {

                        // Map payment status
                        $paymentStatus = match($status) {
                            PaymentStatus::COMPLETED => 'completed',
                            PaymentStatus::FAILED => 'failed',
                            PaymentStatus::CANCELLED => 'cancelled',
                            default => 'pending',
                        };

                        // Call layby webhook handler with the actual LaybyPayment ID (not order ID)
                        $laybyPaymentId = $laybyMatches[1];
                        \App\Http\Controllers\LaybyController::handleWebhookCallback(
                            $laybyPaymentId,
                            $transId,
                            $paymentStatus
                        );

                        return response()->json(['ok' => true]);
                    }

                    // Mark any linked auction deposit as paid
                    if ($status === PaymentStatus::COMPLETED) {
                        \App\Models\AuctionBidDeposit::where('order_id', $order->id)
                            ->whereNull('paid_at')
                            ->update(['paid_at' => now()]);

                        Log::info('DPO Zambia: auction deposit marked paid', ['order_id' => $order->id]);
                    }

                    // Regular order payment - continue with existing logic
                    return self::updateOrderPaymentStatus($order, $status);
                }
            }

            return [];
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Build payment intent for DPO (Zambia). Returns a redirect URL for the client.
     */
    private static function verifyWithDpo($tokenOrId)
    {
        try {
            $cfg  = config('services.dpo', []);
            $base = rtrim((string) ($cfg['base_url'] ?? ''), '/');
            $token = (string) $tokenOrId;

            if ($token === '') {
                return null;
            }

            $xml = new \SimpleXMLElement('<API3G/>' );
            $xml->addChild('CompanyToken', (string) ($cfg['company_token'] ?? ''));
            $xml->addChild('Request', 'verifyToken');
            // DPO variants observed: TransToken vs TransactionToken
            $xml->addChild('TransToken', $token);
            $xml->addChild('TransactionToken', $token);

            $candidates = [
                '/API/v6/Transactions/VerifyToken', // explicit v6 path
                '/API/v6/',                         // generic v6 handler by Request field
            ];
            $resp = null; $url = null;
            foreach ($candidates as $ep) {
                $url = $base . $ep;
                $resp = Http::timeout(25)
                    ->withHeaders([
                        'Accept'       => 'application/xml',
                        'Content-Type' => 'application/xml',
                    ])
                    ->send('POST', $url, ['body' => $xml->asXML()]);
                if ($resp->successful()) {
                    break;
                }
            }

            $body = (string) $resp?->body();
            $xmlResp = @simplexml_load_string($body);
            if (!$xmlResp) {
                return null;
            }

            $arr = json_decode(json_encode($xmlResp), true);
            return is_array($arr) ? $arr : null;
        } catch (\Throwable $e) {
            report($e);
            return null;
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

            $baseAmount = $computedTotal;
            $currency   = $order->currency ?? (method_exists(Helpers::class, 'getDefaultCurrencyCode') ? Helpers::getDefaultCurrencyCode() : 'ZMW');
            $currencyU  = strtoupper((string) $currency);

            switch ($currencyU) {
                case 'ZMW':
                case 'ZMK':
                    $amount = Helpers::convertToZMK($baseAmount);
                    $currency = 'ZMW';
                    break;
                case 'ZAR':
                    $amount = Helpers::convertToZAR($baseAmount);
                    break;
                case 'INR':
                    $amount = Helpers::convertToINR($baseAmount);
                    break;
                case 'USD':
                    $amount = Helpers::convertToUSD($baseAmount);
                    break;
                default:
                    $rate = (float) (method_exists(Helpers::class, 'getCurrencyExchangeRate') ? (Helpers::getCurrencyExchangeRate($currencyU) ?? 1) : 1);
                    $amount = Helpers::roundNumber($baseAmount * $rate);
                    break;
            }

            // Build return/cancel URLs: use API return handler then redirect to front-end
            $frontReturn = rtrim((string) ($request->return_url ?? (config('services.dpo.return_url') ?: (config('app.url') . '/orders/return'))), '/') . '/' . $order->order_number;
            $frontCancel = rtrim((string) (config('services.dpo.cancel_url') ?: (config('app.url') . '/orders/cancel')), '/') . '/' . $order->order_number;

            $params = [
                'return_url'     => route('dpo.return', ['redirect' => $frontReturn]),
                'cancel_url'     => route('dpo.return', ['redirect' => $frontCancel, 'cancel' => 1]),
                'notify_url'     => route('dpo.webhook'),
                'amount'         => $amount,
                'currency'       => $currency,
                'description'    => 'Payment for order number:' . $order->order_number,
                'reference'      => $order->order_number,
                'customer_email' => (string) ($order->consumer->email ?? ''),
                'customer_name'  => (string) ($order->consumer->name ?? ''),
            ];

            return [
                'order_number'   => $order->order_number,
                'transaction_id' => $transactionId,
                'url'            => route('dpo.redirect', $params),
                'is_redirect'    => true,
            ];
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Optionally poll DPO for payment status using stored transaction reference.
     * Currently returns the order unchanged to avoid external calls without credentials.
     */
    public static function status(Order $order, $transaction_id)
    {
        try {
            $token = (string) ($transaction_id ?: ($order->order_transactions()->latest()->value('transaction_id') ?? ''));

            if ($token === '') {
                return $order;
            }

            $verify = self::verifyWithDpo($token);
            if (!is_array($verify)) {
                return $order;
            }

            $vStatus  = strtolower((string) ($verify['TransactionStatus'] ?? $verify['Result'] ?? ''));
            $vCode    = (string) ($verify['ResultCode'] ?? $verify['Result'] ?? '');
            $vApprove = trim((string) ($verify['CCDapproval'] ?? ''));

            $isSuccess = false;
            if (in_array($vStatus, ['paid', 'success', 'completed', 'approved'], true)) {
                $isSuccess = true;
            }
            if (!$isSuccess && in_array($vCode, ['0', '000'], true)) {
                $isSuccess = true;
            }
            if (!$isSuccess && $vApprove !== '') {
                $isSuccess = true;
            }

            $status = $isSuccess ? PaymentStatus::COMPLETED : PaymentStatus::FAILED;

            // Persist real transaction id to the order if not set or different
            $transId = (string) ($verify['TransID'] ?? $verify['TransactionToken'] ?? $verify['TransToken'] ?? '');
            if ($transId !== '') {
                $order->order_transactions()->updateOrCreate(
                    ['order_id' => $order->id],
                    ['transaction_id' => $transId]
                );
            }

            // Persist successful DPO transaction details
            if ($status === PaymentStatus::COMPLETED) {
                try {
                    //Log::info('DPO verifyToken response', ['order_id' => $order->id, 'verify' => $verify]);
                    $tx = DpoZambiaTransaction::create([
                        'order_id'             => $order->id,
                        'raw_response'         => $verify,
                        'trans_id'             => self::strFrom($verify, 'TransID'),
                        'transaction_token'    => self::strFrom($verify, 'TransactionToken') ?: self::strFrom($verify, 'TransToken') ?: $token,
                        'result'               => self::strFrom($verify, 'Result'),
                        'result_code'          => self::strFrom($verify, 'ResultCode'),
                        'result_explanation'   => self::strFrom($verify, 'ResultExplanation'),
                        'transaction_status'   => self::strFrom($verify, 'TransactionStatus'),
                        'ccd_approval'         => self::strFrom($verify, 'CCDapproval'),
                        'company_ref'          => self::strFrom($verify, 'CompanyRef'),
                        'transaction_currency' => self::strFrom($verify, 'TransactionCurrency'),
                        'payment_amount'       => self::floatFrom($verify, 'PaymentAmount'),
                        'customer_name'        => self::strFrom($verify, 'CustomerName'),
                        'customer_phone'       => self::strFrom($verify, 'CustomerPhone'),
                        'customer_email'       => self::strFrom($verify, 'CustomerEmail'),
                        'customer_country'     => self::strFrom($verify, 'CustomerCountry'),
                        'fraud_alert'          => self::boolFrom($verify, 'FraudAlert'),
                        'fraud_explanation'    => self::strFrom($verify, 'FraudExplanation'),
                        'date_created'         => self::strFrom($verify, 'DateCreated') ? date('Y-m-d H:i:s', strtotime(self::strFrom($verify, 'DateCreated'))) : null,
                        'date_approved'        => self::strFrom($verify, 'DateApproved') ? date('Y-m-d H:i:s', strtotime(self::strFrom($verify, 'DateApproved'))) : null,
                        'other_fields'         => [
                            'IP' => self::strFrom($verify, 'IP') ?: null,
                            'Country' => self::strFrom($verify, 'Country') ?: null,
                            'source' => 'status',
                        ],
                    ]);
                    //Log::info('DPO Zambia transaction stored', ['tx_id' => $tx->id, 'order_id' => $order->id]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return self::updateOrderPaymentStatus($order, $status);
        } catch (Exception $e) {
            throw new Exception('DPO Zambia status check failed: ' . $e->getMessage(), $e->getCode());
        }
    }
}
