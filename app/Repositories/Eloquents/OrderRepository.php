<?php

namespace App\Repositories\Eloquents;

use App\Payments\PayFast;
use App\Payments\PesePay;
use App\Payments\PdoZambia;
use App\Payments\Yoco;
use Exception;
use Carbon\Carbon;
use App\Models\Order;
use App\Payments\Cod;
use App\Payments\BankTransfer;
use App\Payments\Mollie;
use App\Payments\PayPal;
use App\Payments\Stripe;
use App\Helpers\Helpers;
use App\Enums\OrderEnum;
use App\Enums\RoleEnum;
use Illuminate\Support\Arr;
use App\Models\OrderStatus;
use App\Models\User;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\PlaceOrderEvent;
use App\Models\OrderTransaction;
use App\Models\Currency;
use App\Enums\WalletPointsDetail;
use App\Http\Traits\PaymentTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\CheckoutTrait;
use App\Http\Traits\TransactionsTrait;
use App\Http\Traits\WalletPointsTrait;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Events\UpdateOrderStatusEvent;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Support\Facades\Log;
use App\Models\OrderStatusHistory;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use Illuminate\Database\QueryException;

class OrderRepository extends BaseRepository
{
    use CheckoutTrait, PaymentTrait, TransactionsTrait, WalletPointsTrait;

    protected $settings;
    protected $orderStatus;
    protected $orderTransaction;

    protected $fieldSearchable = [
        'order_number' => 'ilike',
        'payment_method' => 'ilike',
        'orderStatus.name' => 'ilike',
        'consumer.name' => 'ilike',
        'consumer.email' => 'ilike',
        'consumer.phone' => 'ilike',
        'payment_status' => 'ilike',
        'products.search_keywords' => 'ilike',
        'shipping_address.city' => 'ilike',
        'shipping_address.street' => 'ilike',
    ];

    public function boot()
    {
        try {

            $this->pushCriteria(app(RequestCriteria::class));

        } catch (ExceptionHandler $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    function model()
    {
        $this->orderStatus = new OrderStatus();
        $this->settings = Helpers::getSettings();
        $this->orderTransaction = new OrderTransaction();
        return Order::class;
    }

    public function getOrderNumber($digits)
    {
        $i = 0;
        do {

            $order_number = pow(10, $digits) + $i++;

        } while ($this->model->where("order_number", "=", $order_number)->exists());

        return $order_number;
    }

    public function nextOrderNumber(): int
    {
        // Use raw DB query to bypass any global scopes on the Order model.
        // Locks the current highest order row to serialize allocations.
        $last = DB::table('orders')
            ->select('order_number')
            ->orderByDesc('order_number')
            ->lockForUpdate()
            ->first();

        $base = $last ? (int) $last->order_number : 999;
        return $base + 1;
    }

    public function show($order_number)
    {
        try {

            $order = Helpers::getOrderByOrderNumber($order_number);
            if ($order) {
                return $this->verifyPayment($order);
            }

        } catch (Exception $e) {

            //throw new ExceptionHandler($e->getMessage(), $e->getCode());
            $code = is_numeric($e->getCode()) ? (int)$e->getCode() : 0;
            throw new Exception('The provided order number is not valid.'. $e->getMessage(), $code);

        }
    }

    public function trackOrder($order_number)
    {
        try {

            return $this->show($order_number);

        } catch (Exception $e) {

            $code = is_numeric($e->getCode()) ? (int)$e->getCode() : 0;
            throw new Exception($e->getMessage(), $code);
        }
    }

    public function placeOrder($request)
    {
        if(env('LOG_MOBILE_DEBUG_ALL', false)) {
            // ===== ANDROID APP ORDER DEBUGGING =====
            // Log all incoming data from mobile app BEFORE any processing
            $userAgent = $request->header('User-Agent', '');
            $isFromMobileApp = stripos($userAgent, 'android') !== false ||
                stripos($userAgent, 'okhttp') !== false ||
                stripos($userAgent, 'dart') !== false ||
                $request->header('X-Platform') === 'mobile';

            // ===== END ANDROID APP ORDER DEBUGGING =====
        }

        DB::beginTransaction();
        try {
            // TEMP DEBUG: Enable query log to catch the real failing query
            DB::enableQueryLog();

            $consumer_id = $this->getConsumerId($request);
            $products = $this->getUniqueProducts($request->products);
            // DEBUG: Log normalized unique products to help trace dedup issues
            $request->merge(['products' => $products]);

            // Resolve currency context — prefer what the admin form sent (it always has the
            // correct live rate), but fall back to the Currency DB record for code/symbol
            // normalization. NEVER let a stale/null DB exchange_rate override the form value.
            $currencyCode = strtoupper(trim((string) $request->input('currency', 'USD')));
            $requestExchangeRate = (float) $request->input('exchange_rate', 1);

            $currency = \App\Models\Currency::query()
                ->whereRaw('upper(code) = ?', [$currencyCode])
                ->where('status', 1)
                ->first();
            if ($currency) {
                // Use the DB model for code/symbol normalization only.
                // Exchange rate: prefer the form's value (live rate from CurrencyDetectionService)
                // and only fall back to the DB rate if the form sent 0 or 1 (default/missing).
                $dbRate = (float) ($currency->exchange_rate ?: 1);
                $resolvedRate = $requestExchangeRate > 1 ? $requestExchangeRate : $dbRate;

                $request->merge([
                    'currency'       => $currency->code,
                    'currency_symbol' => $currency->symbol,
                    'exchange_rate'  => $resolvedRate,
                ]);
            } else {
                // fallback
                $request->merge([
                    'currency'       => $currencyCode ?: 'USD',
                    'currency_symbol' => $request->input('currency_symbol', '$'),
                    'exchange_rate'  => $requestExchangeRate ?: 1,
                ]);
            }

            $items = $this->calculate($request);
            // YOCO: Handle currency context for Yoco payments
            // Backend ZAR conversion is DISABLED — Yoco gateway handles conversion like PayFast.
            try {
                $pm = strtolower((string) $request->payment_method);
                $currentCurrency = strtoupper(trim((string) $request->input('currency', 'USD')));

                if ($pm === 'yoco' && $currentCurrency === 'ZAR') {
                    // Already ZAR - just ensure currency fields are set correctly
                    $request->merge([
                        'currency' => 'ZAR',
                        'currency_symbol' => 'R',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('YOCO ZAR conversion failed', ['error' => $e->getMessage()]);
                // MUST re-throw inside a DB transaction to prevent poisoning the transaction
                throw $e;
            }

            if ($request->coupon) {
                $coupon = Helpers::getCoupon($request->coupon);
                $amount = Helpers::getTotalAmount($request->products);
                if ($this->isValidCoupon($coupon, $amount, $request->consumer_id)) {
                    $request->merge(['coupon_id' => $coupon->id]);
                }
            }

            $request->merge(['store_id' => head($items['items'])['store']]);

            // Check if order contains any gift card products
            $hasGiftCards = false;
            foreach ($items['items'] as $storeItem) {
                foreach ($storeItem['products'] as $product) {
                    $productModel = \App\Models\Product::find($product['product_id']);
                    if ($productModel && $productModel->is_gift_card) {
                        $hasGiftCards = true;
                        break 2; // Break out of both loops
                    }
                }
            }

            // Automatically mark order as gift order if it contains gift cards
            if ($hasGiftCards) {
                $request->merge(['is_gift_order' => true]);
            }

            // Pass the first store's items but use the grand total from $items['total'] which includes delivery_price
            $firstStoreItem = head($items['items']);
            // Override the per-store total with the grand total that includes delivery_price
            $firstStoreItem['total'] = $items['total'];

            $order = $this->createOrder($firstStoreItem, $request);

            if (Helpers::isMultiVendorEnable()) {
                $this->createSubOrder($items, $request, $order);
                $order->sub_orders;
            }

            DB::commit();
            //Helpers::removeCart($order);

            $order = $order->fresh();

            // Track wallet and points deductions
            $pointsDeducted = 0;
            $walletDeducted = 0;
            $pointsInPoints = 0; // Track actual points to debit

            if ($request->points_amount) {
                // convert_point_amount is ALREADY in currency (e.g., $10)
                $pointsInCurrency = abs($items['total']['convert_point_amount']);

                // Convert currency back to points for verification (e.g., $10 * 100 = 1000 points)
                $pointsInPoints = $this->currencyToPoints($pointsInCurrency);

                // Verify user has enough points (compares points, not currency)
                if ($this->verifyPoints($consumer_id, $pointsInCurrency)) {
                    // Deduct the currency equivalent from order
                    $pointsDeducted = $pointsInCurrency;

                    // Debit the points from user's balance
                    $this->debitPoints($consumer_id, $pointsInPoints, WalletPointsDetail::POINTS_ORDER.' #'.$order->order_number);
                } else {
                    // User doesn't have enough points - log warning but continue
                    Log::warning("Insufficient points for order", [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'consumer_id' => $consumer_id,
                        'points_required' => $pointsInPoints,
                        'points_requested_currency' => $pointsInCurrency
                    ]);
                    // Don't deduct points if insufficient - order will require full payment
                    $pointsDeducted = 0;
                }
            }

            if ($request->wallet_balance) {
                $walletInCurrency = abs($items['total']['convert_wallet_balance']);
                if ($this->verifyWallet($consumer_id, $walletInCurrency)) {
                    $walletDeducted = $walletInCurrency;
                    $this->debitWallet($consumer_id, $walletInCurrency, WalletPointsDetail::WALLET_ORDER.' #'.$order->order_number);
                } else {
                    // User doesn't have enough wallet balance - log warning but continue
                    Log::warning("Insufficient wallet balance for order", [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'consumer_id' => $consumer_id,
                        'wallet_required' => $walletInCurrency
                    ]);
                    // Don't deduct wallet if insufficient - order will require full payment
                    $walletDeducted = 0;
                }
            }

            // DEBIT POINTS AND WALLET FROM USER ACCOUNTS
            // Note: order->total, order->points_amount, order->wallet_balance are already set correctly in createOrder()
            // We just need to debit the actual amounts from the user's accounts
            if ($pointsDeducted > 0 || $walletDeducted > 0) {
                // Check if order is fully paid (total is zero or near-zero after deductions)
                $remainingTotal = $order->total;

                // ONLY mark payment as completed and advance to PROCESSING if order is FULLY paid
                if ($remainingTotal <= 0.01) {
                    // Payment is complete - use the proper method to update payment status
                    // This will automatically handle setting order status to PROCESSING
                    $this->updateOrderPaymentStatus($order, PaymentStatus::COMPLETED);
                    $order->refresh();

                }
            }

            // Reward points are now awarded when payment is completed or status becomes processing (see OrderObserver::updated)

            if ($request->coupon_id) {
                $this->updateCouponUsage($request->coupon_id);
            }

            return $this->createPayment($order, $request);

        } catch (Exception $e) {

            DB::rollback();
            //throw new ExceptionHandler($e->getMessage(), $e->getCode());

            // TEMP DEBUG: Log all queries that ran in this transaction to find the real failing one
            $queryLog = DB::getQueryLog();
            Log::error('ORDER PLACEMENT FAILED - Full query log (' . count($queryLog) . ' queries):', [
                'error' => $e->getMessage(),
                'total_queries' => count($queryLog),
                'queries' => array_slice($queryLog, -30),
            ]);
            DB::disableQueryLog();

            $code = is_numeric($e->getCode()) ? (int)$e->getCode() : 0;
            throw new Exception('Order placement failed: ' . $e->getMessage(), $code);
        }
    }

    public function createPayment(Order $order, $request)
    {
        try {

            switch ($request->payment_method) {
                case PaymentMethod::PAYPAL:
                    return PayPal::getIntent($order, $request);

                case PaymentMethod::PESE:
                    return PesePay::getIntent($order, $request);

                case PaymentMethod::PAYFAST:
                    return PayFast::getIntent($order, $request);

                case PaymentMethod::PDO_ZAMBIA:
                    return PdoZambia::getIntent($order, $request);

                case PaymentMethod::YOCO:
                    return Yoco::getIntent($order, $request);

                case PaymentMethod::COD:
                    return Cod::status($order, $request);

                case PaymentMethod::BANK_TRANSFER:
                    return BankTransfer::status($order, $request);

                case PaymentMethod::WALLET:
                    // Internal completion path (wallet/points-only or remainder covered);
                    // mark as completed and return basic data for redirect
                    self::updateOrderPaymentStatus($order, PaymentStatus::COMPLETED);
                    $order->refresh();
                    return [
                        'order_number'   => $order->order_number,
                        'payment_method' => 'wallet',
                        'payment_status' => $order->payment_status,
                    ];

                default:
                    throw new Exception('The selected payment method is not valid for this transaction.', 400);
            }

            return $order;

        } catch (Exception $e) {
            //throw new ExceptionHandler($e->getMessage(), $e->getCode());
            $code = is_numeric($e->getCode()) ? (int)$e->getCode() : 0;
            throw new Exception('Payment creation failed: ' . $e->getMessage(), $code);
        }
    }

    public function createOrder($item, $request)
    {
        //Log::info("Request",$request->all());
        if ($this->isActivePaymentMethod($request->payment_method, $item['total']['total'])) {
            if (!$request->points_amount) {
                $item['total']['convert_point_amount'] = 0;
            }
            if (!$request->wallet_balance) {
                $item['total']['convert_wallet_balance'] = 0;
            }

            $attempts = 0;
            start_create:
            $attempts++;
            $order_number = (int) $this->nextOrderNumber();
            try {
                // Use a SAVEPOINT so a unique-constraint violation doesn't poison the whole PG transaction
                DB::statement('SAVEPOINT order_insert');

                // For Yoco payments, use totals from request (frontend pre-converted)
                // For other payments, use backend-calculated totals
                $isYoco = strtolower((string) $request->payment_method) === 'yoco';

                // Get backend-calculated values - NEVER trust frontend totals for non-Yoco payments
                // This ensures the math is always correct based on actual product prices
                $backendSubTotal = (float) ($item['total']['sub_total'] ?? 0);
                $backendTaxTotal = (float) ($item['total']['tax_total'] ?? 0);
                $backendShippingTotal = (float) ($item['total']['shipping_total'] ?? 0);
                $backendFastShippingTotal = (float) ($item['total']['fast_shipping_total'] ?? 0);
                $backendDeliveryPrice = (float) ($request->delivery_price ?? ($item['total']['delivery_price'] ?? 0));
                $backendCouponDiscount = (float) ($item['total']['coupon_total_discount'] ?? 0);
                $backendPointsApplied = abs((float) ($item['total']['convert_point_amount'] ?? 0));
                $backendWalletApplied = abs((float) ($item['total']['convert_wallet_balance'] ?? 0));

                // For admin orders: Include tax_total from request if provided
                $adminTaxTotal = $request->has('tax_total') ? floatval($request->tax_total) : 0;
                $finalTaxTotal = $adminTaxTotal > 0 ? $adminTaxTotal : $backendTaxTotal;

                // PayFast 3% fee (only applicable for SA-only orders paying via PayFast)
                $payfastFee = max(0, (float) ($request->payfast_fee ?? 0));

                // Calculate grand total BEFORE deductions (from backend values)
                $grandBeforeDeductions = $backendSubTotal + $finalTaxTotal + $backendShippingTotal + $backendFastShippingTotal + $backendDeliveryPrice + $payfastFee - $backendCouponDiscount;

                // Calculate final total AFTER deductions
                // For Yoco: trust frontend's already-converted values
                // For other payments: always use backend calculations
                if ($isYoco && $request->has('grand_total') && $request->grand_total !== null) {
                    $finalTotal = floatval($request->grand_total);
                } else {
                    // Backend-calculated final total
                    $finalTotal = max(0, $grandBeforeDeductions - $backendPointsApplied - $backendWalletApplied);
                }


                // NOTE: All monetary values are stored in USD in the database.
                // The exchange_rate is stored for reference only — display conversion
                // happens on the frontend using order.exchange_rate.
                $exchangeRate = (float) $request->input('exchange_rate', 1);

                $order =  $this->model->create([
                'order_number' => $order_number,
                'consumer_id' => $request->consumer_id ?? Helpers::getCurrentUserId(),
                'store_id' => $request->store_id,
                'tax_total' => $finalTaxTotal,
                'shipping_total' => $isYoco && $request->has('shipping_total') ? $request->shipping_total : $backendShippingTotal,
                'fast_shipping_total' => $backendFastShippingTotal,
                'payment_method' => $request->payment_method,
                'order_status_id' => $this->resolveInitialOrderStatusId($request),
                'shipping_address_id' => $request->shipping_address_id,
                'billing_address_id' =>  $request->billing_address_id,
                'is_gift_order' => $request->is_gift_order ?? false,
                'delivery_description' => $request->delivery_description,
                'delivery_interval' => $request->delivery_interval,
                'delivery_price' => $backendDeliveryPrice,
                'payfast_fee' => $payfastFee,
                'parent_id' => $request->parent_id,
                'coupon_id' => $request->coupon_id,
                'note' => $request->note,
                'points_amount' => -$backendPointsApplied, // Store as negative
                'wallet_balance' => -$backendWalletApplied, // Store as negative
                'coupon_total_discount' => $backendCouponDiscount,
                'amount' => $isYoco && $request->has('sub_total') ? $request->sub_total : $backendSubTotal,
                'total' => $finalTotal,
                'currency' => $request->input('currency', 'USD'),
                'currency_symbol' => $request->input('currency_symbol', '$'),
                'exchange_rate' => $exchangeRate,
            ]);

                // Release the savepoint on success
                DB::statement('RELEASE SAVEPOINT order_insert');
            } catch (QueryException $e) {
                // Roll back to the savepoint so the transaction stays clean for retry
                DB::statement('ROLLBACK TO SAVEPOINT order_insert');
                if ($this->isUniqueConstraintViolation($e) && $attempts < 5) {
                    goto start_create;
                }
                throw $e;
            }

            // Ensure the order_status relation is available for mapping
            $order->load('order_status');

            $toAttach = [];
            foreach ($item['products'] as $itemProduct) {
                $itemProduct = Arr::except($itemProduct, ['store_id']);

                // Set has_fast_shipping based on fast_shipping_cost (primary) or item_shipping_method (fallback)
                if (isset($itemProduct['fast_shipping_cost']) && $itemProduct['fast_shipping_cost'] > 0) {
                    $itemProduct['has_fast_shipping'] = true;
                } elseif (isset($itemProduct['item_shipping_method'])) {
                    $shippingMethod = strtolower(trim($itemProduct['item_shipping_method']));
                    $itemProduct['has_fast_shipping'] = in_array($shippingMethod, ['fast', 'expedited', 'express']);
                } else {
                    $itemProduct['has_fast_shipping'] = false;
                }

                // Determine item_status
                if (!isset($itemProduct['item_status'])) {
                    $orderStatus = $order->order_status;
                    $orderStatusSlug = $orderStatus ? strtolower(trim($orderStatus->slug ?? $orderStatus->name ?? '')) : null;
                    $itemProduct['item_status'] = $this->mapOrderStatusToItemStatus($orderStatusSlug);
                }

                // Cast types
                $itemProduct['has_fast_shipping'] = (bool) ($itemProduct['has_fast_shipping'] ?? false);
                $itemProduct['fast_shipping_cost'] = isset($itemProduct['fast_shipping_cost']) ? (float) $itemProduct['fast_shipping_cost'] : 0.0;
                $itemProduct['shipping_cost'] = isset($itemProduct['shipping_cost']) ? (float) $itemProduct['shipping_cost'] : 0.0;
                $itemProduct['tax'] = isset($itemProduct['tax']) ? (float) $itemProduct['tax'] : 0.0;
                $itemProduct['single_price'] = isset($itemProduct['single_price']) ? (float) $itemProduct['single_price'] : 0.0;
                $itemProduct['subtotal'] = isset($itemProduct['subtotal']) ? (float) $itemProduct['subtotal'] : 0.0;
                $itemProduct['quantity'] = isset($itemProduct['quantity']) ? (int) $itemProduct['quantity'] : 1;

                $productId = $itemProduct['product_id'];
                $pivotData = Arr::except($itemProduct, ['product_id']);

                // ── Snapshot: freeze product data at the moment of order creation.
                // These columns are intentionally immutable after this point — do NOT
                // update them when the product is later edited. They ensure order history
                // always shows the exact price, name and image the customer saw.
                try {
                    $productSnap = \App\Models\Product::with('product_thumbnail')
                        ->select('id', 'name', 'sku', 'slug', 'price', 'sale_price')
                        ->find($productId);
                    if ($productSnap) {
                        $pivotData['product_name']       = $productSnap->name;
                        $pivotData['product_sku']        = $productSnap->sku;
                        $pivotData['product_slug']       = $productSnap->slug;
                        $pivotData['product_price']      = $productSnap->price;
                        $pivotData['product_sale_price'] = $productSnap->sale_price;
                        $pivotData['product_image_url']  = $productSnap->product_thumbnail?->image_url
                                                        ?? $productSnap->product_thumbnail?->original_url;
                    }
                } catch (\Throwable $snapEx) {
                    // Non-fatal — log and continue. Snapshot fields stay null.
                    Log::warning('Order snapshot failed for product', [
                        'product_id' => $productId,
                        'error'      => $snapEx->getMessage(),
                    ]);
                }

                $toAttach[] = ['product_id' => $productId, 'pivot' => $pivotData];

            }

            // Mark any cart abandonments as recovered
            $this->markCartAsRecovered($request, $order);

            foreach ($toAttach as $row) {
                try {
                    $order->products()->attach($row['product_id'], $row['pivot']);
                } catch (\Exception $e) {
                    throw $e;
                }
            }

            // DEBUG: Verify what was actually saved
            $order->load('products');

            $item_products = [];

            $order->store;
            $order->products;
            $order->order_status;

            event(new PlaceOrderEvent($order));
            return $order;
        }
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        if ($sqlState === '23000' && $driverCode === 1062) return true; // MySQL/MariaDB
        if ($sqlState === '23505') return true; // PostgreSQL
        if ($sqlState === '23000' && $driverCode === 19) return true; // SQLite

        return str_starts_with((string) $sqlState, '23');
    }

    public function createSubOrder($items, $request, Order $parentOrder)
    {
        $subOrders = [];
        if (count($items['items']) > 1) {
            // Skip the first item since it's already in the main/parent order
            // Only process items starting from index 1
            $itemsToProcess = array_slice($items['items'], 1);

            foreach ($itemsToProcess as $item) {
                if (isset($request->products)){
                    $request->merge(['parent_id' => $parentOrder->id, 'store_id' => $item['store']]);
                    $order = $this->createOrder($item, $request);
                    $subOrders[] = $order;
                }
            }
        }

        return $subOrders;
    }

    public function getRewardPoints($total)
    {
        $minPerOrderAmount = $this->settings['wallet_points']['min_per_order_amount'];
        $rewardPerOrderAmount = $this->settings['wallet_points']['reward_per_order_amount'];
        if ($total >= $minPerOrderAmount) {
            $rewardPoints = ($total/$minPerOrderAmount)*$rewardPerOrderAmount;
            return $rewardPoints;
        }
    }

    public function getWalletRatio()
    {
        $walletRatio = $this->settings['general']['wallet_currency_ratio'];
        return $walletRatio <= 0 ? 1 : $walletRatio;
    }

    public function update($request, $id)
    {
         DB::beginTransaction();
        try {
            //ID IS USING->order_status_id
            $request = Arr::except($request, ['order_number']);

            // Don't eager load order_status during transaction - it causes PostgreSQL issues
            $order = $this->model->without('order_status')->findOrFail($id);

            if (isset($request['order_status_id'])) {
                $order_status = $this->orderStatus->where('id', $request['order_status_id'])->pluck('name')->first();

                // If cancelled and payment still pending, mark as CANCELLED
                if ($order_status == OrderEnum::CANCELLED && $order->payment_status == PaymentStatus::PENDING) {
                    $request['payment_status'] = PaymentStatus::CANCELLED;
                }
                // If status moves away from PENDING to any non-cancelled state and payment still pending, mark as COMPLETED
                elseif ($order_status != OrderEnum::PENDING && $order_status != OrderEnum::CANCELLED && $order->payment_status == PaymentStatus::PENDING) {
                    $request['payment_status'] = PaymentStatus::COMPLETED;
                }
            }

            // Capture old status BEFORE update - this is critical!
            $oldStatusId   = (int) ($order->order_status_id ?? 0);

            // Safely get old status name without triggering relationship load issues
            $oldStatusName = '';
            if ($oldStatusId > 0) {
                $oldStatusRec = $this->orderStatus->where('id', $oldStatusId)->first();
                $oldStatusName = $oldStatusRec ? $oldStatusRec->name : '';
            }

            // Get the old status slug to map to old item_status BEFORE updating
            $oldStatusRecord = $this->orderStatus->where('id', $oldStatusId)->first();
            $oldStatusSlug = $oldStatusRecord ? strtolower(trim($oldStatusRecord->slug ?? $oldStatusRecord->name ?? '')) : strtolower(trim($oldStatusName));
            $oldItemStatus = $this->mapOrderStatusToItemStatus($oldStatusSlug);

            $actorId       = (int) (Helpers::getCurrentUserId() ?? 0);

            // Update the order - this triggers the Observer
            try {
                $order->update($request);
            } catch (\Exception $updateException) {
                throw $updateException;
            }

            // --- Sync order_products item_status for status transitions ---
            // Do this BEFORE commit and BEFORE refreshing the order
            if (isset($request['order_status_id'])) {
                $newStatusId = (int) ($request['order_status_id'] ?? 0);
                if ($newStatusId !== $oldStatusId) {
                    // Get the new status to determine item_status
                    $newStatusRecord = $this->orderStatus->where('id', $newStatusId)->first();
                    $newStatusSlug = $newStatusRecord ? strtolower(trim($newStatusRecord->slug ?? $newStatusRecord->name ?? '')) : '';
                    $newItemStatus = $this->mapOrderStatusToItemStatus($newStatusSlug);

                    // Get current items before update for logging
                    $currentItems = DB::table('order_products')
                        ->where('order_id', $order->id)
                        ->whereNull('deleted_at')
                        ->select('id', 'product_id', 'item_status')
                        ->get();

                    // Strategy: Only update items that currently match the OLD order's item_status
                    // This preserves manually changed items while updating those that follow the order
                    //
                    // Edge case: If oldItemStatus matches newItemStatus, no update needed
                    if ($oldItemStatus !== $newItemStatus) {
                        // Build list of statuses that map to the same item_status as the old order
                        // These items should be updated because they're following the order status
                        $statusesToUpdate = [$oldItemStatus];

                        // Also update items with these related statuses that map to the same item_status:
                        // e.g., if old order was 'pending', also update items that are 'pending'
                        // This handles cases where items might have been set to the exact status name

                        $rows = DB::table('order_products')
                            ->where('order_id', $order->id)
                            ->whereNull('deleted_at')
                            ->whereIn('item_status', $statusesToUpdate)
                            ->update(['item_status' => $newItemStatus, 'updated_at' => now()]);
                    }
                    // Log items after update
                    $afterItems = DB::table('order_products')
                        ->where('order_id', $order->id)
                        ->whereNull('deleted_at')
                        ->select('id', 'product_id', 'item_status')
                        ->get();

                    // Persist history
                    OrderStatusHistory::create([
                        'order_id'      => $order->id,
                        'old_status_id' => $oldStatusId ?: null,
                        'new_status_id' => $newStatusId,
                        'updated_by_id' => $actorId ?: null,
                    ]);
                }
            }

            // Commit the transaction AFTER syncing item statuses
            DB::commit();

            // Now refresh and load relations
            $order = $order->fresh();
            $order->products;
            $order->sub_orders;
            $order->billing_address;
            $order->shipping_address;

             if (isset($request['order_status_id'])) {
                 // Use the status name we already fetched to avoid additional query
                 $statusId = (int)$request['order_status_id'];
                 $statusRecord = $this->orderStatus->where('id', $statusId)->first();
                 $statusName = $statusRecord ? $statusRecord->name : null;

                 if ($statusName == OrderEnum::DELIVERED) {
                     $order->delivered_at = Carbon::now()->toDateString();
                     $order->save();
                 }

                // Calculate commission AFTER transaction for DELIVERED or COLLECTED orders
                // This avoids PostgreSQL transaction abort issues
                if (($statusName == OrderEnum::DELIVERED || $statusName == OrderEnum::COLLECTED)
                    && $order->payment_status == PaymentStatus::COMPLETED) {

                    try {
                        // Load order with all needed relationships
                        $orderForCommission = Order::with(['products.categories', 'products.store', 'sub_orders'])
                            ->find($order->id);

                        if ($orderForCommission) {
                            // Use CommissionTrait
                            $commissionTrait = new class {
                                use \App\Http\Traits\CommissionTrait;
                            };

                            // Calculate commission for MAIN order
                            $commissionTrait->adminVendorCommission($orderForCommission);

                            // IMPORTANT: Also calculate commission for SUB-ORDERS (vendor products)
                            // Sub-orders contain products with store_id, so they need commission calculation too
                            if ($orderForCommission->sub_orders && $orderForCommission->sub_orders->count() > 0) {
                                foreach ($orderForCommission->sub_orders as $subOrder) {
                                    // Only calculate if sub-order has store_id (vendor products)
                                    if ($subOrder->store_id) {
                                        // Load sub-order with relationships
                                        $subOrderForCommission = Order::with(['products.categories', 'products.store'])
                                            ->find($subOrder->id);

                                        if ($subOrderForCommission) {
                                            $commissionTrait->adminVendorCommission($subOrderForCommission);
                                        }
                                    }
                                }
                            }
                        }
                    } catch (\Exception $commissionException) {
                        \Log::error("Commission calculation failed: " . $commissionException->getMessage());
                        // Don't throw - commission failure shouldn't fail the order update
                    }
                }

                // Award loyalty points when order is first marked as DELIVERED
                if ($statusName == OrderEnum::DELIVERED
                    && $oldStatusName != OrderEnum::DELIVERED
                    && $order->consumer_id
                    && Helpers::pointIsEnable()) {
                    try {
                        $rewardPoints = $this->getRewardPoints($order->total);
                        if ($rewardPoints > 0) {
                            $this->creditPoints($order->consumer_id, $rewardPoints, WalletPointsDetail::REWARD);
                        }
                    } catch (\Exception $pointsException) {
                        \Log::error("Failed to award points for order #{$order->id}: " . $pointsException->getMessage());
                    }
                }

                event(new UpdateOrderStatusEvent($order));
            }
             return $order;

        } catch (Exception $e) {

            DB::rollback();

            // Ensure code is an integer (some exceptions return string codes)
            $code = is_numeric($e->getCode()) ? (int)$e->getCode() : 0;
            throw new Exception('Order update failed: ' . $e->getMessage(), $code);
        }
    }

    /**
     * Map an order status slug/name to the corresponding item_status string used on order_products.
     * This centralizes the logic so creation and updates stay consistent.
     */
     protected function mapOrderStatusToItemStatus(?string $orderStatusSlug)
     {
        $s = strtolower(trim((string) ($orderStatusSlug ?? '')));

        $map = [
            'pending' => 'pending',
            'processing' => 'processing',
            'warehouse packing' => 'processing',
            'warehouse-packing' => 'processing',
            'from supplier' => 'processing',
            'from-supplier' => 'processing',
            'stuck' => 'processing',
            'shipped' => 'shipped',
            'in transit to zim' => 'shipped',
            'in-transit-to-zim' => 'shipped',
            'dropped at the deport' => 'shipped',
            'dropped-at-the-deport' => 'shipped',
            'out for delivery' => 'shipped',
            'out-for-delivery' => 'shipped',
            'ready for collection' => 'shipped',
            'ready-for-collection' => 'shipped',
            'arrived at local branch' => 'shipped',
            'arrived-at-local-branch' => 'shipped',
            'delivered' => 'delivered',
            'collected' => 'collected', // Keep collected as collected (not delivered)
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            'refunded' => 'cancelled',
        ];

        $result = $map[$s] ?? 'pending';
        return $result;
     }

    /**
     * Resolve the initial order_status_id from the request.
     * Accepts: order_status_id (int), order_status (slug or name)
     */
    protected function resolveInitialOrderStatusId($request)
    {
        // Prefer explicit id if present
        if ($request->has('order_status_id') && $request->input('order_status_id')) {
            return (int) $request->input('order_status_id');
        }

        // Accept a provided order_status (slug or name)
        $candidate = $request->input('order_status') ?? $request->input('order_status_slug') ?? $request->input('order_status_name') ?? null;
        if ($candidate) {
            $candidate = trim((string) $candidate);
            // Try by slug first
            $rec = $this->orderStatus->whereRaw('lower(slug) = ?', [strtolower($candidate)])->orWhereRaw('lower(name) = ?', [strtolower($candidate)])->first();
            if ($rec) return (int) $rec->id;
        }

        // Fallback to PENDING
        return Helpers::getOrderStatusIdByName(OrderEnum::PENDING);
    }

    public function destroy($id)
    {
        try {

            return $this->model->where('id', $id)->where('consumer_id', Helpers::getCurrentUserId())->destroy($id);

        } catch (Exception $e){

            //throw new ExceptionHandler($e->getMessage(), $e->getCode());
            $code = is_numeric($e->getCode()) ? (int)$e->getCode() : 0;
            throw new Exception('Order deletion failed: ' . $e->getMessage(), $code);
        }
    }

    public function verifyPayment($request)
    {
        try {

            $order = $this->verifyOrderNumber($request->order_number);
            $transaction_id = $this->orderTransaction->where('order_id',$order->id)->pluck('transaction_id')->first();
            if (is_null($order) || !$transaction_id) {
                return $order;
            }

            switch ($order->payment_method) {
                case PaymentMethod::PAYPAL:
                    return PayPal::status($order, $transaction_id);

                case PaymentMethod::PESE:
                    return PesePay::status($order, $transaction_id);

                case PaymentMethod::PAYFAST:
                    return PayFast::status($order, $transaction_id);

                case PaymentMethod::PDO_ZAMBIA:
                    return PdoZambia::status($order, $transaction_id);

                case PaymentMethod::YOCO:
                    return Yoco::status($order, $transaction_id);

                default:
                    return $order;
            }

        } catch (Exception $e) {

            //throw new ExceptionHandler($e->getMessage(), $e->getCode());
            $code = is_numeric($e->getCode()) ? (int)$e->getCode() : 0;
            throw new Exception('Payment verification failed: ' . $e->getMessage(), $code);
        }
    }

    public function verifyOrderNumber($order_number)
    {
        try {

            $query = $this->model
                ->withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)
                ->with(config('enums.order.with'))
                ->where('order_number', $order_number);

            $roleName = Helpers::getCurrentRoleName();
            if ($roleName == RoleEnum::CONSUMER) {
                $query->where('consumer_id', Helpers::getCurrentUserId());
            }

            $order = $query->first();
            if (!$order) {
                throw new Exception('The provided order number is not valid.', 400);
            }

            $order->products;
            return $order;

        }  catch (Exception $e) {

            //throw new ExceptionHandler($e->getMessage(), $e->getCode());
            $code = is_numeric($e->getCode()) ? (int)$e->getCode() : 0;
            throw new Exception('Order verification failed: ' . $e->getMessage(), $code);
        }
    }

    public function rePayment($request)
    {
        try {

            $order = $this->verifyOrderNumber($request->order_number);
            if ($order->payment_status == PaymentStatus::COMPLETED) {
                throw new Exception('This payment has already been successfully processed previously.', 400);
            }

            // Calculate the remaining amount to charge after wallet/points already deducted
            $subTotal  = (float) ($order->amount ?? 0);
            $shipping  = (float) ($order->shipping_total ?? 0);
            $fastShipping = (float) ($order->fast_shipping_total ?? 0);
            $delivery  = (float) ($order->delivery_price ?? 0);
            $tax       = (float) ($order->tax_total ?? 0);
            $coupon    = abs((float) ($order->coupon_total_discount ?? 0));

            // Get already deducted amounts (stored as negative values)
            $pointsAlreadyUsed = abs((float) ($order->points_amount ?? 0));
            $walletAlreadyUsed = abs((float) ($order->wallet_balance ?? 0));

            // Calculate base total before any deductions
            $baseTotal = $subTotal + $shipping + $fastShipping + $delivery + $tax;

            // Calculate remaining amount after existing deductions
            $remaining = max(0, $baseTotal - $coupon - $pointsAlreadyUsed - $walletAlreadyUsed);

            $usingWallet = (bool) $request->input('wallet_balance', false);
            $usingPoints = (bool) $request->input('points_amount', false);

            // If remaining is zero or covered by wallet/points, complete without gateway
            if ($remaining <= 0 || ($remaining <= 0.01 && ($usingWallet || $usingPoints))) {
                // Mark payment as completed
                $this->updateOrderPaymentMethod($order, 'wallet');
                $this->updateOrderPaymentStatus($order, PaymentStatus::COMPLETED);
                $order->refresh();

                return [
                    'order_number'   => $order->order_number,
                    'payment_method' => 'wallet',
                    'payment_status' => $order->payment_status,
                    'amount_charged' => 0,
                ];
            }

            // Merge the remaining amount into the request for payment gateway
            $request->merge([
                'amount' => $remaining,
                'grand_total' => $remaining,
                'total' => $remaining,
            ]);

            return $this->createPayment($order, $request);

        } catch (Exception $e) {

            //throw new ExceptionHandler($e->getMessage(), $e->getCode());
            throw new Exception('Re-payment failed: ' . $e->getMessage(), $e->getCode());
        }
    }

    public function generateInvoiceUrl($order_number)
    {
        return route('invoice', ['order_number' => $order_number]);
    }

    public function getInvoiceUrl($order_number)
    {
        try {

            return $this->verifyOrderNumber($order_number)->invoice_url;

        } catch (Exception $e) {

            //throw new ExceptionHandler($e->getMessage(), $e->getCode());
            throw new Exception('Invoice URL generation failed: ' . $e->getMessage(), $e->getCode());
        }
    }

    public function getInvoice($request)
    {
        try {

            $order = $this->verifyOrderNumber($request->order_number);
            $invoice = [
                'orders' => $order,
                'settings' => Helpers::getSettings(),
            ];

            return PDF::loadView('emails.invoice', $invoice)->download('invoice-'.$order->order_number.'.pdf');

        } catch (Exception $e) {

            //throw new ExceptionHandler($e->getMessage(), $e->getCode());
            throw new Exception('Invoice generation failed: ' . $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Mark cart abandonment as recovered when order is successfully created
     */
    protected function markCartAsRecovered($request, Order $order)
    {
        try {
            // Get session ID from request or cookies
            $sessionId = $request->input('session_id')
                ?? $request->cookie('analytics_session_id')
                ?? null;

            if (!$sessionId) {
                return;
            }

            // Mark any non-recovered cart abandonments for this session as recovered
            \App\Models\Analytics\CartAbandonment::where('session_id', $sessionId)
                ->where('recovered', false)
                ->update([
                    'recovered' => true,
                    'order_id' => $order->id,
                    'recovered_at' => now(),
                ]);

        } catch (\Exception $e) {
            // Don't fail order creation if recovery tracking fails
            Log::error('[CartRecovery] Failed to mark cart as recovered', [
                'error' => $e->getMessage(),
                'order_id' => $order->id ?? null,
            ]);
        }
    }


}
