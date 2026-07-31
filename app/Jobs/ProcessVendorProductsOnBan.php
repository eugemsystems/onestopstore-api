<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\ApiCacheRefresher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessVendorProductsOnBan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $storeId;
    protected $shouldIndex;

    /**
     * Create a new job instance.
     *
     * @param int $storeId
     * @param bool $shouldIndex - true for unban (reindex), false for ban (remove from index)
     */
    public function __construct($storeId, $shouldIndex = false)
    {
        $this->storeId = $storeId;
        $this->shouldIndex = $shouldIndex;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $store = Store::with('products')->find($this->storeId);

            if (!$store) {
                Log::warning("Store {$this->storeId} not found for vendor products processing");
                return;
            }

            $products = $store->products;
            $urls = [];

            foreach ($products as $product) {
                // Handle Elasticsearch indexing/removal
                try {
                    if ($this->shouldIndex && $product->shouldBeSearchable()) {
                        // Reindex approved products when unbanning
                        $product->searchable();
                    } else {
                        // Remove from Elasticsearch when banning
                        $product->unsearchable();
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to update Elasticsearch for product {$product->id}: " . $e->getMessage());
                }

                // Collect cache URLs to refresh
                if ($product->slug) {
                    $urls[] = '/api/product/slug/' . rawurlencode($product->slug);
                }
                $urls[] = "/api/question-and-answer?product_id={$product->id}";

                // Category pages
                foreach ($product->categories()->pluck('slug') as $catSlug) {
                    $urls[] = "/api/product?category={$catSlug}&page=1";
                }
            }

            // Add common cache URLs
            $urls[] = '/api/product?status=1&trending=1&page=1';
            $urls[] = '/api/product?status=1&page=1';

            // Clear product caches
            if (!empty($urls)) {
                try {
                    ApiCacheRefresher::refresh(array_unique($urls));
                } catch (\Exception $e) {
                    Log::warning("Failed to clear product caches: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to process vendor products on ban/unban: " . $e->getMessage(), [
                'store_id' => $this->storeId,
                'should_index' => $this->shouldIndex,
                'exception' => $e
            ]);
            throw $e;
        }
    }
}

