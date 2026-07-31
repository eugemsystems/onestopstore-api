<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Attachment;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    protected $storeId;
    protected $isVendor;
    protected $currency;
    protected $importedCount = 0;
    protected $failedCount = 0;
    protected $errors = [];
    protected $importedProductIds = [];
    protected $progressCallback = null; // Callback for progress updates

    public function __construct($storeId, $isVendor = false, $currency = null)
    {
        $this->storeId = $storeId;
        $this->isVendor = $isVendor;
        $this->currency = $currency;
    }

    /**
     * Set callback for progress updates
     */
    public function setProgressCallback(callable $callback)
    {
        $this->progressCallback = $callback;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Increase execution time for large imports
        set_time_limit(0); // UNLIMITED - no timeout
        ini_set('memory_limit', '1G'); // More memory

        // Report total rows
        $totalRows = $rows->count();
        if ($this->progressCallback) {
            call_user_func($this->progressCallback, "📝 Total rows to process: {$totalRows}");
            call_user_func($this->progressCallback, "⚡ Using BULK processing for maximum speed...");
        }

        // CRITICAL: Disable ALL model events to prevent timeout
        Product::flushEventListeners();
        Product::unsetEventDispatcher();

        // Pre-cache categories and tags
        $categoriesCache = Category::where('status', 1)
            ->select('id', 'name', 'parent_id')
            ->get()
            ->keyBy('id');

        $tagsCache = Tag::select('id', 'name')->get()->keyBy('name');

        // Prepare all products data for BULK insert/update
        $productsData = [];
        $imageData = []; // Store image URLs for later processing
        $relationshipsData = []; // Store categories/tags for later processing

        foreach ($rows as $index => $row) {
            try {
                $rowNumber = $index + 2;

                // Show progress every 100 rows (less frequent for speed)
                if ($this->progressCallback && ($index % 100 === 0 || $index === 0)) {
                    $percentage = round(($index / $totalRows) * 100);
                    call_user_func($this->progressCallback, "⏳ Preparing row {$rowNumber}/{$totalRows} ({$percentage}%)...");
                }

                // Validate required fields
                if (empty($row['name'])) {
                    $this->errors[] = "Row {$rowNumber}: Product name is required";
                    $this->failedCount++;
                    continue;
                }

                if (empty($row['price']) || floatval($row['price']) <= 0) {
                    $this->errors[] = "Row {$rowNumber}: Valid price is required";
                    $this->failedCount++;
                    continue;
                }

                // Generate unique slug
                $slug = $this->generateUniqueSlug($row['name']);

                // Convert prices from selected currency to USD
                $priceInOriginalCurrency = floatval($row['price']);
                $salePriceInOriginalCurrency = !empty($row['sale_price']) ? floatval($row['sale_price']) : null;

                // Convert to USD (all prices stored in USD)
                $priceInUSD = $this->convertToUSD($priceInOriginalCurrency);
                $salePriceInUSD = $salePriceInOriginalCurrency ? $this->convertToUSD($salePriceInOriginalCurrency) : null;

                // Prepare comprehensive product data
                $productData = [
                    // Basic Info
                    'name' => $row['name'],
                    'slug' => $slug,
                    'sku' => $row['sku'] ?? 'SKU-' . strtoupper(Str::random(8)),
                    'short_description' => $row['short_description'] ?? '',
                    'description' => $row['description'] ?? '',
                    'type' => $row['type'] ?? 'simple',
                    'unit' => $row['unit'] ?? null,
                    'weight' => !empty($row['weight']) ? intval($row['weight']) : null,

                    // Pricing (stored in USD)
                    'price' => $priceInUSD,
                    'sale_price' => $salePriceInUSD,
                    'discount' => !empty($row['discount']) ? floatval($row['discount']) : null,

                    // Inventory
                    'quantity' => intval($row['quantity'] ?? 0),
                    'stock_status' => $row['stock_status'] ?? 'in_stock',

                    // Shipping
                    'shipping_days' => !empty($row['shipping_days']) ? intval($row['shipping_days']) : null,
                    'is_free_shipping' => !empty($row['is_free_shipping']) ? intval($row['is_free_shipping']) : 0,
                    'has_expedited_shipping' => !empty($row['has_expedited_shipping']) ? intval($row['has_expedited_shipping']) : 0,
                    'standard_shipping_days' => $row['standard_shipping_days'] ?? null,
                    'expedited_shipping_days' => $row['expedited_shipping_days'] ?? null,
                    'standard_shipping_price' => !empty($row['standard_shipping_price']) ? floatval($row['standard_shipping_price']) : null,
                    'expedited_shipping_price' => !empty($row['expedited_shipping_price']) ? floatval($row['expedited_shipping_price']) : null,

                    // Sale Settings
                    'is_sale_enable' => !empty($row['is_sale_enable']) ? intval($row['is_sale_enable']) : 0,
                    'sale_starts_at' => $this->parseDate($row['sale_starts_at'] ?? null),
                    'sale_expired_at' => $this->parseDate($row['sale_expired_at'] ?? null),

                    // Features
                    'is_featured' => !empty($row['is_featured']) ? intval($row['is_featured']) : 0,
                    'is_trending' => !empty($row['is_trending']) ? intval($row['is_trending']) : 0,
                    'is_return' => !empty($row['is_return']) ? intval($row['is_return']) : 0,
                    'is_cod' => !empty($row['is_cod']) ? intval($row['is_cod']) : 0,

                    // External Product
                    'is_external' => !empty($row['is_external']) ? intval($row['is_external']) : 0,
                    'external_url' => $row['external_url'] ?? null,
                    'external_button_text' => $row['external_button_text'] ?? null,

                    // SEO
                    'meta_title' => $row['meta_title'] ?? $row['name'],
                    'meta_description' => $row['meta_description'] ?? '',

                    // Policy Text
                    'estimated_delivery_text' => $row['estimated_delivery_text'] ?? null,
                    'return_policy_text' => $row['return_policy_text'] ?? null,

                    // Trust Badges
                    'safe_checkout' => !empty($row['safe_checkout']) ? intval($row['safe_checkout']) : 1,
                    'secure_checkout' => !empty($row['secure_checkout']) ? intval($row['secure_checkout']) : 1,
                    'social_share' => !empty($row['social_share']) ? intval($row['social_share']) : 1,
                    'encourage_order' => !empty($row['encourage_order']) ? intval($row['encourage_order']) : 1,
                    'encourage_view' => !empty($row['encourage_view']) ? intval($row['encourage_view']) : 1,

                    // Status
                    'status' => $this->isVendor ? 0 : (!empty($row['status']) ? intval($row['status']) : 1),
                    'is_approved' => $this->isVendor ? 0 : 1,

                    // Relations
                    'store_id' => $this->storeId,
                    'created_by_id' => auth()->id(),
                    'tax_id' => !empty($row['tax_id']) ? intval($row['tax_id']) : null,

                    // Original Currency Info (for display purposes)
                    'original_currency_code' => $this->currency ? $this->currency->code : 'USD',
                    'original_price' => $priceInOriginalCurrency,
                    'original_sale_price' => $salePriceInOriginalCurrency,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Add to bulk array instead of inserting one by one
                $productsData[] = $productData;

                // Store image URLs for later (associate with SKU)
                // Column names match CSV template: thumbnail_url, gallery_urls
                if (!empty($row['thumbnail_url']) || !empty($row['gallery_urls'])) {
                    $imageData[$productData['sku']] = [
                        'thumbnail' => $row['thumbnail_url'] ?? null,
                        'galleries' => $row['gallery_urls'] ?? null,
                    ];
                }

                // Store categories and tags for later (associate with SKU)
                $relationshipsData[$productData['sku']] = [
                    'categories' => !empty($row['categories']) ? $this->handleCategories($row['categories'], $rowNumber) : [],
                    'tags' => !empty($row['tags']) ? $this->handleTags($row['tags']) : [],
                ];

                // Track for success count
                $this->importedCount++;

            } catch (\Exception $e) {
                $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
                $this->failedCount++;
                Log::error("Product import error row {$rowNumber}: " . $e->getMessage());
            }
        }

        // BULK UPSERT - Insert/Update products in CHUNKS (avoid PostgreSQL parameter limit)
        if (!empty($productsData)) {
            if ($this->progressCallback) {
                call_user_func($this->progressCallback, "");
                call_user_func($this->progressCallback, "🚀 BULK inserting/updating " . count($productsData) . " products...");
            }

            try {
                // PostgreSQL has a parameter limit of 65,535
                // With ~49 fields per product: 65535 / 49 = ~1337 products max
                // Use chunks of 100 for safety and better progress tracking
                $chunks = array_chunk($productsData, 100);
                $totalChunks = count($chunks);
                $processedCount = 0;

                foreach ($chunks as $chunkIndex => $chunk) {
                    // Use PostgreSQL upsert (INSERT ... ON CONFLICT UPDATE)
                    DB::table('products')->upsert(
                        $chunk,
                        ['sku'], // Unique key to check
                        array_keys($chunk[0]) // Columns to update if SKU exists
                    );

                    $processedCount += count($chunk);

                    // Show progress every 5 chunks or on last chunk
                    if ($this->progressCallback && (($chunkIndex + 1) % 5 === 0 || ($chunkIndex + 1) === $totalChunks)) {
                        $percentage = round(($processedCount / count($productsData)) * 100);
                        call_user_func($this->progressCallback, "⚡ Processed {$processedCount}/" . count($productsData) . " products ({$percentage}%)...");
                    }
                }

                if ($this->progressCallback) {
                    call_user_func($this->progressCallback, "✅ Bulk operation complete - " . count($productsData) . " products processed!");
                }

                // NOW attach categories and tags after products are in database
                if (!empty($relationshipsData)) {
                    if ($this->progressCallback) {
                        call_user_func($this->progressCallback, "");
                        call_user_func($this->progressCallback, "🔗 Attaching categories and tags to products...");
                    }

                    $processedRelationships = 0;
                    $totalRelationships = count($relationshipsData);

                    foreach ($relationshipsData as $sku => $relationships) {
                        try {
                            // Find the product by SKU
                            $product = Product::where('sku', $sku)->first();

                            if ($product) {
                                // Attach categories
                                if (!empty($relationships['categories'])) {
                                    $product->categories()->sync($relationships['categories']);
                                }

                                // Attach tags
                                if (!empty($relationships['tags'])) {
                                    $product->tags()->sync($relationships['tags']);
                                }

                                // Update search_keywords after attaching relationships
                                $product->refresh();
                                $categories = $product->categories->pluck('name')->implode(' ');
                                $tags = $product->tags->pluck('name')->implode(' ');
                                $keywords = "{$product->name} {$product->sku} {$categories} {$tags}";
                                $product->search_keywords = ucwords(trim($keywords));
                                $product->save();
                            }

                            $processedRelationships++;

                            // Show progress every 100 products
                            if ($this->progressCallback && ($processedRelationships % 100 === 0 || $processedRelationships === $totalRelationships)) {
                                $percentage = round(($processedRelationships / $totalRelationships) * 100);
                                call_user_func($this->progressCallback, "⚡ Attached relationships to {$processedRelationships}/{$totalRelationships} products ({$percentage}%)...");
                            }

                        } catch (\Exception $e) {
                            Log::error("Failed to attach relationships for SKU {$sku}: " . $e->getMessage());
                        }
                    }

                    if ($this->progressCallback) {
                        call_user_func($this->progressCallback, "✅ Categories and tags attached!");
                    }
                }

                // NOW process images after products are in database
                if (!empty($imageData)) {
                    if ($this->progressCallback) {
                        call_user_func($this->progressCallback, "");
                        call_user_func($this->progressCallback, "🖼️  Processing product images...");
                    }

                    $processedImages = 0;
                    $totalProductsWithImages = count($imageData);

                    foreach ($imageData as $sku => $images) {
                        try {
                            // Find the product by SKU
                            $product = Product::where('sku', $sku)->first();

                            if (!$product) {
                                Log::warning("Product not found for SKU {$sku} when processing images");
                                continue;
                            }

                            // Process thumbnail
                            if (!empty($images['thumbnail'])) {
                                $thumbnailUrl = trim($images['thumbnail']);
                                if (!empty($thumbnailUrl)) {
                                    try {
                                        $attachmentId = DB::table('attachments')->insertGetId([
                                            'file_name' => $thumbnailUrl,
                                            'image_url' => $thumbnailUrl,
                                            'disk' => 'public',
                                            'created_by_id' => auth()->id() ?? 1,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);

                                        $product->product_thumbnail_id = $attachmentId;
                                    } catch (\Exception $e) {
                                        Log::error("Failed to create thumbnail attachment for SKU {$sku}: " . $e->getMessage());
                                    }
                                }
                            }


                            // Save product with attachment IDs
                            if ($product->isDirty()) {
                                $product->save();
                            }

                            // Process galleries (comma-separated URLs)
                            if (!empty($images['galleries'])) {
                                $galleryUrls = array_map('trim', explode(',', $images['galleries']));
                                $galleryAttachmentIds = [];

                                foreach ($galleryUrls as $galleryUrl) {
                                    if (empty($galleryUrl)) {
                                        continue;
                                    }

                                    try {
                                        $attachmentId = DB::table('attachments')->insertGetId([
                                            'file_name' => $galleryUrl,
                                            'image_url' => $galleryUrl,
                                            'disk' => 'public',
                                            'created_by_id' => auth()->id() ?? 1,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);

                                        $galleryAttachmentIds[] = $attachmentId;
                                    } catch (\Exception $e) {
                                        Log::error("Failed to create gallery attachment for SKU {$sku}: " . $e->getMessage());
                                    }
                                }

                                // Attach galleries to product (creates records in product_images table)
                                if (!empty($galleryAttachmentIds)) {
                                    $product->product_galleries()->attach($galleryAttachmentIds);
                                }
                            }

                            $processedImages++;

                            // Show progress every 50 products
                            if ($this->progressCallback && ($processedImages % 50 === 0 || $processedImages === $totalProductsWithImages)) {
                                $percentage = round(($processedImages / $totalProductsWithImages) * 100);
                                call_user_func($this->progressCallback, "⚡ Processed images for {$processedImages}/{$totalProductsWithImages} products ({$percentage}%)...");
                            }

                        } catch (\Exception $e) {
                            Log::error("Failed to process images for SKU {$sku}: " . $e->getMessage());
                        }
                    }

                    if ($this->progressCallback) {
                        call_user_func($this->progressCallback, "✅ Product images processed!");
                    }
                }

            } catch (\Exception $e) {
                if ($this->progressCallback) {
                    call_user_func($this->progressCallback, "❌ Bulk insert failed: " . $e->getMessage());
                }
                Log::error("Bulk product insert failed: " . $e->getMessage());
            }
        }

        if ($this->progressCallback) {
            call_user_func($this->progressCallback, "");
        }
    }

    /**
     * Generate unique slug
     */
    protected function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Store image URL directly in attachments (NO DOWNLOAD - like fast import!)
     * This is MUCH faster - stores URLs only, images loaded from external server
     */
    protected function storeImageUrl($url)
    {
        try {
            // Validate URL
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                return null;
            }

            // Check if attachment already exists for this URL
            $existing = Attachment::where('image_url', $url)->first();
            if ($existing) {
                return $existing->id;
            }

            // Create attachment record with URL only (NO DOWNLOAD!)
            $attachment = Attachment::create([
                'image_url' => $url,
                'file_name' => $url,
                'disk' => 'public',
                'name' => basename(parse_url($url, PHP_URL_PATH)) ?: 'image',
                'path' => null, // No local path - image is external
                'mime_type' => 'image/jpeg', // Default - not critical
                'size' => 0, // Unknown - doesn't matter for external URLs
                'created_by_id' => auth()->id(),
            ]);

            return $attachment->id;

        } catch (\Exception $e) {
            Log::error("Failed to store image URL {$url}: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Handle hierarchical categories with auto-creation
     * Format: "Parent > Child > Grandchild" or "CategoryID" or "CategoryName"
     */
    protected function handleCategories($categoriesString, $rowNumber)
    {
        $categoryPaths = array_map('trim', explode(',', $categoriesString));
        $categoryIds = [];

        foreach ($categoryPaths as $categoryPath) {
            // Check if it's a numeric ID
            if (is_numeric($categoryPath)) {
                $category = Category::find(intval($categoryPath));
                if ($category) {
                    $categoryIds[] = $category->id;
                }
                continue;
            }

            // Handle hierarchical path (Parent > Child > Grandchild)
            if (strpos($categoryPath, '>') !== false) {
                $hierarchy = array_map('trim', explode('>', $categoryPath));
                $parentId = null;

                foreach ($hierarchy as $categoryName) {
                    $category = $this->findOrCreateCategory($categoryName, $parentId);
                    $parentId = $category->id;
                }

                if ($parentId) {
                    $categoryIds[] = $parentId;
                }
            } else {
                // Single category name
                $category = $this->findOrCreateCategory($categoryPath, null);
                if ($category) {
                    $categoryIds[] = $category->id;
                }
            }
        }

        return array_unique($categoryIds);
    }

    /**
     * Find existing category or create new one
     */
    protected function findOrCreateCategory($name, $parentId = null)
    {
        // Try to find existing category by name (case-insensitive)
        $query = Category::where(DB::raw('LOWER(name)'), strtolower($name));

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        $category = $query->first();

        // If not found by name, try by slug
        if (!$category) {
            $slug = Str::slug($name);
            $query = Category::where('slug', $slug);

            if ($parentId) {
                $query->where('parent_id', $parentId);
            } else {
                $query->whereNull('parent_id');
            }

            $category = $query->first();
        }

        // Create if not exists
        if (!$category) {
            \Log::warning("Category '{$name}' not found, creating new one");

            $category = Category::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'parent_id' => $parentId,
                'status' => 0, // Inactive by default, needs admin approval
                'description' => "Auto-created from product import",
            ]);
        }

        return $category;
    }

    /**
     * Handle tags (auto-create if not exists)
     */
    protected function handleTags($tagsString)
    {
        $tagNames = array_map('trim', explode(',', $tagsString));
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            if (empty($tagName)) continue;

            $tag = Tag::firstOrCreate(
                ['name' => $tagName],
                [
                    'slug' => Str::slug($tagName),
                    'status' => 1
                ]
            );

            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }

    /**
     * Convert price from selected currency to USD
     */
    protected function convertToUSD($price)
    {
        if (!$this->currency || empty($price)) {
            return $price;
        }

        // If currency is already USD, no conversion needed
        if (strtoupper($this->currency->code) === 'USD') {
            return $price;
        }

        // Get exchange rate (rate is how many units of currency = 1 USD)
        $exchangeRate = floatval($this->currency->exchange_rate);

        if ($exchangeRate <= 0) {
            return $price; // Fallback if no valid rate
        }

        // Convert to USD: original price / exchange rate
        // Example: 100 ZAR / 18.5 (ZAR per USD) = 5.41 USD
        $priceInUSD = $price / $exchangeRate;

        return round($priceInUSD, 2);
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getFailedCount()
    {
        return $this->failedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getImportedProductIds()
    {
        return $this->importedProductIds;
    }

    /**
     * Parse date from various formats
     * Handles: d/m/Y, d-m-Y, Y-m-d, m/d/Y, etc.
     */
    protected function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        // Remove any extra whitespace
        $dateString = trim($dateString);

        // Try common date formats with strict parsing
        $formats = [
            'j/n/Y',      // 31/1/2025 or 1/1/2025 (handles single digits)
            'd/m/Y',      // 31/01/2025
            'j-n-Y',      // 31-1-2025
            'd-m-Y',      // 31-01-2025
            'd/m/y',      // 31/1/25
            'd-m-y',      // 31-1-25
            'Y-m-d',      // 2025-01-31
            'n/j/Y',      // 1/31/2025 (US format)
            'm/d/Y',      // 01/31/2025
            'j.n.Y',      // 31.1.2025
            'd.m.Y',      // 31.01.2025
            'Y/m/d',      // 2025/1/31
        ];

        foreach ($formats as $format) {
            try {
                $date = \DateTime::createFromFormat($format, $dateString);

                if ($date !== false) {
                    // Validate the date is real (e.g., not 31/2/2025)
                    $errors = \DateTime::getLastErrors();

                    if ($errors['warning_count'] === 0 && $errors['error_count'] === 0) {
                        return $date->format('Y-m-d H:i:s');
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // If all else fails, try Carbon parse
        try {
            $carbonDate = \Carbon\Carbon::parse($dateString);
            return $carbonDate->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Return null silently - dates are optional fields
            return null;
        }
    }

    /**
     * Normalize date string for comparison (handle single vs double digit days/months)
     */
    protected function normalizeDate($dateString, $format)
    {
        // For formats with single digit days/months, normalize them
        // 1/1/2025 should equal 01/01/2025
        $dateString = trim($dateString);

        // Replace single digits with double digits in common patterns
        if (strpos($format, '/') !== false) {
            // Handle d/m/Y format: 1/1/2025 -> 01/01/2025
            $parts = explode('/', $dateString);
            if (count($parts) === 3) {
                $parts[0] = str_pad($parts[0], 2, '0', STR_PAD_LEFT); // day
                $parts[1] = str_pad($parts[1], 2, '0', STR_PAD_LEFT); // month
                return implode('/', $parts);
            }
        } elseif (strpos($format, '-') !== false) {
            // Handle d-m-Y format: 1-1-2025 -> 01-01-2025
            $parts = explode('-', $dateString);
            if (count($parts) === 3 && strlen($parts[0]) <= 2) {
                $parts[0] = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                $parts[1] = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                return implode('-', $parts);
            }
        }

        return $dateString;
    }
}

