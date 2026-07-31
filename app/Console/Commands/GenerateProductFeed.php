<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class GenerateProductFeed extends Command
{
    protected $signature = 'product-feed:generate
                            {jobId}
                            {category}
                            {currency}
                            {format}
                            {--store=      : Store ID filter}
                            {--exclude=    : JSON-encoded array of excluded delivery texts}
                            {--promotion=  : Promotion filter (sale_price|sale_enabled|both|either)}
                            {--price-min=  : Minimum price filter}
                            {--price-max=  : Maximum price filter}';

    protected $description = 'Generate product feed in background';

    public function handle()
    {
        $jobId        = $this->argument('jobId');
        $categorySlug = $this->argument('category');
        $currencyCode = $this->argument('currency');
        $format       = $this->argument('format');

        $storeId         = $this->option('store')     ?: null;
        $excludeJson     = $this->option('exclude')   ?: null;
        $promotionFilter = $this->option('promotion') ?: null;
        $priceMin        = $this->option('price-min') !== null ? (float) $this->option('price-min') : null;
        $priceMax        = $this->option('price-max') !== null ? (float) $this->option('price-max') : null;

        $excludedTexts = [];
        if ($excludeJson) {
            $excludedTexts = json_decode($excludeJson, true) ?? [];
        }

        try {
            $this->updateJobStatus($jobId, 'processing', 0, 'Loading category and currency data...');

            $category = Category::whereSlug($categorySlug)->firstOrFail();
            $currency = Currency::where('code', $currencyCode)->where('status', 1)->firstOrFail();

            $categoryIds = $this->getCategoryIdsWithChildren($category);

            // Build count query with all filters
            $query = Product::whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
                ->where('status', 1)
                ->where('is_approved', 1);

            if ($storeId)              $query->where('store_id', $storeId);
            if (!empty($excludedTexts)) {
                $query->where(function ($q) use ($excludedTexts) {
                    $q->whereNotIn('estimated_delivery_text', $excludedTexts)
                      ->orWhereNull('estimated_delivery_text');
                });
            }
            if ($promotionFilter)      $query = $this->applyPromotionFilter($query, $promotionFilter);
            if ($priceMin !== null) {
                $query->where(function ($q) use ($priceMin) {
                    $q->where(fn($s) => $s->where('sale_price', '>', 0)->where('sale_price', '>=', $priceMin))
                      ->orWhere(fn($s) => $s->where(fn($i) => $i->whereNull('sale_price')->orWhere('sale_price', 0))->where('price', '>=', $priceMin));
                });
            }
            if ($priceMax !== null) {
                $query->where(function ($q) use ($priceMax) {
                    $q->where(fn($s) => $s->where('sale_price', '>', 0)->where('sale_price', '<=', $priceMax))
                      ->orWhere(fn($s) => $s->where(fn($i) => $i->whereNull('sale_price')->orWhere('sale_price', 0))->where('price', '<=', $priceMax));
                });
            }

            $totalProducts = $query->count();
            $this->updateJobStatus($jobId, 'processing', 10, "Found {$totalProducts} products. Preparing file...");

            $rate     = (float) $currency->exchange_rate;
            $decimals = (int)   $currency->no_of_decimal;
            $decSep   = $currency->decimal_separator   === 'comma' ? ',' : '.';
            $thouSep  = $currency->thousands_separator === 'comma' ? ',' : '.';

            $filters = compact('storeId', 'excludedTexts', 'promotionFilter', 'priceMin', 'priceMax');

            if ($format === 'xml') {
                $filePath = $this->generateXml($jobId, $category, $categorySlug, $currencyCode, $categoryIds, $rate, $decimals, $decSep, $thouSep, $totalProducts, $filters);
            } else {
                $filePath = $this->generateTsv($jobId, $category, $categorySlug, $currencyCode, $categoryIds, $rate, $decimals, $decSep, $thouSep, $totalProducts, $filters);
            }

            $this->updateJobStatus($jobId, 'completed', 100, 'Export completed!', $filePath);

        } catch (\Exception $e) {
            $this->updateJobStatus($jobId, 'failed', 0, 'Error: ' . $e->getMessage());
            $this->error($e->getMessage());
        }
    }

    private function applyPromotionFilter($query, string $filter)
    {
        return match ($filter) {
            'sale_price'   => $query->where('sale_price', '>', 0),
            'sale_enabled' => $query->where('is_sale_enable', 1),
            'both'         => $query->where('sale_price', '>', 0)->where('is_sale_enable', 1),
            'either'       => $query->where(fn($q) => $q->where('sale_price', '>', 0)->orWhere('is_sale_enable', 1)),
            default        => $query,
        };
    }

    private function buildFilteredQuery(array $categoryIds, array $filters)
    {
        $q = Product::with(['product_thumbnail:id,image_url', 'brand:id,name'])
            ->select(['id', 'name', 'description', 'slug', 'price', 'sale_price', 'stock_status', 'product_thumbnail_id', 'brand_id'])
            ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
            ->where('status', 1)
            ->where('is_approved', 1);

        if (!empty($filters['storeId']))      $q->where('store_id', $filters['storeId']);
        if (!empty($filters['excludedTexts'])) {
            $q->where(function ($sq) use ($filters) {
                $sq->whereNotIn('estimated_delivery_text', $filters['excludedTexts'])
                   ->orWhereNull('estimated_delivery_text');
            });
        }
        if (!empty($filters['promotionFilter'])) $q = $this->applyPromotionFilter($q, $filters['promotionFilter']);
        if ($filters['priceMin'] !== null) {
            $q->where(function ($sq) use ($filters) {
                $sq->where(fn($s) => $s->where('sale_price', '>', 0)->where('sale_price', '>=', $filters['priceMin']))
                   ->orWhere(fn($s) => $s->where(fn($i) => $i->whereNull('sale_price')->orWhere('sale_price', 0))->where('price', '>=', $filters['priceMin']));
            });
        }
        if ($filters['priceMax'] !== null) {
            $q->where(function ($sq) use ($filters) {
                $sq->where(fn($s) => $s->where('sale_price', '>', 0)->where('sale_price', '<=', $filters['priceMax']))
                   ->orWhere(fn($s) => $s->where(fn($i) => $i->whereNull('sale_price')->orWhere('sale_price', 0))->where('price', '<=', $filters['priceMax']));
            });
        }
        return $q;
    }

    private function generateXml($jobId, $category, $categorySlug, $currencyCode, $categoryIds, $rate, $decimals, $decSep, $thouSep, $totalProducts, array $filters = [])
    {
        $productsPerFile = 100000;
        $needsZip = $totalProducts > $productsPerFile;

        if ($needsZip) {
            return $this->generateXmlZip($jobId, $category, $categorySlug, $currencyCode, $categoryIds, $rate, $decimals, $decSep, $thouSep, $totalProducts, $productsPerFile, $filters);
        }

        $filename = "product-feed-{$categorySlug}-{$currencyCode}-" . time() . ".xml";
        $filePath = storage_path("app/temp/{$filename}");
        @mkdir(storage_path('app/temp'), 0755, true);

        $handle = fopen($filePath, 'w');
        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($handle, '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">' . "\n");
        fwrite($handle, '<channel>' . "\n");
        fwrite($handle, '<title>' . htmlspecialchars(config('app.name') . ' Product Feed') . '</title>' . "\n");
        fwrite($handle, '<link>' . htmlspecialchars(config('app.url')) . '</link>' . "\n");
        fwrite($handle, '<description>' . htmlspecialchars(config('app.name') . ' Feed for category ' . $category->name) . '</description>' . "\n");

        $processed    = 0;
        $frontendUrl  = rtrim(config('app.frontend_url') ?? config('app.url'), '/');
        $appName      = config('app.name');

        $this->buildFilteredQuery($categoryIds, $filters)
            ->orderBy('id')
            ->chunkById(2000, function ($products) use (
                &$processed, $totalProducts, $jobId, $handle,
                $rate, $decimals, $currencyCode, $frontendUrl, $appName
            ) {
                $xmlBatch = '';
                foreach ($products as $product) {
                    $regularPrice   = (float)($product->price ?? 0);
                    $salePriceRaw   = (float)($product->sale_price ?? 0);
                    $hasSale        = $salePriceRaw > 0;
                    $fmtRegular     = number_format($regularPrice * $rate, $decimals, '.', '');
                    $fmtSale        = $hasSale ? number_format($salePriceRaw * $rate, $decimals, '.', '') : null;
                    $displayPrice   = $hasSale ? $fmtSale : $fmtRegular;

                    $productUrl  = $frontendUrl . '/en/product/' . $product->slug . '?currency=' . $currencyCode;
                    $imageUrl    = $product->product_thumbnail ? $product->product_thumbnail->image_url : '';
                    $brandName   = $product->brand && $product->brand->name ? $product->brand->name : config('app.name', 'Raines Africa');
                    $cleanName   = $this->removeControlCharacters($product->name);
                    $cleanDesc   = $this->removeControlCharacters(strip_tags($product->description ?? ''));

                    $xmlBatch .= '<item>' . "\n";
                    $xmlBatch .= '  <g:id>'          . $product->id . '</g:id>' . "\n";
                    $xmlBatch .= '  <g:title>'       . htmlspecialchars($cleanName)  . '</g:title>' . "\n";
                    $xmlBatch .= '  <g:description>' . htmlspecialchars($cleanDesc)  . '</g:description>' . "\n";
                    $xmlBatch .= '  <g:link>'        . htmlspecialchars($productUrl) . '</g:link>' . "\n";
                    if ($imageUrl) $xmlBatch .= '  <g:image_link>' . htmlspecialchars($imageUrl) . '</g:image_link>' . "\n";
                    $xmlBatch .= '  <g:availability>' . $product->stock_status . '</g:availability>' . "\n";
                    $xmlBatch .= '  <g:price>'        . htmlspecialchars($fmtRegular . ' ' . $currencyCode) . '</g:price>' . "\n";
                    if ($hasSale) $xmlBatch .= '  <g:sale_price>' . htmlspecialchars($fmtSale . ' ' . $currencyCode) . '</g:sale_price>' . "\n";
                    $xmlBatch .= '  <g:brand>'        . htmlspecialchars($brandName) . '</g:brand>' . "\n";
                    $xmlBatch .= '  <g:condition>new</g:condition>' . "\n";
                    $xmlBatch .= '</item>' . "\n";
                }
                fwrite($handle, $xmlBatch);
                $processed += count($products);
                if ($processed % 10000 == 0 || $processed >= $totalProducts) {
                    $progress = min(90, (int)(($processed / $totalProducts) * 90));
                    $this->updateJobStatus($jobId, 'processing', $progress, "Processing: {$processed} / {$totalProducts} products");
                }
            });

        fwrite($handle, '</channel>' . "\n");
        fwrite($handle, '</rss>' . "\n");
        fclose($handle);

        return url("/admin/product-feed/download-temp?file=" . basename($filePath) . "&name=" . urlencode($filename));
    }

    private function generateXmlZip($jobId, $category, $categorySlug, $currencyCode, $categoryIds, $rate, $decimals, $decSep, $thouSep, $totalProducts, $productsPerFile, array $filters = [])
    {
        $zipFilename = "product-feed-{$categorySlug}-{$currencyCode}-" . time() . ".zip";
        $zipPath = storage_path("app/temp/{$zipFilename}");

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Could not create ZIP file');
        }

        $partNumber     = 1;
        $productsInPart = 0;
        $processed      = 0;
        $frontendUrl    = rtrim(config('app.frontend_url') ?? config('app.url'), '/');
        $appName        = config('app.name');
        $currentPartFile = null;
        $partHandle      = null;

        $this->buildFilteredQuery($categoryIds, $filters)
            ->orderBy('id')
            ->chunkById(2000, function ($products) use (
                &$zip, &$partNumber, &$productsInPart, &$processed, &$currentPartFile, &$partHandle,
                $productsPerFile, $categorySlug, $currencyCode, $rate, $decimals,
                $category, $totalProducts, $jobId, $frontendUrl, $appName
            ) {
                foreach ($products as $product) {
                    if ($productsInPart == 0) {
                        $currentPartFile = tempnam(sys_get_temp_dir(), 'part_');
                        $partHandle = fopen($currentPartFile, 'w');
                        fwrite($partHandle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
                        fwrite($partHandle, '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">' . "\n");
                        fwrite($partHandle, '<channel>' . "\n");
                        fwrite($partHandle, '<title>' . htmlspecialchars(config('app.name') . ' Product Feed - Part ' . $partNumber) . '</title>' . "\n");
                        fwrite($partHandle, '<link>' . htmlspecialchars(config('app.url')) . '</link>' . "\n");
                        fwrite($partHandle, '<description>' . htmlspecialchars(config('app.name') . ' Feed for category ' . $category->name) . '</description>' . "\n");
                    }

                    $regularPrice = (float)($product->price ?? 0);
                    $salePriceRaw = (float)($product->sale_price ?? 0);
                    $hasSale      = $salePriceRaw > 0;
                    $fmtRegular   = number_format($regularPrice * $rate, $decimals, '.', '');
                    $fmtSale      = $hasSale ? number_format($salePriceRaw * $rate, $decimals, '.', '') : null;
                    $productUrl   = $frontendUrl . '/product/' . $product->slug . '?currency=' . $currencyCode;
                    $imageUrl     = $product->product_thumbnail ? $product->product_thumbnail->image_url : '';
                    $brandName    = $product->brand && $product->brand->name ? $product->brand->name : config('app.name', 'Raines Africa');
                    $cleanName    = $this->removeControlCharacters($product->name);
                    $cleanDesc    = $this->removeControlCharacters(strip_tags($product->description ?? ''));

                    fwrite($partHandle, '<item>' . "\n");
                    fwrite($partHandle, '  <g:id>'          . $product->id . '</g:id>' . "\n");
                    fwrite($partHandle, '  <g:title>'       . htmlspecialchars($cleanName)  . '</g:title>' . "\n");
                    fwrite($partHandle, '  <g:description>' . htmlspecialchars($cleanDesc)  . '</g:description>' . "\n");
                    fwrite($partHandle, '  <g:link>'        . htmlspecialchars($productUrl) . '</g:link>' . "\n");
                    if ($imageUrl) fwrite($partHandle, '  <g:image_link>' . htmlspecialchars($imageUrl) . '</g:image_link>' . "\n");
                    fwrite($partHandle, '  <g:availability>' . $product->stock_status . '</g:availability>' . "\n");
                    fwrite($partHandle, '  <g:price>'        . htmlspecialchars($fmtRegular . ' ' . $currencyCode) . '</g:price>' . "\n");
                    if ($hasSale) fwrite($partHandle, '  <g:sale_price>' . htmlspecialchars($fmtSale . ' ' . $currencyCode) . '</g:sale_price>' . "\n");
                    fwrite($partHandle, '  <g:brand>'        . htmlspecialchars($brandName) . '</g:brand>' . "\n");
                    fwrite($partHandle, '  <g:condition>new</g:condition>' . "\n");
                    fwrite($partHandle, '</item>' . "\n");

                    $productsInPart++;
                    $processed++;

                    if ($productsInPart >= $productsPerFile) {
                        fwrite($partHandle, '</channel>' . "\n");
                        fwrite($partHandle, '</rss>' . "\n");
                        fclose($partHandle);
                        $zip->addFile($currentPartFile, "product-feed-{$categorySlug}-{$currencyCode}-part{$partNumber}.xml");
                        $partNumber++;
                        $productsInPart = 0;
                    }

                    if ($processed % 10000 == 0 || $processed >= $totalProducts) {
                        $progress = min(90, (int)(($processed / $totalProducts) * 90));
                        $this->updateJobStatus($jobId, 'processing', $progress, "Processing: {$processed} / {$totalProducts} products (part {$partNumber})");
                    }
                }
            });

        // Close last file if still open
        if ($productsInPart > 0 && $partHandle) {
            fwrite($partHandle, '</channel>' . "\n");
            fwrite($partHandle, '</rss>' . "\n");
            fclose($partHandle);

            $partFilename = "product-feed-{$categorySlug}-{$currencyCode}-part{$partNumber}.xml";
            $zip->addFile($currentPartFile, $partFilename);
        }

        // Add README
        $readme = "Product Feed Export\n\n";
        $readme .= "Category: {$category->name}\n";
        $readme .= "Currency: {$currencyCode}\n";
        $readme .= "Total Products: " . number_format($totalProducts) . "\n";
        $readme .= "Total Parts: {$partNumber}\n";
        $readme .= "Products per file: 100,000\n";
        $readme .= "Export Date: " . date('Y-m-d H:i:s') . "\n\n";
        $readme .= "Upload each XML file separately to your platform.\n";
        $zip->addFromString('README.txt', $readme);

        $zip->close();

        // Clean up temp files
        $tempDir = sys_get_temp_dir();
        foreach (glob($tempDir . '/part_*') as $tempFile) {
            @unlink($tempFile);
        }

        return url("/admin/product-feed/download-temp?file=" . basename($zipPath) . "&name=" . urlencode($zipFilename));
    }

    private function generateTsv($jobId, $category, $categorySlug, $currencyCode, $categoryIds, $rate, $decimals, $decSep, $thouSep, $totalProducts, array $filters = [])
    {
        $productsPerFile = 100000;
        if ($totalProducts > $productsPerFile) {
            return $this->generateTsvZip($jobId, $category, $categorySlug, $currencyCode, $categoryIds, $rate, $decimals, $decSep, $thouSep, $totalProducts, $productsPerFile, $filters);
        }

        $filename = "product-feed-{$categorySlug}-{$currencyCode}-" . time() . ".tsv";
        $filePath = storage_path("app/temp/{$filename}");
        @mkdir(storage_path('app/temp'), 0755, true);

        $handle  = fopen($filePath, 'w');
        $headers = ['id', 'title', 'description', 'link', 'image_link', 'availability', 'price', 'sale_price', 'brand', 'condition'];
        fwrite($handle, implode("\t", $headers) . "\n");

        $processed   = 0;
        $frontendUrl = rtrim(config('app.frontend_url') ?? config('app.url'), '/');
        $appName     = config('app.name');

        $this->buildFilteredQuery($categoryIds, $filters)
            ->orderBy('id')
            ->chunkById(2000, function ($products) use (&$processed, $totalProducts, $jobId, $handle, $rate, $decimals, $currencyCode, $frontendUrl, $appName) {
                $tsvBatch = '';
                foreach ($products as $product) {
                    $regularPrice = (float)($product->price ?? 0);
                    $salePriceRaw = (float)($product->sale_price ?? 0);
                    $hasSale      = $salePriceRaw > 0;
                    $fmtRegular   = number_format($regularPrice * $rate, $decimals, '.', '') . ' ' . $currencyCode;
                    $fmtSale      = $hasSale ? number_format($salePriceRaw * $rate, $decimals, '.', '') . ' ' . $currencyCode : '';
                    $productUrl   = $frontendUrl . '/product/' . $product->slug . '?currency=' . $currencyCode;
                    $imageUrl     = $product->product_thumbnail ? $product->product_thumbnail->image_url : '';
                    $brandName    = $product->brand && $product->brand->name ? $product->brand->name : config('app.name', 'Raines Africa');

                    $tsvBatch .= implode("\t", [
                        $product->id,
                        $this->escapeTsv($product->name),
                        $this->escapeTsv(strip_tags($product->description ?? '')),
                        $productUrl, $imageUrl,
                        $product->stock_status,
                        $fmtRegular, $fmtSale,
                        $this->escapeTsv($brandName), 'new',
                    ]) . "\n";
                }
                fwrite($handle, $tsvBatch);
                $processed += count($products);
                if ($processed % 10000 == 0 || $processed >= $totalProducts) {
                    $progress = min(90, (int)(($processed / $totalProducts) * 90));
                    $this->updateJobStatus($jobId, 'processing', $progress, "Processing: {$processed} / {$totalProducts} products");
                }
            });

        fclose($handle);
        return url("/admin/product-feed/download-temp?file=" . basename($filePath) . "&name=" . urlencode($filename));
    }

    private function generateTsvZip($jobId, $category, $categorySlug, $currencyCode, $categoryIds, $rate, $decimals, $decSep, $thouSep, $totalProducts, $productsPerFile, array $filters = [])
    {
        $zipFilename = "product-feed-{$categorySlug}-{$currencyCode}-" . time() . ".zip";
        $zipPath     = storage_path("app/temp/{$zipFilename}");

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Could not create ZIP file');
        }

        $partNumber      = 1;
        $productsInPart  = 0;
        $processed       = 0;
        $frontendUrl     = rtrim(config('app.frontend_url') ?? config('app.url'), '/');
        $appName         = config('app.name');
        $headers         = ['id', 'title', 'description', 'link', 'image_link', 'availability', 'price', 'sale_price', 'brand', 'condition'];
        $currentPartFile = null;
        $partHandle      = null;

        $this->buildFilteredQuery($categoryIds, $filters)
            ->orderBy('id')
            ->chunkById(2000, function ($products) use (
                &$zip, &$partNumber, &$productsInPart, &$processed, &$currentPartFile, &$partHandle,
                $productsPerFile, $categorySlug, $currencyCode, $rate, $decimals,
                $totalProducts, $jobId, $frontendUrl, $appName, $headers
            ) {
                foreach ($products as $product) {
                    if ($productsInPart == 0) {
                        $currentPartFile = tempnam(sys_get_temp_dir(), 'part_');
                        $partHandle = fopen($currentPartFile, 'w');
                        fwrite($partHandle, implode("\t", $headers) . "\n");
                    }

                    $regularPrice = (float)($product->price ?? 0);
                    $salePriceRaw = (float)($product->sale_price ?? 0);
                    $hasSale      = $salePriceRaw > 0;
                    $fmtRegular   = number_format($regularPrice * $rate, $decimals, '.', '') . ' ' . $currencyCode;
                    $fmtSale      = $hasSale ? number_format($salePriceRaw * $rate, $decimals, '.', '') . ' ' . $currencyCode : '';
                    $productUrl   = $frontendUrl . '/product/' . $product->slug . '?currency=' . $currencyCode;
                    $imageUrl     = $product->product_thumbnail ? $product->product_thumbnail->image_url : '';
                    $brandName    = $product->brand && $product->brand->name ? $product->brand->name : config('app.name', 'Raines Africa');

                    fwrite($partHandle, implode("\t", [
                        $product->id,
                        $this->escapeTsv($product->name),
                        $this->escapeTsv(strip_tags($product->description ?? '')),
                        $productUrl, $imageUrl,
                        $product->stock_status,
                        $fmtRegular, $fmtSale,
                        $this->escapeTsv($brandName), 'new',
                    ]) . "\n");

                    $productsInPart++;
                    $processed++;

                    if ($productsInPart >= $productsPerFile) {
                        fclose($partHandle);
                        $zip->addFile($currentPartFile, "product-feed-{$categorySlug}-{$currencyCode}-part{$partNumber}.tsv");
                        $partNumber++;
                        $productsInPart = 0;
                    }
                    if ($processed % 10000 == 0 || $processed >= $totalProducts) {
                        $progress = min(90, (int)(($processed / $totalProducts) * 90));
                        $this->updateJobStatus($jobId, 'processing', $progress, "Processing: {$processed} / {$totalProducts} products (part {$partNumber})");
                    }
                }
            });

        if ($productsInPart > 0 && $partHandle) {
            fclose($partHandle);
            $zip->addFile($currentPartFile, "product-feed-{$categorySlug}-{$currencyCode}-part{$partNumber}.tsv");
        }

        $zip->addFromString('README.txt', "Product Feed Export (TSV)\n\nCategory: {$category->name}\nCurrency: {$currencyCode}\nTotal Products: " . number_format($totalProducts) . "\nTotal Parts: {$partNumber}\nExport Date: " . date('Y-m-d H:i:s') . "\n");
        $zip->close();

        $tempDir = sys_get_temp_dir();
        foreach (glob($tempDir . '/part_*') as $f) { @unlink($f); }        // Clean up temp files
        $tempDir = sys_get_temp_dir();
        foreach (glob($tempDir . '/part_*') as $tempFile) {
            @unlink($tempFile);
        }

        return url("/admin/product-feed/download-temp?file=" . basename($zipPath) . "&name=" . urlencode($zipFilename));
    }

    private function formatProductXml($product, $rate, $decimals, $decSep, $thouSep, $currencyCode)
    {
        $salePrice = $product->sale_price ?? $product->price;
        $converted = $salePrice * $rate;
        $decSep  = '.';   // decimal separator
        $thouSep = '';    // no thousand separator
        $formatted = number_format($converted, $decimals, $decSep, $thouSep);

        $frontendUrl = config('app.frontend_url') ?? config('app.url');
        $productUrl = rtrim($frontendUrl, '/') . '/product/' . $product->slug . '?currency=' . $currencyCode;
        $imageUrl = $product->product_thumbnail ? $product->product_thumbnail->image_url : '';

        // Clean all text fields from control characters before XML encoding
        $cleanName = $this->removeControlCharacters($product->name);
        $cleanDescription = $this->removeControlCharacters(strip_tags($product->description ?? ''));

        $xml = '<item>' . "\n";
        $xml .= '  <g:id>' . htmlspecialchars($product->id) . '</g:id>' . "\n";
        $xml .= '  <g:title>' . htmlspecialchars($cleanName) . '</g:title>' . "\n";
        $xml .= '  <g:description>' . htmlspecialchars($cleanDescription) . '</g:description>' . "\n";
        $xml .= '  <g:link>' . htmlspecialchars($productUrl) . '</g:link>' . "\n";
        if ($imageUrl) {
            $xml .= '  <g:image_link>' . htmlspecialchars($imageUrl) . '</g:image_link>' . "\n";
        }
        $xml .= '  <g:availability>' . htmlspecialchars($product->stock_status) . '</g:availability>' . "\n";
        $xml .= '  <g:price>' . htmlspecialchars($formatted . ' ' . $currencyCode) . '</g:price>' . "\n";
        $xml .= '  <g:brand>' . htmlspecialchars(config('app.name')) . '</g:brand>' . "\n";
        $xml .= '  <g:condition>new</g:condition>' . "\n";
        $xml .= '</item>' . "\n";

        return $xml;
    }

    private function formatProductTsv($product, $rate, $decimals, $decSep, $thouSep, $currencyCode)
    {
        $salePrice = $product->sale_price ?? $product->price;
        $converted = $salePrice * $rate;
        $formatted = number_format($converted, $decimals, $decSep, $thouSep);

        $frontendUrl = config('app.frontend_url') ?? config('app.url');
        $productUrl = rtrim($frontendUrl, '/') . '/product/' . $product->slug . '?currency=' . $currencyCode;
        $imageUrl = $product->product_thumbnail ? $product->product_thumbnail->image_url : '';

        $row = [
            $product->id,
            $this->escapeTsv($product->name),
            $this->escapeTsv(strip_tags($product->description ?? '')),
            $productUrl,
            $imageUrl,
            $product->stock_status,
            $formatted . ' ' . $currencyCode,
            $this->escapeTsv(config('app.name')),
            'new'
        ];

        return implode("\t", $row) . "\n";
    }

    private function escapeTsv($value)
    {
        if ($value === null) return '';

        // Remove control characters first
        $value = $this->removeControlCharacters($value);

        // Then remove tabs, newlines, carriage returns
        return trim(str_replace(["\t", "\n", "\r"], [' ', ' ', ''], $value));
    }

    /**
     * Remove all non-printable control characters that break XML/TSV formats
     * This removes characters like 0x03 (ETX), 0x1E (Record Separator), etc.
     */
    private function removeControlCharacters($string)
    {
        if ($string === null || $string === '') {
            return '';
        }

        // Remove all control characters except tab (0x09), newline (0x0A), and carriage return (0x0D)
        // Control characters are 0x00-0x1F and 0x7F-0x9F
        $string = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\x9F]/u', '', $string);

        // Also remove any invalid UTF-8 sequences
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // Remove any remaining whitespace control characters
        $string = preg_replace('/\p{C}/u', '', $string);

        return $string;
    }

    private function getCategoryIdsWithChildren(Category $category): array
    {
        return $this->getAllDescendantIdsFromCache($category->id);
    }

    /**
     * Recursively get all descendant category IDs from cached hierarchy
     */
    private function getAllDescendantIdsFromCache(int $categoryId, array &$collected = []): array
    {
        if (empty($collected)) {
            $collected = [$categoryId];
        }

        // Get the cached hierarchical tree
        $cachedTree = Cache::get('categories_hierarchical_tree', []);

        // Find the category in the tree and get its children
        $children = $this->findChildrenInTree($cachedTree, $categoryId);

        foreach ($children as $childId) {
            if (!in_array($childId, $collected)) {
                $collected[] = $childId;
                // Recursively get descendants of this child
                $this->getAllDescendantIdsFromCache($childId, $collected);
            }
        }

        return array_unique($collected);
    }

    /**
     * Find direct children of a category in the cached tree
     */
    private function findChildrenInTree(array $tree, int $categoryId): array
    {
        $children = [];

        foreach ($tree as $node) {
            // If this is the category we're looking for, return its children
            if (isset($node['id']) && $node['id'] === $categoryId) {
                if (isset($node['children']) && is_array($node['children'])) {
                    foreach ($node['children'] as $child) {
                        if (isset($child['id'])) {
                            $children[] = $child['id'];
                        }
                    }
                }
                return $children;
            }

            // Recursively search in children
            if (isset($node['children']) && is_array($node['children'])) {
                $foundChildren = $this->findChildrenInTree($node['children'], $categoryId);
                if (!empty($foundChildren)) {
                    return $foundChildren;
                }
            }
        }

        return $children;
    }

    private function updateJobStatus($jobId, $status, $progress, $message, $filePath = null)
    {
        $data = [
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'updated_at' => now()->toDateTimeString()
        ];

        if ($filePath) {
            $data['file_path'] = $filePath;
        }

        cache()->put("export_job_{$jobId}", array_merge(
            cache()->get("export_job_{$jobId}", []),
            $data
        ), now()->addHours(2));
    }
}

