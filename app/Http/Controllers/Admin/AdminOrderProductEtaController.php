<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderProductEtaController extends BaseAdminController
{
    protected string $permissionPrefix = 'eta-overdue';

    /**
     * Display order products that have passed their ETA date
     * Excludes items with status: collected, cancelled, ready for collection, out for delivery, delivered
     */
    public function index(Request $request)
    {
        //$this->checkPermission('index');

        // Get excluded statuses (completed/cancelled states)
        $excludedItemStatuses = [
            'cancelled',
            'out for delivery',
            'out of stock',
        ];

        $excludedOrderStatuses = [
            'collected',
            'delivered',
            'ready for collection',
            'canceled',
            'cancelled',
            'out for delivery',
        ];

        // Build query to get overdue order products
        $query = DB::table('order_products as op')
            ->join('orders as o', 'op.order_id', '=', 'o.id')
            ->join('products as p', 'op.product_id', '=', 'p.id')
            ->leftJoin('users as u', 'o.consumer_id', '=', 'u.id')
            ->leftJoin('order_status as os', 'o.order_status_id', '=', 'os.id')
            ->select(
                'op.id as order_product_id',
                'op.order_id',
                'o.order_number',
                'op.product_id',
                'p.name as product_name',
                'p.sku as product_sku',
                'op.variation_id',
                'op.variation_display_name',
                'op.quantity',
                'op.single_price',
                'op.subtotal',
                'op.eta',
                'op.item_status',
                'o.consumer_id',
                'u.name as customer_name',
                'u.email as customer_email',
                'o.order_status_id',
                'os.name as order_status_name',
                'o.created_at as order_date',
                'o.delivery_description as collection_point',
                DB::raw('EXTRACT(DAY FROM (NOW()::timestamp - op.eta::timestamp)) as days_overdue')
            )
            ->whereNotNull('op.eta')
            ->where('op.eta', '<', now()->format('Y-m-d'))
            ->whereNull('op.deleted_at')
            ->whereNull('o.deleted_at');

        // Exclude based on ORDER STATUS (collected, delivered, ready for collection)
        if (!empty($excludedOrderStatuses)) {
            foreach ($excludedOrderStatuses as $status) {
                $query->whereRaw('LOWER(COALESCE(os.name, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
            }
        }

        // Also exclude based on item status (cancelled, out for delivery)
        if (!empty($excludedItemStatuses)) {
            foreach ($excludedItemStatuses as $status) {
                $query->whereRaw('LOWER(COALESCE(op.item_status, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
            }
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(p.name) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(p.sku) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhere('o.order_number', 'like', '%' . $search . '%')
                  ->orWhereRaw('LOWER(u.name) LIKE ?', ['%' . strtolower($search) . '%'])
                  ->orWhereRaw('LOWER(u.email) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        // Filter by days overdue
        if ($request->filled('days_overdue')) {
            $daysOverdue = (int) $request->days_overdue;
            $query->havingRaw('days_overdue >= ?', [$daysOverdue]);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('op.product_id', $request->product_id);
        }

        // Filter by order status
        if ($request->filled('order_status_id')) {
            $query->where('o.order_status_id', $request->order_status_id);
        }

        // Filter by date range
        if ($request->filled('eta_from')) {
            $query->where('op.eta', '>=', $request->eta_from);
        }
        if ($request->filled('eta_to')) {
            $query->where('op.eta', '<=', $request->eta_to);
        }

        // Filter by branch/delivery method
        if ($request->filled('branch')) {
            $branchFilter = $request->branch;

            // Build a filter that matches the branch name in delivery_description
            $query->where(function($q) use ($branchFilter) {
                if ($branchFilter === 'Harare Branch') {
                    $q->whereRaw('LOWER(o.delivery_description) LIKE ?', ['%harare branch%']);
                } elseif ($branchFilter === 'Bulawayo Branch') {
                    $q->whereRaw('LOWER(o.delivery_description) LIKE ?', ['%bulawayo branch%']);
                } elseif ($branchFilter === 'Lusaka Branch') {
                    $q->whereRaw('LOWER(o.delivery_description) LIKE ?', ['%lusaka branch%']);
                } elseif ($branchFilter === 'Mutare Branch') {
                    $q->whereRaw('LOWER(o.delivery_description) LIKE ?', ['%mutare branch%']);
                } elseif ($branchFilter === 'Home Delivery') {
                    $q->whereRaw('LOWER(o.delivery_description) LIKE ?', ['%standard home delivery%']);
                } else {
                    // For other delivery methods, try to match exactly
                    $q->where('o.delivery_description', $branchFilter);
                }
            });
        }

        // Order by most overdue first
        $sortBy = $request->get('sort_by', 'eta');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $overdueItems = $query->paginate(50)->withQueryString();

        // Get statistics
        $stats = [
            'total_overdue' => DB::table('order_products as op')
                ->join('orders as o', 'op.order_id', '=', 'o.id')
                ->leftJoin('order_status as os', 'o.order_status_id', '=', 'os.id')
                ->whereNotNull('op.eta')
                ->where('op.eta', '<', now()->format('Y-m-d'))
                ->whereNull('op.deleted_at')
                ->whereNull('o.deleted_at')
                ->where(function($q) use ($excludedOrderStatuses) {
                    foreach ($excludedOrderStatuses as $status) {
                        $q->whereRaw('LOWER(COALESCE(os.name, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
                    }
                })
                ->where(function($q) use ($excludedItemStatuses) {
                    foreach ($excludedItemStatuses as $status) {
                        $q->whereRaw('LOWER(COALESCE(op.item_status, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
                    }
                })
                ->count(),

            'total_value' => DB::table('order_products as op')
                ->join('orders as o', 'op.order_id', '=', 'o.id')
                ->leftJoin('order_status as os', 'o.order_status_id', '=', 'os.id')
                ->whereNotNull('op.eta')
                ->where('op.eta', '<', now()->format('Y-m-d'))
                ->whereNull('op.deleted_at')
                ->whereNull('o.deleted_at')
                ->where(function($q) use ($excludedOrderStatuses) {
                    foreach ($excludedOrderStatuses as $status) {
                        $q->whereRaw('LOWER(COALESCE(os.name, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
                    }
                })
                ->where(function($q) use ($excludedItemStatuses) {
                    foreach ($excludedItemStatuses as $status) {
                        $q->whereRaw('LOWER(COALESCE(op.item_status, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
                    }
                })
                ->sum('op.subtotal'),

            'avg_days_overdue' => DB::table('order_products as op')
                ->join('orders as o', 'op.order_id', '=', 'o.id')
                ->leftJoin('order_status as os', 'o.order_status_id', '=', 'os.id')
                ->whereNotNull('op.eta')
                ->where('op.eta', '<', now()->format('Y-m-d'))
                ->whereNull('op.deleted_at')
                ->whereNull('o.deleted_at')
                ->where(function($q) use ($excludedOrderStatuses) {
                    foreach ($excludedOrderStatuses as $status) {
                        $q->whereRaw('LOWER(COALESCE(os.name, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
                    }
                })
                ->where(function($q) use ($excludedItemStatuses) {
                    foreach ($excludedItemStatuses as $status) {
                        $q->whereRaw('LOWER(COALESCE(op.item_status, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
                    }
                })
                ->avg(DB::raw('EXTRACT(DAY FROM (NOW()::timestamp - op.eta::timestamp))')),
        ];

        // Get unique products for filter
        $products = DB::table('order_products as op')
            ->join('products as p', 'op.product_id', '=', 'p.id')
            ->whereNotNull('op.eta')
            ->where('op.eta', '<', now()->format('Y-m-d'))
            ->whereNull('op.deleted_at')
            ->select('p.id', 'p.name', 'p.sku')
            ->distinct()
            ->orderBy('p.name')
            ->get();

        // Get order statuses for filter
        $orderStatuses = OrderStatus::orderBy('sequence')->get();

        // Calculate counts per branch/delivery method
        $branchCounts = DB::table('order_products as op')
            ->join('orders as o', 'op.order_id', '=', 'o.id')
            ->leftJoin('order_status as os', 'o.order_status_id', '=', 'os.id')
            ->whereNotNull('op.eta')
            ->where('op.eta', '<', now()->format('Y-m-d'))
            ->whereNull('op.deleted_at')
            ->whereNull('o.deleted_at')
            ->where(function($q) use ($excludedOrderStatuses) {
                foreach ($excludedOrderStatuses as $status) {
                    $q->whereRaw('LOWER(COALESCE(os.name, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
                }
            })
            ->where(function($q) use ($excludedItemStatuses) {
                foreach ($excludedItemStatuses as $status) {
                    $q->whereRaw('LOWER(COALESCE(op.item_status, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
                }
            })
            ->select('o.delivery_description', DB::raw('COUNT(*) as count'))
            ->groupBy('o.delivery_description')
            ->get();

        // Aggregate counts by shortened branch names
        $aggregatedCounts = [];
        foreach ($branchCounts as $item) {
            $shortName = $this->shortenDeliveryDescription($item->delivery_description);
            if (!isset($aggregatedCounts[$shortName])) {
                $aggregatedCounts[$shortName] = 0;
            }
            $aggregatedCounts[$shortName] += $item->count;
        }

        $branchCounts = $aggregatedCounts;

        return view('admin.order-products-eta.index', compact(
            'overdueItems',
            'stats',
            'products',
            'orderStatuses',
            'excludedOrderStatuses',
            'excludedItemStatuses',
            'branchCounts'
        ));
    }

    /**
     * Export overdue items to CSV
     */
    public function export(Request $request)
    {
        $this->checkPermission('export');

        $excludedItemStatuses = [
            'cancelled',
            'out for delivery',
            'out of stock',
        ];

        $excludedOrderStatuses = [
            'collected',
            'delivered',
            'ready for collection',
            'canceled',
            'cancelled',
            'out for delivery',
        ];

        $query = DB::table('order_products as op')
            ->join('orders as o', 'op.order_id', '=', 'o.id')
            ->join('products as p', 'op.product_id', '=', 'p.id')
            ->leftJoin('users as u', 'o.consumer_id', '=', 'u.id')
            ->leftJoin('order_status as os', 'o.order_status_id', '=', 'os.id')
            ->select(
                'o.order_number',
                'p.name as product_name',
                'p.sku as product_sku',
                'op.variation_display_name',
                'op.quantity',
                'op.single_price',
                'op.subtotal',
                'op.eta',
                'op.item_status',
                'u.name as customer_name',
                'u.email as customer_email',
                'os.name as order_status_name',
                'o.created_at as order_date',
                'o.delivery_description as collection_point',
                DB::raw('EXTRACT(DAY FROM (NOW()::timestamp - op.eta::timestamp)) as days_overdue')
            )
            ->whereNotNull('op.eta')
            ->where('op.eta', '<', now()->format('Y-m-d'))
            ->whereNull('op.deleted_at')
            ->whereNull('o.deleted_at');

        // Exclude based on ORDER STATUS
        if (!empty($excludedOrderStatuses)) {
            foreach ($excludedOrderStatuses as $status) {
                $query->whereRaw('LOWER(COALESCE(os.name, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
            }
        }

        // Exclude based on item status
        if (!empty($excludedItemStatuses)) {
            foreach ($excludedItemStatuses as $status) {
                $query->whereRaw('LOWER(COALESCE(op.item_status, \'\')) NOT LIKE ?', ['%' . strtolower($status) . '%']);
            }
        }

        $items = $query->orderBy('op.eta', 'asc')->get();

        $filename = 'overdue_order_products_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($items) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Order Number',
                'Product Name',
                'SKU',
                'Variation',
                'Quantity',
                'Unit Price',
                'Subtotal',
                'ETA Date',
                'Days Overdue',
                'Item Status',
                'Customer Name',
                'Customer Email',
                'Order Status',
                'Order Date',
                'Collection Point / Delivery Method'
            ]);

            // Data rows
            foreach ($items as $item) {
                fputcsv($file, [
                    $item->order_number,
                    $item->product_name,
                    $item->product_sku,
                    $item->variation_display_name ?? 'N/A',
                    $item->quantity,
                    number_format($item->single_price, 2),
                    number_format($item->subtotal, 2),
                    $item->eta,
                    $item->days_overdue,
                    $item->item_status ?? 'Pending',
                    $item->customer_name,
                    $item->customer_email,
                    $item->order_status_name,
                    $item->order_date,
                    $this->shortenDeliveryDescription($item->collection_point)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Shorten delivery description for better readability
     */
    private function shortenDeliveryDescription(?string $description): string
    {
        if (empty($description)) {
            return 'Other';
        }

        // Check for partial matches first (most reliable)
        $lowerDesc = strtolower($description);

        if (strpos($lowerDesc, 'harare') !== false) {
            return 'Harare Branch';
        }
        if (strpos($lowerDesc, 'bulawayo') !== false) {
            return 'Bulawayo Branch';
        }
        if (strpos($lowerDesc, 'lusaka') !== false) {
            return 'Lusaka Branch';
        }
        if (strpos($lowerDesc, 'mutare') !== false) {
            return 'Mutare Branch';
        }
        if (strpos($lowerDesc, 'home delivery') !== false || strpos($lowerDesc, 'standard home') !== false) {
            return 'Home Delivery';
        }

        // If no match found, return 'Other' instead of the long description
        return 'Other';
    }
}

