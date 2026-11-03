<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\CustomFeedDefinition;
use App\Models\DailyFeedRun;
use App\Models\DailyFeedSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\CurrencyDetectionService;
use App\Services\CustomFeedGenerator;

/**
 * Generates the 4 daily regional Google Merchant feeds and writes them to
 * storage/app/public/dailyfeeds/... (the 'public' disk — same place
 * feed:google-merchant and sitemaps:generate already write to), streamed
 * back out through a Laravel route rather than served as a raw static file
 * by nginx directly. In this deployment nginx and the laravel-api container
 * don't share a filesystem for anything written to public/ at runtime — the
 * existing /api/sitemaps/{path} route (see routes/api.php) exists for
 * exactly this reason, and public/dailyfeeds files 404'd at the webserver
 * before ever reaching PHP until this was changed to match that pattern.
 * URLs never change:
 *
 *   {APP_URL}/dailyfeeds/global/usd/global-usd-1.xml .. global-usd-{N}.xml
 *   {APP_URL}/dailyfeeds/global/zmw/global-zmw-1.xml .. global-zmw-{N}.xml
 *   {APP_URL}/dailyfeeds/zimbabwe/zimbabwe.xml
 *   {APP_URL}/dailyfeeds/zambia/zambia.xml
 *
 * "Global" feeds include every eligible product regardless of the
 * zambia_only/zimbabwe_only/sa_only region flags (broadest reach). The
 * Zimbabwe and Zambia feeds are strictly filtered to products explicitly
 * flagged zimbabwe_only / zambia_only respectively (region-exclusive
 * listings only, not "everything not excluded from that market").
 *
 * Large catalogs (into the millions) are handled via chunked cursor reads
 * straight into a streamed file write — nothing is held in memory — so a
 * single run comfortably covers a catalog far bigger than what currently
 * exists without needing a queue.
 *
 * Global is split into a FIXED number of files ({key}_shard_count setting,
 * default 20) rather than one giant one. At ~1.5M products a single file
 * was ~800MB, which reliably got cut off partway through Google's fetch
 * (nginx/PHP execution-time limits on a multi-minute transfer) — Google
 * would ingest a fraction of the catalog and stop. A FIXED shard count
 * (not "however many 100k-chunks are needed today") means the file/URL set
 * never changes as the catalog grows — only the per-file item count does —
 * so the shard URLs only need to be registered in Merchant Center once.
 * Products are distributed round-robin across shards in a single query
 * pass (not N separate passes) to keep this as cheap as the unsharded case.
 *
 * Schedule time is admin-configurable (see DailyFeedSetting::get('schedule_time')
 * and routes/console.php, which re-registers the schedule from that setting).
 */
class GenerateDailyRegionalFeeds extends Command
{
    protected $signature = 'feed:daily-regional
                            {--only=* : Restrict to specific file keys (global_usd, global_zmw, zimbabwe_usd, zambia_zmw, custom)}
                            {--batch= : Batch id to group this run\'s rows under (auto-generated if omitted)}';

    protected $description = 'Generate the 4 daily regional Google Merchant XML feeds (and all custom feed links) as static public files';

    private string $frontendUrl;
    private string $zmFrontendUrl;

    /** @var array<string,array{region:?string,currency:string,sharded:bool,path?:string,pathTemplate?:string}> */
    private array $fileDefinitions = [
        'global_usd' => ['region' => null, 'currency' => 'USD', 'sharded' => true, 'pathTemplate' => 'dailyfeeds/global/usd/global-usd-{n}.xml'],
        'global_zmw' => ['region' => null, 'currency' => 'ZMW', 'sharded' => true, 'pathTemplate' => 'dailyfeeds/global/zmw/global-zmw-{n}.xml'],
        'zimbabwe_usd' => ['region' => 'zimbabwe', 'currency' => 'USD', 'sharded' => false, 'path' => 'dailyfeeds/zimbabwe/zimbabwe.xml'],
        'zambia_zmw' => ['region' => 'zambia', 'currency' => 'ZMW', 'sharded' => false, 'path' => 'dailyfeeds/zambia/zambia.xml'],
    ];

    public function handle(): void
    {
        $rawFrontend = rtrim(env('FRONTEND_URL', 'https://onestopstore.co.zw/en'), '/');
        $frontendRoot = preg_replace('#/en$#', '', $rawFrontend);
        $this->frontendUrl = $frontendRoot . '/en';
        $this->zmFrontendUrl = $frontendRoot . '/zm';

        $only = $this->option('only');
        $batchId = $this->option('batch') ?: uniqid('daily_feed_', true);

        $keys = empty($only) ? array_keys($this->fileDefinitions) : array_intersect(array_keys($this->fileDefinitions), $only);
        $runCustom = empty($only) || in_array('custom', $only, true);

        foreach ($keys as $key) {
            $def = $this->fileDefinitions[$key];
            if ($def['sharded'] ?? false) {
                $this->generateShardedFile($key, $def, $batchId);
            } else {
                $this->generateFile($key, $def, $batchId);
            }
        }

        // Custom Feed Links (admin: Daily Regional Feeds → Custom Feed Links) ride on the same
        // schedule, so price/stock changes reach them without clicking "Run Now" on each one.
        if ($runCustom) {
            $this->regenerateCustomFeeds();
        }

        $this->info("Batch {$batchId} complete.");
    }

    /**
     * Rebuild every custom feed link. One feed failing never stops the others, and a feed that
     * is already being generated (e.g. an admin clicked "Run Now") is skipped, not raced.
     */
    private function regenerateCustomFeeds(): void
    {
        if (!Schema::hasTable('custom_feed_definitions')) {
            return;
        }

        $generator = app(CustomFeedGenerator::class);
        $total = CustomFeedDefinition::count();
        $this->line("[Custom feeds] regenerating {$total} feed(s)…");

        foreach (CustomFeedDefinition::orderBy('id')->cursor() as $feed) {
            $lock = Cache::lock("feed:custom:lock:{$feed->id}", 10800);
            if (!$lock->get()) {
                $this->warn("[Custom: {$feed->name}] skipped — already being generated.");
                continue;
            }

            try {
                $run = $generator->generate($feed);
                $this->line("[Custom: {$feed->name}] {$run->status} — {$run->records_written} products.");
            } catch (\Throwable $e) {
                Log::error('feed:daily-regional custom feed failed', ['feed' => $feed->slug, 'error' => $e->getMessage()]);
                $this->error("[Custom: {$feed->name}] failed: {$e->getMessage()}");
            } finally {
                $lock->release();
            }
        }
    }

    private function generateFile(string $key, array $def, string $batchId): void
    {
        $label = DailyFeedRun::LABELS[$key] ?? $key;
        $enabled = DailyFeedSetting::get("{$key}_enabled", '1') == '1';

        if (!$enabled) {
            DailyFeedRun::create([
                'file_key' => $key,
                'batch_id' => $batchId,
                'status' => 'skipped',
                'started_at' => now(),
                'finished_at' => now(),
                'duration_seconds' => 0,
                'error_message' => 'Feed disabled in Daily Feed settings.',
            ]);
            $this->line("[{$label}] skipped (disabled).");
            return;
        }

        // Guards against two invocations of the same file running at once —
        // e.g. the 4 AM scheduled run firing while an earlier "Run Now" click
        // is still mid-generation. withoutOverlapping() on the scheduler
        // entry only protects scheduler ticks against each other; it does
        // nothing for Run Now, which shells out directly and bypasses the
        // scheduler's own lock. Without this, two processes can both open
        // and rename the same .tmp file, producing
        // "rename(...): No such file or directory" when one process moves
        // the temp file out from under the other mid-write.
        $lock = Cache::lock("feed:daily-regional:lock:{$key}", 10800); // 3h safety ceiling
        if (!$lock->get()) {
            DailyFeedRun::create([
                'file_key' => $key,
                'batch_id' => $batchId,
                'status' => 'skipped',
                'started_at' => now(),
                'finished_at' => now(),
                'duration_seconds' => 0,
                'error_message' => 'Another run for this file was already in progress — skipped to avoid a race on the output file.',
            ]);
            $this->warn("[{$label}] skipped — another run for this file is already in progress.");
            Log::warning('feed:daily-regional skipped: already running', ['file_key' => $key, 'batch_id' => $batchId]);
            return;
        }

        $run = DailyFeedRun::create([
            'file_key' => $key,
            'batch_id' => $batchId,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $start = microtime(true);

        try {
            $currency = $def['currency'];
            $minPriceInput = (float) DailyFeedSetting::get("{$key}_min_price", 0);
            $limit = (int) DailyFeedSetting::get("{$key}_limit", 0);

            $minPriceUsd = $this->convertToUsd($minPriceInput, $currency);

            $relativePath = $def['path'];
            // storage/app/public (the 'public' disk), not public_path() — nginx
            // doesn't reliably see files written to public/ at runtime here (see
            // class docblock), so these are streamed back out via a Laravel
            // route (routes/web.php) reading from this same disk, same as the
            // existing /api/sitemaps/{path} route already does.
            $finalPath = storage_path('app/public/' . $relativePath);
            $tempPath = $finalPath . '.tmp';

            if (!is_dir(dirname($finalPath))) {
                mkdir(dirname($finalPath), 0755, true);
            }

            $fp = fopen($tempPath, 'w');
            if (!$fp) {
                throw new \RuntimeException("Cannot open {$tempPath} for writing.");
            }

            fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            fwrite($fp, '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n");
            fwrite($fp, "<channel>\n");
            fwrite($fp, "  <title>One Stop Store Products - {$this->cleanXml($label)}</title>\n");
            fwrite($fp, "  <link>{$this->frontendUrl}</link>\n");
            fwrite($fp, "  <description>Daily regional product feed - {$this->cleanXml($label)}</description>\n");

            $query = DB::table('products')
                ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
                ->leftJoin('attachments', 'products.product_thumbnail_id', '=', 'attachments.id')
                ->select([
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.short_description',
                    'products.description',
                    'products.sku',
                    'products.price',
                    'products.sale_price',
                    'products.is_sale_enable',
                    'products.stock_status',
                    'products.is_featured',
                    'product_brands.name as brand_name',
                    'attachments.image_url',
                ])
                ->where('products.status', 1)
                ->where('products.is_approved', 1)
                ->whereNull('products.deleted_at')
                ->whereNotNull('products.slug')
                ->whereNotNull('products.product_thumbnail_id')
                ->where('products.price', '>', 0);

            if ($minPriceUsd > 0) {
                $query->where('products.price', '>=', $minPriceUsd);
            }

            if ($def['region'] === 'zimbabwe') {
                // Only products explicitly flagged Zimbabwe-exclusive.
                $query->where('products.zimbabwe_only', true);
            } elseif ($def['region'] === 'zambia') {
                // Only products explicitly flagged Zambia-exclusive.
                $query->where('products.zambia_only', true);
            }
            // Global (region === null): no region restriction — broadest reach.

            $written = 0;
            // chunkById (keyset pagination: WHERE products.id > lastId) instead of
            // chunk (OFFSET/LIMIT) — with a catalog in the millions, OFFSET-based
            // paging makes every successive page rescan-and-discard everything
            // before it, so total cost grows quadratically with table size and a
            // run can take hours without ever failing. chunkById stays O(n).
            $query->chunkById(1000, function ($products) use ($fp, $currency, $limit, &$written) {
                foreach ($products as $product) {
                    if ($limit > 0 && $written >= $limit) {
                        return false;
                    }

                    $this->writeItem($fp, $product, $currency);
                    $written++;
                }

                if ($limit > 0 && $written >= $limit) {
                    return false;
                }
            }, 'products.id', 'id');

            fwrite($fp, "</channel>\n");
            fwrite($fp, "</rss>\n");
            fclose($fp);

            rename($tempPath, $finalPath);

            clearstatcache(true, $finalPath);
            $exists = file_exists($finalPath);
            $size = $exists ? filesize($finalPath) : 0;
            // A bare empty-channel feed is a couple hundred bytes — anything
            // smaller means the write was truncated or never completed.
            $verified = $exists && $size > 150;

            $duration = (int) round(microtime(true) - $start);

            $run->update([
                'status' => $verified ? 'success' : 'failed',
                'finished_at' => now(),
                'duration_seconds' => $duration,
                'records_written' => $written,
                'file_path' => $relativePath,
                'file_url' => url($relativePath),
                'file_verified' => $verified,
                'file_size_bytes' => $size,
                'min_price_filter' => $minPriceInput,
                'error_message' => $verified ? null : 'File missing or unexpectedly small after write.',
            ]);

            $this->info("[{$label}] {$written} products written, verified=" . ($verified ? 'yes' : 'NO') . ", {$duration}s -> {$relativePath}");
            Log::info('feed:daily-regional file generated', [
                'file_key' => $key,
                'batch_id' => $batchId,
                'written' => $written,
                'verified' => $verified,
                'duration_seconds' => $duration,
            ]);
        } catch (\Throwable $e) {
            if (isset($fp) && is_resource($fp)) {
                fclose($fp);
            }
            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }

            $duration = (int) round(microtime(true) - $start);
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => $duration,
                'error_message' => $e->getMessage(),
            ]);

            $this->error("[{$label}] failed: {$e->getMessage()}");
            Log::error('feed:daily-regional file failed', [
                'file_key' => $key,
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Same as generateFile(), but writes {n}_shard_count separate files in a
     * single query pass instead of one giant file — see class docblock for
     * why. Products are handed to shards round-robin as the cursor reads
     * them, so all N files fill up evenly without N separate table scans.
     */
    private function generateShardedFile(string $key, array $def, string $batchId): void
    {
        $label = DailyFeedRun::LABELS[$key] ?? $key;
        $enabled = DailyFeedSetting::get("{$key}_enabled", '1') == '1';

        if (!$enabled) {
            DailyFeedRun::create([
                'file_key' => $key,
                'batch_id' => $batchId,
                'status' => 'skipped',
                'started_at' => now(),
                'finished_at' => now(),
                'duration_seconds' => 0,
                'error_message' => 'Feed disabled in Daily Feed settings.',
            ]);
            $this->line("[{$label}] skipped (disabled).");
            return;
        }

        $lock = Cache::lock("feed:daily-regional:lock:{$key}", 10800); // 3h safety ceiling
        if (!$lock->get()) {
            DailyFeedRun::create([
                'file_key' => $key,
                'batch_id' => $batchId,
                'status' => 'skipped',
                'started_at' => now(),
                'finished_at' => now(),
                'duration_seconds' => 0,
                'error_message' => 'Another run for this file was already in progress — skipped to avoid a race on the output file.',
            ]);
            $this->warn("[{$label}] skipped — another run for this file is already in progress.");
            Log::warning('feed:daily-regional skipped: already running', ['file_key' => $key, 'batch_id' => $batchId]);
            return;
        }

        $run = DailyFeedRun::create([
            'file_key' => $key,
            'batch_id' => $batchId,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $start = microtime(true);
        $handles = [];
        $tempPaths = [];
        $finalPaths = [];

        try {
            $currency = $def['currency'];
            $minPriceInput = (float) DailyFeedSetting::get("{$key}_min_price", 0);
            $limit = (int) DailyFeedSetting::get("{$key}_limit", 0);
            $shardCount = max(1, (int) DailyFeedSetting::get("{$key}_shard_count", 20));

            $minPriceUsd = $this->convertToUsd($minPriceInput, $currency);

            for ($i = 1; $i <= $shardCount; $i++) {
                $relativePath = str_replace('{n}', (string) $i, $def['pathTemplate']);
                $finalPath = storage_path('app/public/' . $relativePath);
                $tempPath = $finalPath . '.tmp';

                if (!is_dir(dirname($finalPath))) {
                    mkdir(dirname($finalPath), 0755, true);
                }

                $fp = fopen($tempPath, 'w');
                if (!$fp) {
                    throw new \RuntimeException("Cannot open {$tempPath} for writing.");
                }

                fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
                fwrite($fp, '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n");
                fwrite($fp, "<channel>\n");
                fwrite($fp, "  <title>One Stop Store Products - {$this->cleanXml($label)} (part {$i}/{$shardCount})</title>\n");
                fwrite($fp, "  <link>{$this->frontendUrl}</link>\n");
                fwrite($fp, "  <description>Daily regional product feed - {$this->cleanXml($label)}, part {$i} of {$shardCount}</description>\n");

                $handles[$i] = $fp;
                $tempPaths[$i] = $tempPath;
                $finalPaths[$i] = $finalPath;
            }

            $query = DB::table('products')
                ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
                ->leftJoin('attachments', 'products.product_thumbnail_id', '=', 'attachments.id')
                ->select([
                    'products.id',
                    'products.name',
                    'products.slug',
                    'products.short_description',
                    'products.description',
                    'products.sku',
                    'products.price',
                    'products.sale_price',
                    'products.is_sale_enable',
                    'products.stock_status',
                    'products.is_featured',
                    'product_brands.name as brand_name',
                    'attachments.image_url',
                ])
                ->where('products.status', 1)
                ->where('products.is_approved', 1)
                ->whereNull('products.deleted_at')
                ->whereNotNull('products.slug')
                ->whereNotNull('products.product_thumbnail_id')
                ->where('products.price', '>', 0);

            if ($minPriceUsd > 0) {
                $query->where('products.price', '>=', $minPriceUsd);
            }
            // Global: no region restriction (region is always null here).

            $written = 0;
            $query->chunkById(1000, function ($products) use ($handles, $shardCount, $currency, $limit, &$written) {
                foreach ($products as $product) {
                    if ($limit > 0 && $written >= $limit) {
                        return false;
                    }

                    $shardIndex = ($written % $shardCount) + 1;
                    $this->writeItem($handles[$shardIndex], $product, $currency);
                    $written++;
                }

                if ($limit > 0 && $written >= $limit) {
                    return false;
                }
            }, 'products.id', 'id');

            $totalSize = 0;
            $allVerified = true;
            for ($i = 1; $i <= $shardCount; $i++) {
                fwrite($handles[$i], "</channel>\n");
                fwrite($handles[$i], "</rss>\n");
                fclose($handles[$i]);
                rename($tempPaths[$i], $finalPaths[$i]);

                clearstatcache(true, $finalPaths[$i]);
                $exists = file_exists($finalPaths[$i]);
                $size = $exists ? filesize($finalPaths[$i]) : 0;
                if (!$exists || $size <= 150) {
                    $allVerified = false;
                }
                $totalSize += $size;
            }

            $duration = (int) round(microtime(true) - $start);

            $run->update([
                'status' => $allVerified ? 'success' : 'failed',
                'finished_at' => now(),
                'duration_seconds' => $duration,
                'records_written' => $written,
                'file_path' => $def['pathTemplate'],
                'file_url' => url(str_replace('{n}', '1', $def['pathTemplate'])),
                'file_verified' => $allVerified,
                'file_size_bytes' => $totalSize,
                'shard_count' => $shardCount,
                'min_price_filter' => $minPriceInput,
                'error_message' => $allVerified ? null : 'One or more shard files missing or unexpectedly small after write.',
            ]);

            $this->info("[{$label}] {$written} products written across {$shardCount} files, verified=" . ($allVerified ? 'yes' : 'NO') . ", {$duration}s");
            Log::info('feed:daily-regional sharded file generated', [
                'file_key' => $key,
                'batch_id' => $batchId,
                'written' => $written,
                'shard_count' => $shardCount,
                'verified' => $allVerified,
                'duration_seconds' => $duration,
            ]);
        } catch (\Throwable $e) {
            foreach ($handles as $fp) {
                if (is_resource($fp)) {
                    fclose($fp);
                }
            }
            foreach ($tempPaths as $tempPath) {
                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }
            }

            $duration = (int) round(microtime(true) - $start);
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => $duration,
                'error_message' => $e->getMessage(),
            ]);

            $this->error("[{$label}] failed: {$e->getMessage()}");
            Log::error('feed:daily-regional sharded file failed', [
                'file_key' => $key,
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $lock->release();
        }
    }

    private function writeItem($fp, object $product, string $currency): void
    {
        $title = $this->cleanXml($product->name);
        // Decode entities (e.g. literal "&nbsp;" surviving strip_tags) and
        // truncate the RAW text first, then escape. Escaping before
        // truncating can slice a multi-character entity in half (e.g.
        // "&amp;" -> "&am"), producing an unterminated entity reference
        // that fails XML parsing ("EntityRef: expecting ';'").
        $description = html_entity_decode(
            strip_tags($product->short_description ?: $product->description ?: $product->name),
            ENT_QUOTES | ENT_HTML5
        );
        if (strlen($description) > 5000) {
            $description = substr($description, 0, 4997) . '...';
        }
        $description = $this->cleanXml($description);

        // Same product-link convention as feed:google-merchant: no locale prefix, no ?currency
        $link = \App\Helpers\Helpers::productPageUrl($product->slug);

        $rawPrice = (float) $product->price;
        $convertedPrice = $currency === 'USD' ? $rawPrice : CurrencyDetectionService::convertAmount($rawPrice, $currency);
        $price = number_format($convertedPrice, 2, '.', '') . " {$currency}";
        $availability = $product->stock_status === 'out_of_stock' ? 'out_of_stock' : 'in_stock';
        $brand = $this->cleanXml($product->brand_name ?: 'One Stop Store');
        $id = $product->sku ?: "RA-{$product->id}";

        fwrite($fp, "  <item>\n");
        fwrite($fp, "    <g:id>{$this->cleanXml($id)}</g:id>\n");
        fwrite($fp, "    <g:title>{$title}</g:title>\n");
        fwrite($fp, "    <g:description>{$description}</g:description>\n");
        fwrite($fp, "    <g:link>{$link}</g:link>\n");
        fwrite($fp, "    <g:price>{$price}</g:price>\n");

        if ($product->is_sale_enable && $product->sale_price && $product->sale_price < $product->price) {
            $rawSalePrice = (float) $product->sale_price;
            $convertedSalePrice = $currency === 'USD' ? $rawSalePrice : CurrencyDetectionService::convertAmount($rawSalePrice, $currency);
            $salePrice = number_format($convertedSalePrice, 2, '.', '') . " {$currency}";
            fwrite($fp, "    <g:sale_price>{$salePrice}</g:sale_price>\n");
        }

        fwrite($fp, "    <g:availability>{$availability}</g:availability>\n");
        fwrite($fp, "    <g:condition>new</g:condition>\n");
        fwrite($fp, "    <g:brand>{$brand}</g:brand>\n");
        fwrite($fp, "    <g:image_link>{$this->cleanXml($product->image_url)}</g:image_link>\n");
        fwrite($fp, "  </item>\n");
    }

    /**
     * Convert an admin-entered minimum price (in the feed's own display
     * currency) back to USD so it can be compared against products.price
     * (always stored in USD) directly in SQL.
     */
    private function convertToUsd(float $amount, string $currencyCode): float
    {
        if ($amount <= 0 || $currencyCode === 'USD') {
            return $amount;
        }

        $baseCurrency = Currency::where('code', 'USD')->first();
        $targetCurrency = Currency::where('code', $currencyCode)->where('status', 1)->first();

        if (!$targetCurrency) {
            return $amount;
        }

        $baseRate = $baseCurrency ? floatval($baseCurrency->exchange_rate) : 1.0;
        $targetRate = floatval($targetCurrency->exchange_rate);

        if ($targetRate <= 0) {
            return $amount;
        }

        return round($amount * ($baseRate / $targetRate), 2);
    }

    private function cleanXml(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
