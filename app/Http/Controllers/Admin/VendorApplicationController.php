<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class VendorApplicationController extends Controller
{
    /**
     * Ensure only admins can access vendor applications via API
     */
    public function __construct()
    {
        // Check if user has admin role
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || !auth()->user()->hasRole('admin')) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Admin access required.'
                ], 403);
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of vendor applications.
     */
    public function index(Request $request)
    {
        $query = Store::with(['vendor:id,name,email,phone', 'country:id,name', 'state:id,name'])
            ->orderBy('created_at', 'desc');

        // Filter by approval status
        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->is_approved);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('store_name', 'like', "%{$search}%")
                  ->orWhere('legal_name', 'like', "%{$search}%")
                  ->orWhere('trading_name', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function($vendorQuery) use ($search) {
                      $vendorQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $applications = $query->paginate($request->per_page ?? 15);

        return response()->json($applications);
    }

    /**
     * Display the specified vendor application.
     */
    public function show($id)
    {
        $application = Store::with([
            'vendor:id,name,email,phone,country_code,created_at',
            'country:id,name',
            'state:id,name',
            'product_catalog'
        ])->findOrFail($id);

        return response()->json($application);
    }

    /**
     * Update the approval status.
     */
    public function updateApprovalStatus(Request $request, $id)
    {
        $request->validate([
            'is_approved' => 'required|in:0,1',
            'admin_notes' => 'nullable|string',
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

        return response()->json([
            'success' => true,
            'message' => $request->is_approved ? 'Application approved successfully and vendor notified via email' : 'Application rejected and vendor notified via email',
            'store' => $store
        ]);
    }

    /**
     * Export vendor applications to CSV
     */
    public function export(Request $request)
    {
        $applications = Store::with(['vendor', 'country', 'state'])
            ->when($request->is_approved, fn($q) => $q->where('is_approved', $request->is_approved))
            ->get();

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

        return response()->stream($callback, 200, $headers);
    }
}


