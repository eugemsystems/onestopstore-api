<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\ElasticsearchService;
use Illuminate\Support\Facades\Log;

/**
 * ProductObserver
 *
 * Automatically handles Elasticsearch indexing and cache clearing for products.
 *
 * ✅ Indexes products when approved AND active (status=1, is_approved=1)
 * ✅ Removes products from index when disapproved OR deactivated
 * ✅ Clears product cache on every save
 *
 * Note: Bulk operations using ->update() bypass this observer for performance.
 * Bulk operations handle Elasticsearch manually in AdminVendorApplicationController.
 */
class ProductObserver
{
    /**
     * Set to true during fast/bulk imports to suppress activity logging.
     * Call ProductObserver::$skipLogging = true before import, false after.
     */
    public static bool $skipLogging = false;

    /**
     * Handle the Product "saved" event.
     * This fires after both create and update operations on individual products.
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    public function saved(Product $product)
    {
        // Only index if product is approved AND active
        if ($product->status == 1 && $product->is_approved == 1) {
            $this->indexToElasticsearch($product);
        } else {
            $this->removeFromElasticsearch($product);
        }

        // Clear product cache
        $this->clearProductCache($product);

        // Audit log (skip during bulk/fast imports)
        // IMPORTANT: Run inside DB::afterCommit() to avoid poisoning the open transaction.
        // ActivityLog::create() does a DB INSERT. If it fails and the exception is swallowed
        // by catch(\Throwable), PostgreSQL still marks the transaction as aborted — causing
        // SQLSTATE[25P02] on every subsequent query (e.g. categories()->sync()).
        if (!self::$skipLogging) {
            // Capture values before the closure so we don't capture the whole model
            $productId   = $product->id;
            $productName = $product->name;
            $productSku  = $product->sku;
            $wasCreated  = $product->wasRecentlyCreated;
            $dirty       = $product->getDirty();
            $original    = $product->getOriginal();

            \Illuminate\Support\Facades\DB::afterCommit(function () use (
                $product, $productId, $productName, $productSku, $wasCreated, $dirty, $original
            ) {
                try {
                    $sku = $productSku ? ", SKU: {$productSku}" : '';
                    if ($wasCreated) {
                        ActivityLogger::make()->useLog('product')->event('created')->on($product)
                            ->log("Product '{$productName}' (#{$productId}{$sku}) created");
                    } else {
                        if (!empty($dirty)) {
                            $old = array_intersect_key($original, $dirty);
                            ActivityLogger::make()->useLog('product')->event('updated')->on($product)
                                ->withChanges($old, $dirty)
                                ->log("Product '{$productName}' (#{$productId}{$sku}) updated: " . implode(', ', array_keys($dirty)));
                        }
                    }
                } catch (\Throwable $e) {
                    // Log the failure so we know if activity logging is broken
                    \Illuminate\Support\Facades\Log::warning("ProductObserver: Failed to write activity log for product {$productId}: " . $e->getMessage());
                }
            });
        }
    }

    /**
     * Handle the Product "deleted" event.
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    public function deleted(Product $product)
    {
        $this->removeFromElasticsearch($product);
        $this->clearProductCache($product);

        if (!self::$skipLogging) {
            $productId   = $product->id;
            $productName = $product->name;
            $productSku  = $product->sku;

            \Illuminate\Support\Facades\DB::afterCommit(function () use ($product, $productId, $productName, $productSku) {
                try {
                    $sku = $productSku ? ", SKU: {$productSku}" : '';
                    ActivityLogger::make()->useLog('product')->event('deleted')->on($product)
                        ->log("Product '{$productName}' (#{$productId}{$sku}) deleted");
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("ProductObserver: Failed to write activity log for deleted product {$productId}: " . $e->getMessage());
                }
            });
        }
    }

    /**
     * Index product to Elasticsearch
     *
     * Uses dispatch_sync to run immediately but in isolation to prevent memory buildup
     * When editing products one by one during imports/operations
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    protected function indexToElasticsearch(Product $product)
    {
        try {
            // Check if Elasticsearch is enabled
            if (!env('USE_ELASTIC_SEARCH', false)) {
                return;
            }

            // Use DB::afterCommit to ensure transaction is complete before indexing
            \Illuminate\Support\Facades\DB::afterCommit(function() use ($product) {
                try {
                    // Reload product with ALL relationships to ensure complete data
                    // Must match relationships in Product::toSearchableArray()
                    $freshProduct = Product::with([
                        'variations',
                        'product_thumbnail',
                        'product_meta_image',
                        'product_galleries',
                        'attributes',
                        'categories',
                        'tags',
                        'brand',
                        'store',
                        'tax',
                    ])->find($product->id);

                    if ($freshProduct && $freshProduct->shouldBeSearchable()) {
                        // Use DIRECT Elasticsearch indexing instead of Scout's searchable()
                        // Scout's searchable() queues a job that reloads the model without relationships
                        // which causes the "categories [null]" error
                        $elasticsearchService = app(ElasticsearchService::class);
                        $client = $elasticsearchService::client();
                        $indexName = $elasticsearchService::indexName();

                        // Retry logic for production network issues
                        $maxRetries = 3;
                        $retryCount = 0;
                        $indexSuccess = false;
                        $lastError = null;

                        while ($retryCount < $maxRetries && !$indexSuccess) {
                            try {
                                $client->index([
                                    'index' => $indexName,
                                    'id' => $freshProduct->id,
                                    'body' => $freshProduct->toSearchableArray(), // Relationships already loaded
                                ]);
                                $indexSuccess = true;

                            } catch (\Exception $indexError) {
                                $lastError = $indexError;
                                $retryCount++;

                                if ($retryCount < $maxRetries) {
                                    // Exponential backoff: 100ms, 200ms, 400ms
                                    usleep(pow(2, $retryCount - 1) * 100000);
                                }
                            }
                        }

                        if (!$indexSuccess && $lastError) {
                            Log::error("ProductObserver: Failed to index product {$product->id} after {$maxRetries} retries", [
                                'error' => $lastError->getMessage(),
                                'trace' => $lastError->getTraceAsString()
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("ProductObserver: Failed to index product {$product->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            });

        } catch (\Exception $e) {
            // Log error but don't fail the save operation
            Log::error("ProductObserver: Failed to setup index for product {$product->id}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove product from Elasticsearch index
     *
     * Uses dispatch_sync to run after response to prevent blocking
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    protected function removeFromElasticsearch(Product $product)
    {
        try {
            // Check if Elasticsearch is enabled
            if (!env('USE_ELASTIC_SEARCH', false)) {
                return;
            }

            $productId = $product->id;

            // Remove after response to prevent blocking
            dispatch(function() use ($productId) {
                try {
                    $product = Product::find($productId);
                    if ($product) {
                        $product->unsearchable();
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to remove product {$productId} from Elasticsearch: " . $e->getMessage());
                }
            })->afterResponse();

        } catch (\Exception $e) {
            // Log error but don't fail the operation
            Log::warning("Failed to dispatch removal for product {$product->id} from Elasticsearch: " . $e->getMessage());
        }
    }

    /**
     * Clear product cache
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    protected function clearProductCache(Product $product)
    {
        try {
            // Clear individual product cache
            \Cache::forget("product_{$product->id}");

            // Clear related list caches
            \Cache::forget('products_list');
            \Cache::forget('featured_products');
            \Cache::forget('trending_products');

            // Clear brand-filtered caches if product has a brand
            if ($product->brand_id) {
                \Cache::forget("brand_{$product->brand_id}");

                // If using Redis with tags, clear brand-filtered product lists
                if (\Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                    \Cache::tags(['products', "brand_{$product->brand_id}"])->flush();
                }
            }

        } catch (\Exception $e) {
            Log::warning("Failed to clear cache for product {$product->id}: " . $e->getMessage());
        }
    }
}

