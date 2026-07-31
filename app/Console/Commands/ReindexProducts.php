<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\ElasticsearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReindexProducts extends Command
{
    protected $signature = 'es:reindex-products
                            {--chunk=1000}
                            {--from-id=}
                            {--to-id=}
                            {--disable-refresh}
                            {--dry-run}
                            {--recreate-index : Delete and recreate the index with new mapping}
                            {--test : Index only first 100 products for testing}
                            {--all : Index ALL products regardless of status/approval}
                            ';

    protected $description = 'Rebuild a minimal high-relevance Elasticsearch index for products without touching current functionality';

    public function handle(): int
    {
        $client = ElasticsearchService::client();
        $index = ElasticsearchService::indexName();

        $disableRefresh = (bool) $this->option('disable-refresh');
        $chunk = (int) $this->option('chunk');
        $fromId = $this->option('from-id');
        $toId = $this->option('to-id');
        $dry = (bool) $this->option('dry-run');
        $recreateIndex = (bool) $this->option('recreate-index');
        $testMode = (bool) $this->option('test');
        $indexAll = (bool) $this->option('all');

        // If recreate-index flag is set, delete the existing index first
        if ($recreateIndex) {
            try {
                $this->warn('Deleting existing index to recreate with new mapping...');
                $client->indices()->delete(['index' => $index]);
                $this->info("Index '{$index}' deleted successfully.");
            } catch (\Throwable $e) {
                $this->warn('Index does not exist or could not be deleted: ' . $e->getMessage());
            }
        }

        // Create or update index with focused schema for relevance
        $this->prepareIndex($client, $index, $disableRefresh);

        $query = DB::table('products')->select([
            'id', 'name', 'slug', 'sku', 'description', 'short_description', 'type', 'unit', 'weight', 'quantity',
            'price', 'sale_price', 'discount', 'is_sale_enable','is_gift_card',
            'is_featured', 'is_trending', 'is_return', 'is_cod', 'is_free_shipping', 'is_external', 'is_random_related_products',
            'shipping_days', 'status', 'is_approved', 'stock_status',
            'external_url', 'external_button_text', 'sale_starts_at', 'sale_expired_at',
            'meta_title', 'estimated_delivery_text', 'return_policy_text',
            'safe_checkout', 'secure_checkout', 'social_share', 'encourage_order', 'encourage_view',
            'product_thumbnail_id', 'product_meta_image_id', 'size_chart_image_id',
            'store_id', 'created_by_id', 'tax_id', 'brand_id', 'created_at',
            'zambia_only', 'zimbabwe_only', 'sa_only'
        ]);

        // Apply filters based on mode
        if (!$indexAll) {
            // Normal mode: only index approved and active products
            $query->where('status', 1)->where('is_approved', 1);
            $this->info('Mode: Indexing only approved and active products (status=1, is_approved=1)');
        } else {
            // Index all mode: no filters
            $this->warn('Mode: Indexing ALL products regardless of status/approval');
        }

        if ($testMode) {
            $this->warn('TEST MODE: Limiting to first 100 products');
            $query->limit(100);
        }

        $query->orderBy('id');

        if ($fromId !== null) {
            $query->where('id', '>=', (int)$fromId);
        }
        if ($toId !== null) {
            $query->where('id', '<=', (int)$toId);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->error('No products found to index!');
            $this->warn('Query filters:');
            if (!$indexAll) {
                $this->warn('  - status = 1');
                $this->warn('  - is_approved = 1');
            }
            if ($fromId) $this->warn("  - id >= {$fromId}");
            if ($toId) $this->warn("  - id <= {$toId}");
            $this->info('');
            $this->info('Checking product counts in database:');
            $totalProducts = DB::table('products')->count();
            $statusOne = DB::table('products')->where('status', 1)->count();
            $approvedOne = DB::table('products')->where('is_approved', 1)->count();
            $both = DB::table('products')->where('status', 1)->where('is_approved', 1)->count();
            $this->info("  Total products: {$totalProducts}");
            $this->info("  Products with status=1: {$statusOne}");
            $this->info("  Products with is_approved=1: {$approvedOne}");
            $this->info("  Products with BOTH: {$both}");
            $this->info('');
            $this->warn('Suggestions:');
            if ($both === 0) {
                $this->warn('  1. Use --all flag to index all products regardless of status');
                $this->warn('  2. Set status=1 and is_approved=1 on some products in the database');
                $this->warn('  3. Use --test flag to test with first 100 products');
            }
            return self::FAILURE;
        }

        $this->info("Total to index: {$total} (chunk={$chunk})");

        // Disable progress bar - we'll use our own single-line progress display
        $progress = null;

        $processed = 0;
        $lastReportedPercent = -1;

        // Derive accessory/parts category slugs from DB (name contains 'part' or 'accessor') to prefer DB-driven classification
        $accessoryCategorySlugs = DB::table('categories')
            ->where(function ($q) {
                $q->whereRaw("LOWER(name) LIKE ?", ['%part%'])
                    ->orWhereRaw("LOWER(name) LIKE ?", ['%accessor%']);
            })
            ->pluck('slug')
            ->filter()
            ->map(fn($s) => strtolower($s))
            ->unique()
            ->values()
            ->all();

        $query->chunkById($chunk, function ($rows) use ($client, $index, $progress, &$processed, $dry, $total, &$lastReportedPercent) {
            $ids = $rows->pluck('id')->all();

            // load category rows (name, slug, parent_id)
            $categoryRows = DB::table('product_categories as pc')
                ->join('categories as c', 'c.id', '=', 'pc.category_id')
                ->whereIn('pc.product_id', $ids)
                ->select('pc.product_id', 'c.name', 'c.slug', 'c.parent_id')
                ->get();

            $categoryGroup = $categoryRows->groupBy('product_id');

            // collect parent ids
            $parentIds = $categoryRows->pluck('parent_id')->filter()->unique()->values()->all();
            $parentRows = collect();
            if (!empty($parentIds)) {
                $parentRows = DB::table('categories')
                    ->whereIn('id', $parentIds)
                    ->select('id', 'slug')
                    ->get()
                    ->keyBy('id');
            }

            // build a simple tags map
            $tagMap = DB::table('product_tags as pt')
                ->join('tags as t', 't.id', '=', 'pt.tag_id')
                ->whereIn('pt.product_id', $ids)
                ->select('pt.product_id', 't.name')
                ->get()
                ->groupBy('product_id')
                ->map(fn($g) => $g->pluck('name')->all());

            // Build brand map
            $brandIds = $rows->pluck('brand_id')->filter()->unique()->values()->all();
            $brandMap = collect();
            if (!empty($brandIds)) {
                $brandMap = DB::table('product_brands')
                    ->whereIn('id', $brandIds)
                    ->select('id', 'name', 'slug')
                    ->get()
                    ->keyBy('id');
            }

            // Thumbnails - get full attachment object
            $thumbMap = DB::table('products as p')
                ->leftJoin('attachments as a', 'a.id', '=', 'p.product_thumbnail_id')
                ->whereIn('p.id', $ids)
                ->select('p.id as product_id', 'a.id', 'a.uuid', 'a.name', 'a.disk', 'a.file_name', 'a.image_url', 'a.original_url', 'a.created_by_id', 'a.created_at')
                ->get()
                ->keyBy('product_id')
                ->map(function($thumb) {
                    if (!$thumb->id) return null;
                    return [
                        'id' => (int)$thumb->id,
                        'uuid' => $thumb->uuid,
                        'name' => $thumb->name,
                        'disk' => $thumb->disk ?? 'public',
                        'file_name' => $thumb->file_name,
                        'image_url' => $thumb->image_url,
                        'takealot_url' => url('/proxy-image?url=' . urlencode($thumb->image_url)),
                        'original_url' => $thumb->original_url,
                        'created_by_id' => $thumb->created_by_id ? (int)$thumb->created_by_id : null,
                        'created_at' => $thumb->created_at ? str_replace(' ', 'T', $thumb->created_at) : null,
                    ];
                });

            // Product meta images - get full attachment object
            $metaImageMap = DB::table('products as p')
                ->leftJoin('attachments as a', 'a.id', '=', 'p.product_meta_image_id')
                ->whereIn('p.id', $ids)
                ->select('p.id as product_id', 'a.id', 'a.uuid', 'a.name', 'a.disk', 'a.file_name', 'a.image_url', 'a.original_url', 'a.created_by_id', 'a.created_at')
                ->get()
                ->keyBy('product_id')
                ->map(function($img) {
                    if (!$img->id) return null;
                    return [
                        'id' => (int)$img->id,
                        'uuid' => $img->uuid,
                        'name' => $img->name,
                        'disk' => $img->disk ?? 'public',
                        'file_name' => $img->file_name,
                        'image_url' => $img->image_url,
                        'takealot_url' => null,
                        'original_url' => $img->original_url,
                        'created_by_id' => $img->created_by_id ? (int)$img->created_by_id : null,
                        'created_at' => $img->created_at ? str_replace(' ', 'T', $img->created_at) : null,
                    ];
                });

            // Galleries - return as array of objects to match database API format
            $galleryMap = DB::table('product_images as pi')
                ->join('attachments as a', 'a.id', '=', 'pi.attachment_id')
                ->whereIn('pi.product_id', $ids)
                ->select('pi.product_id', 'a.id', 'a.uuid', 'a.name', 'a.disk', 'a.file_name', 'a.image_url', 'a.original_url')
                ->orderBy('pi.product_id')
                ->orderBy('pi.id')
                ->get()
                ->groupBy('product_id')
                ->map(function($galleries) {
                    // Return array of all gallery objects (not just first)
                    return $galleries->map(function($g) {
                        return [
                            'id' => (int)$g->id,
                            'uuid' => $g->uuid,
                            'name' => $g->name,
                            'disk' => $g->disk ?? 'public',
                            'file_name' => $g->file_name,
                            'image_url' => $g->image_url,
                            'takealot_url' => null,
                            'original_url' => $g->original_url,
                        ];
                    })->all();
                });

            // Reviews - get full review objects with user data
            $reviewRows = DB::table('reviews as r')
                ->leftJoin('users as u', 'u.id', '=', 'r.consumer_id')
                ->whereIn('r.product_id', $ids)
                ->whereNull('r.deleted_at')
                ->select(
                    'r.id', 'r.product_id', 'r.consumer_id', 'r.rating', 'r.description',
                    'r.created_at', 'r.updated_at',
                    'u.name as consumer_name', 'u.email as consumer_email',
                    'u.profile_image_id as consumer_profile_image_id'
                )
                ->orderBy('r.product_id')
                ->orderBy('r.created_at', 'desc')
                ->get()
                ->groupBy('product_id')
                ->map(function($reviews) {
                    return $reviews->map(function($r) {
                        return [
                            'id' => (int)$r->id,
                            'consumer_id' => $r->consumer_id ? (int)$r->consumer_id : null,
                            'product_id' => (int)$r->product_id,
                            'rating' => (int)$r->rating,
                            'description' => $r->description,
                            'created_at' => $r->created_at ? str_replace(' ', 'T', $r->created_at) : null,
                            'updated_at' => $r->updated_at ? str_replace(' ', 'T', $r->updated_at) : null,
                            'consumer' => $r->consumer_id ? [
                                'id' => (int)$r->consumer_id,
                                'name' => $r->consumer_name,
                                'email' => $r->consumer_email,
                                'profile_image_id' => $r->consumer_profile_image_id ? (int)$r->consumer_profile_image_id : null,
                            ] : null,
                        ];
                    })->all();
                });

            // Calculate review stats from the reviews
            $reviewStatsMap = $reviewRows->map(function($reviews) {
                if (empty($reviews)) {
                    return [
                        'avg' => null,
                        'count' => 0,
                        'ratings_distribution' => [0, 0, 0, 0, 0] // [1-star, 2-star, 3-star, 4-star, 5-star]
                    ];
                }
                $ratings = array_column($reviews, 'rating');

                // Calculate distribution: count of each star rating
                $distribution = [0, 0, 0, 0, 0]; // Index 0=1-star, 1=2-star, 2=3-star, 3=4-star, 4=5-star
                foreach ($ratings as $rating) {
                    $index = (int)$rating - 1; // Convert rating (1-5) to index (0-4)
                    if ($index >= 0 && $index < 5) {
                        $distribution[$index]++;
                    }
                }

                return [
                    'avg' => array_sum($ratings) / count($ratings),
                    'count' => count($reviews),
                    'ratings_distribution' => $distribution,
                ];
            });

            // Variations
            $variRows = DB::table('variations as v')
                ->leftJoin('attachments as a', 'a.id', '=', 'v.variation_image_id')
                ->whereIn('v.product_id', $ids)
                ->select('v.id', 'v.product_id', 'v.name', 'v.sku', 'v.price', 'v.sale_price', 'v.stock_status', 'a.image_url', 'a.original_url')
                ->get();

            $varIds = $variRows->pluck('id')->all();
            $varAttrMap = collect();
            if (!empty($varIds)) {
                $varAttrMap = DB::table('variation_attribute_values as vav')
                    ->join('attribute_values as av', 'av.id', '=', 'vav.attribute_value_id')
                    ->join('attributes as attr', 'attr.id', '=', 'av.attribute_id')
                    ->whereIn('vav.variation_id', $varIds)
                    ->select('vav.variation_id', 'av.id', 'av.value', 'av.slug', 'av.hex_color', 'av.attribute_id', 'attr.name as attribute_name')
                    ->get()
                    ->groupBy('variation_id')
                    ->map(fn($g) => $g->map(fn($attr) => [
                        'id' => (int)$attr->id,
                        'value' => $attr->value,
                        'slug' => $attr->slug,
                        'hex_color' => $attr->hex_color,
                        'attribute_id' => (int)$attr->attribute_id,
                        'attribute_name' => $attr->attribute_name,
                    ])->all());
            }

            $variationsMap = $variRows->groupBy('product_id')->map(function ($rows) use ($varAttrMap) {
                $variations = collect($rows)->map(function ($r) use ($varAttrMap) {
                    $variation = [
                        'id' => (int)$r->id,
                        'name' => (string)$r->name,
                        'sku' => (string)$r->sku,
                        'price' => (float)($r->price ?? 0),
                        'sale_price' => (float)($r->sale_price ?? 0),
                        'stock_status' => $r->stock_status,
                        'image' => $r->image_url ?? $r->original_url,
                        'attribute_values' => $varAttrMap[$r->id] ?? [],
                    ];
                    // Unset to free memory
                    unset($r);
                    return $variation;
                })->take(12)->values()->all();

                return $variations;
            });

            // Clear variable to free memory
            unset($variRows, $varAttrMap);

            $params = ['body' => []];

            foreach ($rows as $row) {
                $catRowsForProduct = $categoryGroup[$row->id] ?? collect();
                $categories = $catRowsForProduct->pluck('name')->all();
                $categorySlugs = $catRowsForProduct->pluck('slug')->filter()->map(fn($s) => strtolower($s))->values()->all();

                // resolve parent slugs
                $categoryParentIds = $catRowsForProduct->pluck('parent_id')->filter()->unique()->values()->all();
                $parentSlugsForProduct = [];
                foreach ($categoryParentIds as $pid) {
                    if (isset($parentRows[$pid]) && isset($parentRows[$pid]->slug)) {
                        $parentSlugsForProduct[] = strtolower($parentRows[$pid]->slug);
                    }
                }

                $tags = $tagMap[$row->id] ?? [];

                // Get brand data
                $brand = null;
                if ($row->brand_id && isset($brandMap[$row->brand_id])) {
                    $brandData = $brandMap[$row->brand_id];
                    $brand = [
                        'name' => $brandData->name,
                        'slug' => $brandData->slug,
                    ];
                }

                $title = (string)($row->name ?? '');
                $sku = (string)($row->sku ?? '');
                $desc = (string)($row->short_description ?? $row->description ?? '');

                $productThumbnail = $thumbMap[$row->id] ?? null;
                $productMetaImage = $metaImageMap[$row->id] ?? null;
                $thumbUrl = $productThumbnail ? ($productThumbnail['image_url'] ?? null) : null;
                $productGalleries = $galleryMap[$row->id] ?? []; // Array of gallery objects

                // Reviews - full objects array
                $reviews = $reviewRows[$row->id] ?? [];
                $reviewStats = $reviewStatsMap[$row->id] ?? ['avg' => null, 'count' => 0, 'ratings_distribution' => [0, 0, 0, 0, 0]];
                $rating = $reviewStats['avg'];
                $reviewsCount = $reviewStats['count'];
                $reviewRatings = $reviewStats['ratings_distribution'];

                $variations = $variationsMap[$row->id] ?? [];

                // flag if top-level category exists
                $categoryIsTopLevel = false;
                foreach ($catRowsForProduct as $c) {
                    if ($c->parent_id === null) {
                        $categoryIsTopLevel = true;
                        break;
                    }
                }

                // NEW: detect parent category slug
                $parentCategorySlug = null;
                foreach ($catRowsForProduct as $c) {
                    if ($c->parent_id === null) {
                        $parentCategorySlug = strtolower($c->slug);
                        break;
                    }
                }

                // accessory/part detection (simplified here, keep your old logic if needed)
                $isPart = false;
                foreach (['part','spare','accessory'] as $term) {
                    if (str_contains(strtolower($title), $term)) {
                        $isPart = true;
                        break;
                    }
                }

                // Build document for Elasticsearch
                // NOTE: This structure should match Product::toSearchableArray()
                // When adding/removing fields, update both places for consistency
                $doc = [
                    // Basic product info
                    'id' => (int)$row->id,
                    'name' => $title,
                    'slug' => $row->slug,
                    'short_description' => $row->short_description ?? '',
                    'type' => $row->type ?? 'simple',
                    'unit' => $row->unit,
                    'weight' => $row->weight,
                    'quantity' => (int)($row->quantity ?? 0),

                    // Pricing
                    'price' => (float)($row->price ?? 0),
                    'sale_price' => $row->sale_price ? (float)$row->sale_price : null,
                    'discount' => $row->discount ? (int)$row->discount : null,
                    'is_sale_enable' => (int)($row->is_sale_enable ?? 0),
                    'sale_starts_at' => $row->sale_starts_at ? str_replace(' ', 'T', $row->sale_starts_at) : null,
                    'sale_expired_at' => $row->sale_expired_at ? str_replace(' ', 'T', $row->sale_expired_at) : null,

                    // Features/flags
                    'is_featured' => (int)($row->is_featured ?? 0),
                    'is_trending' => (int)($row->is_trending ?? 0),
                    'is_return' => (int)($row->is_return ?? 0),
                    'is_cod' => (int)($row->is_cod ?? 0),
                    'is_free_shipping' => (int)($row->is_free_shipping ?? 0),
                    'is_external' => (int)($row->is_external ?? 0),
                    'is_random_related_products' => (int)($row->is_random_related_products ?? 0),

                    // Shipping
                    'shipping_days' => (int)($row->shipping_days ?? 0),

                    // Status
                    'stock_status' => $row->stock_status,
                    'status' => (int)$row->status,
                    'is_approved' => (int)$row->is_approved,
                    'is_gift_card' => (int)($row->is_gift_card ?? 0),

                    // Country-specific restrictions
                    'zambia_only'   => (bool)($row->zambia_only ?? false),
                    'zimbabwe_only' => (bool)($row->zimbabwe_only ?? false),
                    'sa_only'       => (bool)($row->sa_only ?? false),

                    // External product
                    'external_url' => $row->external_url,
                    'external_button_text' => $row->external_button_text,

                    // SKU
                    'sku' => $sku,

                    // Meta & content
                    'meta_title' => $row->meta_title,
                    'estimated_delivery_text' => $row->estimated_delivery_text,
                    'return_policy_text' => $row->return_policy_text,
                    'layby_eligible' => (function() use ($row) {
                        $price = $row->sale_price ? (float)$row->sale_price : (float)($row->price ?? 0);
                        return $price >= 100;
                    })(),

                    // Product page features
                    'safe_checkout' => (int)($row->safe_checkout ?? 1),
                    'secure_checkout' => (int)($row->secure_checkout ?? 1),
                    'social_share' => (int)($row->social_share ?? 1),
                    'encourage_order' => (int)($row->encourage_order ?? 1),
                    'encourage_view' => (int)($row->encourage_view ?? 1),

                    // IDs
                    'product_thumbnail_id' => $row->product_thumbnail_id ? (int)$row->product_thumbnail_id : null,
                    'product_meta_image_id' => $row->product_meta_image_id ? (int)$row->product_meta_image_id : null,
                    'size_chart_image_id' => $row->size_chart_image_id ? (int)$row->size_chart_image_id : null,
                    'store_id' => $row->store_id ? (int)$row->store_id : null,
                    'created_by_id' => $row->created_by_id ? (int)$row->created_by_id : null,
                    'tax_id' => $row->tax_id ? (int)$row->tax_id : null,
                    'brand_id' => $row->brand_id ? (int)$row->brand_id : null,

                    // Timestamps
                    'created_at' => $row->created_at ? str_replace(' ', 'T', $row->created_at) : null,

                    // Relationships
                    'categories' => $categories,
                    'category_slugs' => array_merge($categorySlugs, $parentSlugsForProduct),
                    'tags' => $tags,
                    'brand' => $brand,
                    'thumbnail' => $thumbUrl,
                    'product_galleries' => $productGalleries,
                    'product_thumbnail' => $productThumbnail,
                    'product_meta_image' => $productMetaImage,
                    'reviews_avg_rating' => $rating,
                    'reviews_count' => $reviewsCount,
                    'rating_count' => $rating, // Average rating (for React app compatibility - expects 1-5 rating)
                    'review_ratings' => $reviewRatings, // Distribution: [1-star, 2-star, 3-star, 4-star, 5-star]
                    'reviews' => $reviews, // Full reviews array with user data
                    'variations' => $variations,

                    // Search optimization fields
                    'combined' => trim(implode(' ', array_filter([
                        $title,
                        implode(' ', (array)$categories),
                        implode(' ', (array)$tags),
                        $brand ? $brand['name'] : '',
                        $desc,
                        $sku,
                    ]))),
                    'is_part' => $isPart,
                    'is_top_level' => $categoryIsTopLevel,
                    'parent_category' => $parentCategorySlug,
                ];

                $params['body'][] = [
                    'index' => [
                        '_index' => $index,
                        '_id' => $row->id,
                    ]
                ];
                $params['body'][] = $doc;
            }

            if (!empty($params['body']) && !$dry) {
                $this->bulkIndex($client, $index, $params['body']);
            }

            $count = count($rows);
            $processed += $count;

            // Report percentage progress on new line each time
            $percent = round(($processed / $total) * 100, 1);
            if ($percent != $lastReportedPercent) {
                $this->info("PROGRESS: {$percent}% ({$processed}/{$total})");
                $lastReportedPercent = $percent;
            }

            // Free memory after each chunk
            unset($ids, $categoryRows, $categoryGroup, $parentIds, $parentRows, $tagMap, $brandIds, $brandMap);
            unset($thumbMap, $metaImageMap, $galleryMap, $reviewRows, $reviewStatsMap, $variationsMap, $params);
            gc_collect_cycles(); // Force garbage collection
        }, 'id');


        if ($disableRefresh) {
            // Restore replicas/refresh
            try {
                $client->indices()->putSettings([
                    'index' => $index,
                    'body' => [
                        'settings' => [
                            'refresh_interval' => '1s',
                            'number_of_replicas' => 1,
                        ],
                    ],
                ]);
            } catch (\Throwable $e) {
                $this->warn('Failed to restore index settings: ' . $e->getMessage());
            }
        }


        $this->info("\nReindex complete. Processed: {$processed}");

        // Refresh the index to make all documents immediately searchable
        try {
            $this->info('Refreshing index to make documents searchable...');
            $client->indices()->refresh(['index' => $index]);
            $this->info('Index refreshed successfully. Data is now searchable.');
        } catch (\Throwable $e) {
            $this->warn('Failed to refresh index: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }

    /**
     * Bulk-index documents, splitting in half on 429 circuit-breaker errors.
     */
    protected function bulkIndex($client, string $index, array $body, int $attempt = 1): void
    {
        if (empty($body)) return;

        try {
            $response = $client->bulk(['body' => $body]);

            if (isset($response['errors']) && $response['errors'] === true) {
                foreach ($response['items'] as $item) {
                    if (isset($item['index']['error'])) {
                        $this->warn('ES index error on id ' . ($item['index']['_id'] ?? '?') . ': ' . json_encode($item['index']['error']));
                    }
                }
            }
        } catch (\Throwable $e) {
            // 429 circuit-breaker: split body in half and retry each half
            if ($attempt <= 4 && str_contains($e->getMessage(), '429')) {
                $half = intdiv(count($body), 2);
                // body is pairs: [meta, doc, meta, doc, ...] — split on even boundary
                $half = $half % 2 === 0 ? $half : $half + 1;
                if ($half < 2) {
                    $this->error('Bulk request too large even for a single document — skipping.');
                    return;
                }
                $this->warn("429 circuit-breaker hit (attempt {$attempt}), splitting " . (count($body) / 2) . " docs into two halves and retrying...");
                sleep(2 * $attempt);
                $this->bulkIndex($client, $index, array_slice($body, 0, $half), $attempt + 1);
                $this->bulkIndex($client, $index, array_slice($body, $half), $attempt + 1);
            } else {
                $this->error('Bulk indexing failed: ' . $e->getMessage());
                throw $e;
            }
        }
    }

    protected function prepareIndex($client, string $index, bool $disableRefresh): void
    {
        $settings = [
            'settings' => [
                'number_of_shards' => 3,
                'number_of_replicas' => $disableRefresh ? 0 : 1,
                'refresh_interval' => $disableRefresh ? -1 : '1s',
                'analysis' => [
                    'filter' => [
                        'en_stop' => ['type' => 'stop', 'stopwords' => '_english_'],
                        'en_stem' => ['type' => 'stemmer', 'language' => 'english'],
                    ],
                    'tokenizer' => [
                        'edge_ngram_tokenizer' => [
                            'type' => 'edge_ngram',
                            'min_gram' => 2,
                            'max_gram' => 20,
                            'token_chars' => ['letter','digit']
                        ],
                    ],
                    'analyzer' => [
                        'title_analyzer' => [
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'en_stop'],
                        ],
                        'text_analyzer' => [
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'en_stop', 'en_stem'],
                        ],
                        'ngram_analyzer' => [
                            'tokenizer' => 'edge_ngram_tokenizer',
                            'filter' => ['lowercase']
                        ],
                        'keyword_lowercase' => [
                            'tokenizer' => 'keyword',
                            'filter' => ['lowercase'],
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'dynamic' => false,
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'name' => [
                        'type' => 'text',
                        'analyzer' => 'title_analyzer',
                        'fields' => [
                            'keyword' => ['type' => 'keyword', 'ignore_above' => 256], // For exact matching
                            'raw' => ['type' => 'keyword','ignore_above' => 256],
                            'ngram' => [
                                'type' => 'text',
                                'analyzer' => 'ngram_analyzer',
                                'search_analyzer' => 'title_analyzer'
                            ],
                            'suggester' => ['type' => 'search_as_you_type'],
                        ],
                    ],
                    'slug' => ['type' => 'keyword'],
                    'short_description' => ['type' => 'text'],
                    'type' => ['type' => 'keyword'],
                    'unit' => ['type' => 'keyword'],
                    'weight' => ['type' => 'keyword'],
                    'quantity' => ['type' => 'integer'],
                    'sku' => [
                        'type' => 'text',
                        'analyzer' => 'keyword_lowercase',
                        'fields' => [
                            'keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                        ],
                    ],

                    // Pricing
                    'price' => ['type' => 'float'],
                    'sale_price' => ['type' => 'float'],
                    'discount' => ['type' => 'integer'],
                    'is_sale_enable' => ['type' => 'integer'],
                    'sale_starts_at' => ['type' => 'date'],
                    'sale_expired_at' => ['type' => 'date'],

                    // Features/flags
                    'is_featured' => ['type' => 'integer'],
                    'is_trending' => ['type' => 'integer'],
                    'is_return' => ['type' => 'integer'],
                    'is_cod' => ['type' => 'integer'],
                    'is_free_shipping' => ['type' => 'integer'],
                    'is_external' => ['type' => 'integer'],
                    'is_random_related_products' => ['type' => 'integer'],

                    // Shipping
                    'shipping_days' => ['type' => 'integer'],

                    // Status
                    'stock_status' => ['type' => 'keyword'],
                    'status'       => ['type' => 'integer'],
                    'is_approved'  => ['type' => 'integer'],
                    'zambia_only'  => ['type' => 'boolean'],
                    'zimbabwe_only'=> ['type' => 'boolean'],
                    'sa_only'      => ['type' => 'boolean'],

                    // External
                    'external_url' => ['type' => 'keyword'],
                    'external_button_text' => ['type' => 'keyword'],

                    // Meta
                    'meta_title' => ['type' => 'text'],
                    'estimated_delivery_text' => ['type' => 'text'],
                    'return_policy_text' => ['type' => 'text'],
                    'layby_eligible' => ['type' => 'boolean'],

                    // Product page features
                    'safe_checkout' => ['type' => 'integer'],
                    'secure_checkout' => ['type' => 'integer'],
                    'social_share' => ['type' => 'integer'],
                    'encourage_order' => ['type' => 'integer'],
                    'encourage_view' => ['type' => 'integer'],

                    // IDs
                    'product_thumbnail_id' => ['type' => 'integer'],
                    'product_meta_image_id' => ['type' => 'integer'],
                    'size_chart_image_id' => ['type' => 'integer'],
                    'store_id' => ['type' => 'integer'],
                    'created_by_id' => ['type' => 'integer'],
                    'tax_id' => ['type' => 'integer'],
                    'brand_id' => ['type' => 'integer'],

                    // Timestamps
                    'created_at' => ['type' => 'date'],
                    'categories' => [
                        'type' => 'text',
                        'analyzer' => 'keyword_lowercase',
                        'fields' => [
                            'keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                        ],
                    ],
                    'category_slugs' => [
                        'type' => 'keyword', // Use keyword for exact matching
                    ],
                    'tags' => [
                        'type' => 'text',
                        'analyzer' => 'keyword_lowercase',
                        'fields' => [
                            'keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                        ],
                    ],
                    'brand' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'text',
                                'analyzer' => 'title_analyzer',
                                'fields' => [
                                    'keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                                ],
                            ],
                            'slug' => [
                                'type' => 'text',
                                'analyzer' => 'keyword_lowercase',
                                'fields' => [
                                    'keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                                ],
                            ],
                        ],
                    ],
                    'thumbnail' => ['type' => 'keyword'],
                    'product_galleries' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'uuid' => ['type' => 'keyword'],
                            'name' => ['type' => 'keyword'],
                            'disk' => ['type' => 'keyword'],
                            'file_name' => ['type' => 'keyword'],
                            'image_url' => ['type' => 'keyword'],
                            'takealot_url' => ['type' => 'keyword'],
                            'original_url' => ['type' => 'keyword'],
                        ],
                    ],
                    'product_thumbnail' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'uuid' => ['type' => 'keyword'],
                            'name' => ['type' => 'keyword'],
                            'disk' => ['type' => 'keyword'],
                            'file_name' => ['type' => 'keyword'],
                            'image_url' => ['type' => 'keyword'],
                            'takealot_url' => ['type' => 'keyword'],
                            'original_url' => ['type' => 'keyword'],
                            'created_by_id' => ['type' => 'integer'],
                            'created_at' => ['type' => 'date'],
                        ],
                    ],
                    'product_meta_image' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'uuid' => ['type' => 'keyword'],
                            'name' => ['type' => 'keyword'],
                            'disk' => ['type' => 'keyword'],
                            'file_name' => ['type' => 'keyword'],
                            'image_url' => ['type' => 'keyword'],
                            'takealot_url' => ['type' => 'keyword'],
                            'original_url' => ['type' => 'keyword'],
                            'created_by_id' => ['type' => 'integer'],
                            'created_at' => ['type' => 'date'],
                        ],
                    ],
                    'reviews_avg_rating' => ['type' => 'float'],
                    'reviews_count' => ['type' => 'integer'],
                    'rating_count' => ['type' => 'float'], // Average rating (1-5 scale, e.g. 4.6)
                    'review_ratings' => ['type' => 'integer'], // Array of rating distribution [1-star, 2-star, 3-star, 4-star, 5-star]
                    'reviews' => [
                        'type' => 'nested',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'consumer_id' => ['type' => 'integer'],
                            'product_id' => ['type' => 'integer'],
                            'rating' => ['type' => 'integer'],
                            'description' => ['type' => 'text'],
                            'created_at' => ['type' => 'date'],
                            'updated_at' => ['type' => 'date'],
                            'consumer' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'text'],
                                    'email' => ['type' => 'keyword'],
                                    'profile_image_id' => ['type' => 'integer'],
                                ],
                            ],
                        ],
                    ],
                    'variations' => [
                        'type' => 'object',
                        'dynamic' => false,
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'keyword','ignore_above'=>256],
                            'sku' => ['type' => 'keyword','ignore_above'=>256],
                            'price' => ['type' => 'float'],
                            'sale_price' => ['type' => 'float'],
                            'stock_status' => ['type' => 'keyword'],
                            'image' => ['type' => 'keyword'],
                            'attribute_values' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'value' => ['type' => 'keyword'],
                                    'slug' => [
                                        'type' => 'text',
                                        'analyzer' => 'keyword_lowercase',
                                        'fields' => [
                                            'keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                                        ],
                                    ],
                                    'hex_color' => ['type' => 'keyword'],
                                    'attribute_id' => ['type' => 'integer'],
                                    'attribute_name' => ['type' => 'keyword'],
                                ],
                            ],
                        ],
                    ],
                    'combined' => ['type' => 'text', 'analyzer' => 'text_analyzer'],
                    'is_part' => ['type' => 'boolean'],
                    'is_top_level' => ['type' => 'boolean'],
                ],
            ],
        ];

        try {
            $existsResponse = $client->indices()->exists(['index' => $index]);
            $exists = method_exists($existsResponse, 'asBool')
                ? $existsResponse->asBool()
                : (method_exists($existsResponse, 'getStatusCode') && $existsResponse->getStatusCode() === 200);
        } catch (\Throwable $e) {
            $exists = false;
        }

        if ($exists) {
            // Index exists - we can only update dynamic settings and mappings
            // Static settings (analyzers, number_of_shards) cannot be changed on existing index
            $this->warn('Index already exists. Skipping settings update (requires recreate).');

            try {
                $client->indices()->putMapping([
                    'index' => $index,
                    'body' => $settings['mappings'],
                ]);
                $this->info('Mapping updated successfully for existing index.');
            } catch (\Throwable $e) {
                $this->error('CRITICAL: Could not update mapping for existing index: ' . $e->getMessage());
                $this->error('The mapping structure may have changed. You need to recreate the index.');
                $this->warn('Run: php artisan es:reindex-products --recreate-index');
                return; // Don't proceed with indexing if mapping failed
            }
        } else {
            // Index doesn't exist - create with full settings and mappings
            try {
                $client->indices()->create([
                    'index' => $index,
                    'body' => $settings,
                ]);
                $this->info('New index created with updated mapping and settings.');
            } catch (\Throwable $e) {
                $this->error('Failed to create index: ' . $e->getMessage());
                return;
            }
        }
    }


}
