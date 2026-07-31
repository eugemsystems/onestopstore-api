<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backfill product snapshot data into order_products rows that are missing it.
 *
 * This ensures historical orders have self-contained product info and are
 * resilient to future product edits/deletes.
 *
 * Run:  php artisan orders:backfill-snapshots
 */
class BackfillOrderProductSnapshots extends Command
{
    protected $signature = 'orders:backfill-snapshots
                            {--chunk=500 : Number of rows to process per batch}
                            {--dry-run : Show counts without writing anything}';

    protected $description = 'Backfill product snapshot fields (name, sku, slug, image, price) into order_products';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        // Count rows that need backfilling (have product_id but no snapshot)
        $totalWithProductId = DB::table('order_products')
            ->whereNull('product_name')
            ->whereNotNull('product_id')
            ->whereNull('deleted_at')
            ->count();

        // Count orphaned rows (product_id is NULL — product was hard-deleted before FK was removed)
        $totalOrphaned = DB::table('order_products')
            ->whereNull('product_id')
            ->whereNull('product_name')
            ->whereNull('deleted_at')
            ->count();

        $total = $totalWithProductId + $totalOrphaned;

        $this->info("=== Order Products Snapshot Backfill ===");
        $this->info("Rows with product_id needing backfill: {$totalWithProductId}");
        $this->info("Orphaned rows (NULL product_id):       {$totalOrphaned}");
        $this->info("Total to process:                      {$total}");
        $this->newLine();

        if ($total === 0) {
            $this->info('✅ All order_products already have snapshot data. Nothing to do.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Dry run — no changes written.');
            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;
        $errors  = 0;

        // ── Phase 1: Backfill rows that have a product_id ──────────────────
        if ($totalWithProductId > 0) {
            $this->info("Phase 1: Backfilling {$totalWithProductId} rows with valid product_id...");
            $bar = $this->output->createProgressBar($totalWithProductId);
            $bar->start();

            DB::table('order_products')
                ->whereNull('product_name')
                ->whereNotNull('product_id')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->chunk($chunk, function ($rows) use (&$updated, &$skipped, &$errors, $bar) {
                    // Collect unique product IDs
                    $productIds = $rows->pluck('product_id')->unique()->all();

                    // Fetch products (including soft-deleted) with thumbnails
                    $products = \App\Models\Product::withTrashed()
                        ->with('product_thumbnail')
                        ->whereIn('id', $productIds)
                        ->get()
                        ->keyBy('id');

                    foreach ($rows as $row) {
                        try {
                            $product = $products->get($row->product_id);

                            if (!$product) {
                                // Product was hard-deleted from the DB entirely
                                $skipped++;
                                $bar->advance();
                                continue;
                            }

                            // Use null-safe operator for thumbnail access
                            $imageUrl = $product->product_thumbnail?->image_url
                                ?? $product->product_thumbnail?->original_url
                                ?? null;

                            DB::table('order_products')
                                ->where('id', $row->id)
                                ->update([
                                    'product_name'       => $product->name,
                                    'product_sku'        => $product->sku,
                                    'product_slug'       => $product->slug,
                                    'product_price'      => $product->price,
                                    'product_sale_price' => $product->sale_price,
                                    'product_image_url'  => $imageUrl,
                                ]);

                            $updated++;
                        } catch (\Throwable $e) {
                            $errors++;
                            Log::warning('BackfillOrderProductSnapshots: row failed', [
                                'order_product_id' => $row->id,
                                'product_id' => $row->product_id,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        $bar->advance();
                    }
                });

            $bar->finish();
            $this->newLine();
            $this->info("Phase 1 complete. Updated: {$updated} | Skipped (product gone): {$skipped} | Errors: {$errors}");
        }

        // ── Phase 2: Report orphaned rows ──────────────────────────────────
        if ($totalOrphaned > 0) {
            $this->newLine();
            $this->warn("Phase 2: {$totalOrphaned} rows have NULL product_id (product was hard-deleted before FK constraints were removed).");
            $this->info("These rows still exist in order_products but cannot be automatically linked to a product.");
            $this->info("To review: SELECT op.id, op.order_id, op.quantity, op.single_price, op.subtotal, o.order_number");
            $this->info("           FROM order_products op JOIN orders o ON o.id = op.order_id");
            $this->info("           WHERE op.product_id IS NULL AND op.product_name IS NULL AND op.deleted_at IS NULL;");
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->info("Updated:  {$updated}");
        $this->info("Skipped:  {$skipped} (product hard-deleted from DB)");
        $this->info("Errors:   {$errors}");
        $this->info("Orphaned: {$totalOrphaned} (NULL product_id — needs manual review)");

        if ($errors > 0) {
            $this->warn("Check laravel.log for error details.");
        }

        return self::SUCCESS;
    }
}

