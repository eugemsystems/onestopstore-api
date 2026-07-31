<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm(Request $request)
    {
        // Ensure a session cookie is issued on the login GET request
        // This prevents CSRF mismatch when the browser didn't accept a cookie previously
        $request->session()->put('admin_login_boot', Str::random(16));
        $request->session()->regenerateToken();

        return view('admin.auth.login');
    }

    /**
     * Handle login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = auth()->user();

            // Block consumer role from logging in
            if ($user->hasRole('consumer')) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Consumer accounts cannot access the admin panel.',
                ]);
            }

            // Redirect vendor to vendor dashboard
            if ($user->hasRole('vendor')) {
                return redirect()->intended(route('admin.vendor.dashboard'));
            }

            // Check if user has admin or Staff Raines role
            if ($user->roles()->whereIn('name', ['admin', 'Staff Raines'])->exists()) {
                // Audit log the login
                try {
                    ActivityLogger::make()->useLog('auth')->event('login')->by($user)
                        ->log("Admin login: {$user->name} ({$user->email})");
                } catch (\Throwable) {}
                return redirect()->intended(route('admin.dashboard'));
            }

            Auth::logout();
            return back()->withErrors([
                'email' => 'You do not have admin access.',
            ]);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        $user = auth()->user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Audit log logout (use the captured $user since Auth::user() is now null)
        try {
            if ($user) {
                ActivityLogger::make()->useLog('auth')->event('logout')->by($user)
                    ->log("Admin logout: {$user->name} ({$user->email})");
            }
        } catch (\Throwable) {}

        return redirect()->route('admin.login');
    }

    /**
     * Show dashboard
     */
    public function dashboard(Request $request)
    {
        // Redirect vendors to their own dashboard
        if (auth()->user()->hasRole('vendor')) {
            return redirect()->route('admin.vendor.dashboard');
        }

        $isAdmin = auth()->user()->hasRole('admin');

        // ── Date range filter ────────────────────────────────────────────────────
        $range    = $request->input('range', '7d');
        $dateFrom = null;
        $dateTo   = now();

        switch ($range) {
            case '24h':   $dateFrom = now()->subDay();         break;
            case '7d':    $dateFrom = now()->subDays(7);       break;
            case '30d':   $dateFrom = now()->subDays(30);      break;
            case '90d':   $dateFrom = now()->subDays(90);      break;
            case '365d':  $dateFrom = now()->subDays(365);     break;
            case 'custom':
                $dateFrom = $request->filled('date_from') ? \Carbon\Carbon::parse($request->input('date_from'))->startOfDay() : null;
                $dateTo   = $request->filled('date_to')   ? \Carbon\Carbon::parse($request->input('date_to'))->endOfDay()     : now();
                break;
            default: $dateFrom = null; // lifetime
        }

        // Helper closure: apply date window to a query builder
        $applyDate = function ($query) use ($dateFrom, $dateTo) {
            if ($dateFrom) $query->where('created_at', '>=', $dateFrom);
            $query->where('created_at', '<=', $dateTo);
            return $query;
        };

        // Helper closure: apply date window to orders table (with table prefix)
        $applyOrderDate = function ($query, $prefix = 'orders') use ($dateFrom, $dateTo) {
            if ($dateFrom) $query->where("{$prefix}.created_at", '>=', $dateFrom);
            $query->where("{$prefix}.created_at", '<=', $dateTo);
            return $query;
        };



        // ── Helper: base paid orders query scoped to the date window ────────────────
        // Used by KPI cards AND section queries so EVERYTHING respects the filter.
        // Exclude orders that were paid then later cancelled (payment_status stays COMPLETED).
        $cancelledStatusIds = \App\Models\OrderStatus::whereIn('slug', ['cancelled', 'canceled'])->pluck('id')->toArray();

        // Delivery-only statuses: changing TO these must NOT attribute revenue to anyone.
        $deliveryStatusIds = \App\Models\OrderStatus::where(function ($q) {
            $q->whereIn(\Illuminate\Support\Facades\DB::raw('lower(trim(slug))'), [
                'shipped', 'out-for-delivery', 'out for delivery',
                'ready-for-collection', 'ready for collection',
                'collected', 'delivered',
                'in-transit-to-zim', 'in transit to zim',
                'dropped-at-the-deport', 'dropped at the deport',
                'arrived-at-local-branch', 'arrived at local branch',
            ])->orWhereIn(\Illuminate\Support\Facades\DB::raw('lower(trim(name))'), [
                'shipped', 'out for delivery', 'ready for collection',
                'collected', 'delivered',
            ]);
        })->pluck('id')->toArray();

        // All status IDs that should NOT trigger revenue attribution (delivery + cancelled).
        $nonRevenueStatusIds = array_values(array_unique(array_merge($deliveryStatusIds, $cancelledStatusIds)));

        $basePaidOrders = function () use ($dateFrom, $dateTo, $cancelledStatusIds) {
            $q = \Illuminate\Support\Facades\DB::table('orders')
                ->whereNull('parent_id')->whereNull('deleted_at')
                ->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->when(!empty($cancelledStatusIds), fn($q) => $q->whereNotIn('order_status_id', $cancelledStatusIds))
                ->where('created_at', '<=', $dateTo);
            if ($dateFrom) $q->where('created_at', '>=', $dateFrom);
            return $q;
        };

        $baseAllOrders = function () use ($dateFrom, $dateTo) {
            $q = \App\Models\Order::whereNull('parent_id')
                ->where('created_at', '<=', $dateTo);
            if ($dateFrom) $q->where('created_at', '>=', $dateFrom);
            return $q;
        };

        // ── Revenue KPI (date-filtered, GROUP BY currency only to avoid duplicates) ──
        $revenueByCurrency = $basePaidOrders()
            ->selectRaw('currency, MIN(currency_symbol) as currency_symbol, SUM(total) as total_amount')
            ->groupBy('currency')->orderByDesc('total_amount')->get();

        // "This Month" KPI: when a range is active show period totals, otherwise true month
        if ($range === 'all') {
            $thisMonthByCurrency = \Illuminate\Support\Facades\DB::table('orders')
                ->whereNull('parent_id')->whereNull('deleted_at')->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
                ->selectRaw('currency, MIN(currency_symbol) as currency_symbol, SUM(total) as total_amount')
                ->groupBy('currency')->orderByDesc('total_amount')->get();
        } else {
            // Re-use the same date-filtered totals so the column is consistent with range
            $thisMonthByCurrency = $revenueByCurrency;
        }

        // Month-over-month % change (always vs calendar months, not date range)
        $lastMonthNormalised = \Illuminate\Support\Facades\DB::table('orders')
            ->whereNull('parent_id')->whereNull('deleted_at')->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
            ->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)
            ->sum(\Illuminate\Support\Facades\DB::raw('total * COALESCE(exchange_rate, 1)'));

        $thisMonthNormalised = \Illuminate\Support\Facades\DB::table('orders')
            ->whereNull('parent_id')->whereNull('deleted_at')->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
            ->sum(\Illuminate\Support\Facades\DB::raw('total * COALESCE(exchange_rate, 1)'));

        $revChange = $lastMonthNormalised > 0
            ? round((($thisMonthNormalised - $lastMonthNormalised) / $lastMonthNormalised) * 100, 1)
            : null;

        // ── KPI Stats (ALL date-filtered) ─────────────────────────────────────
        $stats = [
            'total_orders'        => $baseAllOrders()->count(),
            'pending_orders'      => $baseAllOrders()->whereHas('order_status', fn($q) => $q->where('name', 'pending'))->count(),
            'total_customers'     => \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'consumer'))->count(),
            'new_customers_month' => \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'consumer'))
                ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
                ->where('created_at', '<=', $dateTo)->count(),
            'total_products'      => \App\Models\Product::whereNull('deleted_at')->count(),
            'active_products'     => \App\Models\Product::whereNull('deleted_at')->where('status', 1)->count(),
            'total_refunds'       => \App\Models\Refund::whereNull('deleted_at')
                ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
                ->where('created_at', '<=', $dateTo)->count(),
            'processing_orders'   => $baseAllOrders()->whereHas('order_status', fn($q) => $q->where('name', 'processing'))->count(),
            'completed_orders'    => $baseAllOrders()->whereHas('order_status', fn($q) => $q->whereIn('name', ['completed', 'delivered']))->count(),
            'rev_change'          => $revChange,
        ];

        // ── Monthly revenue & order chart (last 12 months — always for trend context) ──
        $monthLabels   = [];
        $monthlyOrders = [];
        $monthlyRevenueByCurrency = [];

        $activeCurrencies = \App\Models\Order::whereNull('parent_id')->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->whereNotNull('currency')->distinct()->pluck('currency')->toArray();

        foreach ($activeCurrencies as $cur) { $monthlyRevenueByCurrency[$cur] = []; }

        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $monthLabels[] = $m->format('M Y');
            $monthlyOrders[] = \App\Models\Order::whereNull('parent_id')
                ->whereMonth('created_at', $m->month)->whereYear('created_at', $m->year)->count();
            $monthRevRows = \Illuminate\Support\Facades\DB::table('orders')
                ->whereNull('parent_id')->whereNull('deleted_at')->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                ->whereMonth('created_at', $m->month)->whereYear('created_at', $m->year)
                ->whereNotNull('currency')->selectRaw('currency, SUM(total) as total_amount')->groupBy('currency')
                ->pluck('total_amount', 'currency')->toArray();
            foreach ($activeCurrencies as $cur) {
                $monthlyRevenueByCurrency[$cur][] = round($monthRevRows[$cur] ?? 0, 2);
            }
        }
        $monthlyRevenue = array_fill(0, 12, 0);


        // ── Order status breakdown (date-filtered) ───────────────────────────────
        $statusQuery = \App\Models\Order::whereNull('orders.parent_id')
            ->join('order_status', 'orders.order_status_id', '=', 'order_status.id')
            ->selectRaw('order_status.name as status_name, count(*) as cnt')
            ->groupBy('order_status.name');
        $applyOrderDate($statusQuery);
        $orderStatuses = $statusQuery->pluck('cnt', 'status_name')->toArray();

        // ── Top Products — PAID (paid & non-cancelled orders only, date-filtered) ──
        $topPaidQuery = \Illuminate\Support\Facades\DB::table('order_products')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->join('orders',   'order_products.order_id',   '=', 'orders.id')
            ->whereNull('orders.deleted_at')
            ->whereNull('orders.parent_id')
            ->whereIn('orders.payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
            ->when(!empty($cancelledStatusIds), fn($q) => $q->whereNotIn('orders.order_status_id', $cancelledStatusIds))
            ->selectRaw('products.id, products.name, products.sku,
                         SUM(order_products.quantity) as total_qty,
                         SUM(order_products.subtotal)  as total_revenue')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_qty')
            ->limit(10);
        $applyOrderDate($topPaidQuery);
        $topProductsPaid = $topPaidQuery->get();

        // Back-compat alias so existing blade references still work if any
        $topProducts = $topProductsPaid;

        // ── Top Products — DEMAND (unpaid / pending, non-cancelled, date-filtered) ─
        $topDemandQuery = \Illuminate\Support\Facades\DB::table('order_products')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->join('orders',   'order_products.order_id',   '=', 'orders.id')
            ->whereNull('orders.deleted_at')
            ->whereNull('orders.parent_id')
            ->whereIn('orders.payment_status', ['PENDING', 'UNPAID', 'pending', 'unpaid'])
            ->when(!empty($cancelledStatusIds), fn($q) => $q->whereNotIn('orders.order_status_id', $cancelledStatusIds))
            ->selectRaw('products.id, products.name, products.sku,
                         SUM(order_products.quantity) as total_qty,
                         SUM(order_products.subtotal)  as total_revenue')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_qty')
            ->limit(10);
        $applyOrderDate($topDemandQuery);
        $topProductsDemand = $topDemandQuery->get();

        // ── Payment method breakdown (date-filtered) ──────────────────────────────
        $pmQuery = \App\Models\Order::whereNull('parent_id')->whereNotNull('payment_method')
            ->selectRaw('payment_method, count(*) as cnt')->groupBy('payment_method');
        $applyDate($pmQuery);
        $paymentMethods = $pmQuery->pluck('cnt', 'payment_method')->toArray();

        // ── Recent PAID orders (date-filtered) ────────────────────────────────────
        $recentQuery = \App\Models\Order::with(['consumer', 'order_status'])
            ->whereNull('parent_id')
            ->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
            ->latest()
            ->limit(15);
        $applyDate($recentQuery);
        $recentOrders = $recentQuery->get();

        // ── Staff performance (date-filtered; optional user filter) ────────────────
        $staffUsers = \App\Models\User::whereHas('roles', fn($q) => $q->whereNotIn('name', ['consumer', 'vendor']))
            ->whereNull('deleted_at')->select('id', 'name', 'email')->get();

        $selectedStaffUser = $request->input('staff_user'); // null = not chosen, 'all' = everyone, ID = specific

        if (is_null($selectedStaffUser)) {
            // Not yet chosen — show nothing
            $staffPerformance = collect();
        } else {
            // 'all' => show every staff user; numeric ID => show just that one
            $usersToShow = ($selectedStaffUser === 'all')
                ? $staffUsers
                : $staffUsers->where('id', $selectedStaffUser);

            $staffPerformance = $usersToShow->map(function ($user) use ($dateFrom, $dateTo, $cancelledStatusIds, $nonRevenueStatusIds) {

                // ── Revenue attribution ───────────────────────────────────────────────
                // An order's revenue is assigned to whoever made the FIRST status change
                // that is NOT a delivery/logistics status and NOT a cancellation.
                // Delivery changes (shipped, out for delivery, ready for collection,
                // collected, delivered) do NOT attribute revenue — only payment-confirming
                // changes (e.g. Processing) do.
                //
                // $nonRevenueStatusIds is computed once outside this loop.
                $userOrderFilter = function ($q) use ($user, $nonRevenueStatusIds) {
                    $q->whereExists(function ($sub) use ($user, $nonRevenueStatusIds) {
                        $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('order_status_histories as h')
                            ->whereColumn('h.order_id', 'orders.id')
                            ->where('h.updated_by_id', $user->id)
                            ->when(!empty($nonRevenueStatusIds),
                                fn($s) => $s->whereNotIn('h.new_status_id', $nonRevenueStatusIds))
                            // This user's entry must be the EARLIEST non-revenue change
                            // (no other human made a non-revenue change before this one).
                            ->whereRaw('NOT EXISTS (
                                SELECT 1 FROM order_status_histories h2
                                WHERE h2.order_id = h.order_id
                                  AND h2.id < h.id
                                  AND h2.updated_by_id IS NOT NULL
                                  AND h2.updated_by_id != 0
                                  ' . (!empty($nonRevenueStatusIds)
                                        ? 'AND h2.new_status_id NOT IN (' . implode(',', $nonRevenueStatusIds) . ')'
                                        : '') . '
                            )');
                    });
                };

                $paidOrdersByStatus = \Illuminate\Support\Facades\DB::table('orders')
                    ->join('order_status', 'orders.order_status_id', '=', 'order_status.id')
                    ->tap($userOrderFilter)
                    ->whereIn('orders.payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                    ->when(!empty($cancelledStatusIds), fn($q) => $q->whereNotIn('orders.order_status_id', $cancelledStatusIds))
                    ->whereNull('orders.parent_id')->whereNull('orders.deleted_at')
                    ->when($dateFrom, fn($q) => $q->where('orders.created_at', '>=', $dateFrom))
                    ->where('orders.created_at', '<=', $dateTo)
                    ->selectRaw('order_status.name as status_name, count(*) as cnt, SUM(orders.total) as revenue')
                    ->groupBy('order_status.name')->get()->keyBy('status_name');

                $totalPaidOrders = $paidOrdersByStatus->sum('cnt');

                $revenueByCurrencyForUser = \Illuminate\Support\Facades\DB::table('orders')
                    ->tap($userOrderFilter)
                    ->whereIn('payment_status', ['COMPLETED','COMPLETE','SUCCESS'])
                    ->when(!empty($cancelledStatusIds), fn($q) => $q->whereNotIn('order_status_id', $cancelledStatusIds))
                    ->whereNull('parent_id')->whereNull('deleted_at')
                    ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
                    ->where('created_at', '<=', $dateTo)
                    ->selectRaw('currency, MIN(currency_symbol) as currency_symbol, SUM(total) as total_amount')
                    ->groupBy('currency')->orderByDesc('total_amount')->get();

                $docsRaw = \Illuminate\Support\Facades\DB::table('invoices_quotations')
                    ->where('created_by', $user->id)->whereNull('deleted_at')
                    ->when($dateFrom, fn($q) => $q->where('created_at', '>=', $dateFrom))
                    ->where('created_at', '<=', $dateTo)
                    ->selectRaw('document_type, count(*) as cnt')->groupBy('document_type')
                    ->pluck('cnt', 'document_type');

                return [
                    'id'                  => $user->id,
                    'name'                => $user->name,
                    'email'               => $user->email,
                    'total_paid_orders'   => $totalPaidOrders,
                    'revenue_by_currency' => $revenueByCurrencyForUser,
                    'orders_by_status'    => $paidOrdersByStatus,
                    'quotations'          => $docsRaw->get('quotation', 0),
                    'invoices'            => $docsRaw->get('invoice', 0),
                    'proformas'           => $docsRaw->get('proforma', 0),
                ];
            })->sortByDesc('total_paid_orders')->values();
        }

        return view('admin.dashboard', compact(
            'stats', 'recentOrders', 'isAdmin',
            'monthlyRevenue', 'monthlyOrders', 'monthLabels', 'monthlyRevenueByCurrency',
            'orderStatuses', 'topProducts', 'topProductsPaid', 'topProductsDemand', 'paymentMethods',
            'staffPerformance', 'revenueByCurrency', 'thisMonthByCurrency',
            'staffUsers', 'selectedStaffUser', 'range', 'dateFrom', 'dateTo'
        ));
    }


    /**
     * Show profile
     */
    public function profile()
    {
        return view('admin.profile');
    }
}
