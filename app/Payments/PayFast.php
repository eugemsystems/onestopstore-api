<?php

namespace App\Payments;

use App\Enums\PaypalEvent;
use App\GraphQL\Exceptions\ExceptionHandler;
use Exception;
use App\Models\Order;
use App\Helpers\Helpers;
use App\Enums\PaymentStatus;
use App\Http\Traits\PaymentTrait;
use App\Http\Traits\TransactionsTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayFast
{
    use TransactionsTrait, PaymentTrait;

    public static function webhookHandler($request)
    {
        try {

            // Check if this is a layby payment (using custom_str1)
            $customStr1 = $request->input('custom_str1');

            if ($customStr1 === 'LAYBY_PAYMENT') {

                // Get payment ID from custom_int1 (we set this in LaybyController)
                $paymentId = $request->input('custom_int1'); // or custom_str2

                if (!$paymentId) {
                    $paymentId = $request->input('custom_str2'); // Fallback
                }

                if (!$paymentId) {
                    Log::warning('PayFast webhook: No payment ID provided for layby payment');
                    return response('No payment ID', 400);
                }

                // Create PayfastTransaction record for layby payment
                // Use EXACT same fields as order payments (package structure)
                if (strtoupper($request->input('payment_status')) === 'COMPLETE') {
                    try {

                        // Get the layby payment to get the user_id
                        $laybyPayment = \App\Models\LaybyPayment::find($paymentId);
                        $authUserId = $laybyPayment ? $laybyPayment->user_id : null;

                        // Use EXACT same structure as package does for order payments
                        $transactionData = [
                            'user_id' => $request->input('custom_int2'), // Application ID (for reference)
                            'm_payment_id' => $request->input('m_payment_id'),
                            'pf_payment_id' => $request->input('pf_payment_id'),
                            'payment_status' => $request->input('payment_status'),
                            'item_name' => $request->input('item_name'),
                            'item_description' => $request->input('item_description'),
                            'amount_gross' => $request->input('amount_gross'),
                            'amount_fee' => $request->input('amount_fee'),
                            'amount_net' => $request->input('amount_net'),
                            'custom_str1' => $request->input('custom_str1'), // 'LAYBY_PAYMENT'
                            'custom_str2' => $request->input('custom_str2'), // Payment ID
                            'custom_str3' => $request->input('custom_str3'), // Application ID
                            'custom_str4' => $request->input('custom_str4'),
                            'custom_str5' => $request->input('custom_str5'),
                            'custom_int1' => $request->input('custom_int1'), // Payment ID
                            'custom_int2' => $request->input('custom_int2'), // Application ID
                            'custom_int3' => $authUserId, // Authenticated user (the one who made payment)
                            'custom_int4' => $request->input('custom_int4'),
                            'custom_int5' => $request->input('custom_int5'),
                            'name_first' => $request->input('name_first'),
                            'name_last' => $request->input('name_last'),
                            'email_address' => $request->input('email_address'),
                            'merchant_id' => $request->input('merchant_id'),
                            'signature' => $request->input('signature'),
                        ];


                        $transaction = \App\Models\PayfastTransaction::create($transactionData);


                        // Update payment status + application totals via the shared handler.
                        // Do NOT update $laybyPayment here first — handleWebhookCallback()
                        // does its own update, and a premature status change causes its
                        // double-processing guard to exit before crediting total_paid.
                        if ($laybyPayment) {
                            try {
                                \App\Http\Controllers\LaybyController::handleWebhookCallback(
                                    $paymentId,
                                    $request->input('pf_payment_id'),
                                    'completed',
                                    $request->all()
                                );
                            } catch (\Exception $cbException) {
                                Log::error('PayFast webhook: handleWebhookCallback EXCEPTION', [
                                    'error' => $cbException->getMessage(),
                                    'trace' => $cbException->getTraceAsString(),
                                ]);
                            }
                        } else {
                            Log::warning('PayFast webhook: Layby payment not found', [
                                'payment_id' => $paymentId,
                            ]);
                        }

                    } catch (\Exception $e) {
                        Log::error('PayFast webhook: ✗✗✗ SAVE FAILED ✗✗✗', [
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                } else {
                    Log::warning('PayFast webhook: NOT COMPLETE', [
                        'status' => $request->input('payment_status'),
                    ]);
                }

                return response('OK', 200);
            }

            // Regular order payment - get order ID from custom fields
            $orderId = $request->input('custom_int1');

            if (!$orderId) {
                return response('No order ID', 400);
            }

            $order = Order::find($orderId);

            if (!$order) {
                return response('Order not found', 404);
            }


            // Regular order payment - use existing logic
            $pfStatus = strtoupper((string) $request->input('payment_status', ''));

            if ($pfStatus === 'COMPLETE') {
                // Mark order as completed
                $order->update(['payment_status' => 'completed']);
                Log::info('PayFast webhook: Order marked completed', ['order_id' => $orderId]);

                // If this is an auction win order, mark auction confirmed and send receipt
                if ($order->note && str_starts_with($order->note, 'AUC_WIN')) {
                    try {
                        $auction = \App\Models\AuctionItem::where('order_id', $order->id)->first();
                        if ($auction) {
                            $auction->update(['fulfillment_status' => 'confirmed']);
                            $winner = $auction->winner;
                            if ($winner) {
                                $winner->notify(new \App\Notifications\AuctionPaymentConfirmedNotification($auction, $order));
                            }
                        }
                    } catch (\Exception $notifyEx) {
                        Log::error('PayFast webhook: Failed to send auction receipt', [
                            'order_id' => $orderId,
                            'error'    => $notifyEx->getMessage(),
                        ]);
                    }
                }
            } else {
                Log::info('PayFast webhook: Regular order not complete', [
                    'order_id' => $orderId,
                    'status'   => $pfStatus,
                ]);
            }

            return response('OK', 200);

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
            $payable = (float) ($order->total ?? 0);
            $deliveryPrice = (float) ($order->delivery_price ?? 0);
            $payable = $payable + $deliveryPrice;

            // Fallback only if total is somehow zero or negative
            if ($payable <= 0) {
                $sub  = (float) ($order->amount ?? 0);
                $ship = (float) ($order->shipping_total ?? 0);
                $fast = (float) ($order->fast_shipping_total ?? 0);
                $del  = (float) ($order->delivery_price ?? 0);
                $tax  = (float) ($order->tax_total ?? 0);
                $disc = abs((float) ($order->coupon_total_discount ?? 0));
                $pts  = abs((float) ($order->points_amount ?? 0));
                $wal  = abs((float) ($order->wallet_balance ?? 0));
                $payable = max(0.01, ($sub + $ship + $fast + $del + $tax) - $disc - $pts - $wal);
            }

            $amountZar = Helpers::convertToZAR($payable);

            $params = [
                'return_url' => $request->return_url.'/'.$order->order_number,
                'name_first' => $order->consumer->name,
                'email_address' => $order->consumer->email,
                'amount' => $amountZar,
                //'item_name' => 'Order #'. $order->order_number,
                'item_name' => 'Raines Africa',
                'item_description' => 'Payment for order number:' . $order->order_number,
                'custom_int1' => $order->id,//order id
                'custom_int2' => $order->order_number,//order_number
                'custom_int3' => $order->consumer_id,//customer_id
                //'custom_int4' => ,//exchange rate
                'custom_str1' => 'Order ID',
                'custom_str2' => 'Order Number',
                'custom_str3' => 'Customer ID',
                'custom_str4' => 'Exchange Rate:'.Helpers::getCurrencyExchangeRate('ZAR'),
            ];

            return [
                'order_number'=> $order->order_number,
                'transaction_id' => $transactionId,
                'url' => route('payfast.redirect',$params),
                'is_redirect' => true,
            ];

        } catch (Exception $e) {

            //self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public static function status(Order $order, $transaction_id)
    {
        try {
            return $order;
        } catch (Exception $e) {
            //throw new ExceptionHandler($e->getMessage(), $e->getCode());
            throw new Exception('Payfast status check failed: ' . $e->getMessage(), $e->getCode());
        }
    }
}
