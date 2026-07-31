<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Order;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\OrderRepository;
use App\Http\Traits\WalletPointsTrait;
use App\Enums\WalletPointsDetail;
use App\Enums\PaymentStatus;
use App\Enums\RequestEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\SyncOrderItemStatusToCrm;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Orders", description="Orders")
 */
class OrderController extends Controller
{
    use WalletPointsTrait;

    public $repository;

    public function __construct(OrderRepository $repository)
    {
        $this->repository = $repository;
        // Authorize resource actions, but exclude CRM endpoints and show
        // CRM endpoints (indexCrm, update via crm-orders route, updateItemStatus)
        // bypass resource authorization as they use token authentication
        $this->authorizeResource(Order::class, 'order', [
            'except' => ['show', 'update', 'updateItemStatus', 'indexCrm'],
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/order",
     *   tags={"Orders"},
     *   summary="List orders",
     *   description="Get paginated list of orders. Automatically filtered by user role: Consumers see only their orders, Vendors see orders from their store, Admins see all orders.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="paginate", in="query", required=false, @OA\Schema(type="integer", minimum=1, default=20), description="Items per page"),
     *   @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1, default=1), description="Page number"),
     *   @OA\Parameter(name="field", in="query", required=false, @OA\Schema(type="string", example="created_at"), description="Sort field (created_at, total, order_number)"),
     *   @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"}, default="desc"), description="Sort direction"),
     *   @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer", enum={0,1}), description="Order status (1=active, 0=inactive)"),
     *   @OA\Parameter(name="order_status_id", in="query", required=false, @OA\Schema(type="integer"), description="Filter by order status ID (1=Pending, 2=Processing, 3=Shipped, 4=Delivered, etc.)"),
     *   @OA\Parameter(name="payment_status", in="query", required=false, @OA\Schema(type="string", enum={"pending","completed","failed","refunded"}), description="Filter by payment status"),
     *   @OA\Parameter(name="start_date", in="query", required=false, @OA\Schema(type="string", format="date", example="2025-11-01"), description="Filter orders from this date (YYYY-MM-DD)"),
     *   @OA\Parameter(name="end_date", in="query", required=false, @OA\Schema(type="string", format="date", example="2025-11-30"), description="Filter orders until this date (YYYY-MM-DD)"),
     *   @OA\Parameter(name="consumer_id", in="query", required=false, @OA\Schema(type="integer"), description="Filter by customer ID (Admin only)"),
     *   @OA\Parameter(name="store_id", in="query", required=false, @OA\Schema(type="integer"), description="Filter by store ID (Admin only)"),
     *   @OA\Parameter(name="order_number", in="query", required=false, @OA\Schema(type="string", example="10005"), description="Search by order number"),
     *   @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string"), description="Search in order number, customer name, or email"),
     *   @OA\Response(
     *     response=200,
     *     description="Orders retrieved successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=100),
     *           @OA\Property(property="order_number", type="string", example="10005"),
     *           @OA\Property(property="consumer_id", type="integer", example=1),
     *           @OA\Property(
     *             property="consumer",
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="phone", type="string", example="1234567890")
     *           ),
     *           @OA\Property(property="amount", type="number", example=299.99, description="Subtotal before shipping and tax"),
     *           @OA\Property(property="total", type="number", example=325.98, description="Grand total"),
     *           @OA\Property(property="shipping_total", type="number", example=5.99),
     *           @OA\Property(property="tax_total", type="number", example=20.00),
     *           @OA\Property(property="coupon_total_discount", type="number", example=0),
     *           @OA\Property(property="wallet_balance", type="number", example=0),
     *           @OA\Property(property="points_amount", type="number", example=0),
     *           @OA\Property(property="payment_method", type="string", example="cod"),
     *           @OA\Property(property="payment_status", type="string", example="pending"),
     *           @OA\Property(property="order_status_id", type="integer", example=1),
     *           @OA\Property(
     *             property="order_status",
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Pending"),
     *             @OA\Property(property="slug", type="string", example="pending"),
     *             @OA\Property(property="sequence", type="integer", example=1)
     *           ),
     *           @OA\Property(property="store_id", type="integer", example=1),
     *           @OA\Property(property="currency", type="string", example="USD"),
     *           @OA\Property(property="currency_symbol", type="string", example="$"),
     *           @OA\Property(property="exchange_rate", type="number", example=1.0),
     *           @OA\Property(property="delivery_description", type="string", example="Leave at front desk"),
     *           @OA\Property(property="delivery_interval", type="string", example="8am-5pm"),
     *           @OA\Property(property="note", type="string", example="Gift wrapping"),
     *           @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-26T10:00:00.000000Z"),
     *           @OA\Property(property="delivered_at", type="string", format="date-time", nullable=true),
     *           @OA\Property(
     *             property="products",
     *             type="array",
     *             @OA\Items(
     *               type="object",
     *               @OA\Property(property="id", type="integer", example=1),
     *               @OA\Property(property="name", type="string", example="Premium Headphones"),
     *               @OA\Property(property="product_thumbnail_id", type="integer", example=100)
     *             )
     *           ),
     *           @OA\Property(
     *             property="sub_orders",
     *             type="array",
     *             description="Sub-orders for multi-vendor marketplace",
     *             @OA\Items(type="object")
     *           )
     *         )
     *       ),
     *       @OA\Property(property="current_page", type="integer", example=1),
     *       @OA\Property(property="last_page", type="integer", example=5),
     *       @OA\Property(property="per_page", type="integer", example=20),
     *       @OA\Property(property="total", type="integer", example=95)
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        try {
            // SECURITY: Log order list access
            $currentUserId = Helpers::getCurrentUserId();
            $roleName = Helpers::getCurrentRoleName();

            // Get orders without caching for real-time data
            // NOTE: Must include all foreign keys needed for Product's $with relationships
            // (product_thumbnail_id, product_meta_image_id, size_chart_image_id, store_id)
            // CRITICAL: Also include consumer and order_status to prevent Flutter null casting errors
            $orders = $this->repository->whereNull('parent_id')->with([
                'consumer:id,name,email,phone,country_code,profile_image_id',
                'order_status:id,name,slug,sequence',
                'products:id,name,product_thumbnail_id,product_meta_image_id,size_chart_image_id,store_id',
                'sub_orders.products:id,name,product_thumbnail_id,product_meta_image_id,size_chart_image_id,store_id',
                'billing_address',
                'shipping_address'
            ]);

            // CRITICAL: filter() method applies role-based filtering
            $orders = $this->filter($orders, $request);

            // DEBUG: Log after filtering
            $orderCount = $orders->count();


            $result = $orders->latest('created_at')->paginate($request->paginate ?? $orders->count());

            // DEBUG: Log successful response details AND check for null values that might break Flutter
            $firstOrder = $result->count() > 0 ? $result->items()[0] : null;


            return $result;

        } catch (Exception $e) {

            // CRITICAL FIX: Return proper JSON structure for Flutter instead of throwing exception
            // Flutter expects a consistent response structure even on errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage(),
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'total' => 0,
                    'per_page' => $request->paginate ?? 20,
                    'last_page' => 1
                ]
            ], $e->getCode() && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
        }
    }

    public function indexCrm(Request $request)
    {
        try {
            $orders = $this->repository->whereNull('parent_id')
                ->with(['order_status','sub_orders','products:id,name,slug,price,sale_price,sku','sub_orders.products:id,name,slug,price,sale_price,sku','shipping_address','order_transactions'])
                ->whereHas('order_status',function($q){
                    $q->whereNot('name','pending');
                });

            // Apply filters (but skip role-based filtering for CRM integration)
            if ($request->field && $request->sort) {
                $orders = $orders->orderBy($request->field, $request->sort);
            }

            if (isset($request->status)) {
                $orders = $orders->where('status', $request->status);
            }

            if (isset($request->order_status_id)) {
                $orders = $orders->where('order_status_id', $request->order_status_id);
            }

            if ($request->start_date && $request->end_date) {
                $orders = $orders->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }

            if ($request->updated_since) {
                $orders = $orders->where('updated_at', '>=', $request->updated_since);
            }

            // Use per_page parameter with sensible default
            $perPage = $request->per_page ?? $request->paginate ?? 50;

            return $orders->latest('created_at')->paginate($perPage);

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/order",
     *   tags={"Orders"},
     *   summary="Create order (Admin/Vendor)",
     *   description="Create a new order. Use /api/checkout for customer orders. This endpoint is for admin/vendor manual order creation.",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         required={"consumer_id","products","payment_method","billing_address_id","shipping_address_id"},
     *         @OA\Property(property="consumer_id", type="integer", example=1, description="Customer user ID"),
     *         @OA\Property(
     *           property="products",
     *           type="array",
     *           description="Array of products to order",
     *           @OA\Items(
     *             type="object",
     *             required={"product_id","quantity"},
     *             @OA\Property(property="product_id", type="integer", example=1425296, description="Product ID"),
     *             @OA\Property(property="variation_id", type="integer", nullable=true, example=null, description="Variation ID (for classified products)"),
     *             @OA\Property(property="quantity", type="integer", minimum=1, example=2, description="Quantity to order")
     *           )
     *         ),
     *         @OA\Property(property="payment_method", type="string", enum={"cod","bank_transfer","paypal","stripe","pese","pesepay","payfast","dpo_zambia","yoco"}, example="cod", description="Payment method"),
     *         @OA\Property(property="billing_address_id", type="integer", example=1, description="Billing address ID"),
     *         @OA\Property(property="shipping_address_id", type="integer", example=1, description="Shipping address ID (can be same as billing)"),
     *         @OA\Property(property="coupon", type="string", nullable=true, example="SAVE10", description="Coupon code (optional)"),
     *         @OA\Property(property="coupon_id", type="integer", nullable=true, example=5, description="Coupon ID (optional)"),
     *         @OA\Property(property="delivery_description", type="string", nullable=true, example="Leave at front desk", description="Delivery instructions"),
     *         @OA\Property(property="delivery_interval", type="string", nullable=true, example="8am-5pm", description="Preferred delivery time window"),
     *         @OA\Property(property="delivery_price", type="number", format="float", example=5.99, description="Shipping cost"),
     *         @OA\Property(property="fast_shipping_total", type="number", format="float", nullable=true, example=15.99, description="Expedited shipping cost (if applicable)"),
     *         @OA\Property(property="currency", type="string", example="USD", description="Currency code (ISO 4217)"),
     *         @OA\Property(property="currency_symbol", type="string", example="$", description="Currency symbol"),
     *         @OA\Property(property="exchange_rate", type="number", format="float", example=1.0, description="Exchange rate to base currency"),
     *         @OA\Property(property="wallet_balance", type="boolean", example=false, description="Use wallet balance for payment"),
     *         @OA\Property(property="points_amount", type="boolean", example=false, description="Use loyalty points for payment"),
     *         @OA\Property(property="order_status_id", type="integer", nullable=true, example=1, description="Initial order status (default: pending)"),
     *         @OA\Property(property="note", type="string", nullable=true, example="Gift wrapping requested", description="Order notes"),
     *         @OA\Property(property="return_url", type="string", nullable=true, example="https://example.com/order-confirmation", description="Return URL after payment"),
     *         @OA\Property(property="cancel_url", type="string", nullable=true, example="https://example.com/order-cancelled", description="Cancel URL for payment")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Order created successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Order created successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=100),
     *         @OA\Property(property="order_number", type="string", example="10005"),
     *         @OA\Property(property="amount", type="number", example=299.99),
     *         @OA\Property(property="total", type="number", example=305.98),
     *         @OA\Property(property="shipping_total", type="number", example=5.99),
     *         @OA\Property(property="tax_total", type="number", example=0),
     *         @OA\Property(property="payment_method", type="string", example="cod"),
     *         @OA\Property(property="payment_status", type="string", example="pending"),
     *         @OA\Property(property="order_status_id", type="integer", example=1),
     *         @OA\Property(
     *           property="order_status",
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="name", type="string", example="Pending"),
     *           @OA\Property(property="slug", type="string", example="pending")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="error", type="string", example="Validation failed"),
     *       @OA\Property(
     *         property="details",
     *         type="object",
     *         @OA\Property(property="consumer_id", type="array", @OA\Items(type="string", example="The consumer id field is required."))
     *       )
     *     )
     *   )
     * )
     */
    public function store(CreateOrderRequest $request)
    {
        return $this->repository->placeOrder($request);
    }

    /**
     * @OA\Get(
     *   path="/api/order/{order_number}",
     *   tags={"Orders"},
     *   summary="Get order details",
     *   description="Get complete order details including products, addresses, status history, and payment information. Users can only view their own orders (role-based access).",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="order_number", in="path", required=true, @OA\Schema(type="string", example="10005"), description="Order Number"),
     *   @OA\Response(
     *     response=200,
     *     description="Order details retrieved successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer", example=100),
     *       @OA\Property(property="order_number", type="string", example="10005"),
     *       @OA\Property(property="consumer_id", type="integer", example=1),
     *       @OA\Property(
     *         property="consumer",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="name", type="string", example="John Doe"),
     *         @OA\Property(property="email", type="string", example="john@example.com"),
     *         @OA\Property(property="phone", type="string", example="1234567890"),
     *         @OA\Property(property="country_code", type="string", example="+1")
     *       ),
     *       @OA\Property(property="amount", type="number", example=299.99, description="Subtotal"),
     *       @OA\Property(property="total", type="number", example=325.98, description="Grand total"),
     *       @OA\Property(property="shipping_total", type="number", example=5.99),
     *       @OA\Property(property="fast_shipping_total", type="number", example=0),
     *       @OA\Property(property="tax_total", type="number", example=20.00),
     *       @OA\Property(property="coupon_total_discount", type="number", example=0),
     *       @OA\Property(property="wallet_balance", type="number", example=0),
     *       @OA\Property(property="points_amount", type="number", example=0),
     *       @OA\Property(property="payment_method", type="string", example="cod"),
     *       @OA\Property(property="payment_status", type="string", example="pending"),
     *       @OA\Property(property="order_status_id", type="integer", example=1),
     *       @OA\Property(
     *         property="order_status",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="name", type="string", example="Pending"),
     *         @OA\Property(property="slug", type="string", example="pending"),
     *         @OA\Property(property="sequence", type="integer", example=1)
     *       ),
     *       @OA\Property(property="store_id", type="integer", example=1),
     *       @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *       @OA\Property(property="coupon_id", type="integer", nullable=true, example=5),
     *       @OA\Property(property="billing_address_id", type="integer", example=1),
     *       @OA\Property(
     *         property="billing_address",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="title", type="string", example="Home"),
     *         @OA\Property(property="street", type="string", example="123 Main St"),
     *         @OA\Property(property="city", type="string", example="New York"),
     *         @OA\Property(property="state_id", type="integer", example=10),
     *         @OA\Property(property="country_id", type="integer", example=1),
     *         @OA\Property(property="pincode", type="string", example="10001"),
     *         @OA\Property(property="phone", type="string", example="1234567890")
     *       ),
     *       @OA\Property(property="shipping_address_id", type="integer", example=1),
     *       @OA\Property(
     *         property="shipping_address",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="street", type="string", example="123 Main St"),
     *         @OA\Property(property="city", type="string", example="New York")
     *       ),
     *       @OA\Property(property="currency", type="string", example="USD"),
     *       @OA\Property(property="currency_symbol", type="string", example="$"),
     *       @OA\Property(property="exchange_rate", type="number", example=1.0),
     *       @OA\Property(property="delivery_description", type="string", example="Leave at front desk"),
     *       @OA\Property(property="delivery_interval", type="string", example="8am-5pm"),
     *       @OA\Property(property="delivery_price", type="number", example=5.99),
     *       @OA\Property(property="note", type="string", example="Gift wrapping requested"),
     *       @OA\Property(property="invoice_url", type="string", nullable=true, example="https://example.com/invoices/10005.pdf"),
     *       @OA\Property(property="status", type="integer", example=1),
     *       @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-26T10:00:00.000000Z"),
     *       @OA\Property(property="delivered_at", type="string", format="date-time", nullable=true, example=null),
     *       @OA\Property(
     *         property="products",
     *         type="array",
     *         description="Order items with pivot data",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="name", type="string", example="Premium Headphones"),
     *           @OA\Property(property="slug", type="string", example="premium-headphones"),
     *           @OA\Property(
     *             property="pivot",
     *             type="object",
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="variation_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="quantity", type="integer", example=2),
     *             @OA\Property(property="price", type="number", example=249.99),
     *             @OA\Property(property="subtotal", type="number", example=499.98),
     *             @OA\Property(property="shipping_cost", type="number", example=5.99),
     *             @OA\Property(property="tax", type="number", example=0)
     *           )
     *         )
     *       ),
     *       @OA\Property(
     *         property="status_histories",
     *         type="array",
     *         description="Order status change history",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="old_status_id", type="integer", example=1),
     *           @OA\Property(property="new_status_id", type="integer", example=2),
     *           @OA\Property(
     *             property="old_status",
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Pending")
     *           ),
     *           @OA\Property(
     *             property="new_status",
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Processing")
     *           ),
     *           @OA\Property(property="updated_by_id", type="integer", example=10),
     *           @OA\Property(
     *             property="updated_by",
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Admin User")
     *           ),
     *           @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *       ),
     *       @OA\Property(
     *         property="notes",
     *         type="array",
     *         description="Order notes/comments",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="note", type="string", example="Customer requested gift wrapping"),
     *           @OA\Property(property="is_customer_visible", type="boolean", example=true),
     *           @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *       ),
     *       @OA\Property(
     *         property="sub_orders",
     *         type="array",
     *         description="Sub-orders (for multi-vendor orders)",
     *         @OA\Items(type="object")
     *       ),
     *       @OA\Property(
     *         property="summary",
     *         type="object",
     *         description="Order summary calculations",
     *         @OA\Property(property="subtotal", type="number", example=299.99),
     *         @OA\Property(property="shipping", type="number", example=5.99),
     *         @OA\Property(property="tax", type="number", example=20.00),
     *         @OA\Property(property="discount", type="number", example=0),
     *         @OA\Property(property="total", type="number", example=325.98)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden - You cannot view this order",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="You are not authorized to view this order.")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Order not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string", example="Order not found.")
     *     )
     *   )
     * )
     */
    public function show($id)
    {


        try {
            $order = Order::where('order_number', $id)->firstOrFail();


            // CRITICAL SECURITY: Authorization check BEFORE any data access
            // Ensure user can only view their own orders
            $roleName = Helpers::getCurrentRoleName();
            $currentUserId = Helpers::getCurrentUserId();

            // Log security check for debugging
            if ($roleName == RoleEnum::CONSUMER) {
                // Consumers can ONLY view their own orders - strict check
                if ($order->consumer_id !== $currentUserId) {
                    Log::warning('📱 [ANDROID] Authorization FAILED - Consumer accessing wrong order', [
                        'current_user_id' => $currentUserId,
                        'order_consumer_id' => $order->consumer_id,
                        'order_number' => $order->order_number,
                        'ip' => request()->ip()
                    ]);

                    return response()->json([
                        'message' => 'You are not authorized to view this order.'
                    ], 403);
                }

            } elseif ($roleName == RoleEnum::VENDOR) {
                // Vendors can only view orders containing their products
                $vendorStoreId = Helpers::getCurrentVendorStoreId();

                // Check if order contains any products from vendor's store
                $hasVendorProducts = $order->products()
                    ->where('products.store_id', $vendorStoreId)
                    ->exists();

                if (!$hasVendorProducts) {
                    Log::warning('📱 [ANDROID] Authorization FAILED - Vendor accessing order without their products', [
                        'vendor_user_id' => $currentUserId,
                        'vendor_store_id' => $vendorStoreId,
                        'order_number' => $order->order_number
                    ]);

                    return response()->json([
                        'message' => 'You are not authorized to view this order.'
                    ], 403);
                }


            }
            // Admin can view all orders - no restriction needed

            // Load necessary relationships

            $order->load(config('enums.order.with', []));

            // Use the repository's verifyPayment method to get full order details
            $result = $this->repository->verifyPayment($order);


            return $result;

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found.',
                'error' => 'The requested order does not exist.'
            ], 404);

        } catch (Exception $e) {

            return response()->json([
                'message' => 'Order not found.',
                'error' => $e->getMessage()
            ], $e->getCode() ?: 404);
        }
    }


    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/order/{order}",
     *   tags={"Orders"},
     *   summary="Update order",
     *   description="Update order details. Typically used to change order status, add notes, or update delivery information.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="order", in="path", required=true, @OA\Schema(type="integer"), description="Order ID"),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         @OA\Property(property="order_status_id", type="integer", example=2, description="Update order status (1=Pending, 2=Processing, 3=Shipped, 4=Delivered, 5=Cancelled)"),
     *         @OA\Property(property="payment_status", type="string", enum={"pending","completed","failed","refunded"}, example="completed", description="Update payment status"),
     *         @OA\Property(property="delivery_description", type="string", example="Package left at door", description="Update delivery instructions"),
     *         @OA\Property(property="delivery_interval", type="string", example="9am-6pm", description="Update delivery time window"),
     *         @OA\Property(property="note", type="string", example="Customer called to confirm delivery", description="Add order note"),
     *         @OA\Property(property="tracking_number", type="string", example="TRACK123456789", description="Shipment tracking number"),
     *         @OA\Property(property="invoice_url", type="string", example="https://example.com/invoices/10005.pdf", description="Invoice URL"),
     *         @OA\Property(property="delivered_at", type="string", format="date-time", example="2025-11-28T15:30:00Z", description="Delivery completion timestamp"),
     *         @OA\Property(property="status", type="integer", enum={0,1}, example=1, description="Order active status")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Order updated successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Order updated successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=100),
     *         @OA\Property(property="order_number", type="string", example="10005"),
     *         @OA\Property(property="order_status_id", type="integer", example=2),
     *         @OA\Property(property="payment_status", type="string", example="completed")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=403, description="Forbidden - Cannot update this order"),
     *   @OA\Response(response=404, description="Order not found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $result = $this->repository->update($request->all(), $order->getId($request));

        // Cache invalidation removed since we're no longer caching order lists

        return $result;
    }

    /**
     * @OA\Delete(
     *   path="/api/order/{order}",
     *   tags={"Orders"},
     *   summary="Delete order",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="order", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Order $order)
    {
        return $this->repository->destroy($order->getId($request));
    }

    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }

    /**
     * @OA\Get(
     *   path="/api/trackOrder/{order_number}",
     *   tags={"Orders"},
     *   summary="Track order",
     *   @OA\Parameter(name="order_number", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function trackOrder(Request $request)
    {
        return $this->repository->trackOrder($request->order_number);
    }

    /**
     * @OA\Get(
     *   path="/api/verifyPayment/{order_number}",
     *   tags={"Orders"},
     *   summary="Verify payment",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="order_number", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function verifyPayment(Request $request)
    {
        return $this->repository->verifyPayment($request);
    }

    /**
     * @OA\Post(
     *   path="/api/rePayment",
     *   tags={"Orders"},
     *   summary="Retry payment",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         required={"order_number","payment_method"},
     *         @OA\Property(property="order_number", type="string", example="10005"),
     *         @OA\Property(property="payment_method", type="string", enum={"cod","bank_transfer","paypal","pese","payfast","pdo_zambia","yoco"}, example="paypal")
     *       ),
     *       example={"order_number": "10005", "payment_method": "paypal"}
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function rePayment(Request $request)
    {
        return $this->repository->rePayment($request);
    }

    public function getInvoiceUrl(Request $request)
    {
        return $this->repository->getInvoiceUrl($request->order_number);
    }

    public function getInvoice(Request $request)
    {
        return $this->repository->getInvoice($request);
    }

    // Admin: cancel/remove an item from a paid order and credit wallet
    public function adminCancelItem(Request $request, Order $order)
    {
        try {
            if ($order->payment_status !== PaymentStatus::COMPLETED) {
                throw new Exception('Only items from orders with completed payment can be cancelled and credited.', 400);
            }

            $validated = $request->validate([
                'product_id'   => 'required|integer',
                'variation_id' => 'nullable|integer',
                'quantity'     => 'nullable|integer|min:1',
                'include_shipping' => 'sometimes|boolean',
            ]);

            $productId   = (int) $validated['product_id'];
            $variationId = $validated['variation_id'] ?? null;
            $includeShipping = (bool) ($validated['include_shipping'] ?? false);

            // Load pivot row for this product (and variation if provided)
            $q = $order->products()->where('product_id', $productId);
            if ($variationId !== null) {
                $q->wherePivot('variation_id', $variationId);
            }
            $product = $q->first();
            if (!$product) {
                throw new Exception('The specified item does not exist on this order.', 404);
            }

            $pivot = $product->pivot;
            $currentQty = (int) ($pivot->quantity ?? 0);
            if ($currentQty <= 0) {
                throw new Exception('This item has zero quantity on the order.', 400);
            }

            $cancelQty = (int) ($validated['quantity'] ?? $currentQty);
            if ($cancelQty > $currentQty) { $cancelQty = $currentQty; }

            $unitPrice = (float) ($pivot->single_price ?? 0);
            $unitShipping = $currentQty > 0 ? ((float) ($pivot->shipping_cost ?? 0) / $currentQty) : 0.0;

            $creditAmount = $unitPrice * $cancelQty;
            if ($includeShipping) {
                $creditAmount += ($unitShipping * $cancelQty);
            }

            DB::beginTransaction();

            // Credit consumer wallet
            $this->creditWallet($order->consumer_id, $creditAmount, WalletPointsDetail::ADMIN_CREDIT.' #'.$order->order_number);

            // Update pivot: reduce quantity/subtotal, adjust shipping proportionally; mark as approved
            $newQty = $currentQty - $cancelQty;
            if ($newQty > 0) {
                $newSubtotal = $unitPrice * $newQty;
                $newShipping = max(0, ((float) ($pivot->shipping_cost ?? 0)) - ($unitShipping * $cancelQty));
                $updateData = [
                    'quantity' => $newQty,
                    'subtotal' => Helpers::formatDecimal($newSubtotal),
                    'shipping_cost' => Helpers::formatDecimal($newShipping),
                    'refund_status' => RequestEnum::APPROVED,
                ];
                if ($variationId !== null) {
                    $order->products()->updateExistingPivot($productId, $updateData, false);
                } else {
                    $order->products()->updateExistingPivot($productId, $updateData);
                }
            } else {
                // FIXED: Don't hard delete - mark as cancelled with quantity=0 to preserve order history
                // This allows viewing what was in the order even after cancellation
                $updateData = [
                    'quantity' => 0,
                    'subtotal' => 0,
                    'shipping_cost' => 0,
                    'refund_status' => RequestEnum::APPROVED,
                ];

                if ($variationId !== null) {
                    $order->products()->updateExistingPivot($productId, $updateData, false);
                } else {
                    $order->products()->updateExistingPivot($productId, $updateData);
                }

            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'product_id' => $productId,
                'variation_id' => $variationId,
                'cancelled_quantity' => $cancelQty,
                'credited_amount' => Helpers::formatDecimal($creditAmount),
                'wallet_balance' => Helpers::formatDecimal($this->getWalletBalance($order->consumer_id)),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ExceptionHandler($e->getMessage(), (int) $e->getCode());
        }
    }

    // Admin: minimal orders listing for dashboard widgets
    public function adminOrder(Request $request)
    {
        try {
            $query = Order::query()
                ->setEagerLoads([])
                ->whereNull('parent_id')
                ->select(['id','order_number','consumer_id','order_status_id','total','exchange_rate','currency','currency_symbol'])
                ->with([
                    'consumer:id,name',
                    'order_status:id,name',
                ]);

            if ($request->order_status_id) {
                $query->where('order_status_id', $request->order_status_id);
            }

            $paginator = $query->latest('created_at')->paginate($request->paginate ?? 15);

            $paginator->getCollection()->transform(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'consumer' => [
                        'name' => optional($order->consumer)->name,
                    ],
                    'order_status' => [
                        'id' => optional($order->order_status)->id,
                        'name' => optional($order->order_status)->name,
                    ],
                    'total' => (float) $order->total,
                    'exchange_rate' => (float) $order->exchange_rate,
                    'currency' => $order->currency,
                    'currency_symbol' => $order->currency_symbol,
                ];
            });

            return $paginator;
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Update order item status
     *
     * @OA\Put(
     *   path="/api/order/{order}/item-status",
     *   tags={"Orders"},
     *   summary="Update order item status",
     *   description="Update status of individual order items (e.g., cancelled, out of stock, delivered)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="order",
     *     in="path",
     *     required=true,
     *     description="Order ID",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"product_id", "item_status"},
     *       @OA\Property(property="product_id", type="integer", example=45),
     *       @OA\Property(property="variation_id", type="integer", example=10, nullable=true),
     *       @OA\Property(property="item_status", type="string", enum={"pending", "processing", "shipped", "delivered", "collected", "cancelled", "out_of_stock", "refunded"}, example="cancelled"),
     *       @OA\Property(property="cancellation_reason", type="string", example="Product out of stock")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Item status updated",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Order item status updated successfully"),
     *       @OA\Property(property="order_id", type="integer", example=123)
     *     )
     *   ),
     *   @OA\Response(response=404, description="Order or item not found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateItemStatus(Request $request, string $orderNumber)
    {
        // DEBUG: Write to file to prove this method is being called
        $debugFile = storage_path('logs/api-item-update-calls.txt');
        file_put_contents($debugFile, date('Y-m-d H:i:s') . " | Order: {$orderNumber} | From CRM: " . ($request->header('X-From-CRM') ? 'YES' : 'NO') . "\n", FILE_APPEND);

        try {
            // Find order by order_number
            $order = Order::where('order_number', $orderNumber)->first();

            if (!$order) {
                Log::error('Order not found', ['order_number' => $orderNumber]);
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found with order number: ' . $orderNumber
                ], 404);
            }

            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                //'variation_id' => 'nullable|integer|exists:variations,id',
                'variation_id' => 'nullable',
                // Allow all valid item statuses from CRM
                'item_status' => 'required|string|in:pending,processing,shipped,delivered,collected,cancelled,out_of_stock,refunded,in transit to zim,out for delivery,ready for collection,warehouse packing,dropped at the deport,from supplier,out of stock,stuck,arrived at local branch',
                'cancellation_reason' => 'nullable|string|max:500',
                'eta' => 'nullable|date',
            ]);

            $productId = (int) $validated['product_id'];
            $variationId = $validated['variation_id'] ?? null;
            $itemStatus = $validated['item_status'];
            $cancellationReason = $validated['cancellation_reason'] ?? null;
            $eta = $validated['eta'] ?? null;

            // Find the order_product record directly in the order_products table
            // Strategy 1: Exact match — product_id + variation_id (or NULL if not provided)
            $query = DB::table('order_products')
                ->where('order_id', $order->id)
                ->where('product_id', $productId)
                ->whereNull('deleted_at');

            if ($variationId) {
                $query->where('variation_id', $variationId);
            } else {
                $query->whereNull('variation_id');
            }

            $orderProduct = $query->first();

            // Strategy 2: variation_id was provided but not found — the variation may have been
            // deleted (SET NULL migration sets variation_id to NULL in order_products).
            // Try matching by product_id + NULL variation_id.
            if (!$orderProduct && $variationId) {
                $orderProduct = DB::table('order_products')
                    ->where('order_id', $order->id)
                    ->where('product_id', $productId)
                    ->whereNull('variation_id')
                    ->whereNull('deleted_at')
                    ->first();

                if ($orderProduct) {
                    Log::info('updateItemStatus: Found item via SET NULL fallback (variation was deleted)', [
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'requested_variation_id' => $variationId,
                        'order_product_id' => $orderProduct->id,
                    ]);
                }
            }

            // Strategy 3: product_id itself may be NULL (product was deleted via SET NULL).
            // Try matching by order_id + variation_id where product_id IS NULL.
            if (!$orderProduct && $variationId) {
                $orderProduct = DB::table('order_products')
                    ->where('order_id', $order->id)
                    ->whereNull('product_id')
                    ->where('variation_id', $variationId)
                    ->whereNull('deleted_at')
                    ->first();

                if ($orderProduct) {
                    Log::info('updateItemStatus: Found item via NULL product_id fallback (product was deleted)', [
                        'order_id' => $order->id,
                        'requested_product_id' => $productId,
                        'variation_id' => $variationId,
                        'order_product_id' => $orderProduct->id,
                    ]);
                }
            }

            // Strategy 4: Both product_id and variation_id may be NULL (both deleted).
            // Try matching by order_id + both NULLs — only useful when there's one such row.
            if (!$orderProduct && $variationId) {
                $nullRow = DB::table('order_products')
                    ->where('order_id', $order->id)
                    ->whereNull('product_id')
                    ->whereNull('variation_id')
                    ->whereNull('deleted_at')
                    ->get();

                if ($nullRow->count() === 1) {
                    $orderProduct = $nullRow->first();
                    Log::info('updateItemStatus: Found item via both-NULL fallback (product+variation were deleted)', [
                        'order_id' => $order->id,
                        'requested_product_id' => $productId,
                        'requested_variation_id' => $variationId,
                        'order_product_id' => $orderProduct->id,
                    ]);
                }
            }

            if (!$orderProduct) {
                // Log full order_products snapshot to help diagnose mismatches
                $allItems = DB::table('order_products')
                    ->where('order_id', $order->id)
                    ->whereNull('deleted_at')
                    ->select('id', 'product_id', 'variation_id', 'item_status')
                    ->get();

                Log::warning('Product not found in order', [
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'hint' => 'Check if variation/product was deleted (SET NULL migration). CRM may still hold old IDs.',
                    'order_items_in_db' => $allItems->toArray(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in this order'
                ], 404);
            }

            // Update the order_products table directly
            $updateData = [
                'item_status' => $itemStatus,
                'updated_at' => now(),
            ];

            // Always update ETA if the key exists in request (even if null to clear it)
            if ($request->has('eta')) {
                $updateData['eta'] = $eta; // This will be null if cleared
            }

            if ($cancellationReason) {
                $updateData['cancellation_reason'] = $cancellationReason;
            } else {
                // Clear cancellation reason if status is not cancelled/out_of_stock/refunded
                if (!in_array($itemStatus, ['cancelled', 'out_of_stock', 'refunded'])) {
                    $updateData['cancellation_reason'] = null;
                }
            }

            $updated = DB::table('order_products')
                ->where('id', $orderProduct->id)
                ->update($updateData);

            // Verify the update
            $verifyUpdate = DB::table('order_products')
                ->where('id', $orderProduct->id)
                ->first();

            // NOTE: We deliberately do NOT call checkAndUpdateOrderStatus() here.
            // The main order status and item statuses are independent.
            // Item statuses track per-product progress; the main order status is set manually from the CRM.

            // Auto-credit wallet when the CRM marks an item as out of stock.
            // Fires when: request comes from CRM (X-From-CRM header) AND
            // the new status is out_of_stock AND the item was not already out of stock.
            $outOfStockStatuses = ['out_of_stock', 'out of stock'];
            $isOutOfStock = in_array(strtolower(trim($itemStatus)), $outOfStockStatuses);
            $wasAlreadyOutOfStock = in_array(strtolower(trim($orderProduct->item_status ?? '')), $outOfStockStatuses);
            $fromCrm  = (bool) $request->header('X-From-CRM');
            $crmActor = trim((string) $request->header('X-CRM-Actor', ''));

            Log::info('OOS credit check', [
                'order_number'          => $order->order_number,
                'item_status_received'  => $itemStatus,
                'previous_item_status'  => $orderProduct->item_status,
                'is_out_of_stock'       => $isOutOfStock,
                'was_already_oos'       => $wasAlreadyOutOfStock,
                'from_crm'              => $fromCrm,
                'crm_actor'             => $crmActor,
                'consumer_id'           => $order->consumer_id,
                'subtotal'              => $orderProduct->subtotal,
                'single_price'          => $orderProduct->single_price,
                'quantity'              => $orderProduct->quantity,
            ]);

            if ($isOutOfStock && !$wasAlreadyOutOfStock && $fromCrm && $crmActor === 'super-admin@raines.africa') {
                try {
                    $consumerId = $order->consumer_id;
                    // Credit amount = line subtotal; fall back to unit price × qty if subtotal is zero
                    $creditAmount = (float) ($orderProduct->subtotal ?: ($orderProduct->single_price * ($orderProduct->quantity ?: 1)));

                    if ($consumerId && $creditAmount > 0) {
                        $productLabel = $orderProduct->product_name
                            ?? ($orderProduct->product_id ? optional(\App\Models\Product::find($orderProduct->product_id))->name : null)
                            ?? 'Item';
                        $remark = WalletPointsDetail::OUT_OF_STOCK_CREDIT
                            . ': ' . $productLabel
                            . ' — Order #' . $order->order_number;

                        $this->creditWallet($consumerId, $creditAmount, $remark);

                        Log::info('Auto-credited wallet for out of stock item', [
                            'order_number' => $order->order_number,
                            'consumer_id'  => $consumerId,
                            'amount'       => $creditAmount,
                            'product'      => $productLabel,
                            'crm_actor'    => $crmActor,
                        ]);
                    }
                } catch (\Throwable $creditException) {
                    Log::error('Failed to auto-credit wallet for out of stock item', [
                        'order_number' => $order->order_number,
                        'error'        => $creditException->getMessage(),
                    ]);
                    // Do not fail the item status update if the credit fails
                }
            }

            // Only dispatch event if NOT from CRM
            // CRM handles its own specialized notifications (ETA emails, status emails)
            // API only sends notifications for direct API calls (admin panel, etc.)
            if (!$request->header('X-From-CRM')) {
                $order->refresh();
                event(new \App\Events\UpdateOrderStatusEvent($order));
            }

            // Sync with CRM via webhook (asynchronously) - only if not already from CRM
            try {
                $crmBaseUrl = rtrim(config('services.crm.base_url'), '/');

                // Don't sync back to CRM if this request came from CRM (prevent infinite loop)
                if ($crmBaseUrl && !$request->header('X-From-CRM')) {
                    // Dispatch job to sync asynchronously - this won't block the response
                    SyncOrderItemStatusToCrm::dispatch(
                        $order->id,
                        $productId,
                        $variationId,
                        $itemStatus,
                        $cancellationReason,
                        $eta
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch CRM sync job', [
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the API request if job dispatch fails
            }

            // Reload the order with updated data
            $order->load(['products' => function($q) {
                $q->select('products.id', 'products.name', 'products.sku', 'products.product_thumbnail_id');
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Order item status updated successfully',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'product_id' => $productId,
                'variation_id' => $variationId,
                'item_status' => $itemStatus,
                'eta' => $eta,
                'rows_updated' => $updated,
                'order' => $order
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), (int) $e->getCode());
        }
    }

    public function filter($orders, $request)
    {
        $roleName = Helpers::getCurrentRoleName();
        $currentUserId = Helpers::getCurrentUserId();

        // DEBUG: Log filter entry

        // Role-based filtering
        if ($roleName == RoleEnum::CONSUMER) {


            $orders = $orders->where('consumer_id', $currentUserId);
        }

        if ($roleName == RoleEnum::VENDOR) {
            $vendorStoreId = Helpers::getCurrentVendorStoreId();


            $orders = $this->repository->whereNotNull('parent_id')->where('store_id', $vendorStoreId);
        }

        // Additional filters
        if ($request->field && $request->sort) {

            $orders = $orders->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {

            $orders = $orders->where('status', $request->status);
        }

        if (isset($request->order_status_id)) {

            $orders = $orders->where('order_status_id', $request->order_status_id);
        }

        if ($request->start_date && $request->end_date) {

            $orders = $orders->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        // DEBUG: Log final SQL query

        return $orders;
    }

    /**
     * Check all order items and update order status if needed
     * This ensures the order status reflects the overall state of all items
     */
    protected function checkAndUpdateOrderStatus(Order $order): void
    {
        try {
            // Get all item statuses for this order
            $itemStatuses = DB::table('order_products')
                ->where('order_id', $order->id)
                ->pluck('item_status')
                ->filter()
                ->toArray();

            if (empty($itemStatuses)) {
                return;
            }

            // Determine the appropriate order status based on item statuses
            $newOrderStatus = $this->determineOrderStatusFromItems($itemStatuses);

            if ($newOrderStatus) {
                // Find the order status by name
                $orderStatus = \App\Models\OrderStatus::where('name', $newOrderStatus)
                    ->orWhere('slug', \Illuminate\Support\Str::slug($newOrderStatus))
                    ->first();

                if ($orderStatus && $order->order_status_id !== $orderStatus->id) {

                    $order->update(['order_status_id' => $orderStatus->id]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to check and update order status', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - item status update already succeeded
        }
    }

    /**
     * Determine what the order status should be based on item statuses
     */
    protected function determineOrderStatusFromItems(array $itemStatuses): ?string
    {
        // If all items are delivered, order is delivered
        $allDelivered = count($itemStatuses) === count(array_filter($itemStatuses, fn($s) =>
            in_array(strtolower($s), ['delivered', 'collected'])
        ));

        if ($allDelivered) {
            return 'delivered';
        }

        // If any item is shipped/out for delivery/ready for collection, order is in that status
        $hasShipped = in_array('shipped', array_map('strtolower', $itemStatuses));
        $hasOutForDelivery = in_array('out for delivery', array_map('strtolower', $itemStatuses));
        $hasReadyForCollection = in_array('ready for collection', array_map('strtolower', $itemStatuses));

        if ($hasOutForDelivery) {
            return 'out for delivery';
        }

        if ($hasReadyForCollection) {
            return 'ready for collection';
        }

        if ($hasShipped) {
            return 'shipped';
        }

        // If any item is processing, order is processing
        $hasProcessing = in_array('processing', array_map('strtolower', $itemStatuses));
        if ($hasProcessing) {
            return 'processing';
        }

        // If all items are cancelled, order should be cancelled
        $allCancelled = count($itemStatuses) === count(array_filter($itemStatuses, fn($s) =>
            strtolower($s) === 'cancelled'
        ));

        if ($allCancelled) {
            return 'cancelled';
        }

        // Default: keep current status (don't change)
        return null;
    }
}
