<?php

namespace App\Http\Controllers;

use App\Models\LaybyApplication;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Eloquents\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LaybyController extends Controller
{
    /**
     * Apply for layby
     *
     * @OA\Post(
     *   path="/api/layby/apply",
     *   tags={"Layby"},
     *   summary="Submit layby application",
     *   description="Apply for layby payment plan on a product. Requires authentication.",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"product_id","duration_months","id_document_type","id_document_number"},
     *       @OA\Property(property="product_id", type="integer", example=123, description="Product ID (required)"),
     *       @OA\Property(property="variation_id", type="integer", example=456, description="Variation ID (optional - required if product has variations)"),
     *       @OA\Property(property="selected_attribute_ids", type="array", @OA\Items(type="integer"), example={1,2}, description="Selected attribute IDs (optional)"),
     *       @OA\Property(property="variation_display_name", type="string", example="Red - Large", description="Variation display name (optional)"),
     *       @OA\Property(property="duration_months", type="integer", example=6, description="Layby duration in months (required, min: 1)"),
     *       @OA\Property(property="id_document_attachment_id", type="string", example="uuid-or-id", description="Uploaded ID document attachment ID/UUID (optional - use this OR id_document file)"),
     *       @OA\Property(property="id_document_type", type="string", enum={"passport","id_card","drivers_license"}, example="passport", description="ID document type (required)"),
     *       @OA\Property(property="id_document_number", type="string", example="AB123456", description="ID document number (required, max: 50 chars)")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Layby application submitted successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Layby application submitted successfully"),
     *       @OA\Property(property="application", type="object",
     *         @OA\Property(property="id", type="integer", example=123),
     *         @OA\Property(property="reference_number", type="string", example="LAY-2025-001234"),
     *         @OA\Property(property="product_id", type="integer", example=123),
     *         @OA\Property(property="total_amount", type="number", format="float", example=500.00),
     *         @OA\Property(property="deposit_amount", type="number", format="float", example=100.00),
     *         @OA\Property(property="remaining_amount", type="number", format="float", example=400.00),
     *         @OA\Property(property="monthly_payment", type="number", format="float", example=66.67),
     *         @OA\Property(property="status", type="string", example="pending")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Validation failed"),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function apply(Request $request)
    {
        // Decode JSON strings from FormData
        if ($request->has('selected_attribute_ids') && is_string($request->selected_attribute_ids)) {
            $request->merge([
                'selected_attribute_ids' => json_decode($request->selected_attribute_ids, true)
            ]);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variation_id' => 'nullable|exists:variations,id',
            'selected_attribute_ids' => 'nullable|array',
            'variation_display_name' => 'nullable|string',
            'duration_months' => 'required|integer|min:1',

            // Document fields – all optional at apply time; user can upload later
            'id_document_attachment_id' => 'nullable|string', // UUID string from chunked upload
            'id_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // OLD: Direct upload

            // Type & number are only required when an attachment is being submitted
            'id_document_type'   => 'nullable|in:passport,id_card,drivers_license',
            'id_document_number' => 'nullable|string|max:50',
        ]);

        // Validate attachment exists if provided (custom validation for UUID)
        if ($request->filled('id_document_attachment_id')) {

            // Use whereRaw to force PostgreSQL to treat id as string (UUID)
            // The id column might be varchar/uuid type in database
            $attachmentExists = \App\Models\Attachment::whereRaw(
                "CAST(id AS TEXT) = ?",
                [$validated['id_document_attachment_id']]
            )->exists();


            if (!$attachmentExists) {
                // Check if attachment exists by uuid instead
                $attachmentByUuid = \App\Models\Attachment::where('uuid', $validated['id_document_attachment_id'])->first();

                if ($attachmentByUuid) {
                    // Update the validated value to use the actual id from the attachment found by uuid
                    $validated['id_document_attachment_id'] = $attachmentByUuid->id;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid ID document attachment',
                        'errors' => [
                            'id_document_attachment_id' => ['The selected ID document is invalid']
                        ]
                    ], 422);
                }
            }
        }

        // Document is optional at apply time – user can upload later from My Laybys


        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Please login to apply for layby'], 401);
        }

        // ---------------------------------------------------------------
        // Duplicate check: block if user already has an active application
        // for this product (and variation if specified).
        // Active = not yet completed, cancelled, or rejected.
        // ---------------------------------------------------------------
        $activeStatuses = ['pending', 'under_review', 'approved', 'active'];

        $existingQuery = LaybyApplication::where('user_id', $user->id)
            ->where('product_id', $validated['product_id'])
            ->whereIn('status', $activeStatuses);

        // If a variation is specified, check per-variation; otherwise check any application for this product
        if (!empty($validated['variation_id'])) {
            $existingQuery->where('variation_id', $validated['variation_id']);
        }

        $existing = $existingQuery->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending layby application for this item. Please check your existing application.',
                'existing_application' => [
                    'id'               => $existing->id,
                    'reference_number' => $existing->application_number,
                    'status'           => $existing->status,
                ],
            ], 422);
        }

        $product = Product::with('variations')->findOrFail($validated['product_id']);

        // Get price (variation or product)
        $price = $product->price;
        $isSaleProduct = false;

        if ($validated['variation_id'] ?? null) {
            $variation = $product->variations->firstWhere('id', $validated['variation_id']);
            if ($variation->sale_price) {
                $price = $variation->sale_price;
                $isSaleProduct = true;
            } else {
                $price = $variation->price ?? $price;
            }
        } else if ($product->sale_price) {
            $price = $product->sale_price;
            $isSaleProduct = true;
        }

        // Check minimum amount ($300 USD equivalent)
        $currency = session('currency', config('app.default_currency', 'USD'));
        $exchangeRate = session('exchange_rate', 1);
        $priceInUSD = $price / $exchangeRate;

        if ($priceInUSD < 100) {
            return response()->json(['message' => 'Layby is only available for products above $300'], 422);
        }

        // Get layby settings based on product type
        $depositPercentage = $isSaleProduct
            ? (float)getLaybySetting('sale_products_deposit_percentage', 30)
            : (float)getLaybySetting('regular_products_deposit_percentage', 30);

        // Validate duration for sale products (only 3 months allowed)
        if ($isSaleProduct) {
            $allowedDuration = (int)getLaybySetting('sale_products_duration_months', 3);
            if ($validated['duration_months'] != $allowedDuration) {
                return response()->json([
                    'message' => "Products on sale can only have {$allowedDuration} months payment plan"
                ], 422);
            }
        }

        // Handle ID document - support both new and old methods
        $idDocumentAttachmentId = null;
        $idDocumentPath = null; // Legacy path (kept for old records)

        if ($request->filled('id_document_attachment_id')) {
            // NEW method: Using attachment from chunked upload (preferred)
            $attachment = \App\Models\Attachment::findOrFail($validated['id_document_attachment_id']);
            $idDocumentAttachmentId = $attachment->id;

        } elseif ($request->hasFile('id_document')) {
            // OLD method: Direct file upload (legacy support)
            // Upload to local storage temporarily for backward compatibility
            $file = $request->file('id_document');
            $filename = 'layby_id_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $idDocumentPath = $file->storeAs('layby_documents', $filename, 'public');

            // TODO: Later, upload this to laravel-media and get attachment_id
            // For now, we'll use the local path
        }

        // Calculate layby terms
        $durationMonths = $validated['duration_months'];
        $depositAmount = round($price * ($depositPercentage / 100), 2);
        $remainingAmount = $price - $depositAmount;
        $monthlyAmount = round($remainingAmount / $durationMonths, 2);
        $totalAmount = $depositAmount + ($monthlyAmount * $durationMonths);

        try {
            $application = LaybyApplication::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'variation_id' => $validated['variation_id'] ?? null,
                'selected_attribute_ids' => $validated['selected_attribute_ids'] ?? null,
                'variation_display_name' => $validated['variation_display_name'] ?? null,
                'id_document_attachment_id' => $idDocumentAttachmentId,
                'id_document_path'          => $idDocumentPath,
                'id_document_type'          => $validated['id_document_type']   ?? null,
                'id_document_number'        => $validated['id_document_number'] ?? null,
                'product_name' => $product->name,
                'product_price' => $price,
                'currency' => $currency,
                'currency_symbol' => session('currency_symbol', '$'),
                'exchange_rate' => $exchangeRate,
                'duration_months' => $durationMonths,
                'deposit_amount' => $depositAmount,
                'monthly_amount' => $monthlyAmount,
                'total_amount' => $totalAmount,
                'balance_remaining' => $totalAmount,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Layby application submitted successfully. We will review and get back to you shortly.',
                'application' => $application->load('product'),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Layby apply() failed', [
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
                'user_id' => $user->id ?? null,
            ]);
            return response()->json([
                'message' => 'Failed to submit application',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Check if the authenticated user already has an active layby for a product/variation.
     * GET /api/layby/check-existing?product_id=X[&variation_id=Y]
     */
    public function checkExisting(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['has_existing' => false]);
        }

        $productId   = $request->query('product_id');
        $variationId = $request->query('variation_id');

        if (!$productId) {
            return response()->json(['has_existing' => false]);
        }

        $activeStatuses = ['pending', 'under_review', 'approved', 'active', 'awaiting_documents'];

        $query = LaybyApplication::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->whereIn('status', $activeStatuses);

        if ($variationId) {
            $query->where('variation_id', $variationId);
        }

        $existing = $query->select('id', 'status', 'created_at')->latest()->first();

        return response()->json([
            'has_existing'   => (bool) $existing,
            'application_id' => $existing?->id,
            'status'         => $existing?->status,
        ]);
    }

    /**
     * Get user's layby applications
     */
    public function myApplications(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }


        $applications = LaybyApplication::with(['product.product_thumbnail', 'variation', 'payments'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);



        return response()->json($applications);
    }


    /**
     * Get single application details
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $application = LaybyApplication::with([
            'product.product_thumbnail',
            'variation',
            'order.orderStatus', // Add order relationship
            'payments' => function($q) {
                $q->orderBy('created_at', 'desc');
            }
        ])
        ->where('user_id', $user->id)
        ->findOrFail($id);

        return response()->json($application);
    }

    /**
     * Make a payment towards layby
     */
    public function makePayment(Request $request, $id)
    {
        // Log RAW request data FIRST to see what's actually being sent
        Log::info('🔵 Layby Payment RAW Request', [
            'request_all' => $request->all(),
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'content_type' => $request->header('Content-Type'),
        ]);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.1',
            'payment_method' => 'required|string',
            'currency' => 'nullable|string|size:3', // Add currency from frontend
        ]);

        Log::info('🟢 Layby Payment VALIDATED Data', [
            'validated' => $validated,
            'has_currency' => isset($validated['currency']),
            'currency_value' => $validated['currency'] ?? 'NOT_SET',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $application = LaybyApplication::where('user_id', $user->id)->findOrFail($id);

        if (!in_array($application->status, ['approved', 'active'])) {
            return response()->json(['message' => 'Cannot make payment for this application'], 422);
        }

        // Frontend sends amount in user's selected currency
        // We need to convert it to both USD (for storage) and ZAR (for PayFast)
        $amountInUserCurrency = $validated['amount'];
        $userCurrencyCode = $validated['currency'] ?? 'USD';

        // CRITICAL WARNING if currency is missing
        if (!isset($validated['currency']) || empty($validated['currency'])) {
            Log::warning('⚠️ Layby Payment: Currency parameter missing! Defaulting to USD', [
                'application_id' => $application->id,
                'amount' => $amountInUserCurrency,
                'defaulted_to' => 'USD',
            ]);
        }

        // Log incoming payment for debugging
        Log::info('Layby Payment Received', [
            'application_id' => $application->id,
            'application_number' => $application->application_number,
            'amount' => $amountInUserCurrency,
            'currency' => $userCurrencyCode,
            'payment_method' => $validated['payment_method'],
            'balance_remaining_usd' => $application->balance_remaining,
        ]);

        // Step 1: Convert user currency to USD for storage
        $usdRate = $this->getExchangeRate($userCurrencyCode, 'USD');
        $amountInUSD = $amountInUserCurrency * $usdRate;

        // Step 2: Convert user currency to ZAR for PayFast
        $zarRate = $this->getExchangeRate($userCurrencyCode, 'ZAR');
        $amountInZAR = $amountInUserCurrency * $zarRate;

        // Log conversion details
        Log::info('Layby Payment Conversion', [
            'application_id' => $application->id,
            'original_amount' => $amountInUserCurrency,
            'original_currency' => $userCurrencyCode,
            'usd_rate' => $usdRate,
            'amount_usd' => $amountInUSD,
            'zar_rate' => $zarRate,
            'amount_zar' => $amountInZAR,
        ]);

        if ($amountInUSD > $application->balance_remaining) {
            return response()->json(['message' => 'Payment amount exceeds balance'], 422);
        }

        DB::beginTransaction();
        try {
            // Create payment record with amount in USD
            $payment = $application->payments()->create([
                'amount' => $amountInUSD, // Store in USD
                'currency' => 'USD', // Internal currency is always USD
                'currency_symbol' => '$',
                'exchange_rate' => 1.0000, // USD to USD = 1
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'payment_meta' => [
                    'original_amount' => $amountInUserCurrency,
                    'original_currency' => $userCurrencyCode,
                    'zar_amount' => $amountInZAR, // Amount in ZAR for PayFast
                    'to_usd_rate' => $usdRate,
                    'to_zar_rate' => $zarRate,
                ],
            ]);

            // Process payment through gateway
            $paymentResult = $this->processPaymentGateway($payment, $application, $request);

            DB::commit();

            // Log successful payment initiation
            Log::info('Layby Payment Initiated', [
                'application_id' => $application->id,
                'payment_id' => $payment->id,
                'amount_usd' => $amountInUSD,
                'amount_zar' => $amountInZAR,
                'payment_method' => $validated['payment_method'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated',
                'payment' => $payment,
                'redirect_url' => $paymentResult['redirect_url'] ?? null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Layby Payment Failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Payment processing failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check if product is eligible for layby
     *
     * @OA\Post(
     *   path="/api/layby/check-eligibility",
     *   tags={"Layby"},
     *   summary="Check product layby eligibility",
     *   description="Check if a product/variation is eligible for layby and get terms. No authentication required.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"product_id"},
     *       @OA\Property(property="product_id", type="integer", example=123, description="Product ID"),
     *       @OA\Property(property="variation_id", type="integer", example=456, description="Variation ID (optional)")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Eligibility check result",
     *     @OA\JsonContent(
     *       @OA\Property(property="eligible", type="boolean", example=true, description="Whether product is eligible for layby"),
     *       @OA\Property(property="message", type="string", example="This product is eligible for layby"),
     *       @OA\Property(property="product_price", type="number", format="float", example=500.00, description="Product price"),
     *       @OA\Property(property="deposit_percentage", type="integer", example=30, description="Required deposit percentage"),
     *       @OA\Property(property="deposit_amount", type="number", format="float", example=150.00, description="Deposit amount"),
     *       @OA\Property(property="available_durations", type="array", @OA\Items(type="integer"), example={3,6,12}, description="Available duration options in months"),
     *       @OA\Property(property="is_sale_product", type="boolean", example=false, description="Whether product is on sale"),
     *       @OA\Property(property="currency", type="string", example="USD", description="Currency")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Not eligible",
     *     @OA\JsonContent(
     *       @OA\Property(property="eligible", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Product price is below minimum layby amount"),
     *       @OA\Property(property="reasons", type="array", @OA\Items(type="string"))
     *     )
     *   )
     * )
     */
    public function checkEligibility(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variation_id' => 'nullable|exists:variations,id',
        ]);

        $product = Product::with('variations')->findOrFail($validated['product_id']);

        $price = $product->price;
        $isSaleProduct = false;
        $variation = null;

        if ($validated['variation_id'] ?? null) {
            $variation = $product->variations->firstWhere('id', $validated['variation_id']);
            if ($variation->sale_price) {
                $price = $variation->sale_price;
                $isSaleProduct = true;
            } else {
                $price = $variation->price ?? $price;
            }
        } else if ($product->sale_price) {
            $price = $product->sale_price;
            $isSaleProduct = true;
        }

        $currency = session('currency', config('app.default_currency', 'USD'));
        $exchangeRate = session('exchange_rate', 1);
        $priceInUSD = $price / $exchangeRate;

        $eligible = $priceInUSD >= 100 && !$product->sa_only && !($variation?->sa_only);

        // Get settings based on product type
        $depositPercentage = $isSaleProduct
            ? (int)getLaybySetting('sale_products_deposit_percentage', 30)
            : (int)getLaybySetting('regular_products_deposit_percentage', 30);

        $availableDurations = $isSaleProduct
            ? [(int)getLaybySetting('sale_products_duration_months', 3)]
            : explode(',', getLaybySetting('regular_products_duration_months', '6'));

        $availableDurations = array_map('intval', $availableDurations);

        return response()->json([
            'eligible' => $eligible,
            'price' => $price,
            'price_usd' => round($priceInUSD, 2),
            'currency' => $currency,
            'threshold' => 100,
            'is_sale_product' => $isSaleProduct,
            'deposit_percentage' => $depositPercentage,
            'available_durations' => $availableDurations,
        ]);
    }

    /**
     * Create order for completed layby using OrderRepository
     * This reuses the existing order creation infrastructure
     */
    public static function createOrderForLayby($application)
    {
        // Wrap in DB transaction to ensure nextOrderNumber's lockForUpdate works properly
        return DB::transaction(function () use ($application) {
            try {
                $orderRepo = app(OrderRepository::class);

                // Get user's default billing/shipping addresses
                $user = $application->user;
                $billingAddress = $user->address()->where('is_default', 1)->first()
                    ?? $user->address()->first();
                $shippingAddress = $billingAddress;

                // User must have a valid address before layby can be converted to order
                if (!$billingAddress) {
                    Log::warning('Cannot create order from layby: User has no address', [
                        'layby_id' => $application->id,
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                    ]);

                    throw new \Exception('Cannot create order: User must add a delivery address before the layby can be converted to an order. Please contact the customer to add their address.');
                }

                // Prepare product data in the format expected by OrderRepository
                $productData = [
                    'product_id' => $application->product_id,
                    'variation_id' => $application->variation_id,
                    'selected_attribute_ids' => $application->selected_attribute_ids,
                    'variation_display_name' => $application->variation_display_name,
                    'quantity' => 1,
                    'single_price' => $application->product_price,
                    'subtotal' => $application->product_price,
                    'tax' => 0,
                    'shipping_cost' => 0,
                    'fast_shipping_cost' => 0,
                    'item_shipping_method' => 'standard',
                    'has_fast_shipping' => false,
                ];

                // Build items structure for OrderRepository
                $items = [
                    'items' => [
                        [
                            'store' => null, // No store for layby orders
                            'products' => [$productData],
                            'total' => [
                                'sub_total' => $application->product_price,
                                'shipping_total' => 0,
                                'fast_shipping_total' => 0,
                                'tax_total' => 0,
                                'total' => $application->product_price,
                                'convert_point_amount' => 0,
                                'convert_wallet_balance' => 0,
                                'coupon_total_discount' => 0,
                            ]
                        ]
                    ],
                    'total' => [
                        'sub_total' => $application->product_price,
                        'shipping_total' => 0,
                        'fast_shipping_total' => 0,
                        'delivery_price' => 0,
                        'tax_total' => 0,
                        'total' => $application->product_price,
                        'convert_point_amount' => 0,
                        'convert_wallet_balance' => 0,
                        'coupon_total_discount' => 0,
                    ]
                ];

                // Create mock request with order data
                $request = new \Illuminate\Http\Request([
                    'consumer_id' => $application->user_id,
                    'billing_address_id' => $billingAddress->id,
                    'shipping_address_id' => $shippingAddress->id,
                    'payment_method' => 'layby',
                    'delivery_price' => 0,
                    'currency' => $application->currency,
                    'currency_symbol' => $application->currency_symbol,
                    'exchange_rate' => $application->exchange_rate,
                    'note' => 'Order created from completed Layby: ' . $application->application_number,
                    'products' => [$productData],
                ]);

                // Use OrderRepository's createOrder method
                // This is already wrapped in its own retry logic for unique constraints
                $firstStoreItem = $items['items'][0];
                $firstStoreItem['total'] = $items['total'];

                $order = $orderRepo->createOrder($firstStoreItem, $request);

                // Update order to completed/processing status since layby is fully paid
                $order->update([
                    'payment_status' => 'COMPLETED',
                    'order_status_id' => 2, // Processing status
                ]);

                // Refresh order and load order_status relationship to ensure it's available for CRM sync
                $order = $order->fresh(['order_status', 'consumer', 'products', 'billing_address', 'shipping_address']);

                // Dispatch PlaceOrderEvent to trigger all normal order processing
                // This includes CRM sync, email notifications, etc.
                event(new \App\Events\PlaceOrderEvent($order));


                return $order;

            } catch (\Exception $e) {
                throw $e;
            }
        });
    }



    /**
     * Get user's previously uploaded ID documents
     * Returns attachments from laravel-media service
     */
    /**
     * Get user's uploaded documents
     *
     * @OA\Get(
     *   path="/api/layby/uploaded-documents",
     *   tags={"Layby"},
     *   summary="Get user's previously uploaded documents",
     *   description="Retrieve list of ID documents uploaded by the authenticated user in previous layby applications. Returns unique documents to allow reuse.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Documents retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="documents",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=123, description="Document ID"),
     *           @OA\Property(property="attachment_id", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="Attachment UUID"),
     *           @OA\Property(property="type", type="string", example="passport", description="Document type"),
     *           @OA\Property(property="number", type="string", example="AB123456", description="Document number"),
     *           @OA\Property(property="url", type="string", example="https://laravel-media/storage/uploads/document.pdf", description="Document URL"),
     *           @OA\Property(property="uploaded_at", type="string", example="Dec 17, 2025", description="Upload date")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Unauthorized")
     *     )
     *   )
     * )
     */
    public function getUploadedDocuments(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Get unique document attachments from user's previous layby applications
        $documents = LaybyApplication::with('idDocumentAttachment')
            ->where('user_id', $user->id)
            ->whereNotNull('id_document_attachment_id')
            ->select('id', 'id_document_attachment_id', 'id_document_type', 'id_document_number', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique(function ($item) {
                // Group by attachment_id to avoid duplicates
                return $item->id_document_attachment_id;
            })
            ->values()
            ->map(function ($doc) {
                $attachment = $doc->idDocumentAttachment;
                return [
                    'id' => $attachment->id ?? $doc->id,
                    'attachment_id' => $doc->id_document_attachment_id,
                    'type' => $doc->id_document_type,
                    'number' => $doc->id_document_number,
                    'url' => $attachment->original_url ?? $attachment->image_url ?? null,
                    'uploaded_at' => $doc->created_at->format('M d, Y'),
                ];
            })
            ->filter(function ($doc) {
                // Remove entries without valid attachment
                return $doc['url'] !== null;
            })
            ->values();

        return response()->json([
            'success' => true,
            'documents' => $documents,
        ]);
    }

    /**
     * Upload or replace the ID document on an existing layby application.
     * Allows the user to upload their document after applying (upload later).
     *
     * PUT /layby/applications/{id}/document
     */
    public function updateDocument(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $application = LaybyApplication::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'id_document_attachment_id' => 'required|string',
            'id_document_type'          => 'required|in:passport,id_card,drivers_license',
            'id_document_number'        => 'required|string|max:50',
        ]);

        // Resolve attachment: UUID string → integer id
        $value = $validated['id_document_attachment_id'];
        $attachment = is_numeric($value)
            ? \App\Models\Attachment::find((int) $value)
            : \App\Models\Attachment::where('uuid', $value)->first();

        if (!$attachment) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ID document attachment',
                'errors'  => ['id_document_attachment_id' => ['The selected document is invalid']]
            ], 422);
        }

        $validated['id_document_attachment_id'] = $attachment->id;

        $application->update([
            'id_document_attachment_id' => $validated['id_document_attachment_id'],
            'id_document_type'          => $validated['id_document_type'],
            'id_document_number'        => $validated['id_document_number'],
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Document updated successfully',
            'application' => $application->fresh(),
        ]);
    }

    /**
     * Upload document chunk (for large files)
     *
     * @OA\Post(
     *   path="/api/layby/documents/upload-chunk",
     *   tags={"Layby"},
     *   summary="Upload document chunk",
     *   description="Upload a single chunk of a large document file. Use for files larger than 1MB. Chunk size should be ~50KB.",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"uploadId","fileName","chunkIndex","totalChunks","chunk"},
     *         @OA\Property(property="uploadId", type="string", example="doc_1734432000_abc123", description="Unique upload ID (max 100 chars)"),
     *         @OA\Property(property="fileName", type="string", example="passport.pdf", description="Original file name (max 255 chars)"),
     *         @OA\Property(property="chunkIndex", type="integer", example=0, description="Chunk index (0-based)"),
     *         @OA\Property(property="totalChunks", type="integer", example=10, description="Total number of chunks"),
     *         @OA\Property(property="chunk", type="string", format="binary", description="Chunk file data (max 100KB)")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Chunk uploaded successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Chunk 0 of 10 uploaded"),
     *       @OA\Property(property="chunkIndex", type="integer", example=0),
     *       @OA\Property(property="totalChunks", type="integer", example=10)
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="The given data was invalid."),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   )
     * )
     */
    public function uploadDocumentChunk(Request $request)
    {
        $request->validate([
            'uploadId' => ['required', 'string', 'max:100'],
            'fileName' => ['required', 'string', 'max:255'],
            'chunkIndex' => ['required', 'integer', 'min:0'],
            'totalChunks' => ['required', 'integer', 'min:1'],
            'chunk' => ['required', 'file', 'max:1024'], // Max 1MB (frontend sends 512KB chunks)
        ]);

        $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $request->input('uploadId'));
        $chunkIndex = (int) $request->input('chunkIndex');
        $totalChunks = (int) $request->input('totalChunks');

        // Store chunks in temporary directory
        $tmpDir = storage_path('app/layby_document_chunks/' . $uploadId);
        if (!is_dir($tmpDir)) {
            \File::makeDirectory($tmpDir, 0755, true);
        }

        $request->file('chunk')->move($tmpDir, $chunkIndex . '.part');

        return response()->json([
            'success' => true,
            'received' => $chunkIndex,
            'total' => $totalChunks,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate')
          ->header('Pragma', 'no-cache');
    }

    /**
     * Complete chunked upload
     *
     * @OA\Post(
     *   path="/api/layby/documents/upload-complete",
     *   tags={"Layby"},
     *   summary="Complete chunked document upload",
     *   description="Finalize chunked upload by assembling all chunks and uploading to storage. Call after all chunks are uploaded.",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"uploadId","fileName","totalChunks"},
     *       @OA\Property(property="uploadId", type="string", example="doc_1734432000_abc123", description="Same upload ID used for chunks"),
     *       @OA\Property(property="fileName", type="string", example="passport.pdf", description="Original file name"),
     *       @OA\Property(property="totalChunks", type="integer", example=10, description="Total number of chunks uploaded")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Upload completed successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Document uploaded successfully"),
     *       @OA\Property(
     *         property="attachment",
     *         type="object",
     *         @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="Attachment UUID"),
     *         @OA\Property(property="uuid", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="Attachment UUID"),
     *         @OA\Property(property="file_name", type="string", example="passport.pdf", description="File name"),
     *         @OA\Property(property="url", type="string", example="https://laravel-media/storage/uploads/document.pdf", description="Document URL")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Missing chunks or invalid file",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Missing chunk 3")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Upload session not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Upload session not found")
     *     )
     *   )
     * )
     */
    public function uploadDocumentComplete(Request $request)
    {
        // Single try-catch wrapping everything to ensure 200 response always
        try {
            $request->validate([
                'uploadId' => ['required', 'string', 'max:100'],
                'fileName' => ['required', 'string', 'max:255'],
                'totalChunks' => ['required', 'integer', 'min:1'],
            ]);

            $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $request->input('uploadId'));
            $fileName = $request->input('fileName');
            $totalChunks = (int) $request->input('totalChunks');

            $tmpDir = storage_path('app/layby_document_chunks/' . $uploadId);

            if (!is_dir($tmpDir)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload session not found',
                ], 200); // Return 200 to avoid CORS issues
            }

        // Assemble chunks into temporary file
        $finalPath = storage_path('app/temp_layby_documents/' . $uploadId . '_' . $fileName);
        $finalDir = dirname($finalPath);

        if (!is_dir($finalDir)) {
            \File::makeDirectory($finalDir, 0755, true);
        }

        $out = fopen($finalPath, 'wb');
        if (!$out) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create final file',
            ], 500);
        }

        // Combine all chunks
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $tmpDir . '/' . $i . '.part';
            if (!file_exists($chunkPath)) {
                fclose($out);
                @unlink($finalPath);
                return response()->json([
                    'success' => false,
                    'message' => "Missing chunk {$i}",
                ], 400);
            }

            $in = fopen($chunkPath, 'rb');
            if ($in) {
                while (!feof($in)) {
                    fwrite($out, fread($in, 8192));
                }
                fclose($in);
            }
        }
        fclose($out);

        // Clean up chunks directory
        \File::deleteDirectory($tmpDir);

        // Validate file type (images and PDFs allowed)
        $mimeType = mime_content_type($finalPath);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg', 'application/pdf'];

        if (!in_array($mimeType, $allowedMimes)) {
            @unlink($finalPath);
            return response()->json([
                'success' => false,
                'message' => 'Invalid file type. Only images and PDFs are allowed.',
            ], 400);
        }

        // Create UploadedFile instance from assembled file
        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $finalPath,
            $fileName,
            $mimeType,
            null,
            true // test mode - don't validate
        );

        // Upload to media service (laravel-media server)
        // This MUST succeed - no fallback to local storage
        $attachment = $this->uploadToMediaService($uploadedFile);

        // Clean up temporary file (not stored locally)
        @unlink($finalPath);

        // Handle both Eloquent models and plain objects
        if (is_object($attachment)) {
            $url = $attachment->image_url
                ?? $attachment->original_url
                ?? (method_exists($attachment, 'getAttributes') ? ($attachment->getAttributes()['original_url'] ?? null) : null);

            $attachmentId = $attachment->uuid
                ?? $attachment->id
                ?? (method_exists($attachment, 'getKey') ? $attachment->getKey() : null);
        } else {
            $url = null;
            $attachmentId = null;
        }


        return response()->json([
            'success' => true,
            'attachment' => [
                'id' => $attachmentId,
                'uuid' => $attachmentId,
                'url' => $url,
            ],
            'message' => 'Document uploaded successfully',
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate')
          ->header('Pragma', 'no-cache');

        } catch (\Exception $e) {
            // Clean up temporary files on any error
            @unlink($finalPath ?? null);

            // Log the error
            \Log::error('Failed to upload layby document', [
                'error' => $e->getMessage(),
                'file_name' => $fileName ?? 'unknown',
                'media_api_url' => config('app.image_api_url', env('IMAGE_API_URL')),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return error response
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document. Please try again or contact support.',
                'error' => config('app.debug') ? $e->getMessage() : 'Upload service unavailable',
                'debug_info' => config('app.debug') ? [
                    'exception' => get_class($e),
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile()),
                ] : null,
            ], 500);
        }
    }



    /**
     * Process payment through appropriate gateway
     * PayFast uses direct approach. Others create temporary Order for gateway compatibility.
     */
    private function processPaymentGateway($payment, $application, $request)
    {
        $paymentMethod = strtolower($payment->payment_method);

        // For COD and Bank Transfer, no redirect needed
        if (in_array($paymentMethod, ['cod', 'bank_transfer'])) {
            return [
                'success' => true,
                'message' => 'Payment request received. Please complete payment as per instructions.',
                'redirect_url' => null,
            ];
        }

        // Build return URLs using frontend URL
        $frontendUrl = rtrim(env('FRONTEND_URL', config('app.frontend_url', 'http://localhost:3000')), '/');
        $returnUrl = $frontendUrl . '/en/account/laybys/' . $application->id . '?payment=success&payment_id=' . $payment->id;
        $cancelUrl = $frontendUrl . '/en/account/laybys/' . $application->id . '?payment=failed&payment_id=' . $payment->id;

        // Store payment reference for webhook callbacks
        $payment->update([
            'gateway_reference' => 'LAYBY_PAYMENT:' . $payment->id . '|APPLICATION:' . $application->id,
        ]);

        // Build payment data for gateway (without creating order)
        $paymentData = [
            'payment_id' => $payment->id,
            'application_id' => $application->id,
            'application_number' => $application->application_number,
            'user_id' => $application->user_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'currency_symbol' => $payment->currency_symbol,
            'exchange_rate' => $payment->exchange_rate,
            'payment_method' => $payment->payment_method,
            'return_url' => $returnUrl,
            'cancel_url' => $cancelUrl,
            'item_name' => 'Layby Payment - ' . $application->product_name,
            'item_description' => 'Payment for layby application #' . $application->application_number,
        ];

        try {
            $result = null;

            switch ($paymentMethod) {
                case 'payfast':
                    $result = $this->processPayFastLayby($payment, $application, $paymentData);
                    break;
                case 'pdo_zambia':
                    $result = $this->processGatewayViaTemporaryOrder($payment, $application, $paymentData, 'pdo_zambia');
                    break;
                case 'pese':
                    $result = $this->processGatewayViaTemporaryOrder($payment, $application, $paymentData, 'pese');
                    break;
                default:
                    throw new \Exception('Unsupported payment method: ' . $paymentMethod);
            }

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Process payment for Yoco, PdoZambia, PesePay, PayPal by creating a temporary Order.
     * Each gateway's getIntent() expects an Order model. We create a lightweight temp order
     * with note = "TEMP_LAYBY_PAYMENT:{paymentId}" so webhooks can detect and route to layby handler.
     */
    private function processGatewayViaTemporaryOrder($payment, $application, $paymentData, string $gateway)
    {
        $amountInUSD = (float) $payment->amount; // stored in USD
        $meta = $payment->payment_meta ?? [];

        // Determine the amount in the currency the gateway expects
        switch ($gateway) {
            case 'yoco':
                // Yoco expects ZAR — Helpers::convertToZAR is applied inside Yoco::getIntent
                // but the order total should be in USD (internal). Yoco::getIntent calls convertToZAR itself.
                $orderTotal = $amountInUSD;
                $orderCurrency = 'USD';
                break;
            case 'pdo_zambia':
                // DPO reads order->currency and converts accordingly in getIntent
                $userCurrency = $meta['original_currency'] ?? 'ZMW';
                $orderTotal = $amountInUSD;
                $orderCurrency = $userCurrency;
                break;
            case 'pese':
                // PesePay converts to USD itself in getIntent via Helpers::convertToUSD
                $orderTotal = $amountInUSD;
                $orderCurrency = 'USD';
                break;
            case 'paypal':
                // PayPal uses Helpers::getDefaultCurrencyCode() and order->total directly
                $orderTotal = $amountInUSD;
                $orderCurrency = \App\Helpers\Helpers::getDefaultCurrencyCode() ?? 'USD';
                break;
            default:
                $orderTotal = $amountInUSD;
                $orderCurrency = 'USD';
        }

        // Create temporary order for gateway compatibility
        // Use DB transaction with lock to prevent duplicate order_number race condition
        // Must bypass excludeTempLayby scope to include all orders in the sequence
        $tempOrder = \DB::transaction(function () use ($application, $orderTotal, $orderCurrency, $gateway, $payment) {
            // Lock the last order row to prevent concurrent reads getting same order_number
            $lastOrder = Order::withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)
                ->select('order_number')
                ->orderByDesc('order_number')
                ->lockForUpdate()  // Pessimistic lock
                ->first();

            $orderNumber = $lastOrder ? ((int) $lastOrder->order_number + 1) : 1000;

            return Order::create([
                'order_number' => $orderNumber,
                'consumer_id' => $application->user_id,
                'total' => $orderTotal,
                'amount' => $orderTotal,
                'delivery_price' => 0,
                'shipping_total' => 0,
                'tax_total' => 0,
                'coupon_total_discount' => 0,
                'points_amount' => 0,
                'wallet_balance' => 0,
                'payment_method' => $gateway,
                'payment_status' => 'pending',
                'currency' => $orderCurrency,
                'note' => 'TEMP_LAYBY_PAYMENT:' . $payment->id,
                'status' => 1,
            ]);
        });

        Log::info('Layby temp order created for ' . $gateway, [
            'order_id' => $tempOrder->id,
            'order_number' => $tempOrder->order_number,
            'payment_id' => $payment->id,
            'application_id' => $application->id,
            'total' => $orderTotal,
            'currency' => $orderCurrency,
        ]);

        // Build a mock request with return/cancel URLs
        $mockRequest = new \Illuminate\Http\Request();
        $mockRequest->merge([
            'payment_method' => $gateway,
            'return_url' => $paymentData['return_url'],
            'cancel_url' => $paymentData['cancel_url'],
        ]);

        // Call the gateway's getIntent
        switch ($gateway) {
            case 'yoco':
                $intentResult = \App\Payments\Yoco::getIntent($tempOrder, $mockRequest);
                break;
            case 'pdo_zambia':
                $intentResult = \App\Payments\PdoZambia::getIntent($tempOrder, $mockRequest);
                break;
            case 'pese':
                $intentResult = \App\Payments\PesePay::getIntent($tempOrder, $mockRequest);
                break;
            case 'paypal':
                $intentResult = \App\Payments\PayPal::getIntent($tempOrder, $mockRequest);
                break;
            default:
                throw new \Exception("Unknown gateway: {$gateway}");
        }

        $redirectUrl = $intentResult['url'] ?? null;

        if (!$redirectUrl) {
            throw new \Exception("{$gateway} did not return a redirect URL for layby payment");
        }

        Log::info('Layby gateway intent success', [
            'gateway' => $gateway,
            'payment_id' => $payment->id,
            'redirect_url' => $redirectUrl,
        ]);

        return [
            'success' => true,
            'redirect_url' => $redirectUrl,
        ];
    }

    /**
     * Process PayFast payment for layby - EXACT same flow as working order payments
     * Uses route with params like PayFast::getIntent() does for orders
     * Amount is converted to ZAR in makePayment() and stored in payment_meta
     */
    private function processPayFastLayby($payment, $application, $paymentData)
    {

        // Get the base URL from PayFast notify config (uses ngrok in local env)
        $notifyUrl = config('payfast.notify_url');

        // Replace the package's /payfast/notify path with our custom /api/payfast/webhook
        // This ensures we use the ngrok URL but with our custom webhook handler
        $laybyNotifyUrl = str_replace('/payfast/notify', '/api/payfast/webhook', $notifyUrl);

        // Get ZAR amount from payment_meta (calculated in makePayment())x
        $amountInZAR = $payment->payment_meta['zar_amount'] ?? null;

        if (!$amountInZAR) {
            Log::error('PayFast Layby: Missing ZAR amount in payment_meta', [
                'payment_id' => $payment->id,
                'application_id' => $application->id,
                'payment_meta' => $payment->payment_meta,
            ]);
            throw new \Exception('Missing ZAR amount for PayFast payment');
        }

        // Format amount to 2 decimal places
        $formattedAmount = number_format($amountInZAR, 2, '.', '');

        // Log PayFast payment initiation
        Log::info('PayFast Layby Payment Initiating', [
            'payment_id' => $payment->id,
            'application_id' => $application->id,
            'application_number' => $application->application_number,
            'original_currency' => $payment->payment_meta['original_currency'] ?? 'UNKNOWN',
            'original_amount' => $payment->payment_meta['original_amount'] ?? 0,
            'amount_usd' => $payment->amount,
            'amount_zar' => $amountInZAR,
            'formatted_amount' => $formattedAmount,
            'to_zar_rate' => $payment->payment_meta['to_zar_rate'] ?? null,
        ]);

        // Build params array EXACTLY like PayFast::getIntent() does for orders
        // This will be passed to the PayFast package's redirect controller
        $params = [
            'return_url' => $paymentData['return_url'],
            'cancel_url' => $paymentData['cancel_url'],
            'notify_url' => $laybyNotifyUrl, // Use ngrok URL, not localhost!
            'name_first' => $application->user->name ?? 'Customer',
            'email_address' => $application->user->email,
            'amount' => $formattedAmount, // Send ZAR amount to PayFast (NOT USD!)
            'item_name' => $paymentData['item_name'],
            'item_description' => $paymentData['item_description'],
            'custom_int1' => $payment->id,
            'custom_int2' => $application->id,
            'custom_str1' => 'LAYBY_PAYMENT',
            'custom_str2' => (string)$payment->id,
            'custom_str3' => (string)$application->id,
        ];

        // Log params being sent to PayFast
        Log::info('PayFast Layby Params', [
            'payment_id' => $payment->id,
            'params' => $params,
        ]);

        // Use route with params EXACTLY like order payments do
        // The PayFast package's redirect controller will receive these via $request->all()
        return [
            'success' => true,
            'redirect_url' => route('payfast.redirect', $params),
        ];
    }

    /**
     * Get exchange rate between two currencies
     * @param string $from Source currency code (e.g., 'USD', 'ZMW', 'ZAR')
     * @param string $to Target currency code (e.g., 'ZAR', 'USD')
     * @return float Exchange rate
     */
    private function getExchangeRate($from, $to)
    {
        // If same currency, rate is 1
        if ($from === $to) {
            return 1.0;
        }

        // Get currency records from database
        $fromCurrency = \DB::table('currencies')->where('code', $from)->where('status', 1)->first();
        $toCurrency = \DB::table('currencies')->where('code', $to)->where('status', 1)->first();

        // Default fallback rates if currency not found in database
        $defaultRates = [
            'USD_TO_ZAR' => 16.50, // Configured rate: $1 USD = R 16.50 ZAR
            'USD_TO_ZMW' => 25.00, // Approximate rate
            'ZAR_TO_USD' => 1 / 16.50, // Reverse: R 1 ZAR = $0.0606 USD
            'ZMW_TO_USD' => 1 / 25.00, // Reverse
        ];

        // Log currency lookup
        Log::info('Exchange Rate Lookup', [
            'from' => $from,
            'to' => $to,
            'from_found' => $fromCurrency ? 'yes' : 'no',
            'to_found' => $toCurrency ? 'yes' : 'no',
            'from_rate' => $fromCurrency->exchange_rate ?? null,
            'to_rate' => $toCurrency->exchange_rate ?? null,
        ]);

        // If currencies not found in database, use default rates
        if (!$fromCurrency || !$toCurrency) {
            $fallbackKey = "{$from}_TO_{$to}";
            $reverseFallbackKey = "{$to}_TO_{$from}";

            if (isset($defaultRates[$fallbackKey])) {
                $rate = $defaultRates[$fallbackKey];
                Log::warning('Using default exchange rate (currency not in DB)', [
                    'from' => $from,
                    'to' => $to,
                    'rate' => $rate,
                ]);
                return $rate;
            } elseif (isset($defaultRates[$reverseFallbackKey])) {
                $rate = 1.0 / $defaultRates[$reverseFallbackKey];
                Log::warning('Using reverse default exchange rate (currency not in DB)', [
                    'from' => $from,
                    'to' => $to,
                    'rate' => $rate,
                    'reverse_rate' => $defaultRates[$reverseFallbackKey],
                ]);
                return $rate;
            } else {
                Log::error('No exchange rate available', [
                    'from' => $from,
                    'to' => $to,
                    'using_fallback' => 1.0,
                ]);
                return 1.0; // Last resort fallback
            }
        }

        // Exchange rates in DB are relative to USD (base currency)
        // Each currency's exchange_rate field represents: 1 USD = X of that currency
        // Examples:
        //   - USD: exchange_rate = 1.0000 (1 USD = 1 USD)
        //   - ZAR: exchange_rate = 16.5000 (1 USD = 16.50 ZAR)
        //   - ZMW: exchange_rate = 25.0000 (1 USD = 25 ZMW)

        $calculatedRate = null;

        if ($from === 'USD') {
            // USD to other currency: multiply by that currency's exchange_rate
            // Example: USD to ZAR = 16.50
            $calculatedRate = (float)$toCurrency->exchange_rate;
        } elseif ($to === 'USD') {
            // Other currency to USD: divide by that currency's exchange_rate
            // Example: ZAR to USD = 1 / 16.50 = 0.0606
            $calculatedRate = 1.0 / (float)$fromCurrency->exchange_rate;
        } else {
            // Other to other: convert to USD first, then to target
            // Example: ZMW to ZAR
            //   Step 1: ZMW to USD = 1 / 25 = 0.04
            //   Step 2: USD to ZAR = 16.50
            //   Result: ZMW to ZAR = 0.04 * 16.50 = 0.66
            $toUSD = 1.0 / (float)$fromCurrency->exchange_rate;
            $toTarget = (float)$toCurrency->exchange_rate;
            $calculatedRate = $toUSD * $toTarget;
        }

        return $calculatedRate;
    }

    /**
     * Handle PayFast webhook callback for layby payment
     * Called from PayFast::webhookHandler when payment is successful
     * Updates LaybyApplication totals ONLY (same as manual payment does)
     */
    public static function handleWebhookCallback($paymentId, $transactionId, $paymentStatus = 'completed', $paymentData = [])
    {
        try {

            $payment = \App\Models\LaybyPayment::find($paymentId);

            if (!$payment) {
                return false;
            }

            $application = $payment->laybyApplication; // FIXED: Use correct relationship name

            if (!$application) {
                return false;
            }

            // Only process if payment is still pending (prevent double-processing)
            if ($payment->payment_status === 'completed') {
                return true;
            }

            // Update the LaybyPayment record so it shows in history
            $payment->payment_status = $paymentStatus;
            $payment->gateway_reference = $transactionId;
            $payment->transaction_id    = $transactionId;
            if ($paymentStatus === 'completed') {
                $payment->paid_at = now();
            }
            $payment->save();

            // Only update application totals if payment actually succeeded
            if ($paymentStatus !== 'completed') {
                Log::warning('Layby webhook: payment not completed, skipping application update', [
                    'payment_id'    => $paymentId,
                    'payment_status'=> $paymentStatus,
                    'transaction_id'=> $transactionId,
                ]);
                return false;
            }

            // Update layby application totals (EXACT same as manual payment)
            $application->total_paid += $payment->amount;
            $application->balance_remaining = $application->total_amount - $application->total_paid;
            $application->last_payment_at = now();

            // Update status (EXACT same as manual payment)
            if ($application->status === 'pending' || $application->status === 'approved') {
                $application->status = 'active';
            }

            // Check if fully paid (EXACT same as manual payment)
            $justCompleted = false;
            if ($application->balance_remaining <= 0.01) {
                $application->status = 'completed';
                $application->completed_at = now();
                $justCompleted = true;
            }

            $application->save();

            // Auto-create order when layby becomes fully paid
            if ($justCompleted) {
                try {
                    $createdOrder = self::createOrderForLayby($application);
                    $application->order_id = $createdOrder->id;
                    $application->saveQuietly();

                    Log::info('Layby webhook: order auto-created for completed layby', [
                        'application_id' => $application->id,
                        'order_id'       => $createdOrder->id,
                        'order_number'   => $createdOrder->order_number,
                    ]);

                    // Send completion email
                    try {
                        \Illuminate\Support\Facades\Mail::send('emails.layby.completed', [
                            'application' => $application->fresh(['order', 'user']),
                            'order'       => $createdOrder,
                            'lang'        => 'en',
                        ], function ($message) use ($application) {
                            $message->to($application->user->email, $application->user->name)
                                    ->subject('🎉 Layby Completed - ' . $application->application_number);
                        });
                    } catch (\Exception $mailEx) {
                        Log::error('Layby webhook: failed to send completion email', [
                            'application_id' => $application->id,
                            'error'          => $mailEx->getMessage(),
                        ]);
                    }
                } catch (\Exception $orderEx) {
                    Log::error('Layby webhook: failed to auto-create order for completed layby', [
                        'application_id' => $application->id,
                        'error'          => $orderEx->getMessage(),
                    ]);
                }
            }

            Log::info('Layby webhook: payment recorded and application updated', [
                'payment_id'      => $paymentId,
                'transaction_id'  => $transactionId,
                'amount'          => $payment->amount,
                'application_id'  => $application->id,
                'total_paid'      => $application->total_paid,
                'balance_remaining' => $application->balance_remaining,
                'application_status'=> $application->status,
            ]);

            // CLEANUP: Delete the temporary order that was created for gateway compatibility
            // Find and delete any temp orders with note = "TEMP_LAYBY_PAYMENT:{paymentId}"
            \App\Models\Order::withTempLaybyOrders()
                ->where('note', 'TEMP_LAYBY_PAYMENT:' . $paymentId)
                ->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Layby webhook: handleWebhookCallback failed', [
                'payment_id'    => $paymentId,
                'transaction_id'=> $transactionId,
                'error'         => $e->getMessage(),
            ]);
            return false;
        }
    }


    /**
     * Upload file to laravel-media service.
     *
     * Strategy:
     * 1. Try posting directly to the media service via HTTP
     *    (works when IMAGE_API_URL is an internal/Docker URL like http://laravel-media:8080/api)
     * 2. If that fails – store the file on THIS server's own public disk and create
     *    an Attachment record directly in the local DB.
     *    This bypasses Cloudflare entirely for the server-to-server transfer.
     */
    private function uploadToMediaService($file)
    {
        $uuid = (string) \Illuminate\Support\Str::uuid();
        $mediaApiUrl = config('app.image_api_url', env('IMAGE_API_URL', 'http://localhost:8002/api'));

        // ── Strategy 1: attempt HTTP post to laravel-media ──────────────────────
        // Only worthwhile when IMAGE_API_URL resolves internally (Docker network, etc.)
        // Skip when the URL is a Cloudflare-proxied public hostname to avoid upload blocks.
        $isInternalUrl = !preg_match('/^https?:\/\/[^\/]*\.(africa|com|net|org|io|co)\b/', $mediaApiUrl);

        if ($isInternalUrl) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(60)
                    ->attach(
                        'image',
                        fopen($file->getRealPath(), 'r'), // stream instead of file_get_contents
                        $file->getClientOriginalName()
                    )
                    ->post($mediaApiUrl . '/attachments/from-api', [
                        'model_id'        => $uuid,
                        'model_type'      => 'LaybyDocument',
                        'collection_name' => 'layby_documents',
                    ]);

                if ($response->successful()) {
                    $uploadedFile = $response->json('files')[0] ?? null;

                    if ($uploadedFile) {
                        // Upsert attachment record
                        $existing = \App\Models\Attachment::where('uuid', $uploadedFile['uuid'])->first();
                        if ($existing) return $existing;

                        return \App\Models\Attachment::create([
                            'uuid'            => $uploadedFile['uuid'],
                            'name'            => $uploadedFile['name'],
                            'file_name'       => $uploadedFile['file_name'],
                            'mime_type'       => $uploadedFile['mime_type'],
                            'disk'            => $uploadedFile['disk'],
                            'collection_name' => $uploadedFile['collection_name'],
                            'size'            => $uploadedFile['size'],
                            'original_url'    => $uploadedFile['image_url'],
                            'image_url'       => $uploadedFile['image_url'],
                            'model_id'        => $uuid,
                            'model_type'      => 'LaybyDocument',
                        ]);
                    }
                }

                \Log::warning('LaybyController: media service HTTP upload did not return file data, falling back to local storage', [
                    'status'     => $response->status(),
                    'media_url'  => $mediaApiUrl,
                ]);
            } catch (\Exception $e) {
                \Log::warning('LaybyController: media service HTTP upload failed, falling back to local storage', [
                    'error'     => $e->getMessage(),
                    'media_url' => $mediaApiUrl,
                ]);
            }
        }

        // ── Strategy 2: store locally on this server ─────────────────────────────
        // No HTTP calls through Cloudflare. File is saved to public storage on
        // laravel-api and an Attachment record is created directly in the local DB.
        \Log::info('LaybyController: storing layby document locally (Cloudflare bypass)', [
            'file_name' => $file->getClientOriginalName(),
            'size'      => $file->getSize(),
        ]);

        // Determine which disk to use
        // Prefer 'public' (laravel-api/public/storage) so files are web-accessible.
        $disk     = 'public';
        $path     = 'layby_documents/' . now()->format('Y/m/d');
        $fileName = $uuid . '.' . $file->getClientOriginalExtension();

        // Move the temp file to public storage
        \Illuminate\Support\Facades\Storage::disk($disk)->put(
            $path . '/' . $fileName,
            fopen($file->getRealPath(), 'r')
        );

        // Build URL using the /api/layby-files/ route (streams via Laravel, bypasses nginx symlink issues)
        $publicUrl = rtrim(config('app.url'), '/') . '/api/layby-files/' . $path . '/' . $fileName;

        // Create the Attachment record on laravel-api's local database
        $attachment = \App\Models\Attachment::create([
            'uuid'            => $uuid,
            'name'            => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_name'       => $fileName,
            'mime_type'       => $file->getMimeType(),
            'disk'            => $disk,
            'collection_name' => 'layby_documents',
            'size'            => $file->getSize(),
            'original_url'    => $publicUrl,
            'image_url'       => $publicUrl,
            'model_id'        => $uuid,
            'model_type'      => 'LaybyDocument',
        ]);

        return $attachment;
    }


    /**
     * Render PayFast auto-submit form for layby payment
     * Uses the PayFast package directly to ensure signature is correct
     */
    public function payfastRedirect($paymentId)
    {
        // Get payment data from cache
        $cacheKey = 'payfast_layby_payment_' . $paymentId;
        $data = \Cache::get($cacheKey);

        if (!$data) {
            return response('No payment data received. Payment ID: ' . $paymentId, 400);
        }

        // Clear cache data (one-time use)
        \Cache::forget($cacheKey);

        // Use the PayFast package's makePayment method
        // This ensures the signature is generated and HTML is rendered exactly like working order payments
        $payfast = new \Eugem\Payfast\Payfast();
        $html = $payfast->makePayment($data);

        // Extract signature from generated HTML for logging
        preg_match('/name="signature"\s+value="([^"]+)"/', $html, $matches);
        $generatedSignature = $matches[1] ?? 'NOT_FOUND';

        // Wrap the form with our loading UI
        $styledHtml = '<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to PayFast...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .loading {
            text-align: center;
            margin-bottom: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading">
        <div class="spinner"></div>
        <p>Redirecting to PayFast payment gateway...</p>
        <p style="color: #666; font-size: 14px;">Please wait, do not close this window.</p>
    </div>
    ' . $html . '
</body>
</html>';

        return response($styledHtml)->header('Content-Type', 'text/html');
    }
}

