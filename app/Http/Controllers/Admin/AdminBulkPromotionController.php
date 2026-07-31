<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class AdminBulkPromotionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $action = $request->route()->getActionMethod();

            $permissionMap = [
                'index'          => 'product.edit',
                'apply'          => 'product.edit',
                'reset'          => 'product.edit',
                'applySkuPrices' => 'product.edit',
            ];

            if (isset($permissionMap[$action])) {
                $requiredPermission = $permissionMap[$action];

                if (!auth()->user()->can($requiredPermission)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Unauthorized. You need the '{$requiredPermission}' permission."
                        ], 403);
                    }
                    abort(403, "Unauthorized. You need the '{$requiredPermission}' permission.");
                }
            }

            return $next($request);
        });
    }

    /**
     * Display the bulk promotion page
     */
    public function index()
    {
        return view('admin.bulk-promotion.index');
    }

    /**
     * Apply promotion to multiple products
     */
    public function apply(Request $request)
    {
        $request->validate([
            'identifiers'      => 'nullable|string',
            'identifiers_file' => 'nullable|file|max:102400',
            'is_category_mode' => 'required|boolean',
            'percentage'       => 'required|numeric|min:0|max:100',
            'is_sale_enable'   => 'present',
            'sale_starts_at'   => 'nullable|date',
            'sale_expired_at'  => 'nullable|date',
        ]);

        try {
            // Increase execution time and memory for large operations
            set_time_limit(600); // 10 minutes
            ini_set('memory_limit', '512M');

            // Parse identifiers — from uploaded file or pasted text
            if ($request->hasFile('identifiers_file')) {
                $content = file_get_contents($request->file('identifiers_file')->getRealPath());
                $identifiers = array_values(array_filter(
                    array_map(function ($line) {
                        return trim(explode(',', trim($line), 2)[0]);
                    }, preg_split('/\r\n|\r|\n/', trim($content)))
                ));
            } else {
                $identifiers = array_values(array_filter(
                    array_map(function ($line) {
                        return trim(explode(',', trim($line), 2)[0]);
                    }, preg_split('/\r\n|\r|\n/', trim((string) $request->identifiers))),
                    fn($id) => !empty($id)
                ));
            }

            if (empty($identifiers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid identifiers provided.'
                ], 400);
            }

            $isCategoryMode = $request->is_category_mode;
            $productIds = collect();

            if ($isCategoryMode) {
                // Category mode: Find products by category slugs
                $categories = DB::table('categories')
                    ->whereIn('slug', $identifiers)
                    ->pluck('id');

                if ($categories->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No categories found with the provided slugs.'
                    ], 404);
                }

                // Find all products in these categories (optimized query)
                $productIds = DB::table('product_categories')
                    ->whereIn('category_id', $categories)
                    ->distinct()
                    ->pluck('product_id');

                if ($productIds->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No products found in the specified categories.'
                    ], 404);
                }

            } else {
                // Product mode: Find products by SKU or slug
                $productIds = Product::where(function($query) use ($identifiers) {
                    $query->whereIn('sku', $identifiers)
                          ->orWhereIn('slug', $identifiers);
                })->pluck('id');

                if ($productIds->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No products found with the provided SKUs/slugs.'
                    ], 404);
                }
            }

            $percentage = floatval($request->percentage);
            $isSaleEnable = in_array($request->is_sale_enable, [1, '1', true, 'true'], true) ? 1 : 0;
            $saleStartsAt  = $request->sale_starts_at  ? \Carbon\Carbon::parse($request->sale_starts_at)->startOfDay()->format('Y-m-d H:i:s')  : null;
            $saleExpiredAt = $request->sale_expired_at ? \Carbon\Carbon::parse($request->sale_expired_at)->endOfDay()->format('Y-m-d H:i:s') : null;

            // Process in chunks to avoid memory issues and timeouts
            $chunkSize = 500; // Process 500 products at a time
            $totalProducts = $productIds->count();
            $updatedCount = 0;
            $errors = [];
            $updates = [];
            $updatedProductIds = [];
            $chunks = $productIds->chunk($chunkSize);
            $chunkNumber = 0;
            $totalChunks = $chunks->count();

            foreach ($chunks as $chunk) {
                $chunkNumber++;

                DB::beginTransaction();

                try {
                    // Use direct DB update for better performance with large datasets
                    $chunkProductIds = $chunk->toArray();

                    // Get products with minimal data for price calculation
                    $products = DB::table('products')
                        ->whereIn('id', $chunkProductIds)
                        ->select('id', 'name', 'sku', 'price')
                        ->get();

                    foreach ($products as $product) {
                        $originalPrice = floatval($product->price);

                        if ($originalPrice <= 0) {
                            $errors[] = "Product '{$product->name}' (SKU: {$product->sku}) has invalid price.";
                            continue;
                        }

                        // Calculate new price
                        $newPrice = round($originalPrice * (1 + ($percentage / 100)), 2);
                        $salePrice = $originalPrice;

                        // Direct DB update for performance
                        DB::table('products')
                            ->where('id', $product->id)
                            ->update([
                                'price' => $newPrice,
                                'sale_price' => $salePrice,
                                'is_sale_enable' => $isSaleEnable,
                                'sale_starts_at' => $saleStartsAt,
                                'sale_expired_at' => $saleExpiredAt,
                                'updated_at' => now(),
                            ]);

                        // Update variations with same promotion logic as products
                        // 1. Get all variations for this product
                        $variations = DB::table('variations')
                            ->where('product_id', $product->id)
                            ->whereNull('deleted_at')
                            ->select('id', 'price')
                            ->get();

                        if ($variations->isNotEmpty()) {
                            foreach ($variations as $variation) {
                                $originalVariationPrice = floatval($variation->price);

                                // Only update if variation has a valid price
                                if ($originalVariationPrice > 0) {
                                    // Apply same percentage increase as product
                                    $newVariationPrice = round($originalVariationPrice * (1 + ($percentage / 100)), 2);
                                    $variationSalePrice = $originalVariationPrice;

                                    // Update variation with new prices AND ensure quantity = 999999
                                    DB::table('variations')
                                        ->where('id', $variation->id)
                                        ->update([
                                            'price' => $newVariationPrice,
                                            'sale_price' => $variationSalePrice,
                                            'quantity' => 999999,
                                            'updated_at' => now(),
                                        ]);
                                } else {
                                    // If no valid price, just ensure quantity is set
                                    DB::table('variations')
                                        ->where('id', $variation->id)
                                        ->update([
                                            'quantity' => 999999,
                                            'updated_at' => now(),
                                        ]);
                                }
                            }
                        }

                        // IMMEDIATELY clear cache for this product to prevent stale data
                        $this->clearSingleProductCache($product->id);

                        // Only store first 100 updates for display (avoid memory issues)
                        if (count($updates) < 100) {
                            $updates[] = [
                                'sku' => $product->sku,
                                'name' => $product->name,
                                'old_price' => $originalPrice,
                                'new_price' => $newPrice,
                                'sale_price' => $salePrice,
                                'discount' => round((($newPrice - $salePrice) / $newPrice) * 100, 2)
                            ];
                        }

                        $updatedProductIds[] = $product->id;
                        $updatedCount++;
                    }

                    DB::commit();

                    // Clear memory after each chunk
                    unset($products);
                    gc_collect_cycles();

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Bulk promotion: Chunk {$chunkNumber} failed", [
                        'error' => $e->getMessage(),
                        'chunk_size' => count($chunk)
                    ]);
                    $errors[] = "Chunk {$chunkNumber} failed: {$e->getMessage()}";
                }
            }

            // Post-processing: Clear caches and reindex in correct order

            // STEP 1: Bump cache version FIRST to invalidate all product caches immediately
            $this->bumpProductsCacheVersion();

            // STEP 2: Clear targeted product caches (redundant but ensures thorough clearing)
            $this->clearProductCachesOptimized($updatedProductIds);

            // STEP 3: Reindex to Elasticsearch (conditional based on env)
            // Check if auto-indexing is disabled via .env
            $autoIndexingDisabled = filter_var(env('DISABLE_AUTO_ELASTICSEARCH_INDEXING', false), FILTER_VALIDATE_BOOLEAN);

            if (!$autoIndexingDisabled) {
                // Auto-indexing is enabled - proceed with Elasticsearch reindexing
                $this->reindexProductsToElasticsearchOptimized($updatedProductIds);
            }

            // Build success message based on indexing status
            $cacheMessage = $autoIndexingDisabled
                ? "Cache cleared. Elasticsearch indexing SKIPPED (auto-indexing disabled). Manual reindex required."
                : "Cache clearing and reindexing in progress.";

            $successMessage = $isCategoryMode
                ? "Successfully updated {$updatedCount} product(s) from " . count($identifiers) . " category/categories. {$cacheMessage}"
                : "Successfully updated {$updatedCount} product(s). {$cacheMessage}";

            if (count($updates) == 100 && $updatedCount > 100) {
                $updates[] = [
                    'sku' => '...',
                    'name' => "And " . ($updatedCount - 100) . " more products",
                    'old_price' => 0,
                    'new_price' => 0,
                    'sale_price' => 0,
                    'discount' => 0
                ];
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data' => [
                    'mode' => $isCategoryMode ? 'category' : 'product',
                    'updated_count' => $updatedCount,
                    'total_found' => $totalProducts,
                    'identifiers_processed' => count($identifiers),
                    'updates' => $updates,
                    'errors' => $errors,
                    'processed_in_chunks' => $totalChunks,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk promotion error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while applying promotions: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Reset promotions for products identified by SKU (one per line).
     * Clears: sale_price, is_sale_enable, sale_starts_at, sale_expired_at
     */
    public function reset(Request $request)
    {
        $request->validate([
            'skus'      => 'nullable|string',
            'skus_file' => 'nullable|file|max:102400',
        ]);

        try {
            set_time_limit(300);

            if ($request->hasFile('skus_file')) {
                $content = file_get_contents($request->file('skus_file')->getRealPath());
                $skus = array_values(array_filter(
                    array_map(function ($line) {
                        return trim(explode(',', trim($line), 2)[0]);
                    }, preg_split('/\r\n|\r|\n/', trim($content)))
                ));
            } else {
                $skus = array_values(array_filter(
                    array_map(function ($line) {
                        return trim(explode(',', trim($line), 2)[0]);
                    }, preg_split('/\r\n|\r|\n/', trim((string) $request->skus))),
                    fn($s) => !empty($s)
                ));
            }

            if (empty($skus)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No SKUs provided.',
                ], 400);
            }

            $updated    = [];
            $notFound   = [];
            $updatedIds = [];

            // Chunk to avoid huge IN clauses
            foreach (array_chunk($skus, 500) as $chunk) {
                $products = DB::table('products')
                    ->whereIn('sku', $chunk)
                    ->select('id', 'name', 'sku')
                    ->get()
                    ->keyBy('sku');

                foreach ($chunk as $sku) {
                    if (!isset($products[$sku])) {
                        $notFound[] = $sku;
                        continue;
                    }

                    $product = $products[$sku];

                    DB::table('products')->where('id', $product->id)->update([
                        'sale_price'      => null,
                        'is_sale_enable'  => 0,
                        'sale_starts_at'  => null,
                        'sale_expired_at' => null,
                        'updated_at'      => now(),
                    ]);

                    $this->clearSingleProductCache($product->id);
                    $updated[]    = ['sku' => $sku, 'name' => $product->name];
                    $updatedIds[] = $product->id;
                }
            }

            // Bump cache version
            $this->bumpProductsCacheVersion();

            // Reindex if ES enabled
            if (!empty($updatedIds)) {
                $autoIndexingDisabled = filter_var(env('DISABLE_AUTO_ELASTICSEARCH_INDEXING', false), FILTER_VALIDATE_BOOLEAN);
                if (!$autoIndexingDisabled) {
                    $this->reindexProductsToElasticsearchOptimized($updatedIds);
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($updated) . ' product(s) had their promotions reset.',
                'data'    => [
                    'updated_count' => count($updated),
                    'not_found_count' => count($notFound),
                    'updated'  => $updated,
                    'not_found' => $notFound,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk promotion reset error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Apply explicit sale prices from a sku,sale_price CSV paste.
     *
     * Format of sku_prices textarea (one per line): sku,sale_price
     *
     * Logic:
     *  - If sale_price > current DB price  → inflate DB price by percentage, set sale_price to provided value.
     *  - If sale_price ≤ current DB price  → keep DB price, just set sale_price to provided value.
     */
    public function applySkuPrices(Request $request)
    {
        $request->validate([
            'sku_prices'      => 'nullable|string',
            'sku_prices_file' => 'nullable|file|max:102400',
            'percentage'      => 'required|numeric|min:0|max:500',
            'is_sale_enable'  => 'present',
            'sale_starts_at'  => 'nullable|date',
            'sale_expired_at' => 'nullable|date',
        ]);

        try {
            set_time_limit(600);
            ini_set('memory_limit', '512M');

            // Parse "sku,sale_price" lines — from uploaded file or pasted text
            if ($request->hasFile('sku_prices_file')) {
                $content = file_get_contents($request->file('sku_prices_file')->getRealPath());
                $lines = array_filter(
                    array_map('trim', preg_split('/\r\n|\r|\n/', trim($content)))
                );
            } else {
                $lines = array_filter(
                    array_map('trim', explode("\n", (string) $request->sku_prices)),
                    fn($l) => !empty($l)
                );
            }

            if (empty($lines)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid sku,sale_price entries provided.',
                ], 400);
            }

            $skuPriceMap = [];
            $parseErrors = [];

            foreach ($lines as $line) {
                $parts = explode(',', $line, 2);
                if (count($parts) !== 2) {
                    $parseErrors[] = "Invalid format (expected sku,sale_price): {$line}";
                    continue;
                }

                $sku        = trim($parts[0]);
                $salePrice  = trim($parts[1]);

                if (!is_numeric($salePrice) || floatval($salePrice) < 0) {
                    $parseErrors[] = "Invalid price for SKU '{$sku}': {$salePrice}";
                    continue;
                }

                $skuPriceMap[$sku] = floatval($salePrice);
            }

            if (empty($skuPriceMap)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid sku,sale_price entries could be parsed.',
                    'data'    => ['parse_errors' => $parseErrors],
                ], 400);
            }

            $percentage      = floatval($request->percentage);
            $isSaleEnable    = in_array($request->is_sale_enable, [1, '1', true, 'true'], true) ? 1 : 0;
            $saleStartsAt    = $request->sale_starts_at  ? \Carbon\Carbon::parse($request->sale_starts_at)->startOfDay()->format('Y-m-d H:i:s')  : null;
            $saleExpiredAt   = $request->sale_expired_at ? \Carbon\Carbon::parse($request->sale_expired_at)->endOfDay()->format('Y-m-d H:i:s') : null;

            $skus = array_keys($skuPriceMap);

            $products = DB::table('products')
                ->whereIn('sku', $skus)
                ->select('id', 'name', 'sku', 'price')
                ->get()
                ->keyBy('sku');

            $notFound    = array_values(array_diff($skus, $products->keys()->toArray()));
            $updates     = [];
            $errors      = array_merge($parseErrors);
            $updatedIds  = [];

            foreach ($skuPriceMap as $sku => $providedSalePrice) {
                if (!isset($products[$sku])) {
                    continue;
                }

                $product       = $products[$sku];
                $currentPrice  = floatval($product->price);

                if ($currentPrice <= 0) {
                    $errors[] = "SKU '{$sku}' has an invalid current price ({$currentPrice}).";
                    continue;
                }

                DB::beginTransaction();
                try {
                    if ($providedSalePrice > $currentPrice) {
                        // Inflate DB price by the given percentage
                        $newPrice = round($currentPrice * (1 + $percentage / 100), 2);

                        // If one inflation still leaves price <= sale_price,
                        // derive price directly from sale_price so it is always above it
                        if ($newPrice <= $providedSalePrice) {
                            $newPrice = round($providedSalePrice * (1 + $percentage / 100), 2);
                        }
                    } else {
                        $newPrice = $currentPrice;
                    }

                    DB::table('products')->where('id', $product->id)->update([
                        'price'          => $newPrice,
                        'sale_price'     => $providedSalePrice,
                        'is_sale_enable' => $isSaleEnable,
                        'sale_starts_at' => $saleStartsAt  ? \Carbon\Carbon::parse($saleStartsAt)->format('Y-m-d H:i:s')  : null,
                        'sale_expired_at'=> $saleExpiredAt ? \Carbon\Carbon::parse($saleExpiredAt)->format('Y-m-d H:i:s') : null,
                        'updated_at'     => now(),
                    ]);

                    DB::commit();

                    $this->clearSingleProductCache($product->id);
                    $updatedIds[] = $product->id;

                    $updates[] = [
                        'sku'           => $sku,
                        'name'          => $product->name,
                        'old_price'     => $currentPrice,
                        'new_price'     => $newPrice,
                        'sale_price'    => $providedSalePrice,
                        'price_changed' => $newPrice !== $currentPrice,
                        'discount'      => $newPrice > 0
                            ? round((($newPrice - $providedSalePrice) / $newPrice) * 100, 2)
                            : 0,
                    ];
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errors[] = "Failed to update SKU '{$sku}': {$e->getMessage()}";
                }
            }

            // Clear all product caches and reindex
            if (!empty($updatedIds)) {
                $this->bumpProductsCacheVersion();
                $this->clearProductCachesOptimized($updatedIds);

                $autoIndexingDisabled = filter_var(env('DISABLE_AUTO_ELASTICSEARCH_INDEXING', false), FILTER_VALIDATE_BOOLEAN);
                if (!$autoIndexingDisabled) {
                    $this->reindexProductsToElasticsearchOptimized($updatedIds);
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($updatedIds) . ' product(s) updated successfully.',
                'data'    => [
                    'updated_count'   => count($updatedIds),
                    'not_found_count' => count($notFound),
                    'updates'         => $updates,
                    'not_found'       => $notFound,
                    'errors'          => $errors,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('applySkuPrices error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear product-related caches for updated products (minimal approach).
     *
     * IMPORTANT: We do NOT clear all caches as that affects ALL users.
     * Products are served from Elasticsearch, so reindexing is the main cache invalidation.
     * This method only clears specific product caches if they exist.
     */
    private function clearProductCachesOptimized(array $productIds)
    {
        try {
            // Process in chunks to avoid memory issues
            $chunks = array_chunk($productIds, 100);
            $clearedCount = 0;

            foreach ($chunks as $chunk) {
                foreach ($chunk as $productId) {
                    // Only clear specific product caches that might exist
                    // These are common cache key patterns - adjust based on your actual usage
                    $keysToForget = [
                        "product:{$productId}",
                        "product_details:{$productId}",
                        "product_with_relations:{$productId}",
                        "api_product_{$productId}",
                    ];

                    foreach ($keysToForget as $key) {
                        if (Cache::has($key)) {
                            Cache::forget($key);
                            $clearedCount++;
                        }
                    }
                }

                // Small delay to prevent cache stampede
                if (count($productIds) > 1000) {
                    usleep(5000); // 5ms
                }
            }


        } catch (\Exception $e) {
            Log::error('Error clearing product caches', [
                'error' => $e->getMessage(),
                'product_count' => count($productIds)
            ]);
        }
    }

    /**
     * Bump products cache version to invalidate ALL product caches
     * This is the proper way to invalidate caches without Cache::flush()
     */
    private function bumpProductsCacheVersion(): void
    {
        try {
            $currentVersion = (int) Cache::get('products_cache_version', 1);
            $newVersion = $currentVersion + 1;
            Cache::put('products_cache_version', $newVersion, now()->addDays(365));
        } catch (\Exception $e) {
            Log::error('Error bumping products cache version', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reindex updated products to Elasticsearch (optimized for large batches)
     */
    private function reindexProductsToElasticsearchOptimized(array $productIds)
    {
        try {
            if (!env('USE_ELASTIC_SEARCH', false)) {
                return;
            }

            if (empty($productIds)) {
                return;
            }

            $totalProducts = count($productIds);

            // For large batches, use Artisan command with chunking
            if ($totalProducts > 100) {
                $chunks = array_chunk($productIds, 1000);

                foreach ($chunks as $chunkIndex => $chunk) {
                    $minId = min($chunk);
                    $maxId = max($chunk);

                    // Run reindex command in background for large operations
                    try {
                        Artisan::call('es:reindex-products', [
                            '--from-id' => $minId,
                            '--to-id' => $maxId,
                            '--chunk' => 500,
                        ]);

                    } catch (\Exception $e) {
                        Log::warning("Failed to reindex chunk", [
                            'error' => $e->getMessage(),
                            'chunk' => $chunkIndex + 1
                        ]);
                    }

                    // Small delay to prevent overloading
                    usleep(50000); // 50ms
                }
            } else {
                // For smaller batches, use DIRECT Elasticsearch indexing (same as Fast Import)
                // DON'T use Scout's searchable() - it reloads models without relationships!
                $indexed = 0;
                $failed = 0;

                try {
                    $elasticsearchService = app(\App\Services\ElasticsearchService::class);
                    $client = $elasticsearchService::client();
                    $indexName = $elasticsearchService::indexName();

                    // Fetch products with relationships BEFORE indexing (same as Fast Import)
                    $products = Product::with(['categories', 'tags', 'brand', 'product_thumbnail', 'product_meta_image', 'product_galleries'])
                        ->whereIn('id', $productIds)
                        ->where('status', 1)
                        ->where('is_approved', 1)
                        ->get();

                    $params = ['body' => []];

                    foreach ($products as $product) {
                        try {
                            $params['body'][] = [
                                'index' => [
                                    '_index' => $indexName,
                                    '_id' => $product->id,
                                ]
                            ];

                            // Use toSearchableArray() for consistent indexing
                            $params['body'][] = $product->toSearchableArray();
                            $indexed++;

                            // Send in batches of 100
                            if ($indexed % 100 === 0) {
                                // Retry logic for bulk indexing
                                $maxRetries = 3;
                                $retryCount = 0;
                                $bulkSuccess = false;

                                while ($retryCount < $maxRetries && !$bulkSuccess) {
                                    try {
                                        $client->bulk($params);
                                        $bulkSuccess = true;
                                    } catch (\Exception $bulkError) {
                                        $retryCount++;
                                        if ($retryCount >= $maxRetries) {
                                            Log::error("Bulk index failed after {$maxRetries} retries", [
                                                'error' => $bulkError->getMessage(),
                                                'products_in_batch' => count($params['body']) / 2
                                            ]);
                                            $failed += count($params['body']) / 2;
                                        } else {
                                            // Wait before retry (exponential backoff)
                                            usleep(pow(2, $retryCount) * 100000); // 200ms, 400ms, 800ms
                                        }
                                    }
                                }
                                $params = ['body' => []];
                            }
                        } catch (\Exception $e) {
                            $failed++;
                            Log::error("Failed to prepare product {$product->id} for bulk index", [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }

                    // Send remaining with retry logic
                    if (!empty($params['body'])) {
                        $maxRetries = 3;
                        $retryCount = 0;
                        $bulkSuccess = false;

                        while ($retryCount < $maxRetries && !$bulkSuccess) {
                            try {
                                $client->bulk($params);
                                $bulkSuccess = true;
                            } catch (\Exception $bulkError) {
                                $retryCount++;
                                if ($retryCount >= $maxRetries) {
                                    Log::error("Final bulk index failed after {$maxRetries} retries", [
                                        'error' => $bulkError->getMessage(),
                                        'products_in_batch' => count($params['body']) / 2
                                    ]);
                                    $failed += count($params['body']) / 2;
                                } else {
                                    usleep(pow(2, $retryCount) * 100000);
                                }
                            }
                        }
                    }

                } catch (\Exception $e) {
                    Log::error("Bulk promotion Elasticsearch indexing failed", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }


        } catch (\Exception $e) {
            Log::error('Error during Elasticsearch reindexing for bulk promotion', [
                'error' => $e->getMessage(),
                'product_count' => count($productIds)
            ]);
        }
    }

    /**
     * Clear cache for a single product immediately (called during update loop)
     * This ensures cache is cleared RIGHT AWAY when product is updated
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
            // Silent fail - don't stop bulk promotion if cache clearing fails
            Log::warning("Failed to clear cache for product {$productId} during bulk promotion", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear all product-related caches for updated products
     */
    private function clearProductCaches(array $productIds)
    {
        // Legacy method - redirect to optimized version
        $this->clearProductCachesOptimized($productIds);
    }

    /**
     * Reindex updated products to Elasticsearch
     */
    private function reindexProductsToElasticsearch(array $productIds)
    {
        // Legacy method - redirect to optimized version
        $this->reindexProductsToElasticsearchOptimized($productIds);
    }
}

