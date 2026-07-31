<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductRecommendationsController extends Controller
{
    private const PER_SECTION = 10;

    /**
     * GET /api/product/recommendations/{identifier}
     *
     * Returns 3 arrays of 10 products each:
     *  - related_products : manually related or same-category fallback
     *  - top_rated        : same category, avg rating >= 4, most reviews first
     *  - latest           : same category, newest first
     */
    public function show(Request $request, string $identifier): JsonResponse
    {
        // Resolve product with minimal columns – no heavy eager-loads
        $product = $this->resolveProduct($identifier);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found.',
                'identifier' => $identifier,
            ], 404);
        }

        $currency = strtoupper(trim((string) $request->query('currency', '')));

        // Cache key includes currency so each region gets its own cached result.
        // No currency → key suffix is empty, returns unfiltered results (backward-compatible with mobile apps).
        $cacheKey = "product_recommendations:{$product->id}:{$currency}";
        $data = Cache::remember($cacheKey, now()->addHours(1), function () use ($product, $currency) {
            // Get all category IDs this product belongs to
            $allCategoryIds = DB::table('product_categories')
                ->where('product_id', $product->id)
                ->pluck('category_id')
                ->toArray();

            // Find the deepest (leaf) category: the one whose ID is NOT a parent_id
            // of any other category in the product's category list.
            $parentIds = DB::table('categories')
                ->whereIn('id', $allCategoryIds)
                ->whereNotNull('parent_id')
                ->pluck('parent_id')
                ->toArray();

            $leafCategoryIds = array_values(array_diff($allCategoryIds, $parentIds));

            // Fallback to all categories if somehow none are leaves
            $categoryIds = !empty($leafCategoryIds) ? $leafCategoryIds : $allCategoryIds;

            $related = $this->getRelatedProducts($product, $categoryIds, $currency);
            $topRated = $this->getTopRatedInCategory($product, $categoryIds, $currency);
            $latest = $this->getLatestInCategory($product, $categoryIds, $currency);

            // Batch-load review stats for all product IDs in one query
            $allIds = collect($related)->pluck('id')
                ->merge(collect($topRated)->pluck('id'))
                ->merge(collect($latest)->pluck('id'))
                ->unique()
                ->values()
                ->toArray();

            $reviewStats = $this->batchReviewStats($allIds);

            return [
                'related_products' => $this->attachReviewStats($related, $reviewStats),
                'top_rated'        => $this->attachReviewStats($topRated, $reviewStats),
                'latest'           => $this->attachReviewStats($latest, $reviewStats),
            ];
        });

        return response()
            ->json([
                'success'      => true,
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'data'         => $data,
            ])
            ->setPublic()
            ->setMaxAge(600);
    }

    /**
     * Lightweight product resolution – only loads columns we need.
     */
    private function resolveProduct(string $identifier): ?object
    {
        $query = DB::table('products')
            ->whereNull('deleted_at')
            ->where('status', 1)
            ->where('is_approved', 1);

        if (ctype_digit($identifier)) {
            $query->where('id', (int) $identifier);
        } else {
            $query->where('slug', $identifier);
        }

        return $query->select('id', 'name', 'slug')->first();
    }

    /**
     * 1) Related products – 10 random products from the same categories.
     */
    private function getRelatedProducts(object $product, array $categoryIds, string $currency = ''): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $products = $this->fetchProducts(
            fn ($q) => $q
                ->whereIn('products.id', function ($sub) use ($categoryIds) {
                    $sub->select('product_id')
                        ->from('product_categories')
                        ->whereIn('category_id', $categoryIds);
                })
                ->inRandomOrder(),
            self::PER_SECTION,
            [$product->id],
            $currency
        );

        return $this->transformProducts($products);
    }

    /**
     * 2) Top rated in same category – avg rating >= 4, ordered by review count DESC.
     *    All filtering done at DB level via a subquery join – no PHP filtering.
     */
    private function getTopRatedInCategory(object $product, array $categoryIds, string $currency = ''): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        // Subquery: products with avg rating >= 4
        $ratingSubquery = DB::table('reviews')
            ->select('product_id')
            ->whereNull('deleted_at')
            ->groupBy('product_id')
            ->havingRaw('AVG(rating) >= 4');

        $products = $this->fetchProducts(
            fn ($q) => $q
                ->whereIn('products.id', function ($sub) use ($categoryIds) {
                    $sub->select('product_id')
                        ->from('product_categories')
                        ->whereIn('category_id', $categoryIds);
                })
                ->whereIn('products.id', $ratingSubquery)
                ->orderByRaw('(SELECT COUNT(*) FROM reviews WHERE reviews.product_id = products.id AND reviews.deleted_at IS NULL) DESC'),
            self::PER_SECTION,
            [$product->id],
            $currency
        );

        return $this->transformProducts($products);
    }

    /**
     * 3) Latest products in same category.
     */
    private function getLatestInCategory(object $product, array $categoryIds, string $currency = ''): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $products = $this->fetchProducts(
            fn ($q) => $q
                ->whereIn('products.id', function ($sub) use ($categoryIds) {
                    $sub->select('product_id')
                        ->from('product_categories')
                        ->whereIn('category_id', $categoryIds);
                })
                ->orderByDesc('products.created_at'),
            self::PER_SECTION,
            [$product->id],
            $currency
        );

        return $this->transformProducts($products);
    }

    /**
     * Core query builder – selects only the columns we need,
     * eager-loads thumbnail & brand via a single join each.
     */
    private function fetchProducts(callable $scope, int $limit, array $excludeIds = [], string $currency = ''): \Illuminate\Support\Collection
    {
        $query = DB::table('products')
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.short_description',
                'products.type',
                'products.unit',
                'products.price',
                'products.sale_price',
                'products.discount',
                'products.sku',
                'products.stock_status',
                'products.is_sale_enable',
                'products.is_featured',
                'products.is_trending',
                'products.quantity',
                'products.product_thumbnail_id',
                'products.brand_id',
                'products.sale_starts_at',
                'products.sale_expired_at',
                'products.estimated_delivery_text',
                'products.return_policy_text',
                'products.has_expedited_shipping',
                'products.standard_shipping_days',
                'products.expedited_shipping_days',
                'products.standard_shipping_price',
                'products.expedited_shipping_price',
                'products.created_at',
                // Thumbnail join
                'thumb.id as thumb_id',
                'thumb.image_url as thumb_image_url',
                'thumb.original_url as thumb_original_url',
                // Brand join
                'product_brands.id as brand_id_val',
                'product_brands.name as brand_name',
                'product_brands.slug as brand_slug',
            ])
            ->leftJoin('attachments as thumb', 'products.product_thumbnail_id', '=', 'thumb.id')
            ->leftJoin('product_brands', 'products.brand_id', '=', 'product_brands.id')
            ->whereNull('products.deleted_at')
            ->where('products.status', 1)
            ->where('products.is_approved', 1);

        // Regional filtering — mirrors SearchController logic exactly.
        // No currency supplied → no filter (backward-compatible with mobile apps).
        $this->applyRegionalFilter($query, $currency);

        if (!empty($excludeIds)) {
            $query->whereNotIn('products.id', $excludeIds);
        }

        // Apply the section-specific scope (where clauses, ordering)
        $scope($query);

        return $query->limit($limit)->get();
    }

    /**
     * Apply the same currency-based regional visibility rules as SearchController.
     * Mutates the query in place; does nothing when currency is empty.
     */
    private function applyRegionalFilter(\Illuminate\Database\Query\Builder $query, string $currency): void
    {
        if ($currency === '') {
            return;
        }

        $globalClause = fn ($q) => $q
            ->where('products.zambia_only', false)
            ->where('products.zimbabwe_only', false)
            ->where('products.sa_only', false);

        if ($currency === 'ZMW') {
            $query->where(fn ($q) => $q
                ->where('products.zambia_only', true)
                ->orWhere($globalClause)
            );
        } elseif ($currency === 'ZW' || $currency === 'USD') {
            $query->where(fn ($q) => $q
                ->where('products.zimbabwe_only', true)
                ->orWhere($globalClause)
            );
        } elseif ($currency === 'ZAR') {
            $query->where(fn ($q) => $q
                ->where('products.sa_only', true)
                ->orWhere($globalClause)
            );
        }
    }

    /**
     * Batch-load review stats for a list of product IDs in ONE query.
     */
    private function batchReviewStats(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $stats = DB::table('reviews')
            ->select('product_id')
            ->selectRaw('COUNT(*) as reviews_count')
            ->selectRaw('ROUND(AVG(rating)::numeric, 2) as reviews_avg_rating')
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id')
            ->toArray();

        // Batch-load rating distribution
        $distribution = DB::table('reviews')
            ->select('product_id', 'rating')
            ->selectRaw('COUNT(*) as cnt')
            ->whereIn('product_id', $productIds)
            ->whereNull('deleted_at')
            ->groupBy('product_id', 'rating')
            ->get();

        $distMap = [];
        foreach ($distribution as $row) {
            $pid = $row->product_id;
            $star = (int) $row->rating;
            if (!isset($distMap[$pid])) {
                $distMap[$pid] = [0, 0, 0, 0, 0];
            }
            if ($star >= 1 && $star <= 5) {
                $distMap[$pid][$star - 1] = (int) $row->cnt;
            }
        }

        // Merge distribution into stats
        foreach ($stats as $pid => $stat) {
            $stat->review_ratings = $distMap[$pid] ?? [0, 0, 0, 0, 0];
            $stats[$pid] = $stat;
        }

        return $stats;
    }

    /**
     * Attach review stats to transformed products array.
     */
    private function attachReviewStats(array $products, array $reviewStats): array
    {
        return array_map(function (array $p) use ($reviewStats) {
            $stats = $reviewStats[$p['id']] ?? null;
            $p['reviews_count'] = $stats ? (int) $stats->reviews_count : 0;
            $p['reviews_avg_rating'] = $stats ? (float) $stats->reviews_avg_rating : 0;
            $p['rating_count'] = $stats ? (int) round((float) $stats->reviews_avg_rating) : 0;
            $p['review_ratings'] = $stats->review_ratings ?? [0, 0, 0, 0, 0];
            return $p;
        }, $products);
    }

    /**
     * Transform raw DB rows into lightweight JSON-friendly arrays.
     */
    private function transformProducts(\Illuminate\Support\Collection $products): array
    {
        return $products->map(function ($p) {
            $thumbnail = $p->thumb_id ? [
                'id'           => (int) $p->thumb_id,
                'image_url'    => $p->thumb_image_url,
                'original_url' => $p->thumb_original_url,
            ] : null;

            return [
                'id'                => (int) $p->id,
                'name'              => $p->name,
                'slug'              => $p->slug,
                'short_description' => $p->short_description,
                'type'              => $p->type,
                'unit'              => $p->unit,
                'weight'            => null,
                'price'             => (float) $p->price,
                'sale_price'        => $p->sale_price ? (float) $p->sale_price : 0,
                'discount'          => (int) ($p->discount ?? 0),
                'sku'               => $p->sku,
                'stock_status'      => $p->stock_status,
                'is_sale_enable'    => (int) ($p->is_sale_enable ?? 0),
                'sale_starts_at'    => $p->sale_starts_at,
                'sale_expired_at'   => $p->sale_expired_at,
                'is_featured'       => (int) ($p->is_featured ?? 0),
                'is_trending'       => (int) ($p->is_trending ?? 0),
                'quantity'          => (int) ($p->quantity ?? 0),
                'estimated_delivery_text'  => $p->estimated_delivery_text,
                'return_policy_text'       => $p->return_policy_text,
                'has_expedited_shipping'   => (int) ($p->has_expedited_shipping ?? 0),
                'standard_shipping_days'   => $p->standard_shipping_days,
                'expedited_shipping_days'  => $p->expedited_shipping_days,
                'standard_shipping_price'  => $p->standard_shipping_price ? (float) $p->standard_shipping_price : null,
                'expedited_shipping_price' => $p->expedited_shipping_price ? (float) $p->expedited_shipping_price : null,
                'product_thumbnail' => $thumbnail,
                'product_galleries' => $thumbnail ? [$thumbnail] : [],
                'variations'        => [],
                'brand'             => $p->brand_id_val ? [
                    'id'   => (int) $p->brand_id_val,
                    'name' => $p->brand_name,
                    'slug' => $p->brand_slug,
                ] : null,
                'reviews_count'      => 0,
                'reviews_avg_rating' => 0,
                'rating_count'       => 0,
                'review_ratings'     => [0, 0, 0, 0, 0],
                'created_at'         => $p->created_at,
                'layby_eligibility'  => (function () use ($p) {
                    $isSale = $p->sale_price && (float)$p->sale_price > 0;
                    $price = $isSale ? (float)$p->sale_price : (float)($p->price ?? 0);
                    $eligible = $price >= 100;
                    $depositPct = $isSale
                        ? (int)getLaybySetting('sale_products_deposit_percentage', 30)
                        : (int)getLaybySetting('regular_products_deposit_percentage', 30);
                    $durations = $isSale
                        ? [(int)getLaybySetting('sale_products_duration_months', 3)]
                        : array_map('intval', explode(',', getLaybySetting('regular_products_duration_months', '6')));
                    return [
                        'eligible' => $eligible,
                        'price' => $price,
                        'price_usd' => $price,
                        'currency' => 'USD',
                        'threshold' => 100,
                        'is_sale_product' => $isSale,
                        'deposit_percentage' => $depositPct,
                        'available_durations' => $durations,
                    ];
                })(),
            ];
        })->toArray();
    }
}

