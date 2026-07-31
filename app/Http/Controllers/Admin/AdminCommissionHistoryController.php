<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionHistory;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCommissionHistoryController extends Controller
{
    /**
     * Display a listing of commission histories
     */
    public function index(Request $request)
    {
        $query = CommissionHistory::with([
            'order',
            'store.vendor',
            'items' => function($query) {
                $query->with(['product', 'category']);
            }
        ]);

        // Filter by store/vendor
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Search by order number
        if ($request->filled('search')) {
            $query->whereHas('order', function($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search . '%');
            });
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        $commissions = $query->paginate(20)->withQueryString();

        // Get all stores for filter dropdown
        $stores = Store::with('vendor')
            ->where('is_approved', 1)
            ->orderBy('store_name')
            ->get();

        // Calculate statistics
        $stats = [
            'total_admin_commission' => CommissionHistory::sum('admin_commission'),
            'total_vendor_commission' => CommissionHistory::sum('vendor_commission'),
            'this_month_admin' => CommissionHistory::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('admin_commission'),
            'this_month_vendor' => CommissionHistory::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('vendor_commission'),
            'total_records' => CommissionHistory::count(),
        ];

        return view('admin.commissions.index', compact('commissions', 'stores', 'stats'));
    }

    /**
     * Export commissions to CSV
     */
    public function export(Request $request)
    {
        $query = CommissionHistory::with(['order', 'store.vendor']);

        // Apply same filters as index
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $commissions = $query->orderBy('created_at', 'desc')->get();

        $filename = 'commissions_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($commissions) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'Order Number',
                'Store Name',
                'Vendor Name',
                'Admin Commission',
                'Vendor Commission',
                'Total Amount',
                'Created Date'
            ]);

            // CSV Data
            foreach ($commissions as $commission) {
                fputcsv($file, [
                    $commission->order->order_number ?? 'N/A',
                    $commission->store->store_name ?? 'N/A',
                    $commission->store->vendor->name ?? 'N/A',
                    number_format($commission->admin_commission, 2),
                    number_format($commission->vendor_commission, 2),
                    number_format($commission->admin_commission + $commission->vendor_commission, 2),
                    $commission->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get monthly statistics for chart
     */
    public function monthlyStats(Request $request)
    {
        $year = $request->get('year', now()->year);

        $monthlyData = CommissionHistory::selectRaw('
                MONTH(created_at) as month,
                SUM(admin_commission) as admin_total,
                SUM(vendor_commission) as vendor_total
            ')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Initialize all 12 months with 0
        $adminCommissions = array_fill(1, 12, 0);
        $vendorCommissions = array_fill(1, 12, 0);

        foreach ($monthlyData as $data) {
            $adminCommissions[$data->month] = (float) $data->admin_total;
            $vendorCommissions[$data->month] = (float) $data->vendor_total;
        }

        return response()->json([
            'admin_commissions' => array_values($adminCommissions),
            'vendor_commissions' => array_values($vendorCommissions),
        ]);
    }
}

