<?php

namespace App\Payments;

use Exception;
use App\Models\Order;
use App\Helpers\Helpers;
use App\Enums\PaymentStatus;
use App\Http\Traits\PaymentTrait;
use App\Http\Traits\TransactionsTrait;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\YocoTransaction;
use App\Models\Currency;

class Yoco
{
    use TransactionsTrait, PaymentTrait;

    private static function baseUrl(): string
    {
        // Default to live API base; override via config('yoco.base_url') for sandbox/testing
        return rtrim((string) (config('yoco.base_url') ?? 'https://payments.yoco.com/api'), '/');
    }

    private static function secretKey(): string
    {
        return (string) (config('yoco.secret_key') ?? env('YOCO_SECRET_KEY'));
    }

    private static function webhookSecret(): ?string
    {
        return config('yoco.webhook_secret') ?? env('YOCO_WEBHOOK_SECRET');
    }

    private static function buildRedirectUrl(?string $base, string $orderNumber, ?string $status = null): string
    {
        $base = trim((string) $base);
        if ($base === '') {
            return config('app.url');
        }

        // If placeholder is present, replace and optionally append status
        if (str_contains($base, '{order_number}')) {
            $url = str_replace('{order_number}', $orderNumber, $base);
            if ($status !== null && !str_contains($url, 'status=')) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'status=' . urlencode($status);
            }
            return $url;
        }

        // If URL already has a query string, append missing params as query params
        if (str_contains($base, '?')) {
            $url = $base;
            if ($status !== null && !str_contains($url, 'status=')) {
                $url .= (str_ends_with($url, '?') || str_ends_with($url, '&') ? '' : '&') . 'status=' . urlencode($status);
            }
            if (!str_contains($url, 'order_number=')) {
                $url .= (str_ends_with($url, '?') || str_ends_with($url, '&') || str_contains($url, 'status=') ? '&' : '&') . 'order_number=' . urlencode($orderNumber);
            }
            return $url;
        }

        // Otherwise, append order number as path segment, and add status if provided
        $url = rtrim($base, '/') . '/' . $orderNumber;
        if ($status !== null) {
            $url .= '?status=' . urlencode($status);
        }
        return $url;
    }

    /**
     * Create a Yoco Checkout session and return redirect URL.
     *
     * Response shape follows project convention: [order_number, transaction_id, url, is_redirect]
     */
    public static function getIntent(Order $order, $request): array
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

            // Convert to ZAR (same as PayFast)
            $amountZar = Helpers::convertToZAR($payable);
            $amountCents = (int) round($amountZar * 100);
            if ($amountCents < 100) {
                $amountCents = 100; // Yoco requires >= 1.00 ZAR
            }



            $successUrl = self::buildRedirectUrl(
                (string) ($request->return_url ?? config('yoco.return_url') ?? (config('app.url') . '/orders/return')),
                (string) $order->order_number,
                'successful'
            );
            $cancelUrl  = self::buildRedirectUrl(
                (string) ($request->cancel_url ?? config('yoco.cancel_url') ?? (config('app.url') . '/orders/cancel')),
                (string) $order->order_number,
                'cancelled'
            );

            $payload = [
                'amount'      => $amountCents,
                'currency'    => 'ZAR',
                'description' => 'Payment for order number:' . $order->order_number,
                // Both snake_case and camelCase for maximum compatibility
                'success_url' => $successUrl,
                'cancel_url'  => $cancelUrl,
                'successUrl'  => $successUrl,
                'cancelUrl'   => $cancelUrl,
                'reference'   => (string) $order->order_number,
                'metadata'    => [
                    'order_number' => (string) $order->order_number,
                    'order_id'     => (string) $order->id,
                ],
            ];

            $endpoint = self::baseUrl() . '/checkouts';
            $resp = Http::timeout(25)
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization'=> 'Bearer ' . self::secretKey(),
                ])
                ->post($endpoint, $payload);

            if (!$resp->successful()) {
                $body = $resp->json();
                $message = is_array($body) ? ($body['message'] ?? $body['error'] ?? 'Yoco init failed') : 'Yoco init failed';
                throw new Exception($message, $resp->status());
            }

            $data = $resp->json();
            // Try common keys and nested forms
            $redirectUrl = (string) (
                data_get($data, 'redirect_url') ??
                data_get($data, 'redirectUrl') ??
                data_get($data, 'url') ??
                data_get($data, 'hosted_url') ??
                data_get($data, 'hostedUrl') ??
                data_get($data, 'checkout_url') ??
                data_get($data, 'checkoutUrl') ??
                data_get($data, 'data.redirect_url') ??
                data_get($data, 'data.redirectUrl') ??
                ''
            );
            $yocoRef     = (string) (
                data_get($data, 'id') ??
                data_get($data, 'reference') ??
                data_get($data, 'data.id') ??
                $transactionId
            );

            if (!$redirectUrl) {
                throw new Exception('Yoco did not return a redirect URL', 500);
            }

            // Optionally persist intent in yoco_transactions table for reference
            if (config('yoco.log_intent')) {
                try {
                    YocoTransaction::create([
                        'order_id' => $order->id,
                        'order_number' => (string) $order->order_number,
                        'gateway_transaction_id' => $yocoRef ?: $transactionId,
                        'status' => data_get($data, 'status'),
                        'amount_cents' => $amountCents,
                        'currency' => 'ZAR',
                        'raw_response' => $data,
                        'other_fields' => [
                            'request_payload' => $payload,
                            'endpoint' => $endpoint,
                            'phase' => 'intent',
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Yoco: failed to persist intent', ['e' => $e->getMessage()]);
                }
            }

            // Persist the real Yoco reference/id into our order_transactions for later status checks
            try {
                $order->order_transactions()->updateOrCreate(
                    ['order_id' => $order->id],
                    ['transaction_id' => $yocoRef ?: $transactionId]
                );
            } catch (\Throwable $e) {
                Log::warning('Yoco: failed to persist order transaction id', ['e' => $e->getMessage()]);
            }

            return [
                'order_number'   => $order->order_number,
                'transaction_id' => $yocoRef ?: $transactionId,
                'url'            => $redirectUrl,
                'is_redirect'    => true,
            ];
        } catch (Exception $e) {
            // Do not immediately fail the order here; let webhook decide. Mimic other gateways style.
            throw new ExceptionHandler($e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Optionally query Yoco for the checkout/payment status.
     * If not configured, leaves order unchanged (PENDING) to be completed by webhook.
     */
    public static function status(Order $order, $transaction_id)
    {
        try {
            $ref = (string) $transaction_id;
            if ($ref === '') {
                return $order;
            }

            $endpoint = self::baseUrl() . '/checkouts/' . urlencode($ref);
            $resp = Http::timeout(20)
                ->withHeaders([
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . self::secretKey(),
                ])->get($endpoint);

            if (!$resp->successful()) {
                return $order; // leave as-is, rely on webhook
            }

            $data = $resp->json();
            $status = strtolower((string) ($data['status'] ?? ''));

            $map = [
                'paid'        => PaymentStatus::COMPLETED,
                'succeeded'   => PaymentStatus::COMPLETED,
                'successful'  => PaymentStatus::COMPLETED,
                'completed'   => PaymentStatus::COMPLETED,
                'failed'      => PaymentStatus::FAILED,
                'declined'    => PaymentStatus::FAILED,
                'cancelled'   => PaymentStatus::CANCELLED,
                'canceled'    => PaymentStatus::CANCELLED,
                'pending'     => PaymentStatus::PENDING,
            ];

            $mapped = $map[$status] ?? PaymentStatus::PENDING;

            // Respect config: do not finalize via polling unless explicitly allowed
            if (!config('yoco.allow_poll_finalize', false)) {
                // Return without changing state unless it's a non-final (keep PENDING)
                return $order;
            }

            return self::updateOrderPaymentStatus($order, $mapped);
        } catch (Exception $e) {
            throw new Exception('Yoco status check failed: ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Webhook handler to update order status based on Yoco events.
     * Attempts lightweight signature verification if a webhook secret is configured.
     */
    public static function webhookHandler($request)
    {
        try {
            $payload = $request->getContent();
            $data = $request->all();

            // Persist only final webhook outcomes (success/failure). Skip non-final like "created".
            try {
                $eventType = strtolower((string) (data_get($data, 'type') ?? ''));
                $statusRaw = strtolower((string) (
                    data_get($data, 'payload.status') ??
                    data_get($data, 'status') ??
                    data_get($data, 'data.status') ??
                    ''
                ));
                if ($eventType === 'payment.succeeded') { $statusRaw = 'succeeded'; }
                if ($eventType === 'payment.failed')    { $statusRaw = 'failed'; }

                $checkoutId = (string) (
                    data_get($data, 'payload.metadata.checkoutId') ??
                    data_get($data, 'payload.metadata.checkout_id') ??
                    data_get($data, 'metadata.checkoutId') ??
                    ''
                );
                $gatewayId = $checkoutId ?: (string) (data_get($data, 'id') ?? data_get($data, 'reference') ?? '');

                $successValues = ['paid','success','successful','succeeded','completed','approved'];
                $failedValues  = ['failed','declined','error','cancelled','canceled'];

                if (in_array($statusRaw, $successValues, true) || in_array($statusRaw, $failedValues, true)) {
                    $orderNumberMeta = (string) (
                        data_get($data, 'payload.metadata.order_number') ??
                        data_get($data, 'metadata.order_number') ??
                        data_get($data, 'data.metadata.order_number') ??
                        ''
                    );
                    $orderIdMeta = (string) (
                        data_get($data, 'payload.metadata.order_id') ??
                        data_get($data, 'metadata.order_id') ??
                        data_get($data, 'data.metadata.order_id') ??
                        ''
                    );
                    YocoTransaction::create([
                        'order_id' => ($orderIdMeta !== '' ? (int) $orderIdMeta : null),
                        'order_number' => $orderNumberMeta ?: null,
                        'gateway_transaction_id' => $gatewayId ?: null,
                        'status' => $statusRaw,
                        'amount_cents' => (int) (
                            data_get($data, 'payload.amount') ??
                            data_get($data, 'amount') ??
                            null
                        ),
                        'currency' => (string) (
                            data_get($data, 'payload.currency') ??
                            data_get($data, 'currency') ??
                            'ZAR'
                        ),
                        'raw_response' => $data,
                        'other_fields' => [
                            'headers' => $request->headers->all(),
                            'ip' => $request->ip(),
                            'phase' => 'webhook',
                            'event_type' => $eventType,
                        ],
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Yoco webhook: failed to persist raw transaction', ['e' => $e->getMessage()]);
            }

            // Best effort signature verification (optional, as Yoco signature format can vary)
            $secret = self::webhookSecret();
            if ($secret) {
                $sigHeader = $request->header('webhook-signature')
                    ?? $request->header('X-Yoco-Signature')
                    ?? $request->header('Yoco-Signature');
                if ($sigHeader) {
                    // Common pattern: HMAC-SHA256(payload, secret)
                    try {
                        $calc = hash_hmac('sha256', (string) $payload, (string) $secret);
                        // Some providers send hex; others base64. Try both comparisons.
                        $sigGiven = trim((string) $sigHeader);
                        $sigGivenAlt = @base64_decode($sigGiven, true);
                        if ($sigGivenAlt !== false) {
                            $sigGivenAlt = bin2hex($sigGivenAlt);
                        }
                        $valid = hash_equals($calc, $sigGiven) || ($sigGivenAlt && hash_equals($calc, (string) $sigGivenAlt));
                        if (!$valid) {
                            // If signature provided but invalid: log and optionally reject (enforce via env flag)
                            Log::warning('Yoco webhook: signature invalid', ['signature' => $sigHeader]);
                            if (env('YOCO_ENFORCE_SIGNATURE', false)) {
                                return response()->json(['error' => 'invalid signature'], 400);
                            }
                        }
                    } catch (\Throwable $e) {
                        // if verification errors occur, log and proceed (non-fatal)
                        Log::warning('Yoco webhook signature verification error', ['e' => $e->getMessage()]);
                    }
                }
            }

            // Resolve order
            $orderNumber = data_get($data, 'payload.metadata.order_number')
                ?? data_get($data, 'metadata.order_number')
                ?? data_get($data, 'order_number')
                ?? data_get($data, 'data.metadata.order_number');

            if (!$orderNumber) {
                // Attempt parse from description
                $desc = (string) ($data['description'] ?? ($data['data']['description'] ?? ''));
                if ($desc && preg_match('/order\s*(number)?\s*[:#-]?\s*(\d+)/i', $desc, $m)) {
                    $orderNumber = $m[2] ?? null;
                }
            }

            if (!$orderNumber) {
                return response()->json(['ok' => true]);
            }

            $order = Helpers::getOrderByOrderNumber($orderNumber);
            if (!$order) {
                // Fallback: resolve by checkoutId from event payload
                try {
                    $checkoutId = (string) (
                        data_get($data, 'payload.metadata.checkoutId') ??
                        data_get($data, 'payload.metadata.checkout_id') ??
                        data_get($data, 'metadata.checkoutId') ??
                        ''
                    );
                    if ($checkoutId !== '') {
                        $ot = \App\Models\OrderTransaction::where('transaction_id', $checkoutId)->first();
                        if ($ot) {
                            $order = Order::find($ot->order_id);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Yoco webhook: failed to resolve order by checkoutId', ['e' => $e->getMessage()]);
                }
            }
            if (!$order) {
                return response()->json(['ok' => true]);
            }

            // Update last stored webhook row with order_id and order_number if possible
            try {
                $checkoutId = (string) (
                    data_get($data, 'payload.metadata.checkoutId') ??
                    data_get($data, 'payload.metadata.checkout_id') ??
                    data_get($data, 'metadata.checkoutId') ??
                    ''
                );
                $gatewayId = $checkoutId ?: (string) (data_get($data, 'id') ?? data_get($data, 'reference') ?? '');
                if ($gatewayId) {
                    $row = YocoTransaction::where('gateway_transaction_id', $gatewayId)->latest('id')->first();
                    if ($row) {
                        $changed = false;
                        if (!$row->order_id) { $row->order_id = $order->id; $changed = true; }
                        if (!$row->order_number) { $row->order_number = (string) $order->order_number; $changed = true; }
                        if ($changed) { $row->save(); }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Yoco webhook: failed to backfill order data', ['e' => $e->getMessage()]);
            }

            // Persist the gateway reference into order_transactions for verifyPayment polling (only on final events)
            try {
                $eventType = strtolower((string) (data_get($data, 'type') ?? ''));
                $statusRaw = strtolower((string) (
                    data_get($data, 'payload.status') ??
                    data_get($data, 'status') ??
                    data_get($data, 'data.status') ??
                    ''
                ));
                if ($eventType === 'payment.succeeded') { $statusRaw = 'succeeded'; }
                if ($eventType === 'payment.failed')    { $statusRaw = 'failed'; }
                $checkoutId = (string) (
                    data_get($data, 'payload.metadata.checkoutId') ??
                    data_get($data, 'payload.metadata.checkout_id') ??
                    data_get($data, 'metadata.checkoutId') ??
                    ''
                );
                $gatewayId = $checkoutId ?: (string) (data_get($data, 'id') ?? data_get($data, 'reference') ?? '');

                $successValues = ['paid','success','successful','succeeded','completed','approved'];
                $failedValues  = ['failed','declined','error','cancelled','canceled'];
                if ($gatewayId && isset($order->id) && (in_array($statusRaw, $successValues, true) || in_array($statusRaw, $failedValues, true))) {
                    $order->order_transactions()->updateOrCreate(
                        ['order_id' => $order->id],
                        ['transaction_id' => $gatewayId]
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Yoco webhook: failed to persist order transaction id', ['e' => $e->getMessage()]);
            }

            // Normalize status from payload (prefer doc shape: event.type + payload.status)
            $eventType = strtolower((string) (data_get($data, 'type') ?? ''));
            $statusRaw = strtolower((string) (
                data_get($data, 'payload.status') ??
                data_get($data, 'status') ??
                data_get($data, 'data.status') ??
                ''
            ));
            if ($eventType === 'payment.succeeded') { $statusRaw = 'succeeded'; }
            if ($eventType === 'payment.failed')    { $statusRaw = 'failed'; }

            $successValues = ['paid','success','successful','succeeded','completed','approved'];
            $failedValues  = ['failed','declined','error','cancelled','canceled'];

            // Check if this is a layby payment before updating order
            if ($order->note && preg_match('/TEMP_LAYBY_PAYMENT:(\d+)/', $order->note, $laybyMatches)) {
                // Determine payment status
                $paymentStatus = 'pending';
                if (in_array($statusRaw, $successValues, true)) {
                    $paymentStatus = 'completed';
                } elseif (in_array($statusRaw, $failedValues, true)) {
                    $paymentStatus = 'failed';
                }

                // Get transaction ID
                $checkoutId = (string) (
                    data_get($data, 'payload.metadata.checkoutId') ??
                    data_get($data, 'payload.metadata.checkout_id') ??
                    data_get($data, 'metadata.checkoutId') ??
                    ''
                );
                $transactionId = $checkoutId ?: (string) (data_get($data, 'id') ?? data_get($data, 'reference') ?? '');

                // Call layby webhook handler with the actual LaybyPayment ID (not order ID)
                $laybyPaymentId = $laybyMatches[1];
                \App\Http\Controllers\LaybyController::handleWebhookCallback(
                    $laybyPaymentId,
                    $transactionId,
                    $paymentStatus
                );

                return response()->json(['ok' => true]);
            }

            // Regular order payment - continue with existing logic
            if (in_array($statusRaw, $successValues, true)) {
                return self::updateOrderPaymentStatus($order, PaymentStatus::COMPLETED);
            }
            if (in_array($statusRaw, $failedValues, true)) {
                return self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
            }

            return self::updateOrderPaymentStatus($order, PaymentStatus::PENDING);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), (int) $e->getCode());
        }
    }
}
