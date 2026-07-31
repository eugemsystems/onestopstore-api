<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

ini_set('memory_limit', '-1');

class ExtractCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:extract-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract category hierarchy from JSON files and store in the json file.';

    public function handle()
    {
        $directory = storage_path('app/jsonfiles');

        if (!is_dir($directory)) {
            $this->error("The specified directory {$directory} does not exist.");
            return;
        }

        $categories = [];
        $this->scanDirectory($directory, $categories);

        if (empty($categories)) {
            $this->warn("No categories found in JSON files.");
            return;
        }

        $this->saveCategoriesToJson($categories);
        //$this->storeCategories($categories);

        $this->info("✅ Categories extracted, saved to JSON, and stored in the database successfully.");
    }

    private function scanDirectory($directory, &$categories)
    {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === 'extracted_categories.json') continue;

            $filePath = $directory . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filePath)) {
                $this->scanDirectory($filePath, $categories);
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                //$this->info("🔍 Processing file: {$file}");
                $this->extractCategoriesFromJson($filePath, $categories);
            }
        }
    }

    private function extractCategoriesFromJson($filePath, &$categories)
    {
        if (!file_exists($filePath)) {
            $this->warn("❌ File not found: {$filePath}");
            return;
        }

        try {
            $jsonStream = Items::fromFile($filePath);

            foreach ($jsonStream as $product) {
                if (
                    !isset($product->breadcrumbs->items) ||
                    !is_array($product->breadcrumbs->items)
                ) {
                    $this->warn("⚠️ No breadcrumbs found for a product in file: {$filePath}");
                    continue;
                }

                $breadcrumbItems = $product->breadcrumbs->items;
                $parentId = null;

                foreach ($breadcrumbItems as $item) {
                    $categoryId = $item->id;

                    if (isset($categories[$categoryId])) {
                        $parentId = $categoryId;
                        continue;
                    }

                    $categories[$categoryId] = [
                        'id' => $categoryId,
                        'name' => $item->name,
                        'slug' => $item->slug,
                        'parent_id' => $parentId,
                        'description' => null,
                        'category_image_id' => null,
                        'category_icon_id' => null,
                        'status' => 1,
                        'type' => 'product',
                        'commission_rate' => 0.0,
                        'created_by_id' => 1,
                    ];

                    $this->info("✅ Added category: {$item->name} with slug: {$item->slug} (Parent ID: " . ($parentId ?? 'NULL') . ")");
                    $parentId = $categoryId;
                }
            }

        } catch (\Exception $e) {
            $this->warn("❌ Failed to process file {$filePath}: " . $e->getMessage());
        }
    }



    private function extractCategoriesFromJson_($filePath, &$categories){

        $jsonContent = file_get_contents($filePath);

        // Check if the file is empty
        if (empty($jsonContent)) {
            $this->warn("⚠️ Skipping empty JSON file: {$filePath}");
            return;
        }

        // Decode JSON and check for errors
        $jsonData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn("❌ JSON Error in file {$filePath}: " . json_last_error_msg());
            return;
        }

        if (!is_array($jsonData) || empty($jsonData)) {
            $this->warn("⚠️ JSON file does not contain valid data: {$filePath}");
            return;
        }

        // Process all products in the JSON file
        foreach ($jsonData as $product) {
            if (!isset($product['breadcrumbs']['items']) || !is_array($product['breadcrumbs']['items'])) {
                $this->warn("⚠️ No breadcrumbs found for a product in file: {$filePath}");
                continue; // Skip this product if breadcrumbs are missing
            }

            $breadcrumbItems = $product['breadcrumbs']['items'];
            $parentId = null; // Start with no parent

            foreach ($breadcrumbItems as $item) {
                $categoryId = $item['id'];

                // Skip category if it already exists
                if (isset($categories[$categoryId])) {
                    $parentId = $categoryId; // Set the parent_id for the next category
                    continue;
                }

                // Add new category with correct parent_id
                $categories[$categoryId] = [
                    'id' => $categoryId,
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'parent_id' => $parentId, // Ensuring hierarchy
                    'description' => null,
                    'category_image_id' => null,
                    'category_icon_id' => null,
                    'status' => 1,
                    'type' => 'product',
                    'commission_rate' => 0.0,
                    'created_by_id' => 1,
                ];

                $this->info("✅ Added category: {$item['name']} with slug: {$item['slug']} (Parent ID: " . ($parentId ?? 'NULL') . ")");

                // Update the parent ID for the next category in the hierarchy
                $parentId = $categoryId;
            }
        }
    }


    private function saveCategoriesToJson($categories)
    {
        $filePath = storage_path('app/jsonfiles/extracted_categories.json');
        file_put_contents($filePath, json_encode(array_values($categories), JSON_PRETTY_PRINT));
        $this->info("📂 Extracted categories saved to: {$filePath}");
    }

    private function storeCategories($categories)
    {
        DB::beginTransaction();
        try {
            foreach ($categories as $categoryData) {
                Category::updateOrCreate(
                    ['id' => $categoryData['id']],
                    $categoryData
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error storing categories: " . $e->getMessage());
        }
    }
}
