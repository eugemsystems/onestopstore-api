<?php

namespace App\Jobs;

use App\Services\ElasticsearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IndexProductsToElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // Increased timeout to 30 minutes
    public $tries = 2;
    public $maxExceptions = 2;

    protected $productIds;
    protected $chunkIndex;
    protected $totalChunks;

    /**
     * Create a new job instance.
     */
    public function __construct(array $productIds, int $chunkIndex = 0, int $totalChunks = 1)
    {
        $this->productIds = $productIds;
        $this->chunkIndex = $chunkIndex;
        $this->totalChunks = $totalChunks;

        // Use 'elasticsearch' queue to separate from imports
        $this->onQueue('elasticsearch');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->productIds)) {
            return;
        }

        try {

            // Set reasonable memory limit
            ini_set('memory_limit', '768M');

            $elasticsearchService = app(ElasticsearchService::class);
            $client = $elasticsearchService::client();
            $indexName = $elasticsearchService::indexName();

            // Fetch products with relationships in smaller batches
            $products = \App\Models\Product::with([
                'categories',
                'tags',
                'brand',
                'product_thumbnail',
                'product_meta_image',
                'product_galleries',
                'variations',
                'attributes.attribute_values',
                'tax',
                'store',
            ])
                ->whereIn('id', $this->productIds)
                ->where('status', 1)
                ->where('is_approved', 1)
                ->get();

            if ($products->isEmpty()) {
                Log::warning('No approved/active products found to index', [
                    'product_ids' => $this->productIds,
                ]);
                return;
            }

            // Fetch reviews in bulk for better performance
            $reviewRows = DB::table('reviews as r')
                ->leftJoin('users as u', 'u.id', '=', 'r.consumer_id')
                ->whereIn('r.product_id', $this->productIds)
                ->whereNull('r.deleted_at')
                ->select(
                    'r.id', 'r.product_id', 'r.consumer_id', 'r.rating', 'r.description',
                    'r.created_at', 'r.updated_at',
                    'u.name as consumer_name', 'u.email as consumer_email',
                    'u.profile_image_id as consumer_profile_image_id'
                )
                ->orderBy('r.product_id')
                ->orderBy('r.created_at', 'desc')
                ->get()
                ->groupBy('product_id');

            // Prepare bulk request
            $params = ['body' => []];
            $bulkSize = 0;
            $maxBulkSize = 100; // Index 100 products at a time

            foreach ($products as $product) {
                try {
                    $productArray = $this->prepareProductForIndex($product, $reviewRows);

                    if ($productArray) {
                        $params['body'][] = [
                            'index' => [
                                '_index' => $indexName,
                                '_id' => $product->id,
                            ]
                        ];
                        $params['body'][] = $productArray;
                        $bulkSize++;

                        // Send bulk request when we hit the max size
                        if ($bulkSize >= $maxBulkSize) {
                            $this->sendBulkRequest($client, $params);
                            $params = ['body' => []];
                            $bulkSize = 0;

                            // Small delay to prevent overwhelming ES
                            usleep(100000); // 100ms
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to prepare product for indexing', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // Send remaining products
            if ($bulkSize > 0) {
                $this->sendBulkRequest($client, $params);
            }

            // Clear memory
            unset($products, $reviewRows, $params);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

        } catch (\Throwable $e) {
            Log::error('Elasticsearch indexing job failed', [
                'chunk' => $this->chunkIndex + 1,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Prepare product data for Elasticsearch
     */
    private function prepareProductForIndex($product, $reviewRows): array
    {
        // Simplified version - adapt from your existing toElasticsearch() method
        $reviews = $reviewRows->get($product->id, collect())->take(1000)->map(function($r) {
            $cleanDescription = null;
            if ($r->description) {
                $cleanDescription = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $r->description);
                $cleanDescription = preg_replace('/\s+/', ' ', $cleanDescription);
                $cleanDescription = trim($cleanDescription);
                if ($cleanDescription === '') {
                    $cleanDescription = null;
                }
            }

            return [
                'consumer_id' => $r->consumer_id,
                'consumer_name' => $r->consumer_name,
                'rating' => (int) $r->rating,
                'description' => $cleanDescription,
                'created_at' => $r->created_at,
            ];
        })->values()->toArray();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => (float) $product->price,
            'sale_price' => $product->sale_price ? (float) $product->sale_price : null,
            'stock_status' => $product->stock_status,
            'categories' => $product->categories->pluck('name')->toArray(),
            'brand' => $product->brand?->name,
            'tags' => $product->tags->pluck('name')->toArray(),
            'reviews' => $reviews,
            'average_rating' => $product->reviews_avg_rating ?? 0,
            'reviews_count' => $product->reviews_count ?? 0,
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Send bulk request to Elasticsearch
     */
    private function sendBulkRequest($client, array $params): void
    {
        try {
            $response = $client->bulk($params);

            if (!empty($response['errors'])) {
                Log::warning('Elasticsearch bulk indexing had errors', [
                    'errors' => $response['items'],
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Elasticsearch bulk request failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
