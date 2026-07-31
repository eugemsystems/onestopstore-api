<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Manual permission checks - more reliable than middleware
        $this->middleware(function ($request, $next) {
            $action = $request->route()->getActionMethod();

            // Map actions to required permissions
            $permissionMap = [
                'index' => 'order.index',
                'show' => 'order.index',
                'create' => 'order.create',
                'store' => 'order.create',
                'searchUsers' => 'user.create',
                'searchProducts' => 'order.create',
                'getUserAddresses' => 'order.create',
                'createAddress' => 'order.create',
                'getUserWallet' => 'order.create',
                'getUserPoints' => 'order.create',
                'getShippingMethods' => 'order.create',
                'getCountries' => 'order.create',
                'getStates' => 'order.create',
                'updateStatus' => 'order.edit',
                'updatePaymentMethod' => 'order.edit',
                'storeNote' => 'order.edit',
                'updateNote' => 'order.edit',
                'destroyNote' => 'order.edit',
                'toggleFastShipping' => 'order.edit',
                'updateCurrency' => 'order.edit',
            ];

            // Check if this action requires a permission
            if (isset($permissionMap[$action])) {
                $requiredPermission = $permissionMap[$action];

                if (!auth()->user()->can($requiredPermission)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Unauthorized. You need the '{$requiredPermission}' permission to perform this action."
                        ], 403);
                    }
                    abort(403, "Unauthorized. You need the '{$requiredPermission}' permission to perform this action.");
                }
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of orders with caching
     */
    public function index(Request $request)
    {
        // Branch display labels (used in the dropdown)
        $branchMap = [
            'Harare'       => 'Harare Branch',
            'Bulawayo'     => 'Bulawayo Branch',
            'Mutare'       => 'Mutare Branch',
            'Zambia'       => 'Zambia Branch',
            'HomeDelivery' => 'Home Delivery',
            'Digital'      => 'Digital',
        ];

        // LIKE patterns per branch key (matches real delivery_description values)
        $branchFilters = [
            'Harare'       => ['Harare', 'Rhodesville'],
            'Bulawayo'     => ['Bulawayo'],
            'Mutare'       => ['Mutare'],
            'Zambia'       => ['Zambia'],
            'HomeDelivery' => ['15km radius', 'Standard Delivery'],
            'Digital'      => ['Digital'],
        ];

        // Fetch distinct cities from shipping addresses of top-level orders
        $cities = DB::table('addresses')
            ->join('orders', 'orders.shipping_address_id', '=', 'addresses.id')
            ->whereNull('orders.parent_id')
            ->whereNotNull('addresses.city')
            ->where('addresses.city', '!=', '')
            ->distinct()
            ->orderBy('addresses.city')
            ->pluck('addresses.city');

        // Build query
        $query = Order::with([
            'consumer:id,name,email,phone',
            'consumer.wallet:id,consumer_id,balance',
            'consumer.point:id,consumer_id,balance',
            'order_status:id,name',
            'shipping_address:id,city',
        ])->whereNull('parent_id')->latest('created_at');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('order_status_id', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('consumer', function($q) use ($search) {
                      $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('products', function($q) use ($search) {
                      $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('sku', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Branch filter – OR-match all patterns for the selected branch
        if ($request->filled('branch') && isset($branchFilters[$request->branch])) {
            $patterns = $branchFilters[$request->branch];
            $query->where(function($q) use ($patterns) {
                foreach ($patterns as $pattern) {
                    $q->orWhere('delivery_description', 'LIKE', "%{$pattern}%");
                }
            });
        }

        // City filter – case-insensitive match against shipping address city
        if ($request->filled('city')) {
            $selectedCities = array_values(array_filter(array_map('trim', (array) $request->input('city'))));
            if (!empty($selectedCities)) {
                $lowerCities = array_map('strtolower', $selectedCities);
                $query->whereHas('shipping_address', function($q) use ($lowerCities) {
                    $q->whereRaw('LOWER(city) IN (' . implode(',', array_fill(0, count($lowerCities), '?')) . ')', $lowerCities);
                });
            }
        }

        $orders = $query->paginate(10);

        // Get statuses and counts
        $statuses = OrderStatus::orderBy('sequence')->get();
        $statusCounts = [];

        foreach ($statuses as $status) {
            $statusCounts[$status->id] = Order::whereNull('parent_id')
                ->where('order_status_id', $status->id)
                ->count();
        }

        $totalCount = Order::whereNull('parent_id')->count();

        return view('admin.orders.index', compact('orders', 'statuses', 'statusCounts', 'totalCount', 'branchMap', 'cities'));
    }

    /**
     * Show order details
     */
    public function show($orderNumber)
    {
        $order = Order::with([
            'consumer',
            'consumer.wallet',
            'consumer.point',
            'order_status',
            'products',
            'sub_orders.products', // Load sub-order products
            'shipping_address.state',
            'shipping_address.country',
            'billing_address',
            'status_histories.old_status',
            'status_histories.new_status',
            'status_histories.updated_by',
            'notes.created_by'
        ])
        ->where('order_number', $orderNumber)
        ->firstOrFail();

        $statuses = OrderStatus::orderBy('sequence')->get();

        return view('admin.orders.show', compact('order', 'statuses'));
    }

    /**
     * Update order status - uses OrderRepository to trigger CRM sync
     */
    public function updateStatus(Request $request, $orderId)
    {
        $request->validate([
            'order_status_id' => 'required|exists:order_status,id'
        ]);

        $order = Order::findOrFail($orderId);

        // Use OrderRepository's update method which includes CRM sync and all business logic
        $orderRepository = app(\App\Repositories\Eloquents\OrderRepository::class);
        $orderRepository->update([
            'order_status_id' => $request->order_status_id
        ], $order->id);

        return redirect()->route('admin.orders.show', $order->order_number)
            ->with('success', 'Order status updated successfully!');
    }

    /**
     * Show create order form (POS)
     */
    public function create()
    {
        // OPTIMIZED: Load all countries with their states from cache (0 DB queries)
        $countries = getCachedCountries();
        $cachedStates = getCachedStates()->groupBy('country_id');

        // Attach states to each country
        foreach ($countries as $country) {
            $country->state = $cachedStates->get($country->id, collect());
        }

        // Get shipping options from settings (delivery methods)
        $settings = \App\Helpers\Helpers::getSettings();
        $shippingOptions = $settings['delivery']['shipping_options'] ?? [];

        // Get all shipping rules (grouped by country) for shipping fee calculation
        $shippings = \App\Models\Shipping::with(['country', 'shipping_rules' => function($query) {
            $query->where('status', true);
        }])->where('status', true)->get();

        // Get currency from logged-in user's preference, then look up live rate from Currency model
        $user = auth()->user();
        $preferredCode = strtoupper($user->preferred_currency ?? 'USD');
        $currencyModel = \App\Models\Currency::where('code', $preferredCode)
            ->where('status', 1)
            ->first();

        $detectedCurrency = [
            'code'          => $currencyModel?->code ?? $preferredCode,
            'symbol'        => $currencyModel?->symbol ?? ($user->currency_symbol ?? '$'),
            'exchange_rate' => floatval($currencyModel?->exchange_rate ?? 1.0),
        ];

        // Pass data to view
        return view('admin.orders.create', compact('countries', 'shippingOptions', 'shippings', 'detectedCurrency'));
    }

    /**
     * Store new order
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'consumer_id' => 'required|exists:users,id',
                'payment_method' => 'required|string',
                'billing_address_id' => 'required|exists:addresses,id',
                'shipping_address_id' => 'required|exists:addresses,id',
                'cart' => 'required|json',
            ]);

            // Parse cart data
            $cart = json_decode($request->cart, true);
            if (empty($cart)) {
                return back()->with('error', 'Cart is empty. Please add products to the order.');
            }

            // Prepare products array with shipping method and fast shipping if needed
            $products = array_map(function($item) use ($request) {
                $product = [
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];

                // Add shipping method if provided
                if ($request->has('item_shipping_method')) {
                    $product['item_shipping_method'] = $request->item_shipping_method;
                }

                // Add fast shipping information if present in cart item
                if (isset($item['has_fast_shipping'])) {
                    $product['has_fast_shipping'] = $item['has_fast_shipping'] ? 1 : 0;
                }
                if (isset($item['fast_shipping_cost'])) {
                    $product['fast_shipping_cost'] = floatval($item['fast_shipping_cost']);
                }

                return $product;
            }, $cart);

            // Prepare order data for the repository
            $orderData = [
                'consumer_id' => $request->consumer_id,
                'payment_method' => $request->payment_method,
                'billing_address_id' => $request->billing_address_id,
                'shipping_address_id' => $request->shipping_address_id,
                'payment_status' => 'PENDING',
                'products' => $products,
            ];

            // Add wallet balance flag if checked
            if ($request->has('wallet_balance') && $request->wallet_balance) {
                $orderData['wallet_balance'] = true;

                // Amount is already in USD (frontend always submits USD values)
                if ($request->has('wallet_balance_amount')) {
                    $orderData['wallet_balance_amount'] = floatval($request->wallet_balance_amount);
                }
            }

            // Add points flag if checked
            if ($request->has('points_amount') && $request->points_amount) {
                $orderData['points_amount'] = true;

                // Add actual points amount to deduct if provided
                if ($request->has('points_amount_value')) {
                    $orderData['points_amount_value'] = floatval($request->points_amount_value);
                }
            }

            // Add delivery/shipping information from settings shipping options
            if ($request->has('delivery_method')) {
                $orderData['delivery_description'] = $request->delivery_method . ' | ' . $request->delivery_description;
            }
            if ($request->has('delivery_price')) {
                $orderData['delivery_price'] = floatval($request->delivery_price);
            }

            // Add tax_total if apply_tax checkbox was checked
            if ($request->has('apply_tax') && $request->apply_tax) {
                $orderData['tax_total'] = floatval($request->input('tax_total', 0));
            }

            // Add currency information
            if ($request->has('currency')) {
                $orderData['currency'] = $request->currency;
                $orderData['currency_symbol'] = $request->currency_symbol ?? '$';
                $orderData['exchange_rate'] = floatval($request->exchange_rate ?? 1);
            }

            // Merge with request for OrderRepository
            $request->merge($orderData);

            // Use the existing OrderRepository to create order
            $orderRepository = app(\App\Repositories\Eloquents\OrderRepository::class);
            $order = $orderRepository->placeOrder($request);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully!',
                    'order_number' => $order->order_number
                ]);
            }

            return redirect()->route('admin.orders.show', $order->order_number)
                ->with('success', 'Order created successfully!');

        } catch (\Exception $e) {

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create order: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to create order: ' . $e->getMessage());
        }
    }

    /**
     * Update payment method
     */
    public function updatePaymentMethod(Request $request, $orderId)
    {
        $request->validate([
            'payment_method' => 'required|string|in:cod,bank_transfer,paypal,payfast,yoco,pdo_zambia,pese'
        ]);

        $order = Order::findOrFail($orderId);
        $order->update([
            'payment_method' => $request->payment_method
        ]);

        return redirect()->route('admin.orders.show', $order->order_number)
            ->with('success', 'Payment method updated successfully!');
    }

    /**
     * Store order note
     */
    public function storeNote(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'note' => 'required|string',
            'privacy' => 'required|in:private,public'
        ]);

        $order = Order::findOrFail($request->order_id);

        DB::table('order_notes')->insert([
            'order_id' => $request->order_id,
            'note' => $request->note,
            'privacy' => $request->privacy,
            'created_by_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return redirect()->route('admin.orders.show', $order->order_number)
            ->with('success', 'Note added successfully!');
    }

    /**
     * Update order note
     */
    public function updateNote(Request $request, $noteId)
    {
        $request->validate([
            'note' => 'required|string',
            'privacy' => 'required|in:private,public'
        ]);

        DB::table('order_notes')
            ->where('id', $noteId)
            ->update([
                'note' => $request->note,
                'privacy' => $request->privacy,
                'updated_at' => now()
            ]);

        $note = DB::table('order_notes')->where('id', $noteId)->first();
        $order = Order::findOrFail($note->order_id);

        return redirect()->route('admin.orders.show', $order->order_number)
            ->with('success', 'Note updated successfully!');
    }

    /**
     * Delete order note
     */
    public function destroyNote($noteId)
    {
        $note = DB::table('order_notes')->where('id', $noteId)->first();

        if (!$note) {
            return back()->with('error', 'Note not found!');
        }

        $order = Order::findOrFail($note->order_id);

        DB::table('order_notes')->where('id', $noteId)->delete();

        return redirect()->route('admin.orders.show', $order->order_number)
            ->with('success', 'Note deleted successfully!');
    }

    /**
     * Search users for order creation
     */
    public function searchUsers(Request $request)
    {
        $query = \App\Models\User::query();

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            // Use Spatie's role() scope which expects the role name as parameter
            $query->role($request->role);
        }

        $users = $query->select('id', 'name', 'email', 'phone')
            ->limit($request->get('paginate', 10))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Search products for order creation
     */
    public function searchProducts(Request $request)
    {
        $query = \App\Models\Product::with([
            'product_thumbnail',
            'variations.variation_image',
            'variations.attribute_values'
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->limit($request->get('paginate', 20))
            ->get();

        // Return USD prices (as stored in database) - NO conversion
        // Frontend will handle display conversion
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => $products,
            'currency' => [
                'code' => $user->preferred_currency ?? 'USD',
                'symbol' => $user->currency_symbol ?? '$',
                'exchange_rate' => floatval($user->currency_exchange_rate ?? 1.0)
            ]
        ]);
    }

    /**
     * Get user addresses for order creation
     */
    public function getUserAddresses(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        // OPTIMIZED: Load addresses without country/state, then attach from cache
        $addresses = \App\Models\Address::where('user_id', $request->user_id)->get();

        // Load countries and states from cache (0 DB queries)
        $cachedCountries = getCachedCountries()->keyBy('id');
        $cachedStates = getCachedStates()->keyBy('id');

        // Manually attach cached country and state to each address
        foreach ($addresses as $address) {
            if ($address->country_id && isset($cachedCountries[$address->country_id])) {
                $address->setRelation('country', $cachedCountries[$address->country_id]);
            }
            if ($address->state_id && isset($cachedStates[$address->state_id])) {
                $address->setRelation('state', $cachedStates[$address->state_id]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $addresses
        ]);
    }

    /**
     * Create address for user during order creation
     */
    public function createAddress(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'street' => 'required|string',
            'city' => 'required|string',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'pincode' => 'nullable|string',
            'phone' => 'required|string',
        ]);

        $address = \App\Models\Address::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'street' => $request->street,
            'city' => $request->city,
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
            'pincode' => $request->pincode,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'data' => $address,
            'message' => 'Address created successfully!'
        ]);
    }

    /**
     * Get user wallet balance
     */
    public function getUserWallet(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $wallet = \App\Models\Wallet::firstOrCreate(
            ['consumer_id' => $request->user_id],
            ['balance' => 0]
        );

        // Return raw USD balance — the frontend's formatCurrency() applies the
        // exchange rate for display (e.g. $8 USD → K 200 at 25×).
        // Keeping the raw value here ensures that when the order is submitted
        // the backend receives a consistent USD base amount to deduct.
        $user = auth()->user();
        $exchangeRate = floatval($user->currency_exchange_rate ?? 1.0);

        return response()->json([
            'success' => true,
            'data' => $wallet,  // raw USD balance
            'currency' => [
                'code' => $user->preferred_currency ?? 'USD',
                'symbol' => $user->currency_symbol ?? '$',
                'exchange_rate' => $exchangeRate
            ]
        ]);
    }

    /**
     * Get user points balance
     */
    public function getUserPoints(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $points = \App\Models\Point::firstOrCreate(
            ['consumer_id' => $request->user_id],
            ['balance' => 0]
        );

        // Points remain as is (not currency dependent)
        // But the display will show converted value

        return response()->json([
            'success' => true,
            'data' => $points
        ]);
    }

    /**
     * Get shipping methods for address
     */
    public function getShippingMethods(Request $request)
    {
        // Get shipping options from settings instead of database
        $settings = \App\Helpers\Helpers::getSettings();
        $shippingOptions = $settings['delivery']['shipping_options'] ?? [];

        // Get cart total from request
        $cartTotal = floatval($request->get('cart_total', 0));

        // Get free shipping threshold from settings
        $freeShippingThreshold = floatval($settings['general']['min_order_free_shipping'] ?? 0);

        // Get user's currency preference for conversion
        $user = auth()->user();
        $exchangeRate = floatval($user->currency_exchange_rate ?? 1.0);

        // Convert shipping prices to user's currency
        $shippingOptions = array_map(function($option) use ($exchangeRate, $freeShippingThreshold, $cartTotal) {
            // Convert price to user's currency
            if (isset($option['price'])) {
                $option['price'] = round($option['price'] * $exchangeRate, 2);
            }

            // If this is a paid shipping option and threshold is met, make it free
            if ($cartTotal >= $freeShippingThreshold && $freeShippingThreshold > 0) {
                if (isset($option['price']) && $option['price'] > 0) {
                    $option['original_price'] = $option['price'];
                    $option['price'] = 0;
                    $option['title'] = str_replace('Standard', 'FREE', $option['title']);
                    $option['description'] .= ' (Free shipping applied for orders over $' . number_format($freeShippingThreshold, 2) . ')';
                }
            }

            return $option;
        }, $shippingOptions);

        return response()->json([
            'success' => true,
            'data' => $shippingOptions,
            'cart_total' => $cartTotal,
            'free_shipping_threshold' => $freeShippingThreshold,
            'free_shipping_applied' => $cartTotal >= $freeShippingThreshold && $freeShippingThreshold > 0,
            'currency' => [
                'code' => $user->preferred_currency ?? 'USD',
                'symbol' => $user->currency_symbol ?? '$',
                'exchange_rate' => $exchangeRate
            ]
        ]);
    }

    /**
     * Get all countries for address form
     */
    public function getCountries(Request $request)
    {
        // OPTIMIZED: Use cached countries and states (0 DB queries)
        $countries = getCachedCountries();
        $cachedStates = getCachedStates()->groupBy('country_id');

        // Attach states to each country
        foreach ($countries as $country) {
            $country->state = $cachedStates->get($country->id, collect());
        }

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    /**
     * Get states by country ID
     */
    public function getStates(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id'
        ]);

        $states = getCachedStatesByCountry($request->country_id);

        return response()->json([
            'success' => true,
            'data' => $states
        ]);
    }

    /**
     * Toggle fast shipping for a specific product in an order
     */
    public function toggleFastShipping(Request $request, $orderId)
    {
        try {
            $request->validate([
                'order_product_id' => 'required|integer',
                'product_id' => 'required|integer',
                'has_fast_shipping' => 'required|boolean'
            ]);

            $order = Order::findOrFail($orderId);

            // Update the order_product pivot table
            $orderProduct = DB::table('order_product')
                ->where('id', $request->order_product_id)
                ->where('order_id', $orderId)
                ->first();

            if (!$orderProduct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order product not found'
                ], 404);
            }

            // Get the product to check fast shipping price
            $product = \App\Models\Product::find($request->product_id);
            $fastShippingPrice = $product->fast_shipping_price ?? 10; // Default to $10 if not set

            // Update the has_fast_shipping flag
            DB::table('order_product')
                ->where('id', $request->order_product_id)
                ->update([
                    'has_fast_shipping' => $request->has_fast_shipping ? 1 : 0,
                    'updated_at' => now()
                ]);

            // Recalculate order totals
            $fastShippingTotal = 0;

            // Get all products in this order with fast shipping enabled
            $orderProducts = DB::table('order_product')
                ->where('order_id', $orderId)
                ->where('has_fast_shipping', 1)
                ->get();

            foreach ($orderProducts as $op) {
                $prod = \App\Models\Product::find($op->product_id);
                $fastShippingTotal += ($prod->fast_shipping_price ?? 10) * $op->quantity;
            }

            // Update order fast_shipping_total
            $order->fast_shipping_total = $fastShippingTotal;

            // Recalculate order total
            $order->total = $order->amount + ($order->delivery_charge ?? 0) + ($order->tax_total ?? 0) + $fastShippingTotal;

            // Subtract wallet and points if applicable
            if ($order->wallet_balance > 0) {
                $order->total -= $order->wallet_balance;
            }
            if ($order->points_amount > 0) {
                $order->total -= $order->points_amount;
            }

            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Fast shipping updated successfully',
                'order' => [
                    'fast_shipping_total' => $order->fast_shipping_total,
                    'total' => $order->total
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating fast shipping: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update admin user currency preference
     */
    public function updateCurrency(Request $request)
    {
        try {
            $request->validate([
                'currency' => 'required|string|max:10',
                'symbol' => 'required|string|max:10',
                'exchange_rate' => 'required|numeric|min:0',
            ]);

            $user = auth()->user();
            $user->update([
                'preferred_currency' => $request->currency,
                'currency_symbol' => $request->symbol,
                'currency_exchange_rate' => $request->exchange_rate,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Currency preference updated successfully!',
                'currency' => $request->currency,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display orders with "Get It Tomorrow" products
     * Shows only products with item_status = 'processing'
     * Excludes orders with status: pending, cancelled, collected, ready for delivery, delivered
     */
    public function getItTomorrow(Request $request)
    {
        // Check permission (reusing order.index permission)
        if (!auth()->user()->can('order.index')) {
            abort(403, "Unauthorized. You need the 'order.index' permission to perform this action.");
        }

        // Excluded order statuses (by slug)
        $excludedSlugs = [
            'pending',
            'cancelled',
            'collected',
            'ready-for-delivery',
            'ready_for_delivery',
            'delivered',
        ];

        // Get excluded status IDs
        $excludedStatusIds = OrderStatus::whereIn('slug', $excludedSlugs)->pluck('id')->toArray();

        // Build query for orders that have at least one product with:
        // - products.estimated_delivery_text = "Get It Tomorrow" (from products table)
        // - Optional: filter by specific delivery text if provided
        $query = Order::with([
            'consumer:id,name,email,phone',
            'order_status:id,name,slug',
            'products.product_thumbnail' // Load products with their thumbnails
        ])
        ->whereNull('parent_id')
        ->whereNotIn('order_status_id', $excludedStatusIds)
        ->whereHas('products', function($q) {
            $q->where('products.estimated_delivery_text', 'Get It Tomorrow')
              ->where('order_products.item_status', 'processing');
        })
        ->latest('created_at');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('consumer', function($q) use ($search) {
                      $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(15);

        // Count total orders with delivery filter applied
        $totalCount = Order::whereNull('parent_id')
            ->whereNotIn('order_status_id', $excludedStatusIds)
            ->whereHas('products', function($q) use ($request) {
                if ($request->filled('delivery_filter')) {
                    $q->where('products.estimated_delivery_text', $request->delivery_filter);
                } else {
                    $q->where('products.estimated_delivery_text', 'Get It Tomorrow');
                }
            })
            ->count();

        // Get all statuses for reference
        $statuses = OrderStatus::orderBy('sequence')->get();

        return view('admin.orders.get-it-tomorrow', compact('orders', 'statuses', 'totalCount'));
    }

    public function sameDayDelivery(Request $request)
    {
        if (!auth()->user()->can('order.index')) {
            abort(403, "Unauthorized. You need the 'order.index' permission to perform this action.");
        }

        $excludedSlugs = ['pending', 'cancelled', 'collected', 'ready-for-delivery', 'ready_for_delivery', 'delivered'];
        $excludedStatusIds = OrderStatus::whereIn('slug', $excludedSlugs)->pluck('id')->toArray();

        $query = Order::with([
            'consumer:id,name,email,phone',
            'order_status:id,name,slug',
            'products.product_thumbnail',
        ])
        ->whereNull('parent_id')
        ->whereNotIn('order_status_id', $excludedStatusIds)
        ->whereHas('products', function ($q) {
            $q->where('products.estimated_delivery_text', 'LIKE', 'Same Day%')
              ->where('order_products.item_status', 'processing');
        })
        ->latest('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('consumer', function ($q) use ($search) {
                      $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(15);

        $totalCount = Order::whereNull('parent_id')
            ->whereNotIn('order_status_id', $excludedStatusIds)
            ->whereHas('products', function ($q) {
                $q->where('products.estimated_delivery_text', 'LIKE', 'Same Day%')
                  ->where('order_products.item_status', 'processing');
            })
            ->count();

        $statuses = OrderStatus::orderBy('sequence')->get();

        return view('admin.orders.same-day-delivery', compact('orders', 'statuses', 'totalCount'));
    }

    public function corporateOrders(Request $request)
    {
        if (!auth()->user()->can('order.index')) {
            abort(403, "Unauthorized. You need the 'order.index' permission to perform this action.");
        }

        $processingStatusId = OrderStatus::where('slug', 'processing')->value('id');

        $query = Order::with([
            'consumer:id,name,email,phone,company_name',
            'order_status:id,name,slug',
            'products.product_thumbnail',
        ])
        ->whereNull('parent_id')
        ->where('order_status_id', $processingStatusId)
        ->whereHas('consumer', function ($q) {
            $q->whereNotNull('company_name')->where('company_name', '!=', '');
        })
        ->latest('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('consumer', function ($q) use ($search) {
                      $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('company_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(15);

        $totalCount = Order::whereNull('parent_id')
            ->where('order_status_id', $processingStatusId)
            ->whereHas('consumer', function ($q) {
                $q->whereNotNull('company_name')->where('company_name', '!=', '');
            })
            ->count();

        $statuses = OrderStatus::orderBy('sequence')->get();

        return view('admin.orders.corporate-orders', compact('orders', 'statuses', 'totalCount'));
    }
}


