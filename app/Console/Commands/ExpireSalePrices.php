<?php

namespace App\Console\Commands;

use App\Services\ElasticsearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Clear sale prices on products whose sale window has closed,
 * then bump the product cache version and reindex affected products in Elasticsearch.
 *
 * Rules:
 *  - Only products with a non-null sale_expired_at are touched.
 *  - Products with sale_expired_at in the past AND still having a sale_price get cleared.
 *  - Products with is_sale_enable = 1 but NO sale_expired_at (perpetual/date-free sales) are NEVER touched.
 *
 * Schedule: hourly in routes/console.php
 */
class ExpireSalePrices extends Command
{
    protected $signature   = 'products:expire-sales {--dry-run : Show what would change without writing}';
    protected $description = 'Clear expired sale prices, bump product cache, and reindex affected products';

    public function handle(): int
    {
        $now = now();

        // ── 1. Identify affected products before touching anything ──────────────
        $affectedIds = DB::table('products')
            ->whereNotNull('sale_expired_at')
            ->where('sale_expired_at', '<', $now)
            ->whereNotNull('sale_price')
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();

        $count = count($affectedIds);

        $this->info('=== Expire Sale Prices ===');
        $this->info("Found {$count} product(s) with expired sale prices.");

        if ($count === 0) {
            $this->info('✅ Nothing to expire.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no changes written.');

            DB::table('products')
                ->whereIn('id', $affectedIds)
                ->select('id', 'name', 'sku', 'sale_price', 'sale_expired_at')
                ->orderBy('sale_expired_at')
                ->each(function ($p) {
                    $this->line("  id={$p->id}  sku={$p->sku}  sale_price={$p->sale_price}  expired={$p->sale_expired_at}");
                });

            return self::SUCCESS;
        }

        // ── 2. Clear expired sale prices ───────────────────────────────────────
        $updated = DB::table('products')
            ->whereIn('id', $affectedIds)
            ->update([
                'sale_price'     => null,
                'is_sale_enable' => 0,
                'updated_at'     => $now,
            ]);

        $this->info("✅ Cleared {$updated} expired sale price(s).");

        Log::info("ExpireSalePrices: cleared {$updated} expired sale(s).", [
            'product_ids' => $affectedIds,
            'run_at'      => $now->toDateTimeString(),
        ]);

        // ── 3. Bump products cache version ─────────────────────────────────────
        // This invalidates all cached product list/detail responses so the API
        // serves fresh data on the next request.
        try {
            $currentVersion = (int) Cache::get('products_cache_version', 1);
            $newVersion     = $currentVersion + 1;
            Cache::put('products_cache_version', $newVersion, now()->addDays(365));
            $this->info("Cache version bumped: v{$currentVersion} → v{$newVersion}");
        } catch (\Throwable $e) {
            $this->warn('Failed to bump product cache version: ' . $e->getMessage());
            Log::warning('ExpireSalePrices: cache bump failed', ['error' => $e->getMessage()]);
        }

        // ── 4. Reindex affected products in Elasticsearch ──────────────────────
        // We only reindex the products that changed — much faster than a full reindex.
        try {
            $client = ElasticsearchService::client();
            $index  = ElasticsearchService::indexName();

            $this->info("Reindexing {$count} affected product(s) in Elasticsearch...");

            // Fetch fresh data for the affected products
            $products = DB::table('products as p')
                ->leftJoin('attachments as a', 'a.id', '=', 'p.product_thumbnail_id')
                ->whereIn('p.id', $affectedIds)
                ->select([
                    'p.id', 'p.name', 'p.slug', 'p.sku', 'p.short_description', 'p.type',
                    'p.unit', 'p.weight', 'p.quantity', 'p.price', 'p.sale_price', 'p.discount',
                    'p.is_sale_enable', 'p.sale_starts_at', 'p.sale_expired_at',
                    'p.is_featured', 'p.is_trending', 'p.is_return', 'p.is_cod',
                    'p.is_free_shipping', 'p.is_external', 'p.is_random_related_products',
                    'p.shipping_days', 'p.stock_status', 'p.status', 'p.is_approved',
                    'p.is_gift_card', 'p.external_url', 'p.external_button_text',
                    'p.meta_title', 'p.estimated_delivery_text', 'p.return_policy_text',
                    'p.safe_checkout', 'p.secure_checkout', 'p.social_share',
                    'p.encourage_order', 'p.encourage_view',
                    'p.product_thumbnail_id', 'p.product_meta_image_id', 'p.size_chart_image_id',
                    'p.store_id', 'p.created_by_id', 'p.tax_id', 'p.brand_id', 'p.created_at',
                    'a.image_url as thumb_url',
                ])
                ->get();

            if ($products->isEmpty()) {
                $this->warn('No products found for reindexing (may have been deleted).');
                return self::SUCCESS;
            }

            $params = ['body' => []];

            foreach ($products as $row) {
                // Minimal document update — only update the price/sale fields.
                // Using ES partial update (doc) so we don't overwrite other fields like categories etc.
                $params['body'][] = [
                    'update' => [
                        '_index' => $index,
                        '_id'    => $row->id,
                    ],
                ];
                $params['body'][] = [
                    'doc' => [
                        'sale_price'     => null,
                        'is_sale_enable' => 0,
                        'sale_expired_at' => $row->sale_expired_at
                            ? str_replace(' ', 'T', $row->sale_expired_at) : null,
                        'layby_eligible' => (float)($row->price ?? 0) >= 100,
                    ],
                ];
            }

            $response = $client->bulk($params);

            if (isset($response['errors']) && $response['errors'] === true) {
                $errorCount = 0;
                foreach ($response['items'] as $item) {
                    if (isset($item['update']['error'])) {
                        $this->warn('ES error for product ' . $item['update']['_id'] . ': '
                            . json_encode($item['update']['error']));
                        $errorCount++;
                    }
                }
                $this->warn("{$errorCount} Elasticsearch update(s) had errors.");
            } else {
                $this->info("✅ Elasticsearch reindex complete for {$count} product(s).");
            }

            // Refresh index so changes are immediately searchable
            $client->indices()->refresh(['index' => $index]);

        } catch (\Throwable $e) {
            // ES reindex failure is non-fatal — products are correct in the DB.
            $this->warn('Elasticsearch reindex failed (non-fatal): ' . $e->getMessage());
            Log::warning('ExpireSalePrices: ES reindex failed', [
                'error'       => $e->getMessage(),
                'product_ids' => $affectedIds,
            ]);
        }

        return self::SUCCESS;
    }
}
