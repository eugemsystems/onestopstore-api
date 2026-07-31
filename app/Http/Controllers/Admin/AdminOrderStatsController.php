<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdminOrderStatsController extends BaseAdminController
{
    protected string $permissionPrefix = 'order-stats';

    /**
     * Helper method to get date range from request
     */
    private function getDateRange(Request $request)
    {
        if ($request->has('start_date') && $request->has('end_date')) {
            return [
                'since' => Carbon::parse($request->start_date)->startOfDay(),
                'until' => Carbon::parse($request->end_date)->endOfDay(),
                'days' => Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) + 1
            ];
        }

        $days = (int) $request->query('days', 30);
        return [
            'since' => Carbon::now()->subDays($days)->startOfDay(),
            'until' => Carbon::now()->endOfDay(),
            'days' => $days
        ];
    }

    /**
     * Display the order statistics dashboard
     */
    public function index(Request $request)
    {
        $this->checkPermission('view');

        $days = (int) $request->query('days', 30);

        return view('admin.orders.stats', [
            'days' => $days,
        ]);
    }

    /**
     * Get overview statistics
     * GET /admin/orders/stats/overview
     */
    public function overview(Request $request)
    {
        $this->checkPermission('overview');
        try {
            $range = $this->getDateRange($request);
            $since = $range['since'];
            $until = $range['until'];
            $days  = $range['days'];

            // ── Cancelled status IDs (excluded from revenue & non-status KPIs) ─────
            $cancelledIds = \App\Models\OrderStatus::whereIn('slug', ['cancelled', 'canceled'])->pluck('id')->toArray();

            // Base query helper: paid, non-cancelled, non-sub-orders, date-filtered
            $paidBase = fn() => Order::whereNull('parent_id')
                ->whereNull('deleted_at')
                ->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('order_status_id', $cancelledIds))
                ->where('created_at', '>=', $since)
                ->where('created_at', '<=', $until);

            // Broader base (all statuses) for total order count KPI
            $allBase = fn() => Order::whereNull('parent_id')
                ->whereNull('deleted_at')
                ->where('created_at', '>=', $since)
                ->where('created_at', '<=', $until);

            // Total order count (all, including pending/cancelled — for informational purposes)
            $totalOrders = $allBase()->count();
            $totalOrdersSincePeriod = $totalOrders;

            // Revenue (paid & non-cancelled only)
            $totalRevenue = (float) $paidBase()->sum('total');
            $totalRevenueSincePeriod = $totalRevenue;

            // Average order value (paid only)
            $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / max(1, $paidBase()->count()), 2) : 0;

            // Shipping and discounts (paid only)
            $totalShipping  = (float) $paidBase()->sum(DB::raw('shipping_total + fast_shipping_total'));
            $totalDiscounts = (float) $paidBase()->sum('coupon_total_discount');

            // Average items per order (paid only)
            $avgItemsPerOrder = DB::table('order_products')
                ->join('orders', 'order_products.order_id', '=', 'orders.id')
                ->whereNull('orders.parent_id')->whereNull('orders.deleted_at')
                ->whereIn('orders.payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('orders.order_status_id', $cancelledIds))
                ->where('orders.created_at', '>=', $since)
                ->where('orders.created_at', '<=', $until)
                ->select(DB::raw('AVG(order_products.quantity) as avg_items'))
                ->value('avg_items');

            // Completion & cancellation rates (based on all orders in period)
            $deliveredOrders = $allBase()->whereHas('order_status', fn($q) => $q->where('slug', 'delivered'))->count();
            $cancelledOrders = $allBase()->whereHas('order_status', fn($q) => $q->whereIn('slug', ['cancelled', 'canceled']))->count();
            $totalForRate    = $allBase()->count();

            $completionRate   = $totalForRate > 0 ? round(($deliveredOrders   / $totalForRate) * 100, 1) : 0;
            $cancellationRate = $totalForRate > 0 ? round(($cancelledOrders   / $totalForRate) * 100, 1) : 0;

            // Orders by status (all statuses, date-filtered)
            $byStatus = Order::whereNull('parent_id')->whereNull('deleted_at')
                ->where('created_at', '>=', $since)->where('created_at', '<=', $until)
                ->select('order_status_id', DB::raw('count(*) as count'))
                ->groupBy('order_status_id')
                ->with('order_status:id,name')
                ->get()
                ->map(fn($r) => [
                    'status_id'   => $r->order_status_id,
                    'status_name' => $r->order_status?->name ?? 'Unknown',
                    'count'       => (int) $r->count,
                ]);

            // Daily chart (paid, non-cancelled)
            $daily = DB::table('orders')
                ->whereNull('parent_id')->whereNull('deleted_at')
                ->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('order_status_id', $cancelledIds))
                ->where('created_at', '>=', $since)->where('created_at', '<=', $until)
                ->selectRaw("DATE(created_at) as date, count(*) as count, sum(total) as revenue")
                ->groupBy(DB::raw("DATE(created_at)"))
                ->orderBy('date')
                ->get()
                ->map(fn($r) => [
                    'date'    => $r->date,
                    'count'   => (int) $r->count,
                    'revenue' => (float) $r->revenue,
                ]);

            // Payment method (paid, non-cancelled)
            $byPaymentMethod = DB::table('orders')
                ->whereNull('parent_id')->whereNull('deleted_at')
                ->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('order_status_id', $cancelledIds))
                ->where('created_at', '>=', $since)->where('created_at', '<=', $until)
                ->whereNotNull('payment_method')
                ->selectRaw('payment_method, count(*) as count, sum(total) as revenue')
                ->groupBy('payment_method')
                ->get()
                ->map(fn($r) => [
                    'method'  => $r->payment_method ?: 'Unknown',
                    'count'   => (int) $r->count,
                    'revenue' => (float) $r->revenue,
                ]);

            // Payment status breakdown (informational — all orders)
            $byPaymentStatus = Order::whereNull('parent_id')->whereNull('deleted_at')
                ->where('created_at', '>=', $since)->where('created_at', '<=', $until)
                ->select('payment_status', DB::raw('count(*) as count'))
                ->groupBy('payment_status')
                ->get()
                ->map(fn($r) => [
                    'status' => $r->payment_status ?: 'Unknown',
                    'count'  => (int) $r->count,
                ]);

            // Hourly distribution (paid, non-cancelled)
            $byHour = DB::table('orders')
                ->whereNull('parent_id')->whereNull('deleted_at')
                ->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('order_status_id', $cancelledIds))
                ->where('created_at', '>=', $since)->where('created_at', '<=', $until)
                ->selectRaw("EXTRACT(HOUR FROM created_at) as hour, count(*) as count")
                ->groupBy(DB::raw("EXTRACT(HOUR FROM created_at)"))
                ->orderBy('hour')
                ->get();

            // Day of week distribution (paid, non-cancelled)
            $byWeekday = DB::table('orders')
                ->whereNull('parent_id')->whereNull('deleted_at')
                ->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('order_status_id', $cancelledIds))
                ->where('created_at', '>=', $since)->where('created_at', '<=', $until)
                ->selectRaw("EXTRACT(DOW FROM created_at) as dow, count(*) as count")
                ->groupBy(DB::raw("EXTRACT(DOW FROM created_at)"))
                ->orderBy('dow')
                ->get();

            // Top countries (paid, non-cancelled)
            $topCountries = DB::table('orders')
                ->join('addresses',  'orders.shipping_address_id', '=', 'addresses.id')
                ->join('countries',  'addresses.country_id',       '=', 'countries.id')
                ->whereNull('orders.parent_id')->whereNull('orders.deleted_at')
                ->whereIn('orders.payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('orders.order_status_id', $cancelledIds))
                ->where('orders.created_at', '>=', $since)->where('orders.created_at', '<=', $until)
                ->select('countries.name', DB::raw('count(*) as count'), DB::raw('sum(orders.total) as revenue'))
                ->groupBy('countries.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            // Currency breakdown (paid, non-cancelled)
            $byCurrency = DB::table('orders')
                ->whereNull('parent_id')->whereNull('deleted_at')
                ->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('order_status_id', $cancelledIds))
                ->where('created_at', '>=', $since)->where('created_at', '<=', $until)
                ->selectRaw('currency, count(*) as count, sum(total) as revenue')
                ->groupBy('currency')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_orders'           => (int)   $totalOrders,
                    'total_orders_period'    => (int)   $totalOrdersSincePeriod,
                    'total_revenue'          => (float) $totalRevenue,
                    'total_revenue_period'   => (float) $totalRevenueSincePeriod,
                    'avg_order_value'        => (float) $avgOrderValue,
                    'total_shipping'         => (float) $totalShipping,
                    'total_discounts'        => (float) $totalDiscounts,
                    'avg_items_per_order'    => round((float) $avgItemsPerOrder, 2),
                    'completion_rate'        => (float) $completionRate,
                    'cancellation_rate'      => (float) $cancellationRate,
                    'orders_by_status'       => $byStatus,
                    'daily'                  => $daily,
                    'by_payment_method'      => $byPaymentMethod,
                    'by_payment_status'      => $byPaymentStatus,
                    'by_hour'               => $byHour,
                    'by_weekday'            => $byWeekday,
                    'top_countries'         => $topCountries,
                    'by_currency'           => $byCurrency,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Order stats overview error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load overview statistics',
            ], 500);
        }
    }


    /**
     * Get top products statistics
     * GET /admin/orders/stats/top-products
     */
    public function topProducts(Request $request)
    {
        try {
            $limit = (int) $request->query('limit', 10);
            $range = $this->getDateRange($request);

            $cancelledIds = \App\Models\OrderStatus::whereIn('slug', ['cancelled', 'canceled'])->pluck('id')->toArray();

            $rows = DB::table('order_products')
                ->join('orders', 'order_products.order_id', '=', 'orders.id')
                ->whereNull('orders.parent_id')->whereNull('orders.deleted_at')
                ->whereIn('orders.payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('orders.order_status_id', $cancelledIds))
                ->where('orders.created_at', '>=', $range['since'])
                ->where('orders.created_at', '<=', $range['until'])
                ->select(
                    'order_products.product_id',
                    DB::raw('SUM(order_products.quantity) as total_quantity'),
                    DB::raw('COUNT(DISTINCT order_products.order_id) as order_count'),
                    DB::raw('SUM(order_products.subtotal) as total_revenue')
                )
                ->groupBy('order_products.product_id')
                ->orderByDesc('total_quantity')
                ->limit($limit)
                ->get();

            $productIds = $rows->pluck('product_id')->all();
            $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $result = $rows->map(function ($r) use ($products) {
                $p = $products->get($r->product_id);
                return [
                    'product_id'  => $r->product_id,
                    'name'        => $p ? $p->name : 'Product #' . $r->product_id,
                    'quantity'    => (int)   $r->total_quantity,
                    'order_count' => (int)   $r->order_count,
                    'revenue'     => (float) $r->total_revenue,
                ];
            });

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('Top products error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load top products'], 500);
        }
    }


    /**
     * Get order status statistics with transitions
     * GET /admin/orders/stats/status-details
     */
    public function statusDetails(Request $request)
    {
        try {
            $range = $this->getDateRange($request);
            $since = $range['since'];
            $until = $range['until'];

            // Current status distribution
            $statusDistribution = Order::select('order_status_id', DB::raw('count(*) as count'))
                ->groupBy('order_status_id')
                ->with('order_status:id,name,slug')
                ->get()
                ->map(function ($r) {
                    return [
                        'status_id' => $r->order_status_id,
                        'status_name' => $r->order_status ? $r->order_status->name : 'Unknown',
                        'count' => (int) $r->count
                    ];
                });

            // Status changes over time
            $statusOverTime = DB::table('order_status_histories')
                ->join('order_status', 'order_status_histories.new_status_id', '=', 'order_status.id')
                ->where('order_status_histories.created_at', '>=', $since)
                ->where('order_status_histories.created_at', '<=', $until)
                ->select(
                    DB::raw("DATE(order_status_histories.created_at) as date"),
                    'order_status.name as status_name',
                    DB::raw("count(*) as count")
                )
                ->groupBy(DB::raw("DATE(order_status_histories.created_at)"), 'order_status.name')
                ->orderBy('date')
                ->get();

            // Simplified average time in each status
            // Get average days between status changes
            $avgTimeInStatus = DB::table('order_status_histories')
                ->join('order_status', 'order_status_histories.new_status_id', '=', 'order_status.id')
                ->where('order_status_histories.created_at', '>=', $since)
                ->where('order_status_histories.created_at', '<=', $until)
                ->select(
                    'order_status.name as status_name',
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('order_status.name')
                ->get()
                ->map(function($r) {
                    return [
                        'status_name' => $r->status_name,
                        'avg_hours' => round(24, 2) // Placeholder - could be enhanced with actual time tracking
                    ];
                });

            // Shipping methods distribution
            $shippingMethods = DB::table('order_products')
                ->join('orders', 'order_products.order_id', '=', 'orders.id')
                ->where('orders.created_at', '>=', $since)
                ->where('orders.created_at', '<=', $until)
                ->select(
                    'order_products.item_shipping_method as method',
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('order_products.item_shipping_method')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'status_distribution' => $statusDistribution,
                    'status_over_time' => $statusOverTime,
                    'avg_time_in_status' => $avgTimeInStatus,
                    'shipping_methods' => $shippingMethods,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Status details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load status details'
            ], 500);
        }
    }

    /**
     * Get product performance statistics
     * GET /admin/orders/stats/product-performance
     */
    public function productPerformance(Request $request)
    {
        try {
            $range = $this->getDateRange($request);

            $cancelledIds = \App\Models\OrderStatus::whereIn('slug', ['cancelled', 'canceled'])->pluck('id')->toArray();

            // Top revenue products (paid, non-cancelled)
            $topRevenue = DB::table('order_products')
                ->join('orders',   'order_products.order_id',   '=', 'orders.id')
                ->join('products', 'order_products.product_id', '=', 'products.id')
                ->whereNull('orders.parent_id')->whereNull('orders.deleted_at')
                ->whereIn('orders.payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('orders.order_status_id', $cancelledIds))
                ->where('orders.created_at', '>=', $range['since'])
                ->where('orders.created_at', '<=', $range['until'])
                ->select(
                    'products.id', 'products.name',
                    DB::raw('SUM(order_products.subtotal) as revenue'),
                    DB::raw('SUM(order_products.quantity) as quantity')
                )
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get();

            // Category performance (paid, non-cancelled)
            $categoryPerformance = DB::table('order_products')
                ->join('orders',             'order_products.order_id',       '=', 'orders.id')
                ->join('products',           'order_products.product_id',     '=', 'products.id')
                ->join('product_categories', 'products.id',                   '=', 'product_categories.product_id')
                ->join('categories',         'product_categories.category_id','=', 'categories.id')
                ->whereNull('orders.parent_id')->whereNull('orders.deleted_at')
                ->whereIn('orders.payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('orders.order_status_id', $cancelledIds))
                ->where('orders.created_at', '>=', $range['since'])
                ->where('orders.created_at', '<=', $range['until'])
                ->select(
                    'categories.name as category_name',
                    DB::raw('COUNT(DISTINCT products.id) as product_count'),
                    DB::raw('SUM(order_products.quantity) as total_quantity'),
                    DB::raw('SUM(order_products.subtotal) as total_revenue')
                )
                ->groupBy('categories.name')
                ->orderByDesc('total_revenue')
                ->limit(10)
                ->get();

            // Item status (informational — all orders in period)
            $itemStatusDistribution = DB::table('order_products')
                ->join('orders', 'order_products.order_id', '=', 'orders.id')
                ->whereNull('orders.parent_id')->whereNull('orders.deleted_at')
                ->where('orders.created_at', '>=', $range['since'])
                ->where('orders.created_at', '<=', $range['until'])
                ->select('order_products.item_status', DB::raw('COUNT(*) as count'))
                ->groupBy('order_products.item_status')
                ->get();

            // Top variations (paid, non-cancelled)
            $topVariations = DB::table('order_products')
                ->join('orders', 'order_products.order_id', '=', 'orders.id')
                ->whereNull('orders.parent_id')->whereNull('orders.deleted_at')
                ->whereIn('orders.payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('orders.order_status_id', $cancelledIds))
                ->where('orders.created_at', '>=', $range['since'])
                ->where('orders.created_at', '<=', $range['until'])
                ->whereNotNull('order_products.variation_id')
                ->select(
                    'order_products.variation_display_name as variation_name',
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(order_products.subtotal) as revenue')
                )
                ->groupBy('order_products.variation_display_name')
                ->orderByDesc('order_count')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data'    => [
                    'top_revenue_products'    => $topRevenue,
                    'category_performance'    => $categoryPerformance,
                    'item_status_distribution'=> $itemStatusDistribution,
                    'top_variations'          => $topVariations,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Product performance error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load product performance'], 500);
        }
    }


    /**
     * Get customer insights
     * GET /admin/orders/stats/customer-insights
     */
    public function customerInsights(Request $request)
    {
        try {
            $range = $this->getDateRange($request);

            $cancelledIds = \App\Models\OrderStatus::whereIn('slug', ['cancelled', 'canceled'])->pluck('id')->toArray();

            // Top customers by order count (paid, non-cancelled)
            $topCustomersByOrders = Order::whereNull('parent_id')->whereNull('deleted_at')
                ->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('order_status_id', $cancelledIds))
                ->where('created_at', '>=', $range['since'])
                ->where('created_at', '<=', $range['until'])
                ->select('consumer_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total) as total_spent'))
                ->groupBy('consumer_id')
                ->orderByDesc('order_count')
                ->limit(10)
                ->with('consumer:id,name,email')
                ->get()
                ->map(fn($r) => [
                    'customer_id'    => $r->consumer_id,
                    'customer_name'  => $r->consumer?->name  ?? 'Unknown',
                    'customer_email' => $r->consumer?->email ?? '',
                    'order_count'    => (int)   $r->order_count,
                    'total_spent'    => (float) $r->total_spent,
                ]);

            // Top customers by revenue (paid, non-cancelled)
            $topCustomersByRevenue = Order::whereNull('parent_id')->whereNull('deleted_at')
                ->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledIds), fn($q) => $q->whereNotIn('order_status_id', $cancelledIds))
                ->where('created_at', '>=', $range['since'])
                ->where('created_at', '<=', $range['until'])
                ->select('consumer_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total) as total_spent'))
                ->groupBy('consumer_id')
                ->orderByDesc('total_spent')
                ->limit(10)
                ->with('consumer:id,name,email')
                ->get()
                ->map(fn($r) => [
                    'customer_id'    => $r->consumer_id,
                    'customer_name'  => $r->consumer?->name  ?? 'Unknown',
                    'customer_email' => $r->consumer?->email ?? '',
                    'order_count'    => (int)   $r->order_count,
                    'total_spent'    => (float) $r->total_spent,
                ]);

            // New vs returning customers (all orders, informational)
            $newCustomers = DB::table('orders as o1')
                ->whereNull('o1.parent_id')->whereNull('o1.deleted_at')
                ->where('o1.created_at', '>=', $range['since'])
                ->where('o1.created_at', '<=', $range['until'])
                ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                    ->from('orders as o2')
                    ->whereColumn('o2.consumer_id', 'o1.consumer_id')
                    ->where('o2.created_at', '<', $range['since']))
                ->distinct('consumer_id')->count('consumer_id');

            $returningCustomers = DB::table('orders as o1')
                ->whereNull('o1.parent_id')->whereNull('o1.deleted_at')
                ->where('o1.created_at', '>=', $range['since'])
                ->where('o1.created_at', '<=', $range['until'])
                ->whereExists(fn($q) => $q->select(DB::raw(1))
                    ->from('orders as o2')
                    ->whereColumn('o2.consumer_id', 'o1.consumer_id')
                    ->where('o2.created_at', '<', $range['since']))
                ->distinct('consumer_id')->count('consumer_id');

            return response()->json([
                'success' => true,
                'data'    => [
                    'top_customers_by_orders'  => $topCustomersByOrders,
                    'top_customers_by_revenue' => $topCustomersByRevenue,
                    'new_customers'            => (int) $newCustomers,
                    'returning_customers'      => (int) $returningCustomers,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Customer insights error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load customer insights'], 500);
        }
    }
}
