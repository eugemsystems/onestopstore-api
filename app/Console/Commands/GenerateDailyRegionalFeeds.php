<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\DailyFeedRun;
use App\Models\DailyFeedSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CurrencyDetectionService;

/**
 * Generates the 4 daily regional Google Merchant feeds and writes them as
 * static files directly under public/ so they're served instantly by the
 * webserver (no PHP involved on read) at a URL that never changes:
 *
 *   public/dailyfeeds/global/usd/global-usd.xml     -> {APP_URL}/dailyfeeds/global/usd/global-usd.xml
 *   public/dailyfeeds/global/zmw/global-zmw.xml      -> {APP_URL}/dailyfeeds/global/zmw/global-zmw.xml
 *   public/dailyfeeds/zimbabwe/zimbabwe.xml          -> {APP_URL}/dailyfeeds/zimbabwe/zimbabwe.xml
 *   public/dailyfeeds/zambia/zambia.xml              -> {APP_URL}/dailyfeeds/zambia/zambia.xml
 *
 * "Global" feeds include every eligible product regardless of the
 * zambia_only/zimbabwe_only/sa_only region flags (broadest reach). The
 * Zimbabwe and Zambia feeds only include products actually purchasable in
 * that market, same rule the existing feed:google-merchant command uses.
 *
 * Large catalogs (into the millions) are handled via chunked cursor reads
 * straight into a streamed file write — nothing is held in memory — so a
 * single run comfortably covers a catalog far bigger than what currently
 * exists without needing a queue.
 *
 * Schedule time is admin-configurable (see DailyFeedSetting::get('schedule_time')
 * and routes/console.php, which re-registers the schedule from that setting).
 */
class GenerateDailyRegionalFeeds extends Command
{
    protected $signature = 'feed:daily-regional
                            {--only=* : Restrict to specific file keys (global_usd, global_zmw, zimbabwe_usd, zambia_zmw)}
                            {--batch= : Batch id to group this run\'s rows under (auto-generated if omitted)}';

    protected $description = 'Generate the 4 daily regional Google Merchant XML feeds as static public files';

    private string $frontendUrl;
    private string $zmFrontendUrl;

    /** @var array<string,array{region:?string,currency:string,path:string}> */
    private array $fileDefinitions = [
        'global_usd' => ['region' => null, 'currency' => 'USD', 'path' => 'dailyfeeds/global/usd/global-usd.xml'],
        'global_zmw' => ['region' => null, 'currency' => 'ZMW', 'path' => 'dailyfeeds/global/zmw/global-zmw.xml'],
        'zimbabwe_usd' => ['region' => 'zimbabwe', 'currency' => 'USD', 'path' => 'dailyfeeds/zimbabwe/zimbabwe.xml'],
        'zambia_zmw' => ['region' => 'zambia', 'currency' => 'ZMW', 'path' => 'dailyfeeds/zambia/zambia.xml'],
    ];

    public function handle(): void
    {
        $rawFrontend = rtrim(env('FRONTEND_URL', 'https://raines.africa/en'), '/');
        $frontendRoot = preg_replace('#/en$#', '', $rawFrontend);
        $this->frontendUrl = $frontendRoot . '/en';
        $this->zmFrontendUrl = $frontendRoot . '/zm';

        $only = $this->option('only');
        $batchId = $this->option('batch') ?: uniqid('daily_feed_', true);

        $keys = empty($only) ? array_keys($this->fileDefinitions) : array_intersect(array_keys($this->fileDefinitions), $only);

        foreach ($keys as $key) {
            $this->generateFile($key, $this->fileDefinitions[$key], $batchId);
        }

        $this->info("Batch {$batchId} complete.");
    }

    private function generateFile(string $key, array $def, string $batchId): void
    {
        $label = DailyFeedRun::LABELS[$key] ?? $key;
        $enabled = DailyFeedSetting::get("{$key}_enabled", '1') == '1';

        $run = DailyFeedRun::create([
            'file_key' => $key,
            'batch_id' => $batchId,
            'status' => 'running',
            'started_at' => now(),
        ]);

        if (!$enabled) {
            $run->update([
                'status' => 'skipped',
                'finished_at' => now(),
                'duration_seconds' => 0,
                'error_message' => 'Feed disabled in Daily Feed settings.',
            ]);
            $this->line("[{$label}] skipped (disabled).");
            return;
        }

        $start = microtime(true);

        try {
            $currency = $def['currency'];
            $minPriceInput = (float) DailyFeedSetting::get("{$key}_min_price", 0);
            $limit = (int) DailyFeedSetting::get("{$key}_limit", 0);

            $minPriceUsd = $this->convertToUsd($minPriceInput, $currency);

            $relativePath = $def['path'];
            $finalPath = public_path($relativePath);
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
            fwrite($fp, "  <title>Raines Africa Products - {$this->cleanXml($label)}</title>\n");
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
                // Only products actually purchasable from Zimbabwe.
                $query->whereRaw('COALESCE(products.zambia_only, false) = false')
                      ->whereRaw('COALESCE(products.sa_only, false) = false');
            } elseif ($def['region'] === 'zambia') {
                // Only products actually purchasable from Zambia.
                $query->whereRaw('COALESCE(products.zimbabwe_only, false) = false')
                      ->whereRaw('COALESCE(products.sa_only, false) = false');
            }
            // Global (region === null): no region restriction — broadest reach.

            $written = 0;
            $query->orderBy('products.id')->chunk(1000, function ($products) use ($fp, $currency, $limit, &$written) {
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
            });

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
        }
    }

    private function writeItem($fp, object $product, string $currency): void
    {
        $title = $this->cleanXml($product->name);
        $description = $this->cleanXml(
            strip_tags($product->short_description ?: $product->description ?: $product->name)
        );
        if (strlen($description) > 5000) {
            $description = substr($description, 0, 4997) . '...';
        }

        $link = $currency === 'ZMW'
            ? "{$this->zmFrontendUrl}/product/{$product->slug}"
            : "{$this->frontendUrl}/product/{$product->slug}?currency={$currency}";

        $rawPrice = (float) $product->price;
        $convertedPrice = $currency === 'USD' ? $rawPrice : CurrencyDetectionService::convertAmount($rawPrice, $currency);
        $price = number_format($convertedPrice, 2, '.', '') . " {$currency}";
        $availability = $product->stock_status === 'out_of_stock' ? 'out_of_stock' : 'in_stock';
        $brand = $this->cleanXml($product->brand_name ?: 'Raines Africa');
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
