<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Exception;

class ProcessBackOrderFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    protected $skus;
    protected $batchId;
    protected $fileName;
    protected $chunkIndex;

    /**
     * Create a new job instance.
     */
    public function __construct(array $skus, string $batchId, string $fileName, int $chunkIndex)
    {
        $this->skus = $skus;
        $this->batchId = $batchId;
        $this->fileName = $fileName;
        $this->chunkIndex = $chunkIndex;
    }

    /**
     * Execute the job - OPTIMIZED FOR SPEED
     */
    public function handle(): void
    {
        try {
            $skuCount = count($this->skus);
            $updated = 0;
            $failed = 0;
            $notFound = [];

            // OPTIMIZED: Direct database update using raw SQL for maximum performance
            // This is 10-100x faster than Eloquent for bulk operations
            try {
                $timestamp = now()->toDateTimeString();

                // Build the SQL with placeholders for SKUs
                $placeholders = implode(',', array_fill(0, $skuCount, '?'));

                // Execute raw update query - MUCH faster than Eloquent
                $updated = DB::update(
                    "UPDATE products
                     SET estimated_delivery_text = ?, updated_at = ?
                     WHERE sku IN ({$placeholders})
                     AND deleted_at IS NULL",
                    array_merge(
                        ['Available on Back Order', $timestamp],
                        $this->skus
                    )
                );

                $failed = $skuCount - $updated;

                // Only log not found SKUs if there are any (skip for performance)
                if ($failed > 0 && $skuCount <= 100) {
                    // For small batches, identify which SKUs were not found
                    $foundSkus = DB::table('products')
                        ->whereIn('sku', $this->skus)
                        ->whereNull('deleted_at')
                        ->pluck('sku')
                        ->toArray();

                    $notFound = array_diff($this->skus, $foundSkus);

                    if (!empty($notFound)) {
                        $this->storeNotFoundSkus($notFound);
                    }
                }

            } catch (Exception $e) {
                Log::error("Fast path failed, error: " . $e->getMessage());
                throw $e;
            }

            // Update batch status
            $this->updateBatchStatus($updated, $failed);

        } catch (Exception $e) {
            Log::error("ProcessBackOrderFile job failed: " . $e->getMessage(), [
                'batch_id' => $this->batchId,
                'file' => $this->fileName,
                'chunk' => $this->chunkIndex,
                'trace' => $e->getTraceAsString()
            ]);

            // Update failed count
            $this->updateBatchStatus(0, count($this->skus));

            throw $e;
        }
    }


    /**
     * Update batch processing status
     */
    private function updateBatchStatus(int $updated, int $failed): void
    {
        $cacheKey = "backorder_status_{$this->batchId}";

        $status = Cache::get($cacheKey, [
            'processed' => 0,
            'updated' => 0,
            'failed' => 0,
            'total' => 0,
            'status' => 'processing',
            'started_at' => now()->toDateTimeString()
        ]);

        $status['processed'] += count($this->skus);
        $status['updated'] += $updated;
        $status['failed'] += $failed;
        $status['last_updated'] = now()->toDateTimeString();

        // Calculate percentage
        if ($status['total'] > 0) {
            $status['percentage'] = round(($status['processed'] / $status['total']) * 100, 1);
        } else {
            $status['percentage'] = 0;
        }

        // Mark as complete if all processed
        if ($status['processed'] >= $status['total'] && $status['total'] > 0) {
            $status['status'] = 'completed';
            $status['completed_at'] = now()->toDateTimeString();

            // Calculate duration
            if (isset($status['started_at'])) {
                $start = \Carbon\Carbon::parse($status['started_at']);
                $end = now();
                $status['duration_seconds'] = $end->diffInSeconds($start);
            }
        }

        Cache::put($cacheKey, $status, now()->addHours(24));

        // Also update history
        $this->updateHistory($updated, $failed, $status['status']);
    }

    /**
     * Update processing history
     */
    private function updateHistory(int $updated, int $failed, string $status = 'processing'): void
    {
        $historyKey = 'backorder_history';
        $history = Cache::get($historyKey, []);

        $existingEntry = null;
        foreach ($history as $index => $entry) {
            if ($entry['batch_id'] === $this->batchId) {
                $existingEntry = $index;
                break;
            }
        }

        if ($existingEntry !== null) {
            $history[$existingEntry]['updated'] += $updated;
            $history[$existingEntry]['failed'] += $failed;
            $history[$existingEntry]['status'] = $status;
            $history[$existingEntry]['last_updated'] = now()->toDateTimeString();

            if ($status === 'completed') {
                $history[$existingEntry]['completed_at'] = now()->toDateTimeString();
            }
        } else {
            $history[] = [
                'batch_id' => $this->batchId,
                'file_name' => $this->fileName,
                'updated' => $updated,
                'failed' => $failed,
                'status' => $status,
                'started_at' => now()->toDateTimeString(),
                'last_updated' => now()->toDateTimeString()
            ];
        }

        // Keep only last 100 entries
        if (count($history) > 100) {
            array_shift($history);
        }

        Cache::put($historyKey, $history, now()->addDays(30));
    }

    /**
     * Store not found SKUs for reference
     */
    private function storeNotFoundSkus(array $notFoundSkus): void
    {
        $cacheKey = "backorder_notfound_{$this->batchId}";

        $existing = Cache::get($cacheKey, []);
        $merged = array_merge($existing, $notFoundSkus);

        Cache::put($cacheKey, $merged, now()->addDays(7));
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error("ProcessBackOrderFile job permanently failed", [
            'batch_id' => $this->batchId,
            'file' => $this->fileName,
            'chunk' => $this->chunkIndex,
            'error' => $exception->getMessage()
        ]);

        // Mark entire chunk as failed
        $this->updateBatchStatus(0, count($this->skus));
    }
}
