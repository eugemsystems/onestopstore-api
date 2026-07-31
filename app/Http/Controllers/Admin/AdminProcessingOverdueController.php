<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminProcessingOverdueController extends Controller
{
    /**
     * Display the processing overdue page with tabs
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'orders');
        $branch = $request->get('branch');

        // Get "Processing" status ID
        $processingStatus = OrderStatus::where('slug', 'processing')
            ->orWhere('name', 'Processing')
            ->first();

        $processingStatusId = $processingStatus ? $processingStatus->id : null;

        // Calculate date 3 days ago
        $threeDaysAgo = Carbon::now()->subDays(3);

        // Calculate branch counts for filtering buttons
        $branchCounts = $this->getBranchCounts($processingStatusId, $threeDaysAgo, $tab);

        if ($tab === 'orders') {
            // Tab 1: Orders in Processing for more than 3 days
            $query = Order::with(['consumer', 'order_status', 'products'])
                ->where('order_status_id', $processingStatusId)
                ->where('updated_at', '<=', $threeDaysAgo);

            // Get total count before branch filter
            $totalCount = $query->count();

            if ($branch) {
                // Filter by delivery_description
                $query->where(function($q) use ($branch) {
                    if ($branch === 'Harare Branch') {
                        $q->whereRaw('LOWER(delivery_description) LIKE ?', ['%harare branch%']);
                    } elseif ($branch === 'Bulawayo Branch') {
                        $q->whereRaw('LOWER(delivery_description) LIKE ?', ['%bulawayo branch%']);
                    } elseif ($branch === 'Lusaka Branch') {
                        $q->whereRaw('LOWER(delivery_description) LIKE ?', ['%lusaka branch%']);
                    } elseif ($branch === 'Mutare Branch') {
                        $q->whereRaw('LOWER(delivery_description) LIKE ?', ['%mutare branch%']);
                    } elseif ($branch === 'Home Delivery') {
                        $q->whereRaw('LOWER(delivery_description) LIKE ?', ['%standard home delivery%']);
                    }
                });
            }

            $orders = $query->orderBy('updated_at', 'asc')->paginate(50);
            $orders->appends(['tab' => 'orders', 'branch' => $branch]);

            $stats = [
                'total_overdue' => $totalCount,
            ];

            return view('admin.processing-overdue.index', [
                'tab' => 'orders',
                'orders' => $orders,
                'threeDaysAgo' => $threeDaysAgo,
                'branch' => $branch,
                'stats' => $stats,
                'branchCounts' => $branchCounts,
            ]);
        } else {
            // Tab 2: Order Products with item_status 'processing' for more than 3 days
            $query = DB::table('order_products')
                ->join('orders', 'order_products.order_id', '=', 'orders.id')
                ->leftJoin('products', 'order_products.product_id', '=', 'products.id')
                ->leftJoin('users', 'orders.consumer_id', '=', 'users.id')
                ->leftJoin('order_status', 'orders.order_status_id', '=', 'order_status.id')
                ->where('order_products.item_status', 'processing')
                ->where('order_products.updated_at', '<=', $threeDaysAgo)
                ->whereNull('order_products.deleted_at')
                ->whereNull('orders.deleted_at');

            // Get total count before branch filter
            $totalCount = $query->count();

            if ($branch) {
                // Filter by delivery_description
                $query->where(function($q) use ($branch) {
                    if ($branch === 'Harare Branch') {
                        $q->whereRaw('LOWER(orders.delivery_description) LIKE ?', ['%harare branch%']);
                    } elseif ($branch === 'Bulawayo Branch') {
                        $q->whereRaw('LOWER(orders.delivery_description) LIKE ?', ['%bulawayo branch%']);
                    } elseif ($branch === 'Lusaka Branch') {
                        $q->whereRaw('LOWER(orders.delivery_description) LIKE ?', ['%lusaka branch%']);
                    } elseif ($branch === 'Mutare Branch') {
                        $q->whereRaw('LOWER(orders.delivery_description) LIKE ?', ['%mutare branch%']);
                    } elseif ($branch === 'Home Delivery') {
                        $q->whereRaw('LOWER(orders.delivery_description) LIKE ?', ['%standard home delivery%']);
                    }
                });
            }

            $query->select(
                'order_products.id as op_id',
                'order_products.order_id',
                'order_products.product_id',
                'order_products.variation_id',
                'order_products.quantity',
                'order_products.single_price',
                'order_products.subtotal',
                'order_products.item_status',
                'order_products.updated_at as item_updated_at',
                'orders.order_number',
                'orders.consumer_id',
                'orders.order_status_id',
                'orders.delivery_description',
                DB::raw("COALESCE(products.name, order_products.product_name, 'Deleted Product') as product_name"),
                DB::raw("COALESCE(products.slug, order_products.product_slug) as product_slug"),
                'users.name as customer_name',
                'users.email as customer_email',
                'order_status.name as order_status_name'
            );

            $orderProducts = $query->orderBy('order_products.updated_at', 'asc')->paginate(50);
            $orderProducts->appends(['tab' => 'items', 'branch' => $branch]);

            $stats = [
                'total_overdue' => $totalCount,
            ];

            return view('admin.processing-overdue.index', [
                'tab' => 'items',
                'orderProducts' => $orderProducts,
                'threeDaysAgo' => $threeDaysAgo,
                'branch' => $branch,
                'stats' => $stats,
                'branchCounts' => $branchCounts,
            ]);
        }
    }

    /**
     * Get branch counts for filter buttons
     */
    private function getBranchCounts($processingStatusId, $threeDaysAgo, $tab)
    {
        if ($tab === 'orders') {
            // Count orders by delivery_description
            $branchData = DB::table('orders')
                ->where('orders.order_status_id', $processingStatusId)
                ->where('orders.updated_at', '<=', $threeDaysAgo)
                ->whereNull('orders.deleted_at')
                ->select('orders.delivery_description', DB::raw('COUNT(*) as count'))
                ->groupBy('orders.delivery_description')
                ->get();
        } else {
            // Count order products by delivery_description
            $branchData = DB::table('order_products')
                ->join('orders', 'order_products.order_id', '=', 'orders.id')
                ->where('order_products.item_status', 'processing')
                ->where('order_products.updated_at', '<=', $threeDaysAgo)
                ->whereNull('order_products.deleted_at')
                ->whereNull('orders.deleted_at')
                ->select('orders.delivery_description', DB::raw('COUNT(*) as count'))
                ->groupBy('orders.delivery_description')
                ->get();
        }

        // Map branch names to standard format and create counts array
        $branchCounts = [];
        foreach ($branchData as $row) {
            $branchName = $this->normalizeBranchName($row->delivery_description);
            if ($branchName) {
                $branchCounts[$branchName] = ($branchCounts[$branchName] ?? 0) + $row->count;
            }
        }

        return $branchCounts;
    }

    /**
     * Normalize branch names from delivery_description to match the filter buttons
     */
    private function normalizeBranchName($deliveryDescription)
    {
        if (empty($deliveryDescription)) {
            return null;
        }

        $lowerDesc = strtolower(trim($deliveryDescription));

        // Map various delivery description formats to standard names
        if (strpos($lowerDesc, 'harare branch') !== false || strpos($lowerDesc, 'harare') !== false) {
            return 'Harare Branch';
        }
        if (strpos($lowerDesc, 'bulawayo branch') !== false || strpos($lowerDesc, 'bulawayo') !== false) {
            return 'Bulawayo Branch';
        }
        if (strpos($lowerDesc, 'lusaka branch') !== false || strpos($lowerDesc, 'lusaka') !== false || strpos($lowerDesc, 'zambia') !== false) {
            return 'Lusaka Branch';
        }
        if (strpos($lowerDesc, 'mutare branch') !== false || strpos($lowerDesc, 'mutare') !== false) {
            return 'Mutare Branch';
        }
        if (strpos($lowerDesc, 'home delivery') !== false || strpos($lowerDesc, 'standard home') !== false || strpos($lowerDesc, 'delivery') !== false) {
            return 'Home Delivery';
        }

        return null;
    }

    /**
     * Get count of overdue processing items for menu badge
     */
    public function getCount()
    {
        $threeDaysAgo = Carbon::now()->subDays(3);

        // Count order products with item_status 'processing' for more than 3 days
        $count = DB::table('order_products')
            ->where('item_status', 'processing')
            ->where('updated_at', '<=', $threeDaysAgo)
            ->whereNull('deleted_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}

