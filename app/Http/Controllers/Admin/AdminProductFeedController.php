<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminProductFeedController extends BaseAdminController
{
    protected string $permissionPrefix = 'product-feed';

    /**
     * Show the product feed export form
     */
    public function index()
    {
        $this->checkPermission('index');

        // Don't load categories - they will be fetched via API in JavaScript
        // Using /api/category endpoint (cached) - NO DATABASE QUERIES

        // Cache active currencies for 6 hours
        $currencies = Cache::remember('active_currencies_list_v2', now()->addHours(6), function () {
            return Currency::where('status', 1)
                ->orderBy('code')
                ->get();
        });

        // Get all approved stores for filtering
        $stores = Cache::remember('approved_stores_list', now()->addHours(6), function () {
            return \App\Models\Store::where('is_approved', 1)
                ->where('status', 1)
                ->where('is_banned', 0)
                ->select('id', 'store_name')
                ->orderBy('store_name')
                ->get()
                ->toArray(); // Convert to array to prevent Eloquent overhead
        });

        // Get distinct estimated_delivery_text values for filtering
        $deliveryTexts = Cache::remember('product_delivery_texts', now()->addHours(6), function () {
            return Product::where('status', 1)
                ->where('is_approved', 1)
                ->whereNotNull('estimated_delivery_text')
                ->where('estimated_delivery_text', '!=', '')
                ->select('estimated_delivery_text')
                ->distinct()
                ->orderBy('estimated_delivery_text')
                ->pluck('estimated_delivery_text')
                ->toArray();
        });

        return view('admin.product-feed.index', compact('currencies', 'stores', 'deliveryTexts'));
    }

    /**
     * Get categories filtered by store (optional) with product counts
     * OPTIMIZED: Uses only cache and single database queries - NO N+1 issues
     */
    public function getCategoriesByStore(Request $request)
    {
        // Increase time limit for this operation
        set_time_limit(300); // 5 minutes

        $storeId = $request->query('store_id');
        $excludeDelivery = $request->query('exclude_delivery');

        // Parse excluded delivery texts from JSON
        $excludedTexts = [];
        if ($excludeDelivery) {
            $excludedTexts = json_decode($excludeDelivery, true) ?? [];
        }

        // Get all categories directly from cache (NO DATABASE QUERIES!)
        $allCategories = Cache::get('categories_hierarchical_tree', []);

        if (empty($allCategories)) {
            return response()->json([
                'success' => false,
                'message' => 'Category cache is empty. Please run: php artisan categories:cache-tree'
            ], 500);
        }

        // Create a cache key for this specific query
        $cacheKey = 'product_feed_categories_' . md5(
            'store_' . ($storeId ?? 'all') .
            '_exclude_' . json_encode($excludedTexts)
        );

        // Cache the filtered categories for 5 minutes to avoid repeated queries
        $filteredCategories = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($storeId, $excludedTexts, $allCategories) {
            // Get category product counts in ONE optimized query
            $categoryProductCounts = DB::table('products')
                ->join('product_categories', 'products.id', '=', 'product_categories.product_id')
                ->where('products.status', 1)
                ->where('products.is_approved', 1)
                ->when($storeId, fn($q) => $q->where('products.store_id', $storeId))
                ->when(!empty($excludedTexts), fn($q) => $q->whereNotIn('products.estimated_delivery_text', $excludedTexts))
                ->select('product_categories.category_id', DB::raw('COUNT(DISTINCT products.id) as product_count'))
                ->groupBy('product_categories.category_id')
                ->pluck('product_count', 'category_id')
                ->toArray();

            $categoryIdsWithProducts = array_keys($categoryProductCounts);

            // Get ONLY necessary category details in ONE query
            $categoryDetails = Category::whereIn('id', $categoryIdsWithProducts)
                ->select('id', 'slug', 'name', 'parent_id')
                ->get()
                ->keyBy('id')
                ->toArray(); // Convert to array to prevent Eloquent overhead

            // Filter and map categories using ONLY cached data and the single query result
            return collect($allCategories)
                ->filter(function($cat) use ($categoryIdsWithProducts) {
                    return in_array($cat['id'], $categoryIdsWithProducts);
                })
                ->map(function($cat) use ($categoryProductCounts, $categoryDetails) {
                    $details = $categoryDetails[$cat['id']] ?? null;
                    return [
                        'id' => $cat['id'],
                        'name' => $cat['name'], // Just the name from cache
                        'slug' => $details['slug'] ?? '',
                        'parent_id' => $details['parent_id'] ?? null,
                        'product_count' => $categoryProductCounts[$cat['id']] ?? 0,
                        'level' => $cat['level']
                    ];
                })
                ->values()
                ->toArray();
        });

        return response()->json([
            'success' => true,
            'categories' => $filteredCategories,
            'store_id' => $storeId,
            'exclude_delivery' => $excludedTexts
        ]);
    }

    /**
     * Get product count stats for selected category
     */
    public function stats(Request $request)
    {
        // Increase time limit for this operation
        set_time_limit(300); // 5 minutes

        $categorySlug    = $request->query('category');
        $storeId         = $request->query('store_id');
        $region          = $request->query('region');
        $excludeDelivery = $request->query('exclude_delivery');
        $promotionFilter = $request->query('promotion_filter');
        $priceMin        = ($request->query('price_min') !== null && $request->query('price_min') !== '') ? (float) $request->query('price_min') : null;
        $priceMax        = ($request->query('price_max') !== null && $request->query('price_max') !== '') ? (float) $request->query('price_max') : null;

        // Parse excluded delivery texts from JSON
        $excludedTexts = [];
        if ($excludeDelivery) {
            $excludedTexts = json_decode($excludeDelivery, true) ?? [];
        }

        if (!$categorySlug && !$region) {
            return response()->json([
                'success' => false,
                'message' => 'Category or region is required'
            ], 400);
        }

        $categoryLabel = 'All Products';

        if ($categorySlug) {
            $category = Cache::remember("category_by_slug_{$categorySlug}", now()->addHours(6), function () use ($categorySlug) {
                return Category::whereSlug($categorySlug)->first();
            });

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            $categoryIds = $this->getCategoryIdsWithChildren($category);
            $categoryLabel = $category->name;

            $query = DB::table('products')
                ->join('product_categories', 'products.id', '=', 'product_categories.product_id')
                ->whereIn('product_categories.category_id', $categoryIds)
                ->where('products.status', 1)
                ->where('products.is_approved', 1)
                ->when($storeId, fn($q) => $q->where('products.store_id', $storeId))
                ->when($region, fn($q) => $this->applyRegionFilter($q, $region))
                ->when(!empty($excludedTexts), fn($q) => $q->whereNotIn('products.estimated_delivery_text', $excludedTexts))
                ->when($promotionFilter, fn($q) => $this->applyPromotionFilter($q, $promotionFilter));
        } else {
            // Region-only: no category join needed
            $query = DB::table('products')
                ->where('products.status', 1)
                ->where('products.is_approved', 1)
                ->when($storeId, fn($q) => $q->where('products.store_id', $storeId))
                ->when($region, fn($q) => $this->applyRegionFilter($q, $region))
                ->when(!empty($excludedTexts), fn($q) => $q->whereNotIn('products.estimated_delivery_text', $excludedTexts))
                ->when($promotionFilter, fn($q) => $this->applyPromotionFilter($q, $promotionFilter));
        }

        // Price range filter
        if ($priceMin !== null) {
            $query->where(function ($q) use ($priceMin) {
                $q->where(function ($sq) use ($priceMin) {
                    $sq->where('products.sale_price', '>', 0)->where('products.sale_price', '>=', $priceMin);
                })->orWhere(function ($sq) use ($priceMin) {
                    $sq->where(function ($i) { $i->whereNull('products.sale_price')->orWhere('products.sale_price', 0); });
                    $sq->where('products.price', '>=', $priceMin);
                });
            });
        }
        if ($priceMax !== null) {
            $query->where(function ($q) use ($priceMax) {
                $q->where(function ($sq) use ($priceMax) {
                    $sq->where('products.sale_price', '>', 0)->where('products.sale_price', '<=', $priceMax);
                })->orWhere(function ($sq) use ($priceMax) {
                    $sq->where(function ($i) { $i->whereNull('products.sale_price')->orWhere('products.sale_price', 0); });
                    $sq->where('products.price', '<=', $priceMax);
                });
            });
        }

        $productCount = $categorySlug
            ? $query->distinct()->count('products.id')
            : $query->count('products.id');

        // Calculate estimated file size and number of parts
        $estimatedSizeMB = ($productCount * 2048) / (1024 * 1024);
        $estimatedParts = max(1, ceil($estimatedSizeMB / 200));

        return response()->json([
            'success' => true,
            'category' => $categoryLabel,
            'product_count' => $productCount,
            'estimated_size_mb' => round($estimatedSizeMB, 2),
            'estimated_parts' => $estimatedParts,
            'will_split' => $estimatedParts > 1
        ]);
    }

    /**
     * Apply promotion filter to a query builder
     * Works for both DB::table() and Eloquent builders.
     */
    private function applyPromotionFilter($query, string $filter)
    {
        return match ($filter) {
            'sale_price'   => $query->where('products.sale_price', '>', 0),
            'sale_enabled' => $query->where('products.is_sale_enable', 1),
            'both'         => $query->where('products.sale_price', '>', 0)
                                    ->where('products.is_sale_enable', 1),
            'either'       => $query->where(function ($q) {
                                    $q->where('products.sale_price', '>', 0)
                                      ->orWhere('products.is_sale_enable', 1);
                                }),
            default        => $query,
        };
    }

    /**
     * Apply promotion filter to an Eloquent query (no table prefix needed)
     */
    private function applyPromotionFilterEloquent($query, string $filter)
    {
        return match ($filter) {
            'sale_price'   => $query->where('sale_price', '>', 0),
            'sale_enabled' => $query->where('is_sale_enable', 1),
            'both'         => $query->where('sale_price', '>', 0)
                                    ->where('is_sale_enable', 1),
            'either'       => $query->where(function ($q) {
                                    $q->where('sale_price', '>', 0)
                                      ->orWhere('is_sale_enable', 1);
                                }),
            default        => $query,
        };
    }

    /**
     * Apply region filter to a DB::table() query (prefixed columns)
     */
    private function applyRegionFilter($query, string $region)
    {
        return match ($region) {
            'zambia_only'   => $query->where('products.zambia_only', true),
            'zimbabwe_only' => $query->where('products.zimbabwe_only', true),
            'sa_only'       => $query->where('products.sa_only', true),
            'global'        => $query->where('products.zambia_only', false)
                                     ->where('products.zimbabwe_only', false)
                                     ->where('products.sa_only', false),
            default         => $query,
        };
    }

    /**
     * Apply region filter to an Eloquent query (no table prefix needed)
     */
    private function applyRegionFilterEloquent($query, string $region)
    {
        return match ($region) {
            'zambia_only'   => $query->where('zambia_only', true),
            'zimbabwe_only' => $query->where('zimbabwe_only', true),
            'sa_only'       => $query->where('sa_only', true),
            'global'        => $query->where('zambia_only', false)
                                     ->where('zimbabwe_only', false)
                                     ->where('sa_only', false),
            default         => $query,
        };
    }

    /**
     * Get category IDs including all subcategories recursively
     */
    private function getCategoryIdsWithChildren(Category $category): array
    {
        return $this->getAllDescendantIds($category->id);
    }

    /**
     * Recursively get all descendant category IDs
     */
    private function getAllDescendantIds(int $categoryId, array &$collected = []): array
    {
        if (empty($collected)) {
            $collected = [$categoryId];
        }

        // Get direct children
        $children = Category::where('parent_id', $categoryId)
            ->where('status', 1)
            ->pluck('id')
            ->toArray();

        foreach ($children as $childId) {
            if (!in_array($childId, $collected)) {
                $collected[] = $childId;
                // Recursively get descendants of this child
                $this->getAllDescendantIds($childId, $collected);
            }
        }

        return array_unique($collected);
    }

    /**
     * Export product feed (RSS/XML) - Starts background job
     */
    public function export(Request $request)
    {
        $categorySlug    = $request->query('category');
        $currencyCode    = $request->query('currency');
        $storeId         = $request->query('store_id');
        $excludeDelivery = $request->query('exclude_delivery');
        $promotionFilter = $request->query('promotion_filter');
        $priceMin        = $request->query('price_min');
        $priceMax        = $request->query('price_max');

        // Quick validation
        if (!$categorySlug || !$currencyCode) {
            return response()->json(['success' => false, 'message' => 'Category and currency are required'], 400);
        }

        $excludedTexts = [];
        if ($excludeDelivery) $excludedTexts = json_decode($excludeDelivery, true) ?? [];

        $jobId = uniqid('export_', true);
        cache()->put("export_job_{$jobId}", [
            'status'     => 'queued',
            'category'   => $categorySlug,
            'currency'   => $currencyCode,
            'progress'   => 5,
            'message'    => 'Export job queued, starting worker...',
            'started_at' => now()->toDateTimeString(),
        ], now()->addHours(2));

        $this->prepareExportInBackground($jobId, $categorySlug, $currencyCode, 'xml', $storeId, $excludedTexts, $promotionFilter, $priceMin, $priceMax);

        return response()->json([
            'success'   => true,
            'job_id'    => $jobId,
            'message'   => 'Export job started successfully',
            'check_url' => route('admin.product-feed.check-status', ['jobId' => $jobId]),
        ]);
    }

    /**
     * Export TSV - Starts background job
     */
    public function exportTsv(Request $request)
    {
        $categorySlug    = $request->query('category');
        $currencyCode    = $request->query('currency');
        $storeId         = $request->query('store_id');
        $excludeDelivery = $request->query('exclude_delivery');
        $promotionFilter = $request->query('promotion_filter');
        $priceMin        = $request->query('price_min');
        $priceMax        = $request->query('price_max');

        // Quick validation
        if (!$categorySlug || !$currencyCode) {
            return response()->json(['success' => false, 'message' => 'Category and currency are required'], 400);
        }

        $excludedTexts = [];
        if ($excludeDelivery) $excludedTexts = json_decode($excludeDelivery, true) ?? [];

        $jobId = uniqid('export_', true);
        cache()->put("export_job_{$jobId}", [
            'status'     => 'queued',
            'category'   => $categorySlug,
            'currency'   => $currencyCode,
            'progress'   => 5,
            'message'    => 'Export job queued, starting worker...',
            'started_at' => now()->toDateTimeString(),
        ], now()->addHours(2));

        $this->prepareExportInBackground($jobId, $categorySlug, $currencyCode, 'tsv', $storeId, $excludedTexts, $promotionFilter, $priceMin, $priceMax);

        return response()->json([
            'success'   => true,
            'job_id'    => $jobId,
            'message'   => 'Export job started successfully',
            'check_url' => route('admin.product-feed.check-status', ['jobId' => $jobId]),
        ]);
    }

    /**
     * Check export job status
     */
    public function checkStatus($jobId)
    {
        $job = cache()->get("export_job_{$jobId}");

        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Job not found or expired'], 404);
        }

        return response()->json([
            'success'      => true,
            'status'       => $job['status'],
            'progress'     => $job['progress'] ?? 0,
            'message'      => $job['message'] ?? '',
            'download_url' => $job['file_path'] ?? null,
        ]);
    }

    /**
     * Launch the export artisan command as a detached background process.
     */
    private function prepareExportInBackground(
        string $jobId, string $categorySlug, string $currencyCode, string $format,
        ?string $storeId = null, array $excludedTexts = [],
        ?string $promotionFilter = null, $priceMin = null, $priceMax = null
    ): void {
        $phpBin  = PHP_BINARY ?: 'php';
        $artisan = base_path('artisan');
        $logFile = storage_path("logs/product-feed-{$jobId}.log");

        // Build command with proper escaping
        $cmd = '"' . $phpBin . '" "' . $artisan . '"';
        $cmd .= ' product-feed:generate';
        $cmd .= ' ' . escapeshellarg($jobId);
        $cmd .= ' ' . escapeshellarg($categorySlug);
        $cmd .= ' ' . escapeshellarg($currencyCode);
        $cmd .= ' ' . escapeshellarg($format);
        if ($storeId)                    $cmd .= ' --store=' . escapeshellarg($storeId);
        if (!empty($excludedTexts))      $cmd .= ' --exclude=' . escapeshellarg(json_encode($excludedTexts));
        if ($promotionFilter)            $cmd .= ' --promotion=' . escapeshellarg($promotionFilter);
        if ($priceMin !== null && $priceMin !== '') $cmd .= ' --price-min=' . escapeshellarg((string)$priceMin);
        if ($priceMax !== null && $priceMax !== '') $cmd .= ' --price-max=' . escapeshellarg((string)$priceMax);

        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: Use start /B to run detached, redirect to log file
            $fullCmd = 'start /B "" ' . $cmd . ' > "' . $logFile . '" 2>&1';
            pclose(popen($fullCmd, 'r'));
        } else {
            // Linux/Mac
            exec($cmd . ' > ' . escapeshellarg($logFile) . ' 2>&1 &');
        }

        // Log the command for debugging
        \Log::info("Product feed export started", [
            'job_id' => $jobId,
            'command' => $cmd,
            'log_file' => $logFile,
        ]);
    }

    /**
     * Stream a single XML file
     */
    private function streamSingleXmlFile($categoryIds, $rate, $decimals, $decSep, $thouSep, $currencyCode, $category, $filename)
    {
        return response()->streamDownload(function() use (
            $categoryIds,
            $rate,
            $decimals,
            $decSep,
            $thouSep,
            $currencyCode,
            $category
        ) {
            // XML head & channel open
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">';
            echo '<channel>';
            echo '<title>' . htmlspecialchars(config('app.name') . ' Product Feed') . '</title>';
            echo '<link>'  . htmlspecialchars(config('app.url'))                . '</link>';
            echo '<description>'
                . htmlspecialchars(config('app.name') . ' Feed for category ' . str_replace('&','and',$category->name))
                . '</description>';

            // Stream products in chunks
            Product::with(['product_thumbnail:id,image_url'])
                ->select(['id','sku','name','description','slug','price','sale_price','stock_status','product_thumbnail_id'])
                ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
                ->where('status',1)
                ->where('is_approved',1)
                ->orderBy('id')
                ->chunkById(1000, function($products) use (
                    $rate, $decimals, $decSep, $thouSep, $currencyCode
                ) {
                    foreach ($products as $product) {
                        echo $this->formatProductXml($product, $rate, $decimals, $decSep, $thouSep, $currencyCode);
                    }
                });

            // Close channel & rss
            echo '</channel>';
            echo '</rss>';
        }, $filename, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    /**
     * Stream multiple XML files as a ZIP archive
     */
    private function streamMultipleXmlFiles($categoryIds, $rate, $decimals, $decSep, $thouSep, $currencyCode, $category, $categorySlug, $productsPerFile, $zipFilename)
    {
        // Create a temporary ZIP file
        $zip = new \ZipArchive();
        $tempZip = tempnam(sys_get_temp_dir(), 'feed_');

        if ($zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Could not create ZIP file');
        }

        $partNumber = 1;
        $productsInPart = 0;

        // Create temporary file for current part
        $currentPartFile = tempnam(sys_get_temp_dir(), 'part_');
        $partHandle = fopen($currentPartFile, 'w');

        // Write XML header to first part
        fwrite($partHandle, $this->getXmlHeader($category));

        Product::with(['product_thumbnail:id,image_url'])
            ->select(['id','sku','name','description','slug','price','sale_price','stock_status','product_thumbnail_id'])
            ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
            ->where('status',1)
            ->where('is_approved',1)
            ->orderBy('id')
            ->chunkById(1000, function($products) use (
                &$zip, &$partNumber, &$productsInPart, &$partHandle, &$currentPartFile,
                $productsPerFile, $categorySlug, $currencyCode, $rate, $decimals, $decSep, $thouSep, $category, $tempZip
            ) {
                foreach ($products as $product) {
                    // Write product XML directly to file
                    fwrite($partHandle, $this->formatProductXml($product, $rate, $decimals, $decSep, $thouSep, $currencyCode));
                    $productsInPart++;

                    // If reached limit, save current file and start new one
                    if ($productsInPart >= $productsPerFile) {
                        // Close current XML
                        fwrite($partHandle, '</channel></rss>');
                        fclose($partHandle);

                        // Add to ZIP from file (not memory)
                        $filename = "product-feed-{$categorySlug}-{$currencyCode}-part{$partNumber}.xml";
                        $zip->addFile($currentPartFile, $filename);

                        // Don't close/reopen - just keep adding files

                        // Reset for next file
                        $partNumber++;
                        $productsInPart = 0;
                        $currentPartFile = tempnam(sys_get_temp_dir(), 'part_');
                        $partHandle = fopen($currentPartFile, 'w');
                        fwrite($partHandle, $this->getXmlHeader($category));
                    }
                }
            });

        // Save last file if it has products
        if ($productsInPart > 0) {
            fwrite($partHandle, '</channel></rss>');
            fclose($partHandle);
            $filename = "product-feed-{$categorySlug}-{$currencyCode}-part{$partNumber}.xml";
            $zip->addFile($currentPartFile, $filename);
        }

        // Add README file
        $readme = "Product Feed Export\n\n";
        $readme .= "Category: {$category->name}\n";
        $readme .= "Currency: {$currencyCode}\n";
        $readme .= "Total Parts: {$partNumber}\n";
        $readme .= "Export Date: " . date('Y-m-d H:i:s') . "\n\n";
        $readme .= "Upload each XML file separately to your platform.\n";
        $zip->addFromString('README.txt', $readme);

        $zip->close();

        // Clean up all temp part files
        $tempDir = sys_get_temp_dir();
        foreach (glob($tempDir . '/part_*') as $tempFile) {
            @unlink($tempFile);
        }

        // Return the file as download and delete after
        return response()->download($tempZip, $zipFilename, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Get XML header for a category
     */
    private function getXmlHeader($category): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">';
        $xml .= '<channel>';
        $xml .= '<title>' . htmlspecialchars(config('app.name') . ' Product Feed') . '</title>';
        $xml .= '<link>'  . htmlspecialchars(config('app.url'))                . '</link>';
        $xml .= '<description>'
            . htmlspecialchars(config('app.name') . ' Feed for category ' . str_replace('&','and',$category->name))
            . '</description>';
        return $xml;
    }

    /**
     * Format a single product as XML
     */
    private function formatProductXml($product, $rate, $decimals, $decSep, $thouSep, $currencyCode): string
    {
        // Regular price (converted by exchange rate)
        $regularPrice = (float) ($product->price ?? 0);
        $convertedRegular = $regularPrice * $rate;
        $formattedRegular = number_format($convertedRegular, $decimals, '.', '');

        // Sale price - convert to selected currency if exists
        // Only include if it exists and is greater than 0
        $salePrice = (float) ($product->sale_price ?? 0);
        $hasSalePrice = $salePrice > 0;
        $convertedSale = $hasSalePrice ? ($salePrice * $rate) : 0;
        $formattedSale = $hasSalePrice ? number_format($convertedSale, $decimals, '.', '') : null;

        // Use sale price as the main price if available
        $displayPrice = $hasSalePrice ? $formattedSale : $formattedRegular;

        $frontendUrl = config('app.frontend_url') ?? config('app.url');
        $productUrl  = rtrim($frontendUrl, '/') . '/en/product/' . $product->slug . '?currency=' . $currencyCode;
        $imageUrl    = $product->product_thumbnail
            ? $product->product_thumbnail->image_url
            : '';

        // Get actual brand name if available, otherwise use default
        $brandName = $product->brand && $product->brand->name
            ? $product->brand->name
            : config('app.name', 'Raines Africa');

        // Clean all text fields from control characters before XML encoding
        $cleanName = $this->removeControlCharacters($product->name);
        $cleanDescription = $this->removeControlCharacters(strip_tags($product->description ?? ''));

        $xml = '<item>';
        $xml .= '<g:id>'          . htmlspecialchars((string)$product->id)  . '</g:id>';
        $xml .= '<g:title>'       . htmlspecialchars($cleanName) . '</g:title>';
        $xml .= '<g:description>' . htmlspecialchars($cleanDescription) . '</g:description>';
        $xml .= '<g:link>'        . htmlspecialchars($productUrl)    . '</g:link>';
        if ($imageUrl) {
            $xml .= '<g:image_link>' . htmlspecialchars($imageUrl) . '</g:image_link>';
        }
        $xml .= '<g:availability>' . htmlspecialchars($product->stock_status) . '</g:availability>';
        $xml .= '<g:price>'        . htmlspecialchars($formattedRegular . ' ' . $currencyCode) . '</g:price>';
        if ($hasSalePrice) {
            $xml .= '<g:sale_price>' . htmlspecialchars($formattedSale . ' ' . $currencyCode) . '</g:sale_price>';
        }
        $xml .= '<g:brand>'        . htmlspecialchars($brandName) . '</g:brand>';
        $xml .= '<g:condition>new</g:condition>';
        $xml .= '</item>';

        return $xml;
    }



    /**
     * Format a single product as TSV row
     */
    private function formatProductTsv($product, $rate, $decimals, $decSep, $thouSep, $currencyCode): string
    {
        // Regular price (converted by exchange rate)
        $regularPrice = (float) ($product->price ?? 0);
        $formattedRegular = number_format($regularPrice * $rate, $decimals, '.', '');

        // Sale price - convert to selected currency if exists
        // Only include if it exists and is greater than 0
        $salePrice = (float) ($product->sale_price ?? 0);
        $hasSalePrice = $salePrice > 0;
        $convertedSale = $hasSalePrice ? ($salePrice * $rate) : 0;
        $formattedSale = $hasSalePrice ? number_format($convertedSale, $decimals, '.', '') . ' ' . $currencyCode : '';

        $frontendUrl = config('app.frontend_url') ?? config('app.url');
        $productUrl  = rtrim($frontendUrl, '/') . '/en/product/' . $product->slug . '?currency=' . $currencyCode;
        $imageUrl    = $product->product_thumbnail
            ? $product->product_thumbnail->image_url
            : '';

        // Get actual brand name if available, otherwise use default
        $brandName = $product->brand && $product->brand->name
            ? $product->brand->name
            : config('app.name', 'Raines Africa');

        // Prepare TSV row data (now includes sale_price column)
        $row = [
            $product->id,
            $this->escapeTsv($product->name),
            $this->escapeTsv(strip_tags($product->description ?? '')),
            $productUrl,
            $imageUrl,
            $product->stock_status,
            $formattedRegular . ' ' . $currencyCode,
            $formattedSale,
            $this->escapeTsv($brandName),
            'new'
        ];

        return implode("\t", $row) . "\n";
    }

    /**
     * Format a single product as CSV row (comma-delimited)
     */
    private function formatProductCsv($product, $rate, $decimals, $decSep, $thouSep, $currencyCode): string
    {
        // Regular price (converted by exchange rate)
        $regularPrice = (float) ($product->price ?? 0);
        $formattedRegular = number_format($regularPrice * $rate, $decimals, '.', '');

        // Sale price - convert to selected currency if exists
        $salePrice = (float) ($product->sale_price ?? 0);
        $hasSalePrice = $salePrice > 0;
        $convertedSale = $hasSalePrice ? ($salePrice * $rate) : 0;
        $formattedSale = $hasSalePrice ? number_format($convertedSale, $decimals, '.', '') . ' ' . $currencyCode : '';

        $frontendUrl = config('app.frontend_url') ?? config('app.url');
        $productUrl  = rtrim($frontendUrl, '/') . '/en/product/' . $product->slug . '?currency=' . $currencyCode;
        $imageUrl    = $product->product_thumbnail
            ? $product->product_thumbnail->image_url
            : '';

        // Get actual brand name if available, otherwise use default
        $brandName = $product->brand && $product->brand->name
            ? $product->brand->name
            : config('app.name', 'Raines Africa');

        // Use SKU as ID (fallback to product ID if no SKU)
        $productId = $product->sku ?: $product->id;

        // Prepare CSV row data with proper escaping
        $row = [
            $this->escapeCsv($productId), // SKU as ID
            $this->escapeCsv($product->name),
            $this->escapeCsv(strip_tags($product->description ?? '')),
            $this->escapeCsv($productUrl),
            $this->escapeCsv($imageUrl),
            $this->escapeCsv($product->stock_status),
            $this->escapeCsv($formattedRegular . ' ' . $currencyCode),
            $this->escapeCsv($formattedSale),
            $this->escapeCsv($brandName),
            'new'
        ];

        return implode(",", $row) . "\n";
    }

    /**
     * Escape special characters for TSV format
     */
    private function escapeTsv($value)
    {
        if ($value === null) {
            return '';
        }

        // First, remove ALL control characters
        $value = $this->removeControlCharacters($value);

        // Replace tabs, newlines, and carriage returns with spaces
        $value = str_replace(["\t", "\n", "\r"], [' ', ' ', ''], $value);

        // Trim whitespace
        return trim($value);
    }

    /**
     * Escape special characters for CSV format (RFC 4180 compliant)
     */
    private function escapeCsv($value)
    {
        if ($value === null || $value === '') {
            return '""';
        }

        // Remove control characters first
        $value = $this->removeControlCharacters($value);

        // Replace newlines and carriage returns with spaces
        $value = str_replace(["\n", "\r"], [' ', ''], $value);

        // Trim whitespace
        $value = trim($value);

        // Escape double quotes by doubling them
        $value = str_replace('"', '""', $value);

        // Always wrap in double quotes for CSV (safer for Excel/Sheets)
        return '"' . $value . '"';
    }

    /**
     * Remove all non-printable control characters that break XML/TSV formats
     * This removes characters like 0x03 (ETX), 0x1E (Record Separator), etc.
     */
    private function removeControlCharacters($string)
    {
        if ($string === null || $string === '') {
            return '';
        }

        // Remove all control characters except tab (0x09), newline (0x0A), and carriage return (0x0D)
        // Control characters are 0x00-0x1F and 0x7F-0x9F
        // We keep tab, newline, carriage return as they're handled separately
        $string = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\x9F]/u', '', $string);

        // Also remove any invalid UTF-8 sequences
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // Remove any remaining whitespace control characters
        $string = preg_replace('/\p{C}/u', '', $string);

        return $string;
    }

    /**
     * Export with streaming progress output (like import-fast)
     */
    public function exportStream(Request $request)
    {
        set_time_limit(0);

        $categorySlug    = $request->input('category');
        $currencyCode    = $request->input('currency');
        $storeId         = $request->input('store_id');
        $excludeDelivery = $request->input('exclude_delivery');
        $promotionFilter = $request->input('promotion_filter');
        $priceMin        = ($request->input('price_min') !== null && $request->input('price_min') !== '') ? (float) $request->input('price_min') : null;
        $priceMax        = ($request->input('price_max') !== null && $request->input('price_max') !== '') ? (float) $request->input('price_max') : null;

        $excludedTexts = [];
        if ($excludeDelivery) {
            $excludedTexts = json_decode($excludeDelivery, true) ?? [];
        }

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($categorySlug, $currencyCode, $storeId, $excludedTexts, $promotionFilter, $priceMin, $priceMax) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);
            ob_implicit_flush(true);

            echo str_repeat(" ", 1024);
            flush();

            try {
                $this->streamOutput("🚀 Starting XML export...");

                $category = Cache::remember("category_by_slug_{$categorySlug}", now()->addHours(6), function () use ($categorySlug) {
                    return Category::whereSlug($categorySlug)->firstOrFail();
                });
                $currency = Cache::remember("currency_code_{$currencyCode}", now()->addHours(6), function () use ($currencyCode) {
                    return Currency::where('code', $currencyCode)->where('status', 1)->firstOrFail();
                });

                $this->streamOutput("✓ Category: {$category->name}");
                $this->streamOutput("✓ Currency: {$currencyCode}");
                if ($storeId) $this->streamOutput("🏪 Filtering by store ID: {$storeId}");
                if (!empty($excludedTexts)) $this->streamOutput("🚫 Excluding " . count($excludedTexts) . " delivery type(s)");
                if ($promotionFilter) $this->streamOutput("🏷️ Promotion filter: {$promotionFilter}");
                if ($priceMin !== null || $priceMax !== null) $this->streamOutput("💰 Price range: " . ($priceMin ?? '0') . " – " . ($priceMax ?? '∞'));

                $categoryIds = $this->getCategoryIdsWithChildren($category);

                // Build a reusable base query helper
                $buildQuery = function () use ($categoryIds, $storeId, $excludedTexts, $promotionFilter, $priceMin, $priceMax) {
                    $q = Product::whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
                        ->where('status', 1)->where('is_approved', 1);
                    if ($storeId) $q->where('store_id', $storeId);
                    if (!empty($excludedTexts)) {
                        $q->where(function ($sq) use ($excludedTexts) {
                            $sq->whereNotIn('estimated_delivery_text', $excludedTexts)
                               ->orWhereNull('estimated_delivery_text');
                        });
                    }
                    if ($promotionFilter) $q = $this->applyPromotionFilterEloquent($q, $promotionFilter);
                    if ($priceMin !== null) {
                        $q->where(function ($q2) use ($priceMin) {
                            $q2->where(function ($sq) use ($priceMin) {
                                $sq->where('sale_price', '>', 0)
                                    ->where('sale_price', '>=', $priceMin);
                            })->orWhere(function ($sq) use ($priceMin) {
                                $sq->where(function ($i) {
                                    $i->whereNull('sale_price')
                                        ->orWhere('sale_price', 0);
                                });
                                $sq->where('price', '>=', $priceMin);
                            });
                        });
                    }
                    if ($priceMax !== null) {
                        $q->where(function ($q2) use ($priceMax) {
                            $q2->where(function ($sq) use ($priceMax) {
                                $sq->where('sale_price', '>', 0)
                                    ->where('sale_price', '<=', $priceMax);
                            })->orWhere(function ($sq) use ($priceMax) {
                                $sq->where(function ($i) {
                                    $i->whereNull('sale_price')
                                        ->orWhere('sale_price', 0); });
                                $sq->where('price', '<=', $priceMax);
                            });
                        });
                    }
                    return $q;
                };

                $totalProducts = $buildQuery()->count();
                $this->streamOutput("📊 Total products to export: " . number_format($totalProducts));

                if ($totalProducts === 0) {
                    $this->streamOutput("⚠️ No products found with the selected filters.");
                    return;
                }

                $productsPerFile = 100000;
                $totalFiles      = max(1, ceil($totalProducts / $productsPerFile));
                if ($totalFiles > 1) $this->streamOutput("📦 Will create {$totalFiles} files (100k products each)");

                $rate     = (float) $currency->exchange_rate;
                $decimals = (int)   $currency->no_of_decimal;
                $decSep   = $currency->decimal_separator   === 'comma' ? ',' : '.';
                $thouSep  = $currency->thousands_separator === 'comma' ? ',' : '.';

                $this->streamOutput("📝 Writing products to file...");

                if ($totalFiles === 1) {
                    // ── Single-file export: write plain XML, no ZIP ──────────────
                    $tempXml    = tempnam(sys_get_temp_dir(), 'feed_xml_');
                    $xmlHandle  = fopen($tempXml, 'wb');
                    fwrite($xmlHandle, $this->getXmlHeader($category));

                    $totalProcessed = 0;

                    $buildQuery()->with(['product_thumbnail:id,image_url', 'brand:id,name'])
                        ->select(['id','sku','name','description','slug','price','sale_price','stock_status','product_thumbnail_id','brand_id'])
                        ->orderBy('id')
                        ->chunkById(500, function ($products) use (
                            &$xmlHandle, &$totalProcessed,
                            $rate, $decimals, $decSep, $thouSep, $currencyCode, $totalProducts
                        ) {
                            foreach ($products as $product) {
                                fwrite($xmlHandle, $this->formatProductXml($product, $rate, $decimals, $decSep, $thouSep, $currencyCode));
                                $totalProcessed++;
                                if ($totalProcessed % 5000 === 0) {
                                    $pct = round(($totalProcessed / $totalProducts) * 100, 1);
                                    $this->streamOutput("⏳ Processed " . number_format($totalProcessed) . " / " . number_format($totalProducts) . " ({$pct}%)");
                                }
                            }
                            unset($products);
                        });

                    fwrite($xmlHandle, '</channel></rss>');
                    fclose($xmlHandle);

                    $this->streamOutput("✅ Export completed successfully!");
                    $this->streamOutput("📊 Total products: " . number_format($totalProcessed));

                    $outFilename = "product-feed-{$categorySlug}-{$currencyCode}.xml";
                    $downloadUrl = url("/admin/product-feed/download-temp?file=" . basename($tempXml) . "&name=" . urlencode($outFilename));
                    $this->streamOutput("<a href='{$downloadUrl}' class='btn btn-success mt-3' download><i class='bi bi-download me-2'></i>Download Your File</a>");

                } else {
                    // ── Multi-file export: bundle parts into a ZIP ───────────────
                    $zip     = new \ZipArchive();
                    $tempZip = tempnam(sys_get_temp_dir(), 'feed_');

                    if ($zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                        throw new \Exception('Could not create ZIP file');
                    }

                    $partNumber     = 1;
                    $productsInPart = 0;
                    $totalProcessed = 0;
                    $partFiles      = [];

                    $currentPartFile = tempnam(sys_get_temp_dir(), 'part_');
                    $partFiles[]     = $currentPartFile;
                    $partHandle      = fopen($currentPartFile, 'wb');
                    fwrite($partHandle, $this->getXmlHeader($category));

                    $buildQuery()->with(['product_thumbnail:id,image_url', 'brand:id,name'])
                        ->select(['id','sku','name','description','slug','price','sale_price','stock_status','product_thumbnail_id','brand_id'])
                        ->orderBy('id')
                        ->chunkById(500, function ($products) use (
                            &$zip, &$partNumber, &$productsInPart, &$partHandle,
                            &$currentPartFile, &$totalProcessed, &$partFiles,
                            $productsPerFile, $categorySlug, $currencyCode,
                            $rate, $decimals, $decSep, $thouSep, $category, $totalProducts
                        ) {
                            foreach ($products as $product) {
                                fwrite($partHandle, $this->formatProductXml($product, $rate, $decimals, $decSep, $thouSep, $currencyCode));
                                $productsInPart++;
                                $totalProcessed++;

                                if ($totalProcessed % 5000 === 0) {
                                    $pct = round(($totalProcessed / $totalProducts) * 100, 1);
                                    $this->streamOutput("⏳ Processed " . number_format($totalProcessed) . " / " . number_format($totalProducts) . " ({$pct}%)");
                                }

                                if ($productsInPart >= $productsPerFile) {
                                    fwrite($partHandle, '</channel></rss>');
                                    fclose($partHandle);
                                    $fname = "product-feed-{$categorySlug}-{$currencyCode}-part{$partNumber}.xml";
                                    $zip->addFile($currentPartFile, $fname);
                                    $this->streamOutput("✓ Completed part {$partNumber} with " . number_format($productsInPart) . " products");
                                    $partNumber++;
                                    $productsInPart  = 0;
                                    $currentPartFile = tempnam(sys_get_temp_dir(), 'part_');
                                    $partFiles[]     = $currentPartFile;
                                    $partHandle      = fopen($currentPartFile, 'w');
                                    fwrite($partHandle, $this->getXmlHeader($category));
                                }
                            }
                            unset($products);
                        });

                    if ($productsInPart > 0) {
                        fwrite($partHandle, '</channel></rss>');
                        fclose($partHandle);
                        $fname = "product-feed-{$categorySlug}-{$currencyCode}-part{$partNumber}.xml";
                        $zip->addFile($currentPartFile, $fname);
                        $this->streamOutput("✓ Completed part {$partNumber} with " . number_format($productsInPart) . " products");
                    } elseif (is_resource($partHandle)) {
                        fclose($partHandle);
                    }

                    $zip->addFromString('README.txt', "Product Feed Export\n\nCategory: {$category->name}\nCurrency: {$currencyCode}\nTotal Parts: {$partNumber}\nExport Date: " . date('Y-m-d H:i:s') . "\n\nUpload each XML file separately to your platform.\n");
                    $zip->close();

                    foreach ($partFiles as $f) { @unlink($f); }

                    $this->streamOutput("✅ Export completed successfully!");
                    $this->streamOutput("📦 Total files: {$partNumber}");
                    $this->streamOutput("📊 Total products: " . number_format($totalProcessed));

                    $zipFilename = "product-feed-{$categorySlug}-{$currencyCode}.zip";
                    $downloadUrl = url("/admin/product-feed/download-temp?file=" . basename($tempZip) . "&name=" . urlencode($zipFilename));
                    $this->streamOutput("<a href='{$downloadUrl}' class='btn btn-success mt-3' download><i class='bi bi-download me-2'></i>Download Your File</a>");
                }

            } catch (\Throwable $e) {
                $this->streamOutput("❌ Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            }
        });

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');

        return $response;
    }

    /**
     * Export TSV with streaming progress output
     */
    public function exportTsvStream(Request $request)
    {
        set_time_limit(0);

        $categorySlug    = $request->input('category');
        $currencyCode    = $request->input('currency');
        $storeId         = $request->input('store_id');
        $excludeDelivery = $request->input('exclude_delivery');
        $promotionFilter = $request->input('promotion_filter');
        $priceMin        = ($request->input('price_min') !== null && $request->input('price_min') !== '') ? (float) $request->input('price_min') : null;
        $priceMax        = ($request->input('price_max') !== null && $request->input('price_max') !== '') ? (float) $request->input('price_max') : null;

        $excludedTexts = [];
        if ($excludeDelivery) {
            $excludedTexts = json_decode($excludeDelivery, true) ?? [];
        }

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($categorySlug, $currencyCode, $storeId, $excludedTexts, $promotionFilter, $priceMin, $priceMax) {
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);
            ob_implicit_flush(true);

            echo str_repeat(" ", 1024);
            flush();

            try {
                $this->streamOutput("🚀 Starting TSV export...");

                $category = Cache::remember("category_by_slug_{$categorySlug}", now()->addHours(6), function () use ($categorySlug) {
                    return Category::whereSlug($categorySlug)->firstOrFail();
                });
                $currency = Cache::remember("currency_code_{$currencyCode}", now()->addHours(6), function () use ($currencyCode) {
                    return Currency::where('code', $currencyCode)->where('status', 1)->firstOrFail();
                });

                $this->streamOutput("✓ Category: {$category->name}");
                $this->streamOutput("✓ Currency: {$currencyCode}");
                if ($storeId) $this->streamOutput("🏪 Filtering by store ID: {$storeId}");
                if (!empty($excludedTexts)) $this->streamOutput("🚫 Excluding " . count($excludedTexts) . " delivery type(s)");
                if ($promotionFilter) $this->streamOutput("🏷️ Promotion filter: {$promotionFilter}");
                if ($priceMin !== null || $priceMax !== null) $this->streamOutput("💰 Price range: " . ($priceMin ?? '0') . " – " . ($priceMax ?? '∞'));

                $categoryIds = $this->getCategoryIdsWithChildren($category);

                $buildQuery = function () use ($categoryIds, $storeId, $excludedTexts, $promotionFilter, $priceMin, $priceMax) {
                    $q = Product::whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
                        ->where('status', 1)->where('is_approved', 1);
                    if ($storeId) $q->where('store_id', $storeId);
                    if (!empty($excludedTexts)) {
                        $q->where(function ($sq) use ($excludedTexts) {
                            $sq->whereNotIn('estimated_delivery_text', $excludedTexts)
                               ->orWhereNull('estimated_delivery_text');
                        });
                    }
                    if ($promotionFilter) $q = $this->applyPromotionFilterEloquent($q, $promotionFilter);
                    if ($priceMin !== null) {
                        $q->where(function ($q2) use ($priceMin) {
                            $q2->where(function ($sq) use ($priceMin) {
                                $sq->where('sale_price', '>', 0)->where('sale_price', '>=', $priceMin);
                            })->orWhere(function ($sq) use ($priceMin) {
                                $sq->where(function ($i) { $i->whereNull('sale_price')->orWhere('sale_price', 0); });
                                $sq->where('price', '>=', $priceMin);
                            });
                        });
                    }
                    if ($priceMax !== null) {
                        $q->where(function ($q2) use ($priceMax) {
                            $q2->where(function ($sq) use ($priceMax) {
                                $sq->where('sale_price', '>', 0)->where('sale_price', '<=', $priceMax);
                            })->orWhere(function ($sq) use ($priceMax) {
                                $sq->where(function ($i) { $i->whereNull('sale_price')->orWhere('sale_price', 0); });
                                $sq->where('price', '<=', $priceMax);
                            });
                        });
                    }
                    return $q;
                };

                $totalProducts = $buildQuery()->count();
                $this->streamOutput("📊 Total products to export: " . number_format($totalProducts));

                if ($totalProducts === 0) {
                    $this->streamOutput("⚠️ No products found with the selected filters.");
                    return;
                }

                $productsPerFile = 100000;
                $totalFiles      = max(1, ceil($totalProducts / $productsPerFile));
                if ($totalFiles > 1) $this->streamOutput("📦 Will create {$totalFiles} files (100k products each)");

                $rate     = (float) $currency->exchange_rate;
                $decimals = (int)   $currency->no_of_decimal;
                $decSep   = $currency->decimal_separator   === 'comma' ? ',' : '.';
                $thouSep  = $currency->thousands_separator === 'comma' ? ',' : '.';
                $tsvHeaders = ['id', 'title', 'description', 'link', 'image_link', 'availability', 'price', 'sale_price', 'brand', 'condition'];

                $this->streamOutput("📝 Writing products to file...");

                if ($totalFiles === 1) {
                    // ── Single-file export: write plain TSV, no ZIP ──────────────
                    $tempTsv   = tempnam(sys_get_temp_dir(), 'feed_tsv_');
                    $tsvHandle = fopen($tempTsv, 'w');
                    fwrite($tsvHandle, implode("\t", $tsvHeaders) . "\n");

                    $totalProcessed = 0;

                    $buildQuery()->with(['product_thumbnail:id,image_url'])
                        ->select(['id','sku','name','description','slug','price','sale_price','stock_status','product_thumbnail_id'])
                        ->orderBy('id')
                        ->chunkById(500, function ($products) use (
                            &$tsvHandle, &$totalProcessed,
                            $rate, $decimals, $decSep, $thouSep, $currencyCode, $totalProducts
                        ) {
                            foreach ($products as $product) {
                                fwrite($tsvHandle, $this->formatProductTsv($product, $rate, $decimals, $decSep, $thouSep, $currencyCode));
                                $totalProcessed++;
                                if ($totalProcessed % 5000 === 0) {
                                    $pct = round(($totalProcessed / $totalProducts) * 100, 1);
                                    $this->streamOutput("⏳ Processed " . number_format($totalProcessed) . " / " . number_format($totalProducts) . " ({$pct}%)");
                                }
                            }
                            unset($products);
                        });

                    fclose($tsvHandle);

                    $this->streamOutput("✅ Export completed successfully!");
                    $this->streamOutput("📊 Total products: " . number_format($totalProcessed));

                    $outFilename = "product-feed-{$categorySlug}-{$currencyCode}.tsv";
                    $downloadUrl = url("/admin/product-feed/download-temp?file=" . basename($tempTsv) . "&name=" . urlencode($outFilename));
                    $this->streamOutput("<a href='{$downloadUrl}' class='btn btn-success mt-3' download><i class='bi bi-download me-2'></i>Download Your File</a>");

                } else {
                    // ── Multi-file export: bundle parts into a ZIP ───────────────
                    $zip     = new \ZipArchive();
                    $tempZip = tempnam(sys_get_temp_dir(), 'feed_');

                    if ($zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                        throw new \Exception('Could not create ZIP file');
                    }

                    $partNumber     = 1;
                    $productsInPart = 0;
                    $totalProcessed = 0;
                    $partFiles      = [];

                    $currentPartFile = tempnam(sys_get_temp_dir(), 'part_');
                    $partFiles[]     = $currentPartFile;
                    $partHandle      = fopen($currentPartFile, 'w');
                    fwrite($partHandle, implode("\t", $tsvHeaders) . "\n");

                    $buildQuery()->with(['product_thumbnail:id,image_url'])
                        ->select(['id','sku','name','description','slug','price','sale_price','stock_status','product_thumbnail_id'])
                        ->orderBy('id')
                        ->chunkById(500, function ($products) use (
                            &$zip, &$partNumber, &$productsInPart, &$partHandle,
                            &$currentPartFile, &$totalProcessed, &$partFiles,
                            $productsPerFile, $categorySlug, $currencyCode,
                            $rate, $decimals, $decSep, $thouSep, $tsvHeaders, $totalProducts
                        ) {
                            foreach ($products as $product) {
                                fwrite($partHandle, $this->formatProductTsv($product, $rate, $decimals, $decSep, $thouSep, $currencyCode));
                                $productsInPart++;
                                $totalProcessed++;

                                if ($totalProcessed % 5000 === 0) {
                                    $pct = round(($totalProcessed / $totalProducts) * 100, 1);
                                    $this->streamOutput("⏳ Processed " . number_format($totalProcessed) . " / " . number_format($totalProducts) . " ({$pct}%)");
                                }

                                if ($productsInPart >= $productsPerFile) {
                                    fclose($partHandle);
                                    $fname = "product-feed-{$categorySlug}-{$currencyCode}-part{$partNumber}.tsv";
                                    $zip->addFile($currentPartFile, $fname);
                                    $this->streamOutput("✓ Completed part {$partNumber} with " . number_format($productsInPart) . " products");
                                    $partNumber++;
                                    $productsInPart  = 0;
                                    $currentPartFile = tempnam(sys_get_temp_dir(), 'part_');
                                    $partFiles[]     = $currentPartFile;
                                    $partHandle      = fopen($currentPartFile, 'w');
                                    fwrite($partHandle, implode("\t", $tsvHeaders) . "\n");
                                }
                            }
                            unset($products);
                        });

                    if ($productsInPart > 0) {
                        fclose($partHandle);
                        $fname = "product-feed-{$categorySlug}-{$currencyCode}-part{$partNumber}.tsv";
                        $zip->addFile($currentPartFile, $fname);
                        $this->streamOutput("✓ Completed part {$partNumber} with " . number_format($productsInPart) . " products");
                    } elseif (is_resource($partHandle)) {
                        fclose($partHandle);
                    }

                    $zip->addFromString('README.txt', "Product Feed Export (TSV)\n\nCategory: {$category->name}\nCurrency: {$currencyCode}\nTotal Parts: {$partNumber}\nExport Date: " . date('Y-m-d H:i:s') . "\n\nUpload each TSV file separately to your platform.\n");
                    $zip->close();

                    foreach ($partFiles as $f) { @unlink($f); }

                    $this->streamOutput("✅ Export completed successfully!");
                    $this->streamOutput("📦 Total files: {$partNumber}");
                    $this->streamOutput("📊 Total products: " . number_format($totalProcessed));

                    $zipFilename = "product-feed-{$categorySlug}-{$currencyCode}-tsv.zip";
                    $downloadUrl = url("/admin/product-feed/download-temp?file=" . basename($tempZip) . "&name=" . urlencode($zipFilename));
                    $this->streamOutput("<a href='{$downloadUrl}' class='btn btn-success mt-3' download><i class='bi bi-download me-2'></i>Download Your File</a>");
                }

            } catch (\Throwable $e) {
                $this->streamOutput("❌ Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            }
        });

        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');

        return $response;
    }

    /**
     * Stream output to browser
     */
    private function streamOutput($text)
    {
        // If the text starts with an HTML tag (like <a ...), output it raw (it's already safe HTML we built)
        if (str_starts_with(ltrim($text), '<')) {
            echo $text . "<br>\n";
        } else {
            $clean = preg_replace('/\e\[[;\d]*m/', '', $text);
            echo nl2br(e($clean)) . "<br>\n";
        }
        flush();
        if (ob_get_level() > 0) {
            ob_flush();
        }
    }

    /**
     * Direct download XML — chunked, never loads all products into memory
     */
    public function downloadXml(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M'); // Increase to 1GB for large exports
        ini_set('max_execution_time', '600'); // 10 minutes max

        // Disable query log to reduce memory usage
        \DB::connection()->disableQueryLog();

        // Clean any output buffers to prevent BOM/whitespace before XML declaration
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $categorySlug    = $request->input('category');
        $skuListJson     = $request->input('sku_list');
        $currencyCode    = $request->input('currency');
        $storeId         = $request->input('store_id');
        $region          = $request->input('region');
        $excludeDelivery = $request->input('exclude_delivery');
        $promotionFilter = $request->input('promotion_filter');
        $priceMin        = ($request->input('price_min') !== null && $request->input('price_min') !== '') ? (float) $request->input('price_min') : null;
        $priceMax        = ($request->input('price_max') !== null && $request->input('price_max') !== '') ? (float) $request->input('price_max') : null;

        $excludedTexts = [];
        if ($excludeDelivery) {
            $excludedTexts = json_decode($excludeDelivery, true) ?? [];
        }

        // Determine export mode: SKU list or Category
        $exportMode = $skuListJson ? 'sku' : 'category';

        $category = null;
        $categoryIds = null;

        $category    = null;
        $categoryIds = null;

        if ($exportMode === 'sku') {
            $skuList = json_decode($skuListJson, true) ?? [];
            if (empty($skuList)) {
                abort(400, 'SKU list is empty');
            }
        } else {
            if (!$categorySlug && !$region) {
                abort(400, 'Category or region is required');
            }
            if ($categorySlug) {
                $category    = Category::whereSlug($categorySlug)->firstOrFail();
                $categoryIds = $this->getCategoryIdsWithChildren($category);
            }
        }

        $currency = Currency::where('code', $currencyCode)->where('status', 1)->firstOrFail();

        $rate     = (float) $currency->exchange_rate;
        $decimals = (int)   $currency->no_of_decimal;
        $decSep   = $currency->decimal_separator   === 'comma' ? ',' : '.';
        $thouSep  = $currency->thousands_separator === 'comma' ? ',' : '.';

        // Write to a temp file — use binary mode (wb) to prevent any BOM or encoding issues
        $tempFile = tempnam(sys_get_temp_dir(), 'feed_xml_');
        $handle   = fopen($tempFile, 'wb');

        // Write XML header manually (no BOM, no leading whitespace)
        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>');
        fwrite($handle, '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">');
        fwrite($handle, '<channel>');
        fwrite($handle, '<title>' . htmlspecialchars(config('app.name') . ' Product Feed') . '</title>');
        fwrite($handle, '<link>' . htmlspecialchars(config('app.url')) . '</link>');

        $description = $exportMode === 'sku'
            ? 'Feed for selected SKUs'
            : ($category ? 'Feed for category ' . str_replace('&', 'and', $category->name) : 'Feed for region ' . $region);
        fwrite($handle, '<description>' . htmlspecialchars(config('app.name') . ' ' . $description) . '</description>');

        $query = Product::with(['product_thumbnail:id,image_url', 'brand:id,name'])
            ->select(['id','sku','name','description','slug','price','sale_price','stock_status','product_thumbnail_id','brand_id'])
            ->where('status', 1)
            ->where('is_approved', 1);

        // Apply mode-specific filter
        if ($exportMode === 'sku') {
            $query->whereIn('sku', $skuList);
        } elseif ($categoryIds) {
            $query->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds));
        }

        // Apply other filters
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($region) {
            $query = $this->applyRegionFilterEloquent($query, $region);
        }

        if (!empty($excludedTexts)) {
            $query->where(function ($q) use ($excludedTexts) {
                $q->whereNotIn('estimated_delivery_text', $excludedTexts)
                  ->orWhereNull('estimated_delivery_text');
            });
        }

        if ($promotionFilter) {
            $query = $this->applyPromotionFilterEloquent($query, $promotionFilter);
        }

        // Price range filter (applies to the effective price: sale_price if set, else price)
        if ($priceMin !== null) {
            $query->where(function ($q) use ($priceMin) {
                $q->where(function ($sq) use ($priceMin) {
                    $sq->where('sale_price', '>', 0)->where('sale_price', '>=', $priceMin);
                })->orWhere(function ($sq) use ($priceMin) {
                    $sq->where(function ($i) { $i->whereNull('sale_price')->orWhere('sale_price', 0); });
                    $sq->where('price', '>=', $priceMin);
                });
            });
        }
        if ($priceMax !== null) {
            $query->where(function ($q) use ($priceMax) {
                $q->where(function ($sq) use ($priceMax) {
                    $sq->where('sale_price', '>', 0)->where('sale_price', '<=', $priceMax);
                })->orWhere(function ($sq) use ($priceMax) {
                    $sq->where(function ($i) { $i->whereNull('sale_price')->orWhere('sale_price', 0); });
                    $sq->where('price', '<=', $priceMax);
                });
            });
        }

        $query->orderBy('id')->chunkById(2000, function ($products) use ($handle, $rate, $decimals, $decSep, $thouSep, $currencyCode) {
            foreach ($products as $product) {
                fwrite($handle, $this->formatProductXml($product, $rate, $decimals, $decSep, $thouSep, $currencyCode));
            }
            // Free memory after each chunk
            unset($products);
            gc_collect_cycles(); // Force garbage collection
        });

        fwrite($handle, '</channel></rss>');
        fclose($handle);

        $regionSuffix = $region ? "-{$region}" : '';
        if ($exportMode === 'sku') {
            $filename = "product-feed-skus-{$currencyCode}{$regionSuffix}.xml";
        } elseif ($categorySlug) {
            $filename = "product-feed-{$categorySlug}-{$currencyCode}{$regionSuffix}.xml";
        } else {
            $filename = "product-feed-all-{$currencyCode}{$regionSuffix}.xml";
        }

        return response()->download($tempFile, $filename, [
            'Content-Type'        => 'application/xml; charset=UTF-8',
            'Cache-Control'       => 'no-cache, must-revalidate',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->deleteFileAfterSend(true);
    }

    /**
     * Direct download TSV — chunked, never loads all products into memory
     */
    public function downloadTsv(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M'); // Increase to 1GB for large exports
        ini_set('max_execution_time', '600'); // 10 minutes max

        // Disable query log to reduce memory usage
        \DB::connection()->disableQueryLog();

        $categorySlug    = $request->input('category');
        $skuListJson     = $request->input('sku_list');
        $currencyCode    = $request->input('currency');
        $storeId         = $request->input('store_id');
        $region          = $request->input('region');
        $excludeDelivery = $request->input('exclude_delivery');
        $promotionFilter = $request->input('promotion_filter');
        $priceMin        = ($request->input('price_min') !== null && $request->input('price_min') !== '') ? (float) $request->input('price_min') : null;
        $priceMax        = ($request->input('price_max') !== null && $request->input('price_max') !== '') ? (float) $request->input('price_max') : null;

        $excludedTexts = [];
        if ($excludeDelivery) {
            $excludedTexts = json_decode($excludeDelivery, true) ?? [];
        }

        // Determine export mode: SKU list or Category
        $exportMode = $skuListJson ? 'sku' : 'category';

        $category = null;
        $categoryIds = null;

        if ($exportMode === 'sku') {
            $skuList = json_decode($skuListJson, true) ?? [];
            if (empty($skuList)) {
                abort(400, 'SKU list is empty');
            }
        } else {
            if (!$categorySlug && !$region) {
                abort(400, 'Category or region is required');
            }
            if ($categorySlug) {
                $category    = Category::whereSlug($categorySlug)->firstOrFail();
                $categoryIds = $this->getCategoryIdsWithChildren($category);
            }
        }

        $currency = Currency::where('code', $currencyCode)->where('status', 1)->firstOrFail();

        $rate     = (float) $currency->exchange_rate;
        $decimals = (int)   $currency->no_of_decimal;
        $decSep   = $currency->decimal_separator   === 'comma' ? ',' : '.';
        $thouSep  = $currency->thousands_separator === 'comma' ? ',' : '.';

        $tsvHeaders = ['id', 'title', 'description', 'link', 'image_link', 'availability', 'price', 'sale_price', 'brand', 'condition'];

        // Write to temp file so we never buffer all rows in RAM
        $tempFile = tempnam(sys_get_temp_dir(), 'feed_tsv_');
        $handle   = fopen($tempFile, 'w');

        fwrite($handle, implode("\t", $tsvHeaders) . "\n");

        $query = Product::with(['product_thumbnail:id,image_url', 'brand:id,name'])
            ->select(['id','sku','name','description','slug','price','sale_price','stock_status','product_thumbnail_id','brand_id'])
            ->where('status', 1)
            ->where('is_approved', 1);

        // Apply mode-specific filter
        if ($exportMode === 'sku') {
            $query->whereIn('sku', $skuList);
        } elseif ($categoryIds) {
            $query->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds));
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($region) {
            $query = $this->applyRegionFilterEloquent($query, $region);
        }

        if (!empty($excludedTexts)) {
            $query->where(function ($q) use ($excludedTexts) {
                $q->whereNotIn('estimated_delivery_text', $excludedTexts)
                  ->orWhereNull('estimated_delivery_text');
            });
        }

        if ($promotionFilter) {
            $query = $this->applyPromotionFilterEloquent($query, $promotionFilter);
        }

        // Price range filter (applies to the effective price: sale_price if set, else price)
        if ($priceMin !== null) {
            $query->where(function ($q) use ($priceMin) {
                $q->where(function ($sq) use ($priceMin) {
                    $sq->where('sale_price', '>', 0)->where('sale_price', '>=', $priceMin);
                })->orWhere(function ($sq) use ($priceMin) {
                    $sq->where(function ($i) { $i->whereNull('sale_price')->orWhere('sale_price', 0); });
                    $sq->where('price', '>=', $priceMin);
                });
            });
        }
        if ($priceMax !== null) {
            $query->where(function ($q) use ($priceMax) {
                $q->where(function ($sq) use ($priceMax) {
                    $sq->where('sale_price', '>', 0)->where('sale_price', '<=', $priceMax);
                })->orWhere(function ($sq) use ($priceMax) {
                    $sq->where(function ($i) { $i->whereNull('sale_price')->orWhere('sale_price', 0); });
                    $sq->where('price', '<=', $priceMax);
                });
            });
        }

        $query->orderBy('id')->chunkById(2000, function ($products) use ($handle, $rate, $decimals, $decSep, $thouSep, $currencyCode) {
            foreach ($products as $product) {
                fwrite($handle, $this->formatProductTsv($product, $rate, $decimals, $decSep, $thouSep, $currencyCode));
            }
            unset($products);
            gc_collect_cycles(); // Force garbage collection
        });

        fclose($handle);

        $regionSuffix = $region ? "-{$region}" : '';
        if ($exportMode === 'sku') {
            $filename = "product-feed-skus-{$currencyCode}{$regionSuffix}.tsv";
        } elseif ($categorySlug) {
            $filename = "product-feed-{$categorySlug}-{$currencyCode}{$regionSuffix}.tsv";
        } else {
            $filename = "product-feed-all-{$currencyCode}{$regionSuffix}.tsv";
        }

        return response()->download($tempFile, $filename, [
            'Content-Type'        => 'text/tab-separated-values; charset=UTF-8',
            'Cache-Control'       => 'no-cache, must-revalidate',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->deleteFileAfterSend(true);
    }

    /**
     * Direct download CSV — chunked, comma-delimited format
     */
    public function downloadCsv(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M'); // Increase to 1GB for large exports
        ini_set('max_execution_time', '600'); // 10 minutes max

        // Disable query log to reduce memory usage
        \DB::connection()->disableQueryLog();

        $categorySlug    = $request->input('category');
        $skuListJson     = $request->input('sku_list');
        $currencyCode    = $request->input('currency');
        $storeId         = $request->input('store_id');
        $region          = $request->input('region');
        $excludeDelivery = $request->input('exclude_delivery');
        $promotionFilter = $request->input('promotion_filter');
        $priceMin        = ($request->input('price_min') !== null && $request->input('price_min') !== '') ? (float) $request->input('price_min') : null;
        $priceMax        = ($request->input('price_max') !== null && $request->input('price_max') !== '') ? (float) $request->input('price_max') : null;

        $excludedTexts = [];
        if ($excludeDelivery) {
            $excludedTexts = json_decode($excludeDelivery, true) ?? [];
        }

        // Determine export mode: SKU list or Category
        $exportMode = $skuListJson ? 'sku' : 'category';

        $category = null;
        $categoryIds = null;

        if ($exportMode === 'sku') {
            $skuList = json_decode($skuListJson, true) ?? [];
            if (empty($skuList)) {
                abort(400, 'SKU list is empty');
            }
        } else {
            if (!$categorySlug && !$region) {
                abort(400, 'Category or region is required');
            }
            if ($categorySlug) {
                $category    = Category::whereSlug($categorySlug)->firstOrFail();
                $categoryIds = $this->getCategoryIdsWithChildren($category);
            }
        }

        $currency = Currency::where('code', $currencyCode)->where('status', 1)->firstOrFail();

        $rate     = (float) $currency->exchange_rate;
        $decimals = (int)   $currency->no_of_decimal;
        $decSep   = $currency->decimal_separator   === 'comma' ? ',' : '.';
        $thouSep  = $currency->thousands_separator === 'comma' ? ',' : '.';

        $csvHeaders = ['id', 'title', 'description', 'link', 'image_link', 'availability', 'price', 'sale_price', 'brand', 'condition'];

        // Write to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'feed_csv_');
        $handle   = fopen($tempFile, 'w');

        // Write CSV header with BOM for Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
        fwrite($handle, implode(",", array_map(function($h) { return '"' . $h . '"'; }, $csvHeaders)) . "\n");

        $query = Product::with(['product_thumbnail:id,image_url', 'brand:id,name'])
            ->select(['id','sku','name','description','slug','price','sale_price','stock_status','product_thumbnail_id','brand_id'])
            ->where('status', 1)
            ->where('is_approved', 1);

        // Apply mode-specific filter
        if ($exportMode === 'sku') {
            $query->whereIn('sku', $skuList);
        } elseif ($categoryIds) {
            $query->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds));
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($region) {
            $query = $this->applyRegionFilterEloquent($query, $region);
        }

        if (!empty($excludedTexts)) {
            $query->where(function ($q) use ($excludedTexts) {
                $q->whereNotIn('estimated_delivery_text', $excludedTexts)
                  ->orWhereNull('estimated_delivery_text');
            });
        }

        if ($promotionFilter) {
            $query = $this->applyPromotionFilterEloquent($query, $promotionFilter);
        }

        // Price range filter
        if ($priceMin !== null) {
            $query->where(function ($q) use ($priceMin) {
                $q->where(function ($sq) use ($priceMin) {
                    $sq->where('sale_price', '>', 0)->where('sale_price', '>=', $priceMin);
                })->orWhere(function ($sq) use ($priceMin) {
                    $sq->where(function ($i) { $i->whereNull('sale_price')->orWhere('sale_price', 0); });
                    $sq->where('price', '>=', $priceMin);
                });
            });
        }
        if ($priceMax !== null) {
            $query->where(function ($q) use ($priceMax) {
                $q->where(function ($sq) use ($priceMax) {
                    $sq->where('sale_price', '>', 0)->where('sale_price', '<=', $priceMax);
                })->orWhere(function ($sq) use ($priceMax) {
                    $sq->where(function ($i) { $i->whereNull('sale_price')->orWhere('sale_price', 0); });
                    $sq->where('price', '<=', $priceMax);
                });
            });
        }

        $query->orderBy('id')->chunkById(2000, function ($products) use ($handle, $rate, $decimals, $decSep, $thouSep, $currencyCode) {
            foreach ($products as $product) {
                fwrite($handle, $this->formatProductCsv($product, $rate, $decimals, $decSep, $thouSep, $currencyCode));
            }
            unset($products);
            gc_collect_cycles(); // Force garbage collection
        });

        fclose($handle);

        $regionSuffix = $region ? "-{$region}" : '';
        if ($exportMode === 'sku') {
            $filename = "product-feed-skus-{$currencyCode}{$regionSuffix}.csv";
        } elseif ($categorySlug) {
            $filename = "product-feed-{$categorySlug}-{$currencyCode}{$regionSuffix}.csv";
        } else {
            $filename = "product-feed-all-{$currencyCode}{$regionSuffix}.csv";
        }

        return response()->download($tempFile, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Cache-Control'       => 'no-cache, must-revalidate',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ])->deleteFileAfterSend(true);
    }

    /**
     * Download temporary file
     */
    public function downloadTemp(Request $request)
    {
        $file = $request->query('file');
        $name = $request->query('name', 'download.zip');

        // Try temp directory first (for async exports)
        $tempFile = storage_path('app/temp/' . basename($file));

        // Fall back to system temp directory (for stream exports)
        if (!file_exists($tempFile)) {
            $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($file);
        }

        if (!file_exists($tempFile)) {
            abort(404, 'File not found');
        }

        // Determine correct Content-Type from filename extension
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $contentType = match ($ext) {
            'xml'  => 'application/xml; charset=UTF-8',
            'tsv'  => 'text/tab-separated-values; charset=UTF-8',
            'zip'  => 'application/zip',
            default => 'application/octet-stream',
        };

        return response()->download($tempFile, $name, [
            'Content-Type'        => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Cache-Control'       => 'no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ])->deleteFileAfterSend(true);
    }
}
