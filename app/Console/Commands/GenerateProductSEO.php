<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateProductSEO extends Command
{
    protected $signature = 'products:generate-seo {--limit=100 : Number of products to process} {--force : Regenerate even if meta data exists}';
    protected $description = 'Generate SEO meta titles and descriptions for products that are missing them';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $this->info('Generating SEO metadata for products...');
        $this->newLine();

        // Query products without meta data (or all if force)
        $query = Product::where('status', 1)
            ->where('is_approved', 1);

        if (!$force) {
            $query->where(function($q) {
                $q->whereNull('meta_title')
                  ->orWhere('meta_title', '')
                  ->orWhereNull('meta_description')
                  ->orWhere('meta_description', '');
            });
        }

        $products = $query->with(['brand', 'categories'])
            ->limit($limit)
            ->get();

        if ($products->isEmpty()) {
            $this->info('✓ No products need SEO generation!');
            return Command::SUCCESS;
        }

        $this->info("Found {$products->count()} products to process");
        $this->newLine();

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $updated = 0;
        $skipped = 0;

        foreach ($products as $product) {
            try {
                $needsUpdate = false;
                $updates = [];

                // Generate meta_title if missing
                if (empty($product->meta_title) || $force) {
                    $metaTitle = $this->generateMetaTitle($product);
                    $updates['meta_title'] = $metaTitle;
                    $needsUpdate = true;
                }

                // Generate meta_description if missing
                if (empty($product->meta_description) || $force) {
                    $metaDescription = $this->generateMetaDescription($product);
                    $updates['meta_description'] = $metaDescription;
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $product->update($updates);
                    $updated++;
                } else {
                    $skipped++;
                }

                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error processing product {$product->id}: {$e->getMessage()}");
                $skipped++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Successfully updated {$updated} products");
        if ($skipped > 0) {
            $this->comment("○ Skipped {$skipped} products");
        }

        return Command::SUCCESS;
    }

    private function generateMetaTitle(Product $product): string
    {
        $parts = [];

        // Add product name (limit to avoid too long titles)
        $productName = Str::limit($product->name, 50, '');
        $parts[] = $productName;

        // Add brand if available
        if ($product->brand && $product->brand->name) {
            $parts[] = $product->brand->name;
        }

        // Add main category if available
        if ($product->categories && $product->categories->isNotEmpty()) {
            $mainCategory = $product->categories->first();
            if ($mainCategory && $mainCategory->name) {
                $parts[] = $mainCategory->name;
            }
        }

        // Add site name
        $parts[] = 'Raines Africa';

        // Combine (max 60 characters for SEO)
        $title = implode(' | ', $parts);
        return Str::limit($title, 60, '');
    }

    private function generateMetaDescription(Product $product): string
    {
        $parts = [];

        // Use short_description if available and good length
        if (!empty($product->short_description) && strlen($product->short_description) > 20) {
            $description = strip_tags($product->short_description);
            $description = Str::limit($description, 155, '');
            return $description;
        }

        // Generate description from product data
        $parts[] = "Shop {$product->name}";

        if ($product->brand && $product->brand->name) {
            $parts[] = "by {$product->brand->name}";
        }

        if ($product->sale_price && $product->sale_price > 0) {
            $parts[] = "at \${$product->sale_price}";
        } elseif ($product->price && $product->price > 0) {
            $parts[] = "at \${$product->price}";
        }

        if ($product->categories && $product->categories->isNotEmpty()) {
            $categoryNames = $product->categories->pluck('name')->take(2)->join(', ');
            $parts[] = "in {$categoryNames}";
        }

        $parts[] = 'at Raines Africa. Fast delivery across Zimbabwe and Zambia. Shop now!';

        $description = implode(' ', $parts);
        return Str::limit($description, 155, '');
    }
}
