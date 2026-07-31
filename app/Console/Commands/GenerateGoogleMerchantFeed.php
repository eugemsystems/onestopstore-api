<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Generates a Google Merchant Center product feed (RSS 2.0 with g: namespace)
 * and stores it at storage/app/public/feeds/google-merchant.xml
 *
 * Submit this URL to Google Merchant Center:
 *   https://api.raines.africa/storage/feeds/google-merchant.xml
 *
 * Schedule: runs daily at 4 AM (after ES reindex at 3 AM d)
 */
class GenerateGoogleMerchantFeed extends Command
{
    protected $signature = 'feed:google-merchant
                            {--limit=10000 : Maximum products to include}
                            {--currency=USD : Currency code for product URLs (USD, ZAR, ZMW)}
                            {--dry-run : Count products without generating the file}';

    protected $description = 'Generate a Google Merchant Center XML product feed for automated submission';

    public function handle(): void
    {
        $limit    = (int) $this->option('limit');
        $currency = strtoupper($this->option('currency') ?: 'USD');
        $dryRun   = $this->option('dry-run');

        $frontendUrl = rtrim(env('FRONTEND_URL', 'https://raines.africa/en'), '/');

        // Query active products — prioritise featured and sale items
        $query = DB::table('products')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
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
                'brands.name as brand_name',
            ])
            ->where('products.status', 1)
            ->whereNull('products.deleted_at')
            ->whereNotNull('products.slug')
            ->where('products.price', '>', 0)
            ->orderByDesc('products.is_featured')
            ->orderByDesc('products.is_sale_enable')
            ->orderByDesc('products.updated_at')
            ->limit($limit);

        $count = (clone $query)->count();
        $this->info("Found {$count} products for feed.");

        if ($dryRun) {
            $this->info('[DRY RUN] Would generate feed with above count. Exiting.');
            return;
        }

        // Stream-build the XML to avoid memory issues with large catalogs
        $currencyLower = strtolower($currency);
        $tempPath = storage_path("app/public/feeds/google-merchant-{$currencyLower}.xml.tmp");
        $finalPath = storage_path("app/public/feeds/google-merchant-{$currencyLower}.xml");

        // Ensure directory exists
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $fp = fopen($tempPath, 'w');
        if (!$fp) {
            $this->error('Cannot write to feed file.');
            return;
        }

        // XML header
        fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($fp, '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n");
        fwrite($fp, "<channel>\n");
        fwrite($fp, "  <title>Raines Africa Products</title>\n");
        fwrite($fp, "  <link>{$frontendUrl}</link>\n");
        fwrite($fp, "  <description>Quality products with fast delivery across Zimbabwe and Zambia</description>\n");

        $written = 0;

        $query->orderBy('products.id')->chunk(1000, function ($products) use ($fp, $frontendUrl, &$written) {
            foreach ($products as $product) {
                $title = $this->cleanXml($product->name);
                $description = $this->cleanXml(
                    strip_tags($product->short_description ?: $product->description ?: $product->name)
                );
                // Trim description to 5000 chars (Google limit)
                if (strlen($description) > 5000) {
                    $description = substr($description, 0, 4997) . '...';
                }

                $link  = "{$frontendUrl}/en/product/{$product->slug}?currency={$currency}";
                $price = number_format((float) $product->price, 2, '.', '') . ' USD';
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
                    $salePrice = number_format((float) $product->sale_price, 2, '.', '') . ' USD';
                    fwrite($fp, "    <g:sale_price>{$salePrice}</g:sale_price>\n");
                }

                fwrite($fp, "    <g:availability>{$availability}</g:availability>\n");
                fwrite($fp, "    <g:condition>new</g:condition>\n");
                fwrite($fp, "    <g:brand>{$brand}</g:brand>\n");

                // Fetch product thumbnail image
                $image = DB::table('attachments')
                    ->join('products', 'products.product_thumbnail_id', '=', 'attachments.id')
                    ->where('products.id', $product->id)
                    ->value('attachments.image_url');

                if ($image) {
                    fwrite($fp, "    <g:image_link>{$this->cleanXml($image)}</g:image_link>\n");
                }

                fwrite($fp, "  </item>\n");
                $written++;
            }
        });

        fwrite($fp, "</channel>\n");
        fwrite($fp, "</rss>\n");
        fclose($fp);

        // Atomic rename so the live file is never partially written
        rename($tempPath, $finalPath);

        $this->info("Feed generated: {$written} products written to {$finalPath}");
        Log::info('feed:google-merchant completed', ['products' => $written]);
    }

    private function cleanXml(string $text): string
    {
        // Remove control characters that break XML
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
