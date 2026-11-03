<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Currency;
use App\Models\CustomFeedDefinition;
use App\Models\DailyFeedRun;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds the static Google Merchant XML file(s) for a custom feed link
 * (admin: Daily Regional Feeds → Custom Feed Links) and records the attempt
 * in daily_feed_runs (file_key = the feed's slug), exactly like the 4 fixed
 * region feeds. Only ever called from an explicit trigger — feed creation
 * and the per-feed "Run Now" button in AdminCustomFeedController — never
 * from the public-facing route, which just serves whatever this last wrote.
 *
 * Mirrors GenerateDailyRegionalFeeds' min-price filter and shard-count
 * splitting: shard_count=1 (the default) writes a single file at
 * custom-feeds/{slug}.xml exactly as before; shard_count>1 writes N files
 * at custom-feeds/{slug}-{n}.xml, distributing products round-robin so all
 * N fill up evenly in one query pass.
 */
class CustomFeedGenerator
{
    public function generate(CustomFeedDefinition $feed): DailyFeedRun
    {
        $startedAt = now();
        $run = DailyFeedRun::create([
            'file_key' => $feed->slug,
            'batch_id' => (string) Str::uuid(),
            'status' => 'running',
            'started_at' => $startedAt,
        ]);

        try {
            $shardCount = max(1, (int) ($feed->shard_count ?: 1));
            [$recordCount, $paths] = $this->writeFeedFiles($feed, $shardCount);

            $disk = Storage::disk('public');
            $allVerified = true;
            $totalSize = 0;
            foreach ($paths as $path) {
                if (!$disk->exists($path)) {
                    $allVerified = false;
                    continue;
                }
                $totalSize += $disk->size($path);
            }

            $finishedAt = now();

            $run->update([
                'status' => $allVerified ? 'success' : 'failed',
                'finished_at' => $finishedAt,
                'duration_seconds' => (int) round($startedAt->diffInSeconds($finishedAt)),
                'records_written' => $recordCount,
                'file_path' => $paths[0] ?? null,
                'file_url' => $shardCount > 1
                    ? route('custom-feeds.show-shard', ['slug' => $feed->slug, 'shard' => 1])
                    : route('custom-feeds.show', ['slug' => $feed->slug]),
                'file_verified' => $allVerified,
                'file_size_bytes' => $totalSize,
                'shard_count' => $shardCount > 1 ? $shardCount : null,
                'min_price_filter' => $feed->min_price ?: null,
                'error_message' => $allVerified ? null : 'One or more shard files missing or unexpectedly small after write.',
            ]);

            return $run->fresh();
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete every file this feed could have on disk, at its current
     * shard_count. Used on feed deletion; shard_count can't change after
     * creation (no edit endpoint), so there's nothing stale to guess at.
     */
    public function deleteFiles(CustomFeedDefinition $feed): void
    {
        $disk = Storage::disk('public');
        $shardCount = max(1, (int) ($feed->shard_count ?: 1));

        if ($shardCount > 1) {
            for ($i = 1; $i <= $shardCount; $i++) {
                $disk->delete("custom-feeds/{$feed->slug}-{$i}.xml");
            }
        } else {
            $disk->delete("custom-feeds/{$feed->slug}.xml");
        }
    }

    /**
     * @return array{0: int, 1: array<int, string>} [total record count, shard paths in order]
     */
    private function writeFeedFiles(CustomFeedDefinition $feed, int $shardCount): array
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $currency = Currency::where('code', $feed->currency)->where('status', 1)->first()
            ?? Currency::where('code', 'USD')->where('status', 1)->firstOrFail();

        $rate = (float) $currency->exchange_rate;
        $decimals = (int) $currency->no_of_decimal;

        $categoryIds = [];
        foreach (($feed->category_slugs ?? []) as $categorySlug) {
            $category = Category::where('slug', $categorySlug)->where('status', 1)->first();
            if ($category) {
                $categoryIds = array_merge($categoryIds, $this->getAllDescendantIds($category->id));
            }
        }
        $categoryIds = array_values(array_unique($categoryIds));

        $skus = $feed->skus ?? [];

        if (empty($categoryIds) && empty($skus)) {
            throw new \RuntimeException('This feed has no valid categories or SKUs configured.');
        }

        $minPriceUsd = $this->convertToUsd((float) ($feed->min_price ?? 0), $currency->code);

        $disk = Storage::disk('public');
        $disk->makeDirectory('custom-feeds');

        $paths = [];
        $handles = [];
        $tempPaths = [];

        for ($i = 1; $i <= $shardCount; $i++) {
            $path = $shardCount > 1 ? "custom-feeds/{$feed->slug}-{$i}.xml" : "custom-feeds/{$feed->slug}.xml";
            $tempPath = $disk->path($path) . '.tmp-' . uniqid();
            $handle = fopen($tempPath, 'wb');
            if (!$handle) {
                throw new \RuntimeException("Cannot open {$tempPath} for writing.");
            }

            $titleSuffix = $shardCount > 1 ? " (part {$i}/{$shardCount})" : '';
            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>');
            fwrite($handle, '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">');
            fwrite($handle, '<channel>');
            fwrite($handle, '<title>' . htmlspecialchars(config('app.name') . ' - ' . $feed->name . $titleSuffix) . '</title>');
            fwrite($handle, '<link>' . htmlspecialchars(config('app.url')) . '</link>');
            fwrite($handle, '<description>' . htmlspecialchars('Custom feed: ' . $feed->name) . '</description>');

            $paths[$i] = $path;
            $handles[$i] = $handle;
            $tempPaths[$i] = $tempPath;
        }

        $recordCount = 0;

        $query = Product::with(['product_thumbnail:id,image_url', 'brand:id,name'])
            ->select(['id', 'sku', 'name', 'description', 'slug', 'price', 'sale_price', 'stock_status', 'product_thumbnail_id', 'brand_id'])
            ->where('status', 1)
            ->where('is_approved', 1)
            ->where(function ($q) use ($categoryIds, $skus) {
                if (!empty($categoryIds)) {
                    $q->orWhereHas('categories', fn ($q2) => $q2->whereIn('categories.id', $categoryIds));
                }
                if (!empty($skus)) {
                    $q->orWhereIn('sku', $skus);
                }
            });

        if ($minPriceUsd > 0) {
            $query->where('products.price', '>=', $minPriceUsd);
        }

        $this->applyRegionFilter($query, $currency->code);

        $query->orderBy('id')->chunkById(2000, function ($products) use ($handles, $shardCount, $rate, $decimals, $currency, &$recordCount) {
            foreach ($products as $product) {
                $shardIndex = ($recordCount % $shardCount) + 1;
                fwrite($handles[$shardIndex], $this->formatProductXml($product, $rate, $decimals, $currency->code));
                $recordCount++;
            }
            unset($products);
            gc_collect_cycles();
        });

        foreach ($handles as $i => $handle) {
            fwrite($handle, '</channel></rss>');
            fclose($handle);
            rename($tempPaths[$i], $disk->path($paths[$i]));
        }

        return [$recordCount, array_values($paths)];
    }

    /**
     * Recursively collect a category id and all of its descendant ids.
     * Mirrors AdminProductFeedController::getAllDescendantIds.
     */
    private function getAllDescendantIds(int $categoryId, array &$collected = []): array
    {
        if (empty($collected)) {
            $collected = [$categoryId];
        }

        $children = Category::where('parent_id', $categoryId)
            ->where('status', 1)
            ->pluck('id')
            ->toArray();

        foreach ($children as $childId) {
            if (!in_array($childId, $collected)) {
                $collected[] = $childId;
                $this->getAllDescendantIds($childId, $collected);
            }
        }

        return $collected;
    }

    /**
     * Exclude products restricted to a different region than the feed's
     * currency implies, same convention as GenerateGoogleMerchantFeed: a
     * product with none of zambia_only/zimbabwe_only/sa_only set is global
     * and always included; one flagged for another region is excluded.
     * ZMW -> Zambia, ZAR -> South Africa, everything else (USD, etc.) -> Zimbabwe.
     */
    private function applyRegionFilter($query, string $currencyCode): void
    {
        $excludedColumns = match (strtoupper($currencyCode)) {
            'ZMW' => ['zimbabwe_only', 'sa_only'],
            'ZAR' => ['zambia_only', 'zimbabwe_only'],
            default => ['zambia_only', 'sa_only'],
        };

        foreach ($excludedColumns as $column) {
            $query->where("products.{$column}", false);
        }
    }

    /**
     * Converts a min-price input (entered in the feed's own currency) to USD,
     * since products.price is stored in USD. Mirrors
     * GenerateDailyRegionalFeeds::convertToUsd.
     */
    private function convertToUsd(float $amount, string $currencyCode): float
    {
        if ($amount <= 0 || $currencyCode === 'USD') {
            return $amount;
        }

        $target = Currency::where('code', $currencyCode)->where('status', 1)->first();
        if (!$target || (float) $target->exchange_rate <= 0) {
            return $amount;
        }

        $base = Currency::where('code', 'USD')->first();
        $baseRate = $base ? (float) $base->exchange_rate : 1.0;

        return round($amount * ($baseRate / (float) $target->exchange_rate), 2);
    }

    /**
     * Format a single product as a Google Merchant <item>.
     * Mirrors AdminProductFeedController::formatProductXml for consistency
     * with the feeds already published from this codebase.
     */
    private function formatProductXml($product, float $rate, int $decimals, string $currencyCode): string
    {
        $regularPrice = (float) ($product->price ?? 0);
        $formattedRegular = number_format($regularPrice * $rate, $decimals, '.', '');

        $salePrice = (float) ($product->sale_price ?? 0);
        $hasSalePrice = $salePrice > 0;
        $formattedSale = $hasSalePrice ? number_format($salePrice * $rate, $decimals, '.', '') : null;

        $productUrl = \App\Helpers\Helpers::productPageUrl($product->slug);
        $imageUrl = $product->product_thumbnail ? $product->product_thumbnail->image_url : '';

        $brandName = $product->brand && $product->brand->name
            ? $product->brand->name
            : config('app.name', 'One Stop Store');

        $cleanName = $this->removeControlCharacters($product->name);
        $cleanDescription = $this->removeControlCharacters(strip_tags($product->description ?? ''));

        $xml = '<item>';
        $xml .= '<g:id>' . htmlspecialchars((string) $product->id) . '</g:id>';
        $xml .= '<g:title>' . htmlspecialchars($cleanName) . '</g:title>';
        $xml .= '<g:description>' . htmlspecialchars($cleanDescription) . '</g:description>';
        $xml .= '<g:link>' . htmlspecialchars($productUrl) . '</g:link>';
        if ($imageUrl) {
            $xml .= '<g:image_link>' . htmlspecialchars($imageUrl) . '</g:image_link>';
        }
        $xml .= '<g:availability>' . htmlspecialchars($product->stock_status) . '</g:availability>';
        $xml .= '<g:price>' . htmlspecialchars($formattedRegular . ' ' . $currencyCode) . '</g:price>';
        if ($hasSalePrice) {
            $xml .= '<g:sale_price>' . htmlspecialchars($formattedSale . ' ' . $currencyCode) . '</g:sale_price>';
        }
        $xml .= '<g:brand>' . htmlspecialchars($brandName) . '</g:brand>';
        $xml .= '<g:condition>new</g:condition>';
        $xml .= '</item>';

        return $xml;
    }

    private function removeControlCharacters($string)
    {
        if ($string === null || $string === '') {
            return '';
        }

        return preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\x9F]/u', '', $string);
    }
}
