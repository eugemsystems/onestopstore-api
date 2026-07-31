<?php

namespace App\Jobs;

use App\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class ProcessFastImportChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes per chunk
    public $tries = 3;
    public $maxExceptions = 3;

    protected $importJobId;
    protected $filePath;
    protected $chunkIndex;
    protected $totalChunks;
    protected $productsChunk;
    protected $percentage;
    protected $todaysRate;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $importJobId,
        string $filePath,
        int $chunkIndex,
        int $totalChunks,
        array $productsChunk,
        float $percentage,
        float $todaysRate
    ) {
        $this->importJobId = $importJobId;
        $this->filePath = $filePath;
        $this->chunkIndex = $chunkIndex;
        $this->totalChunks = $totalChunks;
        $this->productsChunk = $productsChunk;
        $this->percentage = $percentage;
        $this->todaysRate = $todaysRate;

        // Use 'import' queue for Horizon
        $this->onQueue('import');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importJob = ImportJob::find($this->importJobId);

        if (!$importJob) {
            Log::error('Import job not found', ['id' => $this->importJobId]);
            return;
        }

        try {

            // Use ImportFastController's batch processing logic
            $controller = app(\App\Http\Controllers\ImportFastController::class);
            $result = $controller->processProductBatchFromJob(
                $this->productsChunk,
                $this->percentage,
                $this->todaysRate
            );

            // Update import job progress
            $importJob->increment('processed_items', $result['processed']);
            $importJob->increment('updated_items', $result['created']);
            $importJob->increment('skipped_items', $result['skipped']);

            // If this is the last chunk, mark as completed and check if batch is done
            if ($this->chunkIndex === $this->totalChunks - 1) {
                $importJob->markAsCompleted();

                // Check if ALL files in the batch are completed
                $batchId = $importJob->batch_id;
                $allJobsInBatch = ImportJob::where('batch_id', $batchId)->get();
                $allCompleted = $allJobsInBatch->every(function ($job) {
                    return in_array($job->status, ['completed', 'completed_with_errors', 'failed']);
                });

                // Only trigger post-processing if ALL jobs in batch are done
                if ($allCompleted) {

                    // Add all job details to batch stats for the email
                    $batchStats = [
                        'jobs' => $allJobsInBatch->map(function ($job) {
                            return [
                                'filename' => $job->filename,
                                'total_items' => $job->total_items,
                                'processed_items' => $job->processed_items,
                                'updated_items' => $job->updated_items,
                                'skipped_items' => $job->skipped_items,
                                'status' => $job->status,
                            ];
                        })->toArray(),
                    ];

                    // Trigger cache clear, Elasticsearch indexing, and email notification
                    $controller->postProcessImport($importJob->id);
                }
            }

        } catch (\Throwable $e) {
            $importJob->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Process a chunk of products (copied from ImportFastController logic)
     */
    private function processProductsChunk(array $productsArr): array
    {
        // Set reasonable memory limit for chunk processing
        ini_set('memory_limit', '512M');

        $now = now()->toDateTimeString();
        $productsData = [];
        $attachmentsData = [];
        $variationsData = [];
        $productImages = [];
        $thumbnailUpdates = [];
        $seenProducts = [];
        $processed = 0;
        $created = 0;
        $skipped = 0;

        foreach ($productsArr as $p) {
            try {
                $core = $p['core'] ?? [];
                $buybox = $p['buybox']['items'][0] ?? [];

                if (empty($core['id']) || empty($core['title'])) {
                    $skipped++;
                    continue;
                }

                $sku = $core['id'];

                if (isset($seenProducts[$sku])) {
                    $skipped++;
                    continue;
                }

                $seenProducts[$sku] = true;

                // Prepare product data (simplified version)
                $productData = $this->prepareProductData($p, $now);

                if ($productData) {
                    $productsData[] = $productData;
                    $processed++;
                }

            } catch (\Throwable $e) {
                Log::warning('Failed to process product in chunk', [
                    'sku' => $core['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                $skipped++;
                continue;
            }
        }

        // Bulk upsert products in smaller batches
        if (!empty($productsData)) {
            $created += $this->bulkUpsertProducts($productsData);
        }

        return [
            'processed' => $processed,
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    /**
     * Prepare product data from JSON
     */
    private function prepareProductData(array $p, string $now): ?array
    {
        $core = $p['core'] ?? [];
        $buybox = $p['buybox']['items'][0] ?? [];
        $desc = $p['description']['html'] ?? '';

        $sku = $core['id'];
        $name = $core['title'];
        $slug = !empty($core['slug']) ? $core['slug'] : \Illuminate\Support\Str::slug($name);
        $shortDescription = $core['subtitle'] ?? strip_tags($desc);

        $rawPrice = $buybox['price'] ?? 0;
        $price = $this->convertPrice($rawPrice);
        $rawSale = $buybox['sale_price'] ?? null;
        $salePrice = $rawSale !== null ? $this->convertPrice($rawSale) : null;

        $variants = $p['variants']['selectors'] ?? [];
        $stockStatus = !empty($variants) ? 'in_stock' : (!empty($buybox['is_add_to_cart_available']) ? 'in_stock' : 'out_of_stock');

        return [
            'sku' => $sku,
            'name' => $name,
            'slug' => $slug,
            'short_description' => $shortDescription,
            'description' => $desc,
            'price' => $price,
            'sale_price' => $salePrice,
            'stock_status' => $stockStatus,
            'status' => 1,
            'is_approved' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * Convert price using percentage and rate
     */
    private function convertPrice(float $rawPrice): float
    {
        return round(($rawPrice * $this->percentage) / $this->todaysRate, 2);
    }

    /**
     * Bulk upsert products in smaller batches to avoid memory issues
     */
    private function bulkUpsertProducts(array $productsData): int
    {
        $created = 0;
        $batchSize = 100; // Smaller batch size for better memory management

        foreach (array_chunk($productsData, $batchSize) as $batch) {
            try {
                DB::table('products')->upsert(
                    $batch,
                    ['sku'], // Unique key
                    ['name', 'slug', 'short_description', 'description', 'price', 'sale_price', 'stock_status', 'updated_at']
                );
                $created += count($batch);
            } catch (\Throwable $e) {
                Log::error('Bulk upsert failed for batch', [
                    'batch_size' => count($batch),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $created;
    }
}
