<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;

class AdminVendorProductImportController extends BaseAdminController
{
    protected string $permissionPrefix = 'product';

    /**
     * Show the product import page
     */
    public function index()
    {
        // Check if user is vendor or admin
        $user = auth()->user();
        $isVendor = $user->hasRole('vendor');
        $isAdmin = $user->hasRole('admin');

        if (!$isVendor && !$isAdmin) {
            abort(403, 'Unauthorized access');
        }

        // Get store for vendor
        $store = null;
        if ($isVendor) {
            $store = $user->store;
            if (!$store) {
                return redirect()->back()->with('error', 'You must have an approved store to import products.');
            }
        }

        // Don't load categories on page load - they'll be loaded via AJAX
        // to prevent timeout with 5000+ categories
        $categories = []; // Will be loaded via AJAX

        // Get all active currencies for selection (cached)
        $currencies = getCachedActiveCurrencies();

        return view('admin.vendor.products.import', compact('store', 'isVendor', 'isAdmin', 'categories', 'currencies'));
    }

    /**
     * Get categories via AJAX - returns from CACHED data
     */
    public function getCategories()
    {
        try {
            // Get cached categories - NO DB QUERIES!
            $cachedCategories = Cache::rememberForever('categories', function () {
                return \App\Models\Category::where('status', 1)
                    ->select('id', 'name', 'parent_id')
                    ->orderBy('name')
                    ->get();
            });

            // Build category lookup map for fast parent lookups (no queries!)
            $categoryMap = [];
            foreach ($cachedCategories as $cat) {
                $categoryMap[$cat->id] = $cat;
            }

            // Build paths using cached data only
            $categories = [];
            foreach ($cachedCategories as $cat) {
                $path = $cat->name;
                $level = 0;

                // Get parent name from cache (NO DB QUERY!)
                if ($cat->parent_id && isset($categoryMap[$cat->parent_id])) {
                    $parent = $categoryMap[$cat->parent_id];
                    $path = $parent->name . ' > ' . $cat->name;
                    $level = 1;
                }

                $categories[] = [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'path' => $path,
                    'level' => $level
                ];
            }

            return response()->json([
                'success' => true,
                'categories' => $categories,
                'count' => count($categories)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load categories: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle CSV/Excel upload and import with streaming progress
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:51200', // 50MB max (increased)
            'currency_id' => 'required|exists:currencies,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file or currency selection.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $isVendor = $user->hasRole('vendor');

        // Get store for vendor
        $storeId = null;
        if ($isVendor) {
            $store = $user->store;
            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must have an approved store to import products.'
                ], 403);
            }
            $storeId = $store->id;
        } else {
            // Admin can specify store or default store
            $storeId = $request->input('store_id', 1);
        }

        // Get selected currency with exchange rate
        $currency = \App\Models\Currency::findOrFail($request->currency_id);
        $file = $request->file('file');

        // Use streaming response for real-time progress
        return response()->stream(function () use ($file, $storeId, $isVendor, $currency, $user) {
            // Setup streaming
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);
            ob_implicit_flush(true);
            @ignore_user_abort(true);

            // Output HTML header FIRST before anything else
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            background: #1e1e1e;
            color: #00ff00;
            font-family: "Courier New", monospace;
            font-size: 14px;
            line-height: 1.8;
            margin: 0;
            padding: 15px;
        }
        .success { color: #00ff00; }
        .error { color: #ff4444; }
        .info { color: #4da6ff; }
        .warning { color: #ffaa00; }
    </style>
</head>
<body>';
            flush();

            // Remove time and memory limits
            set_time_limit(0);
            ini_set('memory_limit', '1G');

            try {
                $this->streamOut("🚀 Starting CSV/Excel import...");
                $this->streamOut("📦 Store ID: {$storeId}");
                $this->streamOut("💱 Currency: {$currency->code} → USD (rate: {$currency->exchange_rate})");
                $this->streamOut("📄 File: {$file->getClientOriginalName()} (" . round($file->getSize() / 1024, 2) . " KB)");
                $this->streamOut("");

                $import = new ProductsImport($storeId, $isVendor, $currency);

                // Set progress callback for real-time updates
                $import->setProgressCallback(function($message) {
                    $this->streamOut($message);
                });

                $this->streamOut("⏳ Processing rows...");
                $this->streamOut("");

                Excel::import($import, $file);

                $this->streamOut("");
                $this->streamOut("✅ Import completed!");
                $this->streamOut("📊 Imported: {$import->getImportedCount()} products");
                $this->streamOut("❌ Failed: {$import->getFailedCount()} products");

                if ($import->getFailedCount() > 0) {
                    $this->streamOut("");
                    $this->streamOut("⚠️ Errors:");
                    foreach (array_slice($import->getErrors(), 0, 10) as $error) {
                        $this->streamOut("   • {$error}");
                    }
                    if (count($import->getErrors()) > 10) {
                        $this->streamOut("   ... and " . (count($import->getErrors()) - 10) . " more errors");
                    }
                }

                $this->streamOut("");
                $this->streamOut("🎉 Import finished successfully!");

            } catch (\Exception $e) {
                $this->streamOut("");
                $this->streamOut("❌ Import failed: " . $e->getMessage());
            }

            // Close HTML
            echo '</body></html>';
            flush();

        }, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache, no-transform',
        ]);
    }

    /**
     * Output text with streaming flush - outputs proper HTML with line breaks
     */
    private function streamOut(string $text): void
    {
        // Output as HTML with line break
        echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . "<br>\n";

        // Force output immediately
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    /**
     * Download sample CSV template
     */
    public function downloadTemplate()
    {
        $headers = [
            // Basic Information
            'name',
            'sku',
            'short_description',
            'description',
            'type',
            'unit',
            'weight',

            // Pricing
            'price',
            'sale_price',
            'discount',

            // Inventory
            'quantity',
            'stock_status',

            // Categories & Tags
            'categories',
            'tags',

            // Images (URLs from external server)
            'thumbnail_url',
            'gallery_urls',

            // Shipping
            'shipping_days',
            'is_free_shipping',
            'has_expedited_shipping',
            'standard_shipping_days',
            'expedited_shipping_days',
            'standard_shipping_price',
            'expedited_shipping_price',

            // Sale Settings
            'is_sale_enable',
            'sale_starts_at',
            'sale_expired_at',

            // Features
            'is_featured',
            'is_trending',
            'is_return',
            'is_cod',

            // External Product
            'is_external',
            'external_url',
            'external_button_text',

            // SEO
            'meta_title',
            'meta_description',

            // Policy Text
            'estimated_delivery_text',
            'return_policy_text',

            // Trust Badges
            'safe_checkout',
            'secure_checkout',
            'social_share',
            'encourage_order',
            'encourage_view',

            // Status
            'status',

            // Tax
            'tax_id',
        ];

        $sampleData = [
            [
                // Basic Information
                'Modern Leather Sofa',
                'SOFA-001',
                'Luxurious 3-seater leather sofa in dark brown',
                'This premium leather sofa features Italian leather upholstery, solid wood frame, and deep cushioning for maximum comfort. Perfect for modern living rooms.',
                'simple',
                'piece',
                '85',

                // Pricing
                '1299.99',
                '999.99',
                '23',

                // Inventory
                '15',
                'in_stock',

                // Categories - Hierarchical with >
                'Furniture > Living Room > Sofas',
                'luxury,modern,leather,comfortable',

                // Images - External URLs separated by commas
                'https://example.com/images/sofa-main.jpg',
                'https://example.com/images/sofa-1.jpg,https://example.com/images/sofa-2.jpg,https://example.com/images/sofa-3.jpg',

                // Shipping
                '7',
                '0',
                '1',
                '7-10',
                '3-5',
                '49.99',
                '89.99',

                // Sale Settings
                '1',
                '2025-01-01',    // Accepted formats: 2025-01-01, 01/01/2025, 1/1/2025, 01-01-2025
                '2025-01-31',    // Accepted formats: 2025-01-31, 31/01/2025, 31/1/2025, 31-01-2025

                // Features
                '1',
                '1',
                '1',
                '0',

                // External Product
                '0',
                '',
                '',

                // SEO
                'Modern Leather Sofa - Premium Italian Leather',
                'Buy premium modern leather sofa with Italian leather upholstery. Free shipping available.',

                // Policy Text
                'Delivery within 7-10 business days',
                '30-day return policy. Item must be in original condition.',

                // Trust Badges
                '1',
                '1',
                '1',
                '1',
                '1',

                // Status (1=active, 0=inactive)
                '1',

                // Tax
                '',
            ],
            [
                // Basic Information
                'Wireless Bluetooth Headphones',
                'HEAD-002',
                'Premium noise-cancelling headphones',
                'High-quality wireless headphones with active noise cancellation, 30-hour battery life, and premium sound quality.',
                'simple',
                'unit',
                '0.5',

                // Pricing
                '199.99',
                '149.99',
                '25',

                // Inventory
                '50',
                'in_stock',

                // Categories - Can create new categories
                'Electronics > Audio > Headphones,New Arrivals',
                'wireless,bluetooth,noise-cancelling,audio',

                // Images
                'https://example.com/images/headphones-main.jpg',
                'https://example.com/images/headphones-1.jpg,https://example.com/images/headphones-2.jpg',

                // Shipping
                '3',
                '1',
                '0',
                '',
                '',
                '',
                '',

                // Sale Settings
                '1',
                '2025-12-01',    // Or use: 01/12/2025, 1/12/2025, etc.
                '2025-12-25',    // Or use: 25/12/2025, 25/12/2025, etc.

                // Features
                '1',
                '0',
                '1',
                '1',

                // External Product
                '0',
                '',
                '',

                // SEO
                'Wireless Bluetooth Headphones with Noise Cancellation',
                'Premium wireless headphones with active noise cancellation. 30-hour battery, free shipping.',

                // Policy Text
                'Ships within 1-3 business days',
                '60-day return policy for electronics',

                // Trust Badges
                '1',
                '1',
                '1',
                '1',
                '1',

                // Status
                '1',

                // Tax
                '',
            ]
        ];

        $filename = 'comprehensive-product-import-template.csv';
        $handle = fopen('php://temp', 'w');

        // Write headers
        fputcsv($handle, $headers);

        // Write sample data
        foreach ($sampleData as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}

