<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminVendorApplicationController extends Controller
{
    /**
     * Ensure only admins can access vendor applications
     */
    public function __construct()
    {
        // Check if user has admin role
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || !auth()->user()->hasRole('admin')) {
                abort(403, 'Unauthorized. Admin access required.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of vendor applications (Web view).
     */
    public function index(Request $request)
    {
        // OPTIMIZED: Use LEFT JOIN with aggregation instead of withCount subqueries
        // This is MUCH faster - single query instead of 45+ subqueries
        $query = Store::select([
                'stores.id',
                'stores.store_name',
                'stores.legal_name',
                'stores.trading_name',
                'stores.city',
                'stores.vendor_id',
                'stores.country_id',
                'stores.state_id',
                'stores.is_approved',
                'stores.status',
                'stores.is_banned',
                'stores.created_at',
                \DB::raw('COALESCE(COUNT(DISTINCT products.id), 0) as total_products_count'),
                \DB::raw('COALESCE(SUM(CASE WHEN products.is_approved = 1 AND products.status = 1 THEN 1 ELSE 0 END), 0) as active_products_count'),
                \DB::raw('COALESCE(SUM(CASE WHEN products.is_approved = 0 THEN 1 ELSE 0 END), 0) as pending_products_count')
            ])
            ->leftJoin('products', function($join) {
                $join->on('stores.id', '=', 'products.store_id')
                     ->whereNull('products.deleted_at');
            })
            ->whereNull('stores.deleted_at')
            ->groupBy(
                'stores.id',
                'stores.store_name',
                'stores.legal_name',
                'stores.trading_name',
                'stores.city',
                'stores.vendor_id',
                'stores.country_id',
                'stores.state_id',
                'stores.is_approved',
                'stores.status',
                'stores.is_banned',
                'stores.created_at'
            )
            ->with([
                'vendor:id,name,email,phone'
                // REMOVED: country and state eager loading - we'll use cache instead
            ])
            ->orderBy('stores.created_at', 'desc');

        // Filter by approval status
        if ($request->has('is_approved') && $request->is_approved !== '') {
            $query->where('stores.is_approved', $request->is_approved);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('stores.status', $request->status);
        }

        // Filter by banned status
        if ($request->has('is_banned') && $request->is_banned !== '') {
            $query->where('stores.is_banned', $request->is_banned);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('stores.store_name', 'like', "%{$search}%")
                  ->orWhere('stores.legal_name', 'like', "%{$search}%")
                  ->orWhere('stores.trading_name', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function($vendorQuery) use ($search) {
                      $vendorQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $applications = $query->paginate(15);

        // OPTIMIZED: Load countries and states from cache (0 DB queries)
        // Countries and states don't change often, so cache them instead of querying DB
        $cachedCountries = getCachedCountries()->keyBy('id');
        $cachedStates = getCachedStates()->keyBy('id');

        // Manually attach cached country and state to each application
        foreach ($applications as $application) {
            if ($application->country_id && isset($cachedCountries[$application->country_id])) {
                $application->setRelation('country', $cachedCountries[$application->country_id]);
            }
            if ($application->state_id && isset($cachedStates[$application->state_id])) {
                $application->setRelation('state', $cachedStates[$application->state_id]);
            }
        }

        return view('admin.vendor-applications.index', compact('applications'));
    }

    /**
     * Display the specified vendor application (Web view).
     */
    public function show(Request $request, $id)
    {

        // OPTIMIZED: Get product counts efficiently using single query with conditional aggregation
        $productCounts = DB::table('products')
            ->select([
                DB::raw('COUNT(DISTINCT id) as total_products_count'),
                DB::raw('SUM(CASE WHEN is_approved = 1 AND status = 1 THEN 1 ELSE 0 END) as active_products_count'),
                DB::raw('SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending_products_count')
            ])
            ->where('store_id', $id)
            ->whereNull('deleted_at')
            ->first();

        $application = Store::with([
            'vendor:id,name,email,phone,country_code,created_at',
            // REMOVED: country and state eager loading - we'll use cache instead
            'product_catalog',
            'bannedBy:id,name'
        ])
        ->findOrFail($id);

        // Attach counts to the model
        $application->total_products_count = $productCounts->total_products_count ?? 0;
        $application->active_products_count = $productCounts->active_products_count ?? 0;
        $application->pending_products_count = $productCounts->pending_products_count ?? 0;

        // OPTIMIZED: Load country and state from cache (0 DB queries)
        $cachedCountries = getCachedCountries()->keyBy('id');
        $cachedStates = getCachedStates()->keyBy('id');

        if ($application->country_id && isset($cachedCountries[$application->country_id])) {
            $application->setRelation('country', $cachedCountries[$application->country_id]);
        }
        if ($application->state_id && isset($cachedStates[$application->state_id])) {
            $application->setRelation('state', $cachedStates[$application->state_id]);
        }

        // Load products for the products table with filters
        $productsQuery = $application->products()
            ->with(['product_thumbnail'])
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($request->has('product_status') && $request->product_status !== '') {
            $productsQuery->where('status', $request->product_status);
        }

        // Filter by approval if provided
        if ($request->has('product_approval') && $request->product_approval !== '') {
            $productsQuery->where('is_approved', $request->product_approval);
        }

        $products = $productsQuery->paginate(20)->appends($request->except('page'));

        // Get commission data for this vendor
        $commissionData = $this->getVendorCommissionData($application);

        return view('admin.vendor-applications.show', compact('application', 'products', 'commissionData'));
    }

    /**
     * Get vendor commission statistics and history
     */
    private function getVendorCommissionData($store)
    {
        $vendorId = $store->vendor_id;

        // Get wallet balance
        $wallet = \App\Models\VendorWallet::where('vendor_id', $vendorId)->first();
        $walletBalance = $wallet ? $wallet->balance : 0;

        // Get total commission earned
        $totalCommission = \App\Models\CommissionHistory::where('store_id', $store->id)
            ->sum('vendor_commission');

        // Get this month's commission
        $thisMonthCommission = \App\Models\CommissionHistory::where('store_id', $store->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('vendor_commission');

        // Get recent commission history (last 10) with items breakdown
        $recentCommissions = \App\Models\CommissionHistory::where('store_id', $store->id)
            ->with([
                'order' => function($query) {
                    $query->select('id', 'order_number', 'created_at', 'payment_status');
                },
                'items' => function($query) {
                    $query->with(['product', 'category']);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'wallet_balance' => $walletBalance,
            'total_commission' => $totalCommission,
            'this_month_commission' => $thisMonthCommission,
            'recent_commissions' => $recentCommissions,
            'commission_count' => $recentCommissions->count(),
        ];
    }

    /**
     * Update the approval status (Web action).
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'is_approved' => 'required|in:0,1',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $store = Store::with('vendor')->findOrFail($id);
        $store->update([
            'is_approved' => $request->is_approved,
            'status' => $request->is_approved == 1 ? 1 : 0, // Active if approved
        ]);

        // Send email notification to vendor
        try {
            if ($request->is_approved == 1) {
                // Application approved
                \Mail::to($store->vendor->email)->send(new \App\Mail\VendorApplicationApproved($store));
            } else {
                // Application rejected
                \Mail::to($store->vendor->email)->send(new \App\Mail\VendorApplicationRejected($store, $request->rejection_reason));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send vendor application email: ' . $e->getMessage());
            // Don't fail the approval/rejection if email fails
        }

        $message = $request->is_approved ? 'Application approved successfully and vendor notified via email!' : 'Application rejected and vendor notified via email.';

        return redirect()->route('admin.vendor-applications.index')
            ->with('success', $message);
    }

    /**
     * Reject vendor application
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $store = Store::with('vendor')->findOrFail($id);
        $store->update([
            'is_approved' => 0,
            'status' => 0,
            'ban_reason' => $request->rejection_reason, // Store rejection reason in ban_reason column
        ]);

        // Send email notification to vendor
        try {
            \Mail::to($store->vendor->email)->send(new \App\Mail\VendorApplicationRejected($store, $request->rejection_reason));
        } catch (\Exception $e) {
            \Log::error('Failed to send vendor application rejection email: ' . $e->getMessage());
        }

        return redirect()->route('admin.vendor-applications.show', $id)
            ->with('success', 'Application rejected and vendor notified via email.');
    }

    /**
     * Export vendor applications to CSV
     */
    public function export(Request $request)
    {
        $applications = Store::with(['vendor'])  // Only load vendor from DB
            ->when($request->is_approved !== null, fn($q) => $q->where('is_approved', $request->is_approved))
            ->get();

        // OPTIMIZED: Load countries and states from cache (0 DB queries)
        $cachedCountries = getCachedCountries()->keyBy('id');
        $cachedStates = getCachedStates()->keyBy('id');

        // Manually attach cached country and state to each application
        foreach ($applications as $application) {
            if ($application->country_id && isset($cachedCountries[$application->country_id])) {
                $application->setRelation('country', $cachedCountries[$application->country_id]);
            }
            if ($application->state_id && isset($cachedStates[$application->state_id])) {
                $application->setRelation('state', $cachedStates[$application->state_id]);
            }
        }

        $filename = 'vendor_applications_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($applications) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'ID',
                'Store Name',
                'Legal Name',
                'Trading Name',
                'Vendor Name',
                'Email',
                'Phone',
                'VAT Registered',
                'VAT Number',
                'Identification Type',
                'ID Number',
                'Monthly Revenue',
                'Physical Stores',
                'Number of Stores',
                'Supplier to Retailers',
                'Marketplace Accounts',
                'Number of Products',
                'Primary Category',
                'Stock Holding',
                'Product Source',
                'Product Branding',
                'Country',
                'State',
                'City',
                'Referral Source',
                'Status',
                'Approved',
                'Created At'
            ]);

            // Data rows
            foreach ($applications as $app) {
                fputcsv($file, [
                    $app->id,
                    $app->store_name,
                    $app->legal_name,
                    $app->trading_name,
                    $app->vendor->name ?? '',
                    $app->vendor->email ?? '',
                    $app->vendor->phone ?? '',
                    $app->is_vat_registered,
                    $app->vat_number,
                    $app->identification_type,
                    $app->id_number,
                    $app->monthly_revenue,
                    $app->has_physical_stores,
                    $app->number_of_stores,
                    $app->is_supplier_to_retailers,
                    $app->has_marketplace_accounts,
                    $app->number_of_products,
                    $app->primary_category,
                    $app->stock_holding,
                    $app->product_source,
                    $app->product_branding,
                    $app->country->name ?? '',
                    $app->state->name ?? '',
                    $app->city,
                    $app->referral_source,
                    $app->status ? 'Active' : 'Inactive',
                    $app->is_approved ? 'Yes' : 'No',
                    $app->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    /**
     * Ban a vendor
     */
    public function ban(Request $request, $id)
    {
        $request->validate([
            'ban_reason' => 'required|string|max:1000',
        ]);

        $store = Store::with('vendor')->findOrFail($id);

        $store->update([
            'is_banned' => true,
            'ban_reason' => $request->ban_reason,
            'banned_at' => now(),
            'banned_by' => auth()->id(),
            'status' => 0, // Deactivate store
        ]);

        // Disable all products from this vendor
        $store->products()->update([
            'status' => 0,
            'is_approved' => 0,
        ]);

        // Get all product IDs for this vendor
        $productIds = $store->products()->pluck('id')->toArray();

        // IMMEDIATELY clear cache for each product
        foreach ($productIds as $productId) {
            $this->clearSingleProductCache($productId);
        }

        // COMPLETELY CLEAR product caches (not bump - we want them gone!)
        $this->clearProductCachesCompletely($productIds);

        // Remove ALL vendor products from Elasticsearch immediately
        try {
            $products = $store->products()->get();
            $removed = 0;

            foreach ($products as $product) {
                try {
                    $product->unsearchable();
                    $removed++;
                } catch (\Exception $e) {
                    \Log::error("Failed to remove product {$product->id} from Elasticsearch: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            \Log::error("Failed to remove vendor products from Elasticsearch: " . $e->getMessage());
        }

        // Send email notification (optional)
        try {
            \Mail::to($store->vendor->email)->send(new \App\Mail\VendorBanned($store));
        } catch (\Exception $e) {
            \Log::error('Failed to send vendor ban email: ' . $e->getMessage());
        }

        return redirect()->route('admin.vendor-applications.show', $id)
            ->with('success', 'Vendor has been banned. All products have been disabled and removed from search.');
    }

    /**
     * Unban a vendor
     */
    public function unban($id)
    {
        $store = Store::findOrFail($id);

        $store->update([
            'is_banned' => false,
            'ban_reason' => null,
            'banned_at' => null,
            'banned_by' => null,
            'status' => 1, // Reactivate store
        ]);

        // Products remain disabled (status=0, is_approved=0)
        // Admin must manually approve products they want to reactivate


        return redirect()->route('admin.vendor-applications.show', $id)
            ->with('success', 'Vendor has been unbanned. Store is active but products remain inactive and need individual approval.');
    }

    /**
     * Approve a single product
     */
    public function approveProduct(Request $request, $storeId, $productId)
    {
        $store = Store::findOrFail($storeId);
        $product = $store->products()->findOrFail($productId);

        $approved = $request->input('approve', 1);

        $product->update([
            'is_approved' => $approved,
            'status' => $approved ? 1 : 0,
        ]);

        // IMMEDIATELY clear product cache (new pattern)
        $this->clearSingleProductCache($product->id);

        // Handle Elasticsearch indexing with error handling
        try {
            if ($approved && $product->shouldBeSearchable()) {
                // Load relationships before indexing
                $product->load(['categories', 'tags', 'product_thumbnail', 'product_meta_image', 'product_galleries']);
                $product->searchable();
            } elseif (!$approved) {
                $product->unsearchable();
            }
        } catch (\Exception $e) {
            \Log::error("Failed to update Elasticsearch for product {$product->id}: " . $e->getMessage());
            // Continue - database update was successful
        }

        $message = $approved ? 'Product approved and activated!' : 'Product disapproved and deactivated.';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'product' => $product
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Bulk approve products
     */
    public function bulkApproveProducts(Request $request, $storeId)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|integer',
            'approve' => 'required|boolean',
        ]);

        $store = Store::findOrFail($storeId);
        $approved = $request->approve;

        // Update products in database
        $count = $store->products()
            ->whereIn('id', $request->product_ids)
            ->update([
                'is_approved' => $approved,
                'status' => $approved ? 1 : 0,
            ]);

        // IMMEDIATELY clear cache for each product (new pattern)
        foreach ($request->product_ids as $productId) {
            $this->clearSingleProductCache($productId);
        }

        // Post-processing: Bump cache version, then reindex to Elasticsearch
        $this->bumpProductsCacheVersion();

        // Re-index products to Elasticsearch after approval
        try {
            if ($approved) {
                // Load products with relationships for proper indexing
                $products = $store->products()
                    ->with(['categories', 'tags', 'product_thumbnail', 'product_meta_image', 'product_galleries'])
                    ->whereIn('id', $request->product_ids)
                    ->get();

                $indexed = 0;
                $failed = 0;

                foreach ($products as $product) {
                    if ($product->shouldBeSearchable()) {
                        try {
                            $product->searchable();
                            $indexed++;
                        } catch (\Exception $e) {
                            $failed++;
                            \Log::error("Failed to index product {$product->id}: " . $e->getMessage());
                        }
                    }
                }

            } else {
                // Remove from Elasticsearch when disapproved
                $products = $store->products()->whereIn('id', $request->product_ids)->get();

                $removed = 0;
                foreach ($products as $product) {
                    try {
                        $product->unsearchable();
                        $removed++;
                    } catch (\Exception $e) {
                        \Log::error("Failed to remove product {$product->id} from Elasticsearch: " . $e->getMessage());
                    }
                }

            }
        } catch (\Exception $e) {
            \Log::error("Elasticsearch operation failed during bulk approval: " . $e->getMessage());
            // Continue execution - the database update was successful
        }

        $message = $approved
            ? "{$count} product(s) approved and activated!"
            : "{$count} product(s) disapproved and deactivated.";

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $count
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Approve ALL products for a vendor (regardless of filters or pagination)
     */
    public function approveAllProducts(Request $request, $storeId)
    {
        $store = Store::findOrFail($storeId);

        // Get count of products that will be approved (only non-approved ones)
        $pendingCount = $store->products()
            ->where('is_approved', 0)
            ->count();

        if ($pendingCount === 0) {
            return redirect()->back()->with('info', 'All products are already approved!');
        }

        // Update ALL products for this vendor
        $count = $store->products()->update([
            'is_approved' => true,
            'status' => 1, // Also activate them
        ]);

        // Get all product IDs for cache clearing
        $productIds = $store->products()->pluck('id')->toArray();

        // IMMEDIATELY clear cache for each product (new pattern)
        foreach ($productIds as $productId) {
            $this->clearSingleProductCache($productId);
        }

        // Post-processing: Bump cache version
        $this->bumpProductsCacheVersion();

        // Re-index all approved products to Elasticsearch
        try {
            // Load products with relationships for proper indexing
            $products = $store->products()
                ->with(['categories', 'tags', 'product_thumbnail', 'product_meta_image', 'product_galleries'])
                ->get();

            $indexed = 0;
            $failed = 0;
            $skipped = 0;

            foreach ($products as $product) {
                if ($product->shouldBeSearchable()) {
                    try {
                        $product->searchable();
                        $indexed++;
                    } catch (\Exception $e) {
                        $failed++;
                        \Log::error("Failed to index product {$product->id}: " . $e->getMessage());
                    }
                } else {
                    $skipped++;
                }
            }

        } catch (\Exception $e) {
            \Log::error("Elasticsearch operation failed during approve-all: " . $e->getMessage());
            // Continue execution - the database update was successful
        }

        $message = "Successfully approved {$pendingCount} product(s) and activated them! All {$count} products are now approved.";

        return redirect()->back()->with('success', $message);
    }

    /**
     * Clear cache for a single product immediately (called during approval/disapproval)
     */
    private function clearSingleProductCache(int $productId): void
    {
        try {
            $keysToForget = [
                "product:{$productId}",
                "product_details:{$productId}",
                "product_with_relations:{$productId}",
                "api_product_{$productId}",
            ];

            foreach ($keysToForget as $key) {
                \Cache::forget($key);
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to clear cache for product {$productId} during vendor action", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Bump products cache version to invalidate ALL product caches
     */
    private function bumpProductsCacheVersion(): void
    {
        try {
            $currentVersion = (int) \Cache::get('products_cache_version', 1);
            $newVersion = $currentVersion + 1;
            \Cache::put('products_cache_version', $newVersion, now()->addDays(365));

        } catch (\Exception $e) {
            \Log::error('Error bumping products cache version in vendor controller', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Completely clear product caches (used when banning vendor - we want them GONE!)
     */
    private function clearProductCachesCompletely(array $productIds): void
    {
        try {
            // Clear individual product caches
            foreach ($productIds as $productId) {
                $keysToForget = [
                    "product:{$productId}",
                    "product_details:{$productId}",
                    "product_with_relations:{$productId}",
                    "api_product_{$productId}",
                ];

                foreach ($keysToForget as $key) {
                    \Cache::forget($key);
                }
            }

            // Clear general product list caches
            \Cache::forget('products:all');
            \Cache::forget('products:featured');
            \Cache::forget('products:trending');

        } catch (\Exception $e) {
            \Log::error('Error clearing product caches completely', [
                'error' => $e->getMessage(),
                'product_count' => count($productIds)
            ]);
        }
    }
}

