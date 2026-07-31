<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class ProductsImportFast extends Command
{
    protected $signature = 'app:products-import-fast
                        {--percentage= : Percentage markup to apply}
                        {--todaysRate= : Today\'s currency conversion rate}';


    protected $description = 'High-performance bulk import from large JSON files (1M+ products).';

    private int $chunkSize = 300;

    // Count how many total products we see in all JSON files.
    private int $totalJsonProducts = 0;

    // Count how many products already in DB (to find how many new we inserted).
    private int $productsCountBefore = 0;

    // Price conversion params read from .env
    private float $percentage;
    private float $todaysRate;

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        // 1) Load ENV values for price conversion
        $this->percentage = (float) $this->option('percentage')  ?: (float) env('PERCENTAGE', 1);
        $this->todaysRate = (float) $this->option('todaysRate')  ?: (float) env('TODAYS_RATE', 18);

        if ($this->percentage <= 0 || $this->todaysRate <= 0) {
            $this->error("❌ Invalid percentage or today's rate. Both must be > 0.");
            return 1;
        }

        // 2) Remove time/memory limits & disable DB query log

        DB::disableQueryLog();

        // 3) Capture how many products exist right now
        $this->productsCountBefore = DB::table('products')->count();

        // 4) Find all JSON files
        $directory = storage_path('app/jsonfiles');
        if (!is_dir($directory)) {
            $this->error("❌ Directory does not exist: $directory");
            return 1;
        }
        $files = glob("$directory/*.json");
        if (empty($files)) {
            $this->info("📁 No JSON files found in $directory.");
            return 0;
        }

        // 5) Process each JSON file (skipping extracted_categories.json)
        $totalFiles = count($files);
        $fileCount  = 0;

        foreach ($files as $file) {
            if (basename($file) === 'extracted_categories.json') {
                $this->info("❌ Skipping file: " . basename($file));
                continue;
            }
            $fileCount++;
            $this->info("\nProcessing file [$fileCount/$totalFiles]: " . basename($file));
            $this->processFile($file);
        }

        // 6) After processing all files, compute how many new products were inserted
        $productsCountAfter = DB::table('products')->count();
        $newlyInserted      = $productsCountAfter - $this->productsCountBefore;

        // 7) Final summary
        $this->info("\n🎉 All JSON files processed successfully!");
        $this->info("=========================================");
        $this->info("Total Json files found: {$totalFiles}");
        $this->info("Products found on JSON files: {$this->totalJsonProducts}");
        $this->info("New products actually inserted: {$newlyInserted}");
        $this->info("=========================================");

        return 0;
    }

    /**
     * Process a single JSON file's array of products.
     */
    /**
     * Process a single JSON file's array of products.
     */
    /**
     * Process a single JSON file's array of products.
     */
    private function processFile(string $filePath)
    {
        $jsonContent = File::get($filePath);
        $productsArr = json_decode($jsonContent, true);
        if (!is_array($productsArr)) {
            $this->error("❌ Invalid JSON: " . basename($filePath));
            return;
        }

        // Support both a single product object or an array of products
        if (isset($productsArr['core']) && isset($productsArr['core']['id'])) {
            $productsArr = [$productsArr];
        }

        // Track totals
        $this->totalJsonProducts += count($productsArr);
        $now = now()->toDateTimeString();

        // ============ Data Arrays & Dedup Sets ============
        $productsData             = [];
        $attachmentsData          = [];
        $attributesData           = [];
        $tagsData                 = [];
        $productAttributes        = [];
        $attributeValuesData      = [];
        $variationsData           = [];
        $reviewsData              = [];
        $productImages            = [];
        $productTags              = [];
        $variationAttributeValues = [];
        $thumbnailUpdates         = [];

        // NEW (categories)
        $categoriesData        = [];   // rows for categories (explicit id)
        $categoryLinks         = [];   // product_sku -> category_id (for pivot)
        $categoryParentHints   = [];   // child_id => parent_id (from breadcrumb chain)

        // SKUs that are genuinely in stock and should re-enable if currently disabled
        $reEnableSkus          = [];

        $seenProducts          = [];
        $seenAttachments       = [];
        $seenAttributes        = [];
        $seenTags              = [];
        $seenAttrValues        = [];
        $seenVariations        = [];
        $seenProductAttributes = [];
        $seenCategoryId        = [];

        // ============ Build Arrays from JSON ============
        foreach ($productsArr as $p) {
            $core        = $p['core'] ?? [];
            $buybox      = $p['buybox']['items'][0] ?? [];
            $desc        = $p['description']['html'] ?? '';
            $variants    = $p['variants']['selectors'] ?? [];
            $reviews     = $p['reviews']['star_rating'] ?? 0;
            $tags        = $p['top_navigation']['items'] ?? [];
            $gallery     = $p['gallery']['images'] ?? [];
            $breadcrumbs = $p['breadcrumbs']['items'] ?? [];

            // Require ID + title
            if (empty($core['id']) || empty($core['title'])) {
                continue;
            }
            $sku = $core['id'];
            if (isset($seenProducts[$sku])) {
                continue;
            }
            $seenProducts[$sku] = true;

            // Name, slug
            $name = $core['title'];
            $slug = !empty($core['slug']) ? $core['slug'] : Str::slug($name);

            // Prices
            $rawPrice  = $buybox['price'] ?? 0;
            $price     = $this->convertPrice($rawPrice);
            $rawSale   = $buybox['sale_price'] ?? null;
            $salePrice = $rawSale !== null ? $this->convertPrice($rawSale) : null;

            // Stock
            $stockStatus = !empty($variants)
                ? 'in_stock'
                : (!empty($buybox['is_add_to_cart_available']) ? 'in_stock' : 'out_of_stock');

            // --- Product row ---
            $productsData[] = [
                'sku'               => $sku,
                'name'              => $name,
                'slug'              => $slug,
                'type'              => 'simple',
                'description'       => $desc,
                'short_description' => strip_tags($desc),
                'price'             => $price,
                'quantity'          => 9999999,
                'sale_price'        => $salePrice,
                'stock_status'      => $stockStatus,
                'created_by_id'     => 1,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            // --- Thumbnail placeholder (first gallery image) ---
            $firstImg = $gallery[0] ?? null;
            $thumbnailUpdates[] = [
                'sku'   => $sku,
                'image' => $firstImg ? str_replace('{size}', 'zoom', $firstImg) : null,
            ];

            // --- Categories (create missing + remember parent links & pivots) ---
            $prevId = null; // parent chain: department -> category -> subcategory ...
            foreach ($breadcrumbs as $bc) {
                if (empty($bc['id']) || empty($bc['name'])) continue;

                $catId   = (int) $bc['id']; // Takealot ID == categories.id
                $catSlug = $bc['slug'] ?? Str::slug($bc['name']);

                if (!isset($seenCategoryId[$catId])) {
                    $seenCategoryId[$catId] = true;
                    // Upsert by explicit primary key id
                    $categoriesData[] = [
                        'id'            => $catId,
                        'name'          => $bc['name'],
                        'slug'          => $catSlug,
                        // remove fields below if your categories table doesn’t have them
                        'status'        => 1,
                        'created_by_id' => 1,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }

                if ($prevId !== null) {
                    // current category has parent = previous breadcrumb ID
                    $categoryParentHints[$catId] = $prevId; // last one wins (fine)
                }
                $prevId = $catId;

                // product ↔ category link (for pivot later)
                $categoryLinks[] = [
                    'product_sku' => $sku,
                    'category_id' => $catId,
                ];
            }

            // --- Attachments & product_images ---
            foreach ($gallery as $url) {
                $full = str_replace('{size}', 'zoom', $url);
                if (!isset($seenAttachments[$full])) {
                    $seenAttachments[$full] = true;
                    $attachmentsData[] = [
                        'image_url'     => $full,
                        'file_name'     => $full,
                        'disk'          => 'public',
                        'created_by_id' => 1,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
                $productImages[] = [
                    'product_sku'    => $sku,
                    'attachment_url' => $full,
                ];
            }

            // --- Tags & product_tags ---
            foreach ($tags as $tg) {
                $txt = $tg['text'] ?? null;
                if (!$txt) continue;
                $tSlug = Str::slug($txt);
                if (!isset($seenTags[$tSlug])) {
                    $seenTags[$tSlug] = true;
                    $tagsData[] = [
                        'slug'          => $tSlug,
                        'name'          => $txt,
                        'created_by_id' => 1,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
                $productTags[] = [
                    'product_sku' => $sku,
                    'tag_slug'    => $tSlug,
                ];
            }

            // --- Attributes, Attribute Values, Variations & VAV pivot ---
            foreach ($variants as $variant) {
                $attrTitle = $variant['title'] ?? 'Unknown';
                $attrSlug  = Str::slug($attrTitle);
                if (!isset($seenAttributes[$attrSlug])) {
                    $seenAttributes[$attrSlug] = true;
                    $attributesData[] = [
                        'slug'          => $attrSlug,
                        'name'          => $attrTitle,
                        'created_by_id' => 1,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
                $paKey = "{$sku}||{$attrSlug}";
                if (!isset($seenProductAttributes[$paKey])) {
                    $seenProductAttributes[$paKey] = true;
                    $productAttributes[] = [
                        'product_sku'    => $sku,
                        'attribute_slug' => $attrSlug,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }

                foreach ($variant['options'] ?? [] as $opt) {
                    // Value & slug
                    $val     = is_array($opt['value']) ? ($opt['value']['value'] ?? '') : ($opt['value'] ?? '');
                    $valSlug = Str::slug($val);
                    $avKey   = "{$attrSlug}||{$valSlug}";
                    if (!isset($seenAttrValues[$avKey])) {
                        $seenAttrValues[$avKey] = true;
                        $attributeValuesData[] = [
                            'attribute_slug' => $attrSlug,
                            'value_slug'     => $valSlug,
                            'value'          => $val,
                            'created_by_id'  => 1,
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ];
                    }

                    // Variation price & image
                    $rawVarPrice = $opt['price'] ?? $rawPrice;
                    $varPrice    = $this->convertPrice($rawVarPrice);
                    $img0        = $opt['image'][0] ?? null;
                    $fullOptImg  = $img0 ? str_replace('{size}', 'zoom', $img0) : null;
                    if ($fullOptImg && !isset($seenAttachments[$fullOptImg])) {
                        $seenAttachments[$fullOptImg] = true;
                        $attachmentsData[] = [
                            'image_url'     => $fullOptImg,
                            'file_name'     => $fullOptImg,
                            'disk'          => 'public',
                            'created_by_id' => 1,
                            'created_at'    => $now,
                            'updated_at'    => $now,
                        ];
                    }

                    // Variation row
                    $varKey = "{$sku}||{$val}";
                    if (!isset($seenVariations[$varKey])) {
                        $seenVariations[$varKey] = true;
                        $variationsData[] = [
                            'product_sku'        => $sku,
                            'name'               => $val,
                            'price'              => $varPrice,
                            'variation_image_id' => $fullOptImg,
                            'created_by_id'      => 1,
                            'created_at'         => $now,
                            'updated_at'         => $now,
                        ];
                    }

                    // Variation-AttributeValue pivot
                    $variationAttributeValues[] = [
                        'product_sku'    => $sku,
                        'variation_name' => $val,
                        'attribute_slug' => $attrSlug,
                        'value_slug'     => $valSlug,
                    ];
                }
            }

            // --- Reviews ---
            if (!empty($reviews)) {
                $reviewsData[] = [
                    'product_sku'   => $sku,
                    'rating'        => $reviews,
                    'created_by_id' => 1,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            // --- Re-enable candidate ---
            // Only queue for re-enable when truly in stock: add-to-cart available,
            // not a lead-time/backorder item, and physically in a distribution centre.
            $stockAvail   = $buybox['stock_availability'] ?? [];
            $isLeadtime   = $stockAvail['is_leadtime'] ?? false;
            $hasCentres   = !empty($stockAvail['distribution_centres']);
            if (!empty($buybox['is_add_to_cart_available']) && !$isLeadtime && $hasCentres) {
                $reEnableSkus[] = (string) $sku;
            }
        }

        // ============ Persist everything in a single transaction ============
        DB::transaction(function () use (
            $productsData,
            $reEnableSkus,
            $attachmentsData,
            $attributesData,
            $tagsData,
            $attributeValuesData,
            $variationsData,
            $reviewsData,
            $productImages,
            $productTags,
            $variationAttributeValues,
            $thumbnailUpdates,
            $productAttributes,
            // NEW (categories)
            $categoriesData,
            $categoryLinks,
            $categoryParentHints
        ) {
            // A) Products upsert
            $this->chunkUpsert(
                'products',
                $productsData,
                ['sku'],
                ['name','slug','type','description','short_description','price','quantity','sale_price','stock_status','updated_at']
            );

            // A2) Re-enable disabled products that are now genuinely in stock.
            // $reEnableSkus only contains SKUs where: add-to-cart available,
            // is_leadtime=false, AND a distribution centre holds physical stock.
            $this->info('[re-enable] Candidates from this file: ' . count($reEnableSkus) . ' — [' . implode(', ', array_slice($reEnableSkus, 0, 20)) . ']');
            $totalReEnabled = 0;
            if (!empty($reEnableSkus)) {
                foreach (array_chunk($reEnableSkus, 500) as $chunk) {
                    $rows = DB::table('products')
                        ->whereIn('sku', $chunk)
                        ->where(function ($q) {
                            $q->where('status', 0)->orWhere('is_approved', 0);
                        })
                        ->whereNull('deleted_at')
                        ->update(['status' => 1, 'is_approved' => 1, 'updated_at' => now()]);
                    $totalReEnabled += $rows;
                }
            }
            $this->info("[re-enable] Products turned back on: {$totalReEnabled}");

            // Build maps
            $productMap = $this->buildMap('products', 'sku', 'id', $productsData);
            $productIds = array_values($productMap);

            // B) Clean out old children
            $oldVarIds = DB::table('variations')
                ->whereIn('product_id', $productIds)
                ->whereNull('deleted_at')
                ->pluck('id')->toArray();
            if (!empty($oldVarIds)) {
                DB::table('variation_attribute_values')
                    ->whereIn('variation_id', $oldVarIds)->delete();
            }
            // Soft-delete variations to protect order_products history
            DB::table('variations')->whereIn('product_id', $productIds)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
            DB::table('product_images')->whereIn('product_id', $productIds)->delete();
            DB::table('product_tags')->whereIn('product_id', $productIds)->delete();
            DB::table('product_categories')->whereIn('product_id', $productIds)->delete();
            DB::table('reviews')->whereIn('product_id', $productIds)->delete();
            DB::table('product_attributes')->whereIn('product_id', $productIds)->delete();

            // C) Attachments, Attributes, Tags
            $this->chunkInsertOrIgnore('attachments', $attachmentsData, 'attachments');
            $this->chunkInsertOrIgnore('attributes',  $attributesData,  'attributes');
            $this->chunkInsertOrIgnore('tags',        $tagsData,        'tags');

            // D) Attribute Values (map attribute_slug -> id)
            $attrMap = $this->buildMap('attributes','slug','id',$attributesData);
            $finalAV = array_map(function($av) use ($attrMap) {
                return [
                    'attribute_id'  => $attrMap[$av['attribute_slug']] ?? null,
                    'slug'          => $av['value_slug'],
                    'value'         => $av['value'],
                    'created_by_id' => 1,
                    'created_at'    => $av['created_at'],
                    'updated_at'    => $av['updated_at'],
                ];
            }, $attributeValuesData);
            $this->chunkInsertOrIgnore('attribute_values', $finalAV, 'attribute_values');

            // E) Variations upsert
            $attachMap = $this->buildMap('attachments','image_url','id',$attachmentsData);
            $finalVars = [];
            foreach ($variationsData as $v) {
                $pid = $productMap[$v['product_sku']] ?? null;
                if (!$pid) continue;
                $finalVars[] = [
                    'product_id'         => $pid,
                    'name'               => $v['name'],
                    'price'              => $v['price'],
                    'variation_image_id' => $attachMap[$v['variation_image_id']] ?? null,
                    'created_at'         => $v['created_at'],
                    'updated_at'         => $v['updated_at'],
                ];
            }
            $this->chunkUpsert(
                'variations',
                $finalVars,
                ['product_id','name'],
                ['price','variation_image_id','updated_at']
            );

            // F) Variation-AttributeValue pivot
            $avMap  = $this->buildAVMap($finalAV);
            $varMap = $this->buildVariationMap($finalVars);
            $pivotVAV = [];
            foreach ($variationAttributeValues as $vav) {
                $pid  = $productMap[$vav['product_sku']] ?? null;
                $vid  = $varMap["{$pid}||{$vav['variation_name']}"] ?? null;
                $aid  = $attrMap[$vav['attribute_slug']] ?? null;
                $avid = $avMap["{$aid}||{$vav['value_slug']}"] ?? null;
                if ($vid && $avid) {
                    $pivotVAV[] = [
                        'variation_id'       => $vid,
                        'attribute_value_id' => $avid
                    ];
                }
            }
            $this->chunkInsertOrIgnore('variation_attribute_values', $pivotVAV, 'variation_attribute_values');

            // G) product_images pivot
            $finalPI = [];
            foreach ($productImages as $pi) {
                $pid = $productMap[$pi['product_sku']] ?? null;
                $aid = $attachMap[$pi['attachment_url']] ?? null;
                if ($pid && $aid) {
                    $finalPI[] = ['product_id'=>$pid,'attachment_id'=>$aid];
                }
            }
            $this->chunkInsertOrIgnore('product_images', $finalPI, 'product_images');

            // H) product_tags pivot
            $tagMap = $this->buildMap('tags','slug','id',$tagsData);
            $finalPT = [];
            foreach ($productTags as $pt) {
                $pid = $productMap[$pt['product_sku']] ?? null;
                $tid = $tagMap[$pt['tag_slug']] ?? null;
                if ($pid && $tid) {
                    $finalPT[] = ['product_id'=>$pid,'tag_id'=>$tid];
                }
            }
            $this->chunkInsertOrIgnore('product_tags', $finalPT, 'product_tags');

            // **CATS-1) Ensure all referenced categories exist (by explicit primary key `id`)**
            if (!empty($categoriesData)) {
                // de-dup by id (last write wins)
                $byId = [];
                foreach ($categoriesData as $row) { $byId[$row['id']] = $row; }
                $catsForUpsert = array_values($byId);

                // Upsert by id
                $this->chunkUpsert(
                    'categories',
                    $catsForUpsert,
                    ['id'],                          // conflict on PK
                    ['name','slug','updated_at']     // update if exists
                );
            }

            // **CATS-2) Patch parent_id AFTER all categories exist**
            if (!empty($categoryParentHints)) {
                // Filter only pairs where both child & parent exist now
                $existingIds = DB::table('categories')->pluck('id')->all();
                $exists = array_fill_keys($existingIds, true);
                $finalPairs = [];
                foreach ($categoryParentHints as $childId => $parentId) {
                    $childId = (int)$childId; $parentId = (int)$parentId;
                    if (isset($exists[$childId]) && isset($exists[$parentId])) {
                        $finalPairs[$childId] = $parentId;
                    }
                }

                if (!empty($finalPairs)) {
                    $ids  = array_keys($finalPairs);
                    $case = 'CASE id ';
                    foreach ($finalPairs as $child => $parent) {
                        $case .= 'WHEN '.$child.' THEN '.$parent.' ';
                    }
                    $case .= 'END';

                    DB::statement("
                    UPDATE categories
                       SET parent_id = $case,
                           updated_at = NOW()
                     WHERE id IN (".implode(',', $ids).")
                ");
                }
            }

            // I) reviews
            $finalRev = [];
            foreach ($reviewsData as $r) {
                $pid = $productMap[$r['product_sku']] ?? null;
                if ($pid) {
                    $finalRev[] = [
                        'product_id'   => $pid,
                        'rating'       => $r['rating'],
                        'created_at'   => $r['created_at'],
                        'updated_at'   => $r['updated_at'],
                    ];
                }
            }
            $this->chunkInsertOrIgnore('reviews', $finalRev, 'reviews');

            // J) Thumbnails
            foreach ($thumbnailUpdates as $tu) {
                $pid = $productMap[$tu['sku']] ?? null;
                $aid = $attachMap[$tu['image']] ?? null;
                if ($pid && $aid) {
                    DB::table('products')->where('id', $pid)
                        ->update([
                            'product_thumbnail_id'  => $aid,
                            'product_meta_image_id' => $aid,
                            'updated_at'            => now(),
                        ]);
                }
            }

            // K) product_attributes pivot
            $finalPA = [];
            foreach ($productAttributes as $pa) {
                $pid = $productMap[$pa['product_sku']] ?? null;
                $aid = $attrMap[$pa['attribute_slug']] ?? null;
                if ($pid && $aid) {
                    $finalPA[] = [
                        'product_id'   => $pid,
                        'attribute_id' => $aid,
                        'created_at'   => $pa['created_at'],
                        'updated_at'   => $pa['updated_at'],
                    ];
                }
            }
            $this->chunkInsertOrIgnore('product_attributes', $finalPA, 'product_attributes');

            // **PCATS) product_categories pivot (now safe: all categories exist & parents set)**
            $finalPC = [];
            foreach ($categoryLinks as $cl) {
                $pid = $productMap[$cl['product_sku']] ?? null;
                if ($pid) {
                    $finalPC[] = ['product_id' => $pid, 'category_id' => (int)$cl['category_id']];
                }
            }

            if (!empty($finalPC)) {
                // Safety filter (keeps FK strict; avoids abort if something slipped through)
                $existingIds = DB::table('categories')->pluck('id')->all();
                $exists = array_fill_keys($existingIds, true);

                $filtered = [];
                $missing = [];
                foreach ($finalPC as $row) {
                    if (isset($exists[$row['category_id']])) $filtered[] = $row;
                    else $missing[$row['category_id']] = true;
                }
                if ($missing) {
                    $this->warn('⚠️ Skipped product_categories for missing category IDs: ' . implode(',', array_keys($missing)));
                }

                $this->chunkInsertOrIgnore('product_categories', $filtered, 'product_categories');
            } else {
                $this->info('[product_categories] nothing to insert.');
            }
        });

        $this->info("✅ Completed file: " . basename($filePath));
    }




    /**
     * Insert or ignore rows in chunks, quietly ignoring duplicates.
     */
    private function chunkInsertOrIgnore(string $table, array $rows, string $label)
    {
        if (empty($rows)) {
            return;
        }

        $chunks = array_chunk($rows, $this->chunkSize);
        $total  = 0;

        foreach ($chunks as $index => $chunk) {
            $countChunk = count($chunk);
            $total     += $countChunk;
            try {
                DB::table($table)->insertOrIgnore($chunk);
            } catch (QueryException $e) {
                // Truncate error message to avoid huge logs
                $msg = Str::limit($e->getMessage(), 300, '... [TRUNCATED]');
                $this->error("QueryException on [$table] chunk #$index: $msg");
            }
        }

        $this->info("[$table] ($label): attempted $total rows in chunks of {$this->chunkSize}.");
    }

    /**
     * Build map from a unique column => id. e.g. 'sku' => 'id' in products table.
     */
    private function buildMap(string $table, string $uniqueCol, string $idCol, array $rows): array
    {
        // gather all unique keys from $rows
        $keys = collect($rows)->pluck($uniqueCol)->unique()->values();
        if ($keys->isEmpty()) {
            return [];
        }

        // fetch from DB
        $map = DB::table($table)
            ->whereIn($uniqueCol, $keys)
            ->pluck($idCol, $uniqueCol)
            ->toArray();

        return $map;
    }

    /**
     * Build map of (product_id+name) => variation.id in 'variations' table,
     * from the $finalVariationsData we inserted.
     */
    private function buildVariationMap(array $finalVars): array
    {
        if (empty($finalVars)) {
            return [];
        }
        // gather all product_ids
        $pIds = collect($finalVars)->pluck('product_id')->unique()->values();
        if ($pIds->isEmpty()) {
            return [];
        }

        // fetch from DB
        $rows = DB::table('variations')
            ->whereIn('product_id', $pIds)
            ->get(['id','product_id','name']);

        $map = [];
        foreach ($rows as $r) {
            $key       = $r->product_id.'||'.$r->name;
            $map[$key] = $r->id;
        }
        return $map;
    }

    /**
     * Build map of (attribute_id + value_slug) => id in 'attribute_values' table.
     */
    private function buildAVMap(array $finalAV): array
    {
        if (empty($finalAV)) {
            return [];
        }
        // gather unique attribute_ids
        $attrIds = collect($finalAV)->pluck('attribute_id')->unique()->values();
        if ($attrIds->isEmpty()) {
            return [];
        }

        // fetch them
        $rows = DB::table('attribute_values')
            ->whereIn('attribute_id', $attrIds)
            ->get(['id','attribute_id','slug']);

        $map = [];
        foreach ($rows as $r) {
            $key       = $r->attribute_id.'||'.$r->slug;
            $map[$key] = $r->id;
        }
        return $map;
    }

    /**
     * Convert price, reading PERCENTAGE & TODAYS_RATE from .env
     * final_price = round( (percentage/100 * (price/todays_rate)) + (price/todays_rate), 2)
     */
    private function convertPrice($price)
    {
        $converted = (float) $price / $this->todaysRate;
        $result    = $converted + ($this->percentage / 100 * $converted);
        return round($result, 2);
    }


    /**
     * Upsert rows in chunks.
     *
     * @param string $table         Table name
     * @param array  $rows          Array of associative rows to upsert
     * @param array  $uniqueBy      Columns to detect conflict (e.g. ['sku'])
     * @param array  $updateColumns Columns to update on conflict
     */
    private function chunkUpsert(string $table, array $rows, array $uniqueBy, array $updateColumns): void
    {
        $total = 0;
        foreach (array_chunk($rows, $this->chunkSize) as $i => $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy, $updateColumns);
            $count  = count($chunk);
            $total += $count;
            $this->info("[$table] upsert chunk #{$i}: {$count} rows");
        }
        $this->info("[$table] total upserted {$total} rows (chunk size {$this->chunkSize}).");
    }


}
