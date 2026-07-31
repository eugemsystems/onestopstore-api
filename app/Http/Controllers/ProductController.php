<?php

namespace App\Http\Controllers;

use App\GraphQL\Exceptions\ExceptionHandler;
use App\Models\Category;
use Exception;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Enums\SortByEnum;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\CreateProductRequest;
use App\Repositories\Eloquents\ProductRepository;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Products", description="Product catalogue management")
 */
class ProductController extends Controller
{
    use AuthorizesRequests;

    public $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->authorizeResource(Product::class,'product', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/product",
     *   tags={"Products"},
     *   summary="List products",
     *   @OA\Parameter(name="paginate", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *   @OA\Parameter(name="top_selling", in="query", required=false, @OA\Schema(type="boolean")),
     *   @OA\Parameter(name="filter_by", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="rating", in="query", required=false, description="Comma-separated integers 1-5", @OA\Schema(type="string")),
     *   @OA\Parameter(name="trending", in="query", required=false, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Parameter(name="ids", in="query", required=false, description="Comma-separated IDs", @OA\Schema(type="string")),
     *   @OA\Parameter(name="skus", in="query", required=false, description="Comma-separated SKUs", @OA\Schema(type="string")),
     *   @OA\Parameter(name="min", in="query", required=false, @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="max", in="query", required=false, @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="category", in="query", required=false, description="Comma-separated slugs", @OA\Schema(type="string")),
     *   @OA\Parameter(name="tag", in="query", required=false, description="Comma-separated slugs", @OA\Schema(type="string")),
     *   @OA\Parameter(name="sortBy", in="query", required=false, description="Sort preset", @OA\Schema(type="string", enum={"ASCENDING","DESCENDING","LOW_TO_HIGH","HIGH_TO_LOW","A_TO_Z","Z_TO_A"})),
     *   @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Parameter(name="field", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="store_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="store_slug", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="attribute", in="query", required=false, description="Comma-separated attribute value slugs", @OA\Schema(type="string")),
     *   @OA\Parameter(name="price", in="query", required=false, description="Comma-separated ranges (e.g. 0-100,100-200 or 200)", @OA\Schema(type="string")),
     *   @OA\Parameter(name="store_ids", in="query", required=false, description="Comma-separated store IDs", @OA\Schema(type="string")),
     *   @OA\Parameter(name="category_ids", in="query", required=false, description="Comma-separated category IDs", @OA\Schema(type="string")),
     *   @OA\Parameter(name="tag_ids", in="query", required=false, description="Comma-separated tag IDs", @OA\Schema(type="string")),
     *   @OA\Parameter(name="search", in="query", required=false, description="Full-text keyword search", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {
            // Runtime switch: delegate to Elasticsearch when enabled.
            // search=* is a frontend wildcard for "browse all" — Elasticsearch handles it as matchAll,
            // which supports category/price/sort filters. Pass it through as-is.
            $term = trim((string) ($request->search ?? $request->q ?? $request->keyword ?? ''));
            $useElastic = (\filter_var(env('USE_ELASTIC_SEARCH', false), FILTER_VALIDATE_BOOLEAN) === true) && ($term !== '');

            if ($useElastic) {
                $size = max(1, min((int) ($request->paginate ?? $request->size ?? 20), 100));
                $page = max(1, (int) ($request->page ?? 1));

                // Pass ALL query parameters including search term
                // Keep 'search' parameter as-is for SearchController
                $params = array_merge($request->query(), [
                    'search' => $term,  // Use 'search' instead of 'q' for consistency
                    'q'      => $term,  // Also include 'q' for backward compatibility
                    'size'   => $size,
                    'page'   => $page,
                ]);


                // Create a new request with query parameters properly set
                $esReq = \Illuminate\Http\Request::create(
                    '/api/search',
                    'GET',
                    $params
                );

                // Forward original client IP and User-Agent so search logging
                // can build an accurate session fingerprint for deduplication.
                $esReq->headers->set('User-Agent', $request->userAgent() ?? '');
                $esReq->server->set('REMOTE_ADDR', $request->ip());

                return app(\App\Http\Controllers\SearchController::class)->products($esReq);
            }

            //$query = $this->filter($this->repository, $request);
            $query = $this->filter(\App\Models\Product::query(), $request);

            // For list queries, suppress the global $with (variations, galleries, etc.) and
            // the $appends that run sub-queries (related_products, cross_sell_products, etc.).
            // Loading all of those for 20+ products exhausts memory. Only the thumbnail is needed here.
            $query->without(['variations', 'product_galleries', 'product_meta_image', 'brand', 'variations.attribute_values'])
                  ->with(['product_thumbnail'])
                  ->withAvg('reviews', 'rating');

            $ttl = $request->has('ids') ? now()->addMinutes(60) : now()->addMinutes(10);
            $payload = Cache::remember($this->makeProductListCacheKey($request), $ttl, function () use ($query, $request) {
                $products = $query->paginate($request->paginate ?? 20);

                // Strip expensive computed appends — related_products and cross_sell_products
                // each run sub-queries that load MORE products with the same $with chain,
                // causing exponential memory use on list endpoints.
                $heavyAppends = ['related_products', 'cross_sell_products', 'shipping_options', 'layby_eligibility', 'user_review', 'review_ratings', 'rating_count', 'order_amount', 'orders_count'];
                foreach ($products->items() as $product) {
                    $product->setAppends(array_diff($product->getAppends(), $heavyAppends));
                }

                // Batch-load review_ratings (star distribution) for all products in this page.
                // We inject the value into the serialized array AFTER toArray() rather than setting
                // it on the model, because Eloquent's transformModelValue() still calls the
                // getReviewRatingsAttribute() accessor for any key that has a get-mutator method,
                // even when the key is stripped from $appends — overwriting our computed value.
                $productIds = collect($products->items())->pluck('id')->toArray();
                $distributions = collect();
                if (!empty($productIds)) {
                    $distributions = \DB::table('reviews')
                        ->whereIn('product_id', $productIds)
                        ->whereNull('deleted_at')
                        ->selectRaw("
                            product_id,
                            SUM(CASE WHEN ROUND(rating::numeric) = 1 THEN 1 ELSE 0 END) AS star1,
                            SUM(CASE WHEN ROUND(rating::numeric) = 2 THEN 1 ELSE 0 END) AS star2,
                            SUM(CASE WHEN ROUND(rating::numeric) = 3 THEN 1 ELSE 0 END) AS star3,
                            SUM(CASE WHEN ROUND(rating::numeric) = 4 THEN 1 ELSE 0 END) AS star4,
                            SUM(CASE WHEN ROUND(rating::numeric) = 5 THEN 1 ELSE 0 END) AS star5
                        ")
                        ->groupBy('product_id')
                        ->get()
                        ->keyBy('product_id');
                }

                // Transform products to proxy image URLs (avoid CORS/taint issues)
                // and inject the batch-computed review_ratings into the plain array.
                $items = collect($products->items())->map(function ($product) use ($distributions) {
                    if ($product->product_thumbnail) {
                        $imageUrl = $product->product_thumbnail->takealot_url
                                 ?? $product->product_thumbnail->image_url
                                 ?? $product->product_thumbnail->original_url;

                        if ($imageUrl && !str_contains($imageUrl, 'proxy-image') && (str_contains($imageUrl, 'takealot.com') || str_contains($imageUrl, 'media.raines.africa'))) {
                            $proxiedUrl = url('/proxy-image?url=' . urlencode($imageUrl));
                            $product->product_thumbnail->image_url = $proxiedUrl;
                            $product->product_thumbnail->original_url = $proxiedUrl;
                            $product->product_thumbnail->takealot_url = $proxiedUrl;
                        }
                    }

                    $data = $product->toArray();

                    $row = $distributions->get($product->id);
                    $data['review_ratings'] = [
                        (int)($row->star1 ?? 0),
                        (int)($row->star2 ?? 0),
                        (int)($row->star3 ?? 0),
                        (int)($row->star4 ?? 0),
                        (int)($row->star5 ?? 0),
                    ];

                    return $data;
                })->all();

                return [
                    'data'         => $items,
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                ];
            });

            // Log search query for analytics (non-Elasticsearch fallback path)
            $this->logSearchQuery($request, $payload['total'] ?? 0);

            return response()->json($payload);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function indexNoElasticSearch(Request $request)
    {
        try {
            $query = $this->filter(\App\Models\Product::query(), $request);

            $ttl = $request->has('ids') ? now()->addMinutes(60) : now()->addMinutes(10);
            $payload = Cache::remember($this->makeProductListCacheKey($request), $ttl, function () use ($query, $request) {
                $products = $query->paginate($request->paginate ?? 20);
                return [
                    'data'         => $products->items(),
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                ];
            });
            return response()->json($payload);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function filter($product, $request)
    {
        if ($request->top_selling && $request->filter_by) {
            $product = Helpers::getTopSellingProducts($this->repository);
        }

        if (Helpers::isUserLogin()) {
            $roleName = Helpers::getCurrentRoleName();
            if ($roleName == RoleEnum::VENDOR) {
                $product = $product->where('store_id', Helpers::getCurrentVendorStoreId());
            }
        }

        if ($request->rating) {
            $ratings = explode(',', $request->rating);
            $product = $this->getProductByRating($ratings, $product);
        }

        if (isset($request->trending)) {
            $product = $product->where('is_trending',$request->trending);
        }

        if ($request->ids) {
            $ids = array_map('trim', explode(',', trim($request->ids, ',')));
            $ids = array_filter($ids, 'is_numeric');
            $ids = array_values($ids);
            $product = $product->whereIn('id', $ids);
            // Skip array_position ordering — it scans every row and is O(n*m) with no index benefit.
            // The frontend re-orders products by its own product_ids list anyway.
        }

        if ($request->skus) {
            $skus = explode(',', trim($request->skus, ','));
            $skus = array_map('trim', $skus);
            $skus = array_filter($skus);
            $skus = array_values($skus);
            $product = $product->whereIn('sku', $skus);
            // Preserve the requested order using PostgreSQL array_position
            if (!empty($skus)) {
                $quotedSkus = implode(',', array_map(fn($s) => "'" . addslashes($s) . "'", $skus));
                $product = $product->orderByRaw("array_position(ARRAY[{$quotedSkus}]::text[], sku::text)");
            }
        }

        if (isset($request->min) && isset($request->max)) {
            $product = $product->whereBetween('sale_price', [$request->min, $request->max]);
        }

        // Filter for products on sale
        if (isset($request->on_sale) && $request->on_sale == 1) {
            $product = $product->whereNotNull('sale_price')
                              ->where('sale_price', '>', 0)
                              ->whereColumn('sale_price', '<', 'price');
        }

        if ($request->category) {
            $slugs = explode(',', $request->category);
            $product = $product->whereHas('categories', function (Builder $categories) use($slugs) {
                $categories->WhereIn('slug', $slugs);
            });
        }

        if ($request->tag) {
            $slugs = explode(',', $request->tag);
            $product = $product->whereHas('tags', function (Builder $tags) use($slugs){
                $tags->WhereIn('slug', $slugs);
            });
        }

        if ($request->brand) {
            $brandIdentifiers = explode(',', $request->brand);
            $product = $product->whereHas('brand', function (Builder $brands) use($brandIdentifiers){
                // Separate numeric IDs from slugs
                $ids = [];
                $slugs = [];

                foreach ($brandIdentifiers as $identifier) {
                    $identifier = trim($identifier);
                    if (is_numeric($identifier)) {
                        $ids[] = (int)$identifier;
                    } else {
                        $slugs[] = $identifier;
                    }
                }

                $brands->where(function($query) use($ids, $slugs) {
                    if (!empty($slugs)) {
                        $query->whereIn('slug', $slugs);
                    }
                    if (!empty($ids)) {
                        $query->orWhereIn('id', $ids);
                    }
                });
            });
        }

        // Filter by delivery/estimated_delivery_text
        if ($request->delivery) {
            $deliveryTexts = explode(',', $request->delivery);
            $product = $product->whereIn('estimated_delivery_text', $deliveryTexts);
        }

        if (isset($request->status)) {
            $product = $product->where('status', $request->status);
        }

        // Only show approved products on frontend (unless admin is explicitly requesting unapproved ones)
        if (!isset($request->is_approved) || $request->is_approved !== 'all') {
            $product = $product->where('is_approved', 1);
        }

        // NOTE: zambia_only / zimbabwe_only products are intentionally NOT filtered out here.
        // They are returned by the API and rendered on the frontend with blurred prices
        // and hidden cart buttons to indicate regional unavailability.

        // Consolidated sort — maps both legacy enum values AND frontend string values (e.g. LOW_TO_HIGH, A_TO_Z)
        if ($request->sortBy && trim((string) $request->sortBy) !== '') {
            $sortBy = strtoupper(trim((string) $request->sortBy));

            switch ($sortBy) {
                // Price sorts
                case 'LOW_TO_HIGH':
                case 'PRICE_LOW_TO_HIGH':
                case 'LOW-HIGH':
                    $product = $product->orderBy('sale_price', 'asc');
                    break;

                case 'HIGH_TO_LOW':
                case 'PRICE_HIGH_TO_LOW':
                case 'HIGH-LOW':
                    $product = $product->orderBy('sale_price', 'desc');
                    break;

                // Alphabetical sorts
                case 'A_TO_Z':
                case 'A-Z':
                    $product = $product->orderBy('name', 'asc');
                    break;

                case 'Z_TO_A':
                case 'Z-A':
                    $product = $product->orderBy('name', 'desc');
                    break;

                // ID / creation sorts
                case 'ASCENDING':
                case 'ASC':
                    $column = (isset($request->field) && trim((string) $request->field) !== '') ? $request->field : 'id';
                    $product = $product->orderBy($column, 'asc');
                    break;

                case 'DESCENDING':
                case 'DESC':
                    $column = (isset($request->field) && trim((string) $request->field) !== '') ? $request->field : 'id';
                    $product = $product->orderBy($column, 'desc');
                    break;

                // Discount
                case 'DISCOUNT_HIGH_TO_LOW':
                case 'DISCOUNT-HIGH-LOW':
                    $product = $product->orderBy('discount', 'desc');
                    break;

                // Newest / Oldest
                case 'NEWEST':
                case 'NEWEST_FIRST':
                    $product = $product->orderBy('created_at', 'desc');
                    break;

                case 'OLDEST':
                case 'OLDEST_FIRST':
                    $product = $product->orderBy('created_at', 'asc');
                    break;

                // On sale
                case 'ON_SALE':
                case 'SALE':
                    $product = $product->where('is_sale_enable', 1)
                                       ->whereRaw('sale_price > 0')
                                       ->whereRaw('sale_price < price')
                                       ->orderBy('discount', 'desc');
                    break;

                // Rating
                case 'RATING_HIGH_TO_LOW':
                case 'STARS_HIGH_TO_LOW':
                    $product = $product->orderBy('reviews_avg_rating', 'desc')
                                       ->orderBy('reviews_count', 'desc');
                    break;

                case 'RATING_LOW_TO_HIGH':
                case 'STARS_LOW_TO_HIGH':
                    $product = $product->orderBy('reviews_avg_rating', 'asc');
                    break;

                // Popular
                case 'POPULAR':
                case 'MOST_POPULAR':
                    $product = $product->orderBy('orders_count', 'desc')
                                       ->orderBy('order_amount', 'desc');
                    break;

                default:
                    $product = $product->orderBy('created_at', 'desc');
                    break;
            }
        }

        if ($request->store_id) {
            $product = $product->where('store_id',$request->store_id);
        }

        if ($request->store_slug) {
            $slug = $request->store_slug;
            $product = $product->whereHas('store', function (Builder $stores) use($slug) {
                $stores->where('slug', $slug);
            });
        }

        if ($request->attribute) {
            $slugs = explode(',', $request->attribute);
            $product = $product->whereHas('variations', function (Builder $attributes) use($slugs) {
                $attributes->whereHas('attribute_values', function (Builder $attributeValues) use ($slugs) {
                    $attributeValues->WhereIn('slug', $slugs);
                });
            });
        }

        if ($request->price) {
            $ranges = explode(',', $request->price);

            $product = $product->where(function($query) use ($ranges) {
                foreach($ranges as $range) {
                    $values = explode('-', trim($range));

                    if (count($values) > 1) {
                        // Range format: "100-200" or "0-100"
                        $min = (float) $values[0];
                        $max = (float) $values[1];
                        $query->orWhereBetween('sale_price', [$min, $max]);
                    } elseif (count($values) == 1) {
                        // Single value - should not happen with new format, but handle for safety
                        $value = (float) $values[0];
                        $query->orWhere('sale_price', '>=', $value);
                    }
                }
            });
        }

        if ($request->store_ids) {
            $store_ids = explode(',', $request->store_ids);
            $product = $product->whereIn('store_id', $store_ids);
        }

        if ($request->category_ids) {
            $category_ids = explode(',', $request->category_ids);
            $product = $product->whereRelation('categories', function($categories) use($category_ids) {
                $categories->WhereIn('category_id', $category_ids);
            });
        }

        if ($request->tag_ids) {
            $tag_ids = explode(',', $request->tag_ids);
            $product = $product->whereRelation('tags', function($tags) use ($tag_ids) {
                $tags->WhereIn('tag_id', $tag_ids);
            });
        }

        if ($request->search) {
            $searchTerm = trim((string) $request->search);

            // '*' is a frontend wildcard meaning "all products" — skip text filtering
            if ($searchTerm !== '' && $searchTerm !== '*') {
                $lowerSearchTerm = strtolower($searchTerm);
                $words = preg_split('/\s+/', $lowerSearchTerm);

                foreach ($words as $word) {
                    if ($word !== '') {
                        $product = $product->whereRaw('LOWER(search_keywords) LIKE ?', ["%{$word}%"]);
                    }
                }
                // Only override sort when there is a real search term and no explicit sortBy
                if (!$request->sortBy || trim((string) $request->sortBy) === '') {
                    // Sort priority:
                    //   1. Same Day Delivery / Get It Tomorrow  → score 0
                    //   2. Products with specific delivery windows (ascending days)
                    //   3. Back Order products  → score 999
                    //   4. No estimated_delivery_text → score 500
                    // Secondary sort: price DESC (relevance fallback)
                    $product = $product->orderByRaw("
                        CASE
                            WHEN estimated_delivery_text ILIKE '%same day%'
                              OR estimated_delivery_text ILIKE '%same delivery%' THEN 0
                            WHEN estimated_delivery_text ILIKE '%get it tomorrow%'
                              OR estimated_delivery_text ILIKE '%tomorrow%' THEN 1
                            WHEN estimated_delivery_text ILIKE '%back order%' THEN 999
                            WHEN estimated_delivery_text IS NULL
                              OR estimated_delivery_text = '' THEN 500
                            ELSE 2 + COALESCE(
                                (SELECT (regexp_match(estimated_delivery_text, '(\\d+)'))[1]::int),
                                498
                            )
                        END ASC,
                        price DESC
                    ");
                }
            }
        }

        return $product->with([
            'product_thumbnail:id,uuid,name,disk,file_name,image_url,takealot_url',
            'product_galleries:id,uuid,name,disk,file_name,image_url,takealot_url',
            'categories',
            'tags'
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/productPagination",
     *   tags={"Products"},
     *   summary="List products (paginated)",
     *   @OA\Parameter(name="paginate", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function indexPagination(Request $request)
    {
        try {
            $query = $this->filter(\App\Models\Product::query(), $request);
            // Only apply default sort if no sortBy was specified (filter() handles it when sortBy is present)
            if (!$request->sortBy || trim((string) $request->sortBy) === '') {
                $query = $query->latest('created_at');
            }
            return $query->paginate($request->paginate ?? 20);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *   path="/api/product/by-tag",
     *   tags={"Products"},
     *   summary="List products by tag id, name, or slug",
     *   @OA\Parameter(name="tag_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="tag_name", in="query", required=false, description="Tag name contains (case-insensitive)", @OA\Schema(type="string")),
     *   @OA\Parameter(name="tag_slug", in="query", required=false, description="Exact tag slug", @OA\Schema(type="string")),
     *   @OA\Parameter(name="paginate", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=422, description="No tag filter provided")
     * )
     */
    public function getProductsByTag(Request $request)
    {
        $hasId   = (bool) $request->tag_id;
        $hasName = (bool) ($request->tag_name !== null && $request->tag_name !== '');
        $hasSlug = (bool) ($request->tag_slug !== null && $request->tag_slug !== '');

        if (!$hasId && !$hasName && !$hasSlug) {
            return response()->json(['message' => 'Provide at least one of: tag_id, tag_name, tag_slug'], 422);
        }

        $query = $this->filter(\App\Models\Product::query(), $request);

        if ($hasId) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('id', (int) $request->tag_id);
            });
        }

        if ($hasName) {
            $needle = '%' . strtolower(trim($request->tag_name)) . '%';
            $query->whereHas('tags', function ($q) use ($needle) {
                $q->whereRaw('LOWER(name) LIKE ?', [$needle]);
            });
        }

        if ($hasSlug) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', trim($request->tag_slug));
            });
        }

        $cacheKey = $this->makeProductByTagCacheKey($request);
        $payload = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($query, $request) {
            $products = $query->latest('created_at')->paginate($request->paginate ?? 20);
            return [
                'data'         => $products->items(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'per_page'     => $products->perPage(),
                'total'        => $products->total(),
            ];
        });

        return response()->json($payload);
    }

    public function create() { /* no view controller here */ }

    /**
     * @OA\Post(
     *   path="/api/product",
     *   tags={"Products"},
     *   summary="Create product",
     *   description="Create a new product (simple or classified with variations)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         required={"name", "description", "short_description", "type", "categories", "stock_status", "status"},
     *         @OA\Property(property="name", type="string", example="Premium Wireless Headphones", description="Product name (max 255 chars)"),
     *         @OA\Property(property="short_description", type="string", example="High-quality wireless headphones with noise cancellation", description="Brief description (recommended max 300 chars)"),
     *         @OA\Property(property="description", type="string", example="<p>Premium wireless headphones featuring...</p>", description="Full HTML description (min 10 chars)"),
     *         @OA\Property(property="type", type="string", enum={"simple", "classified"}, example="simple", description="Product type: simple=single variant, classified=multiple variations"),
     *         @OA\Property(property="unit", type="string", example="piece", description="Unit of measurement"),
     *         @OA\Property(property="price", type="number", format="float", example=299.99, description="Regular price (required for simple products)"),
     *         @OA\Property(property="sale_price", type="number", format="float", example=249.99, description="Sale price (optional, must be <= price)"),
     *         @OA\Property(property="discount", type="number", format="float", example=17, description="Discount percentage (0-99.99)"),
     *         @OA\Property(property="quantity", type="integer", example=100, description="Stock quantity (required for simple products)"),
     *         @OA\Property(property="weight", type="integer", example=250, description="Weight in grams"),
     *         @OA\Property(property="sku", type="string", example="HDPHN-001", description="Stock Keeping Unit (required for simple, must be unique)"),
     *         @OA\Property(property="stock_status", type="string", enum={"in_stock", "out_of_stock", "coming_soon"}, example="in_stock", description="Stock status (required for simple)"),
     *         @OA\Property(property="product_thumbnail_id", type="integer", example=100, description="Main product image attachment ID"),
     *         @OA\Property(property="product_thumbnail_uuid", type="string", example="abc-123-def", description="Main product image attachment UUID"),
     *         @OA\Property(property="product_galleries_id", type="array", @OA\Items(type="integer"), example={101, 102, 103}, description="Gallery image attachment IDs"),
     *         @OA\Property(property="product_galleries_uuid", type="array", @OA\Items(type="string"), example={"xyz-456", "uvw-789"}, description="Gallery image attachment UUIDs"),
     *         @OA\Property(property="size_chart_image_id", type="integer", example=104, description="Size chart image attachment ID"),
     *         @OA\Property(property="product_meta_image_id", type="integer", example=105, description="SEO/social media image attachment ID"),
     *         @OA\Property(property="meta_title", type="string", example="Premium Wireless Headphones - Best Sound Quality", description="SEO title (max 255 chars)"),
     *         @OA\Property(property="meta_description", type="string", example="Shop premium wireless headphones with noise cancellation...", description="SEO description"),
     *         @OA\Property(property="categories", type="array", @OA\Items(type="integer"), example={1, 5}, description="Array of category IDs (required)"),
     *         @OA\Property(property="tags", type="array", @OA\Items(type="integer"), example={2, 8}, description="Array of tag IDs"),
     *         @OA\Property(property="tax_id", type="integer", example=1, description="Tax rule ID"),
     *         @OA\Property(property="store_id", type="integer", example=1, description="Vendor store ID (multi-vendor)"),
     *         @OA\Property(property="is_trending", type="integer", enum={0, 1}, example=1, description="Show as trending product"),
     *         @OA\Property(property="is_featured", type="integer", enum={0, 1}, example=1, description="Featured product"),
     *         @OA\Property(property="is_return", type="integer", enum={0, 1}, example=1, description="Returnable product"),
     *         @OA\Property(property="is_free_shipping", type="integer", enum={0, 1}, example=0, description="Free shipping"),
     *         @OA\Property(property="has_expedited_shipping", type="integer", enum={0, 1}, example=1, description="Expedited shipping available"),
     *         @OA\Property(property="shipping_days", type="string", example="5-7", description="Standard delivery timeframe"),
     *         @OA\Property(property="standard_shipping_days", type="integer", example=5, description="Standard delivery days"),
     *         @OA\Property(property="expedited_shipping_days", type="integer", example=2, description="Expedited delivery days"),
     *         @OA\Property(property="standard_shipping_price", type="number", format="float", example=5.99, description="Standard shipping cost"),
     *         @OA\Property(property="expedited_shipping_price", type="number", format="float", example=15.99, description="Expedited shipping cost"),
     *         @OA\Property(property="estimated_delivery_text", type="string", example="Arrives by Dec 5", description="Custom delivery text"),
     *         @OA\Property(property="return_policy_text", type="string", example="30-day return policy", description="Custom return policy"),
     *         @OA\Property(property="safe_checkout", type="integer", enum={0, 1}, example=1, description="Show safe checkout badge"),
     *         @OA\Property(property="secure_checkout", type="integer", enum={0, 1}, example=1, description="Show secure checkout badge"),
     *         @OA\Property(property="social_share", type="integer", enum={0, 1}, example=1, description="Enable social sharing"),
     *         @OA\Property(property="encourage_order", type="integer", enum={0, 1}, example=1, description="Show order encouragement"),
     *         @OA\Property(property="encourage_view", type="integer", enum={0, 1}, example=1, description="Show view encouragement"),
     *         @OA\Property(property="is_sale_enable", type="integer", enum={0, 1}, example=1, description="Enable sale pricing"),
     *         @OA\Property(property="sale_starts_at", type="string", format="date-time", example="2025-11-01T00:00:00Z", description="Sale start date"),
     *         @OA\Property(property="sale_expired_at", type="string", format="date-time", example="2025-12-31T23:59:59Z", description="Sale end date (after start)"),
     *         @OA\Property(property="is_external", type="integer", enum={0, 1}, example=0, description="External/affiliate product"),
     *         @OA\Property(property="external_url", type="string", example="https://amazon.com/product", description="External product URL (required if is_external=1)"),
     *         @OA\Property(property="external_button_text", type="string", example="Buy on Amazon", description="Custom button text for external products"),
     *         @OA\Property(property="related_products", type="array", @OA\Items(type="integer"), example={10, 11, 12}, description="Related product IDs"),
     *         @OA\Property(property="cross_sell_products", type="array", @OA\Items(type="integer"), example={15, 16}, description="Cross-sell product IDs"),
     *         @OA\Property(property="status", type="integer", enum={0, 1}, example=1, description="Active=1, Inactive=0 (required)"),
     *         @OA\Property(property="is_random_related_products", type="integer", enum={0, 1}, example=0, description="Show random related products"),
     *         @OA\Property(property="attributes_ids", type="array", @OA\Items(type="integer"), example={1, 2}, description="Attribute IDs for classified products (required if type=classified)"),
     *         @OA\Property(
     *           property="variations",
     *           type="array",
     *           description="Product variations for classified products (required if type=classified)",
     *           @OA\Items(
     *             type="object",
     *             required={"name", "price", "sku", "stock_status", "attribute_values", "status"},
     *             @OA\Property(property="name", type="string", example="Red / Small", description="Variation name"),
     *             @OA\Property(property="price", type="number", format="float", example=29.99, description="Variation price"),
     *             @OA\Property(property="sale_price", type="number", format="float", example=24.99, description="Variation sale price"),
     *             @OA\Property(property="discount", type="number", format="float", example=17, description="Variation discount %"),
     *             @OA\Property(property="quantity", type="integer", example=50, description="Variation stock quantity"),
     *             @OA\Property(property="sku", type="string", example="TSHIRT-RED-SM", description="Unique variation SKU"),
     *             @OA\Property(property="stock_status", type="string", enum={"in_stock", "out_of_stock", "coming_soon"}, example="in_stock", description="Variation stock status"),
     *             @OA\Property(property="attribute_values", type="array", @OA\Items(type="integer"), example={1, 4}, description="Attribute value IDs for this variation"),
     *             @OA\Property(property="variation_image_id", type="integer", example=200, description="Specific image for this variation"),
     *             @OA\Property(property="status", type="integer", enum={0, 1}, example=1, description="Variation active status")
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Product created successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Product created successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=123),
     *         @OA\Property(property="name", type="string", example="Premium Wireless Headphones"),
     *         @OA\Property(property="slug", type="string", example="premium-wireless-headphones"),
     *         @OA\Property(property="price", type="number", example=299.99),
     *         @OA\Property(property="sale_price", type="number", example=249.99)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="error", type="string", example="Validation failed"),
     *       @OA\Property(
     *         property="details",
     *         type="object",
     *         @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required."))
     *       )
     *     )
     *   )
     * )
     */
    public function store(CreateProductRequest $request)
    {
        // IMPORTANT: only validated data moves forward
        $result = $this->repository->store($request->validated());
        $this->bumpProductsCacheVersion();
        return $result;
    }

    /**
     * @OA\Get(
     *   path="/api/product/{product}",
     *   tags={"Products"},
     *   summary="Get product by ID",
     *   description="Get detailed product information including variations, images, categories, tags, and reviews",
     *   @OA\Parameter(name="product", in="path", required=true, @OA\Schema(type="integer"), description="Product ID"),
     *   @OA\Response(
     *     response=200,
     *     description="Product retrieved successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="name", type="string", example="Premium Wireless Headphones"),
     *       @OA\Property(property="slug", type="string", example="premium-wireless-headphones"),
     *       @OA\Property(property="short_description", type="string", example="High-quality wireless headphones"),
     *       @OA\Property(property="description", type="string", example="<p>Full HTML description...</p>"),
     *       @OA\Property(property="type", type="string", example="simple"),
     *       @OA\Property(property="unit", type="string", example="piece"),
     *       @OA\Property(property="quantity", type="integer", example=100),
     *       @OA\Property(property="weight", type="integer", example=250),
     *       @OA\Property(property="price", type="number", example=299.99),
     *       @OA\Property(property="sale_price", type="number", example=249.99),
     *       @OA\Property(property="discount", type="integer", example=17),
     *       @OA\Property(property="sku", type="string", example="HDPHN-001"),
     *       @OA\Property(property="stock_status", type="string", example="in_stock"),
     *       @OA\Property(property="is_trending", type="integer", example=1),
     *       @OA\Property(property="is_featured", type="integer", example=1),
     *       @OA\Property(property="is_sale_enable", type="integer", example=1),
     *       @OA\Property(property="sale_starts_at", type="string", example="2025-11-01T00:00:00.000000Z"),
     *       @OA\Property(property="sale_expired_at", type="string", example="2025-12-31T23:59:59.000000Z"),
     *       @OA\Property(property="is_return", type="integer", example=1),
     *       @OA\Property(property="is_free_shipping", type="integer", example=0),
     *       @OA\Property(property="has_expedited_shipping", type="integer", example=1),
     *       @OA\Property(property="standard_shipping_days", type="string", example="5-7"),
     *       @OA\Property(property="expedited_shipping_days", type="string", example="1-2"),
     *       @OA\Property(property="standard_shipping_price", type="number", example=5.99),
     *       @OA\Property(property="expedited_shipping_price", type="number", example=15.99),
     *       @OA\Property(property="safe_checkout", type="integer", example=1),
     *       @OA\Property(property="secure_checkout", type="integer", example=1),
     *       @OA\Property(property="social_share", type="integer", example=1),
     *       @OA\Property(property="encourage_order", type="integer", example=1),
     *       @OA\Property(property="encourage_view", type="integer", example=1),
     *       @OA\Property(property="meta_title", type="string", example="Premium Headphones - Best Sound"),
     *       @OA\Property(property="meta_description", type="string", example="Shop premium headphones..."),
     *       @OA\Property(property="tax_id", type="integer", example=1),
     *       @OA\Property(property="store_id", type="integer", example=1),
     *       @OA\Property(property="status", type="integer", example=1),
     *       @OA\Property(property="is_approved", type="integer", example=1),
     *       @OA\Property(property="rating_count", type="number", example=4.5),
     *       @OA\Property(property="reviews_count", type="integer", example=128),
     *       @OA\Property(property="orders_count", type="integer", example=542),
     *       @OA\Property(property="created_at", type="string", example="2025-11-01T10:00:00.000000Z"),
     *       @OA\Property(
     *         property="product_thumbnail",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=100),
     *         @OA\Property(property="uuid", type="string", example="abc-123-def"),
     *         @OA\Property(property="name", type="string", example="headphones.jpg"),
     *         @OA\Property(property="image_url", type="string", example="https://cdn.example.com/headphones.jpg")
     *       ),
     *       @OA\Property(
     *         property="product_galleries",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=101),
     *           @OA\Property(property="uuid", type="string", example="xyz-456"),
     *           @OA\Property(property="image_url", type="string", example="https://cdn.example.com/image.jpg")
     *         )
     *       ),
     *       @OA\Property(
     *         property="categories",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=5),
     *           @OA\Property(property="name", type="string", example="Electronics"),
     *           @OA\Property(property="slug", type="string", example="electronics")
     *         )
     *       ),
     *       @OA\Property(
     *         property="tags",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=8),
     *           @OA\Property(property="name", type="string", example="Wireless"),
     *           @OA\Property(property="slug", type="string", example="wireless")
     *         )
     *       ),
     *       @OA\Property(property="variations", type="array", @OA\Items(type="object"), description="For classified products"),
     *       @OA\Property(property="related_products", type="array", @OA\Items(type="object")),
     *       @OA\Property(property="cross_sell_products", type="array", @OA\Items(type="object"))
     *     )
     *   ),
     *   @OA\Response(response=404, description="Product not found")
     * )
     */
    public function show(Product $product)
    {
        $version = $this->getProductsCacheVersion();
        $cacheKey = "product:{$product->id}:v{$version}";
        return Cache::remember($cacheKey, now()->addMinutes(10), function() use ($product) {
            return $this->repository->show($product->id);
        });
    }


    public function indexNoElasticSearchShow($id)
    {
        return $this->repository->show($id);
    }

    public function edit(Product $product) { /* no view controller here */ }


    public function update(UpdateProductRequest $request, Product $product)
    {
        // IMPORTANT: only validated data moves forward
        $result = $this->repository->update($request->validated(), $product->getId($request));

        // Clear specific product caches
        $productSlug = $product->slug ?? $result['data']->slug ?? null;
        if ($productSlug) {
            $version = $this->getProductsCacheVersion();
            Cache::forget("product_slug:{$productSlug}:v{$version}");
            // Also clear the old version in case request happens during version bump
            Cache::forget("product_slug:{$productSlug}:v" . ($version - 1));
        }

        // Bump version for all product lists
        $this->bumpProductsCacheVersion();

        return $result;
    }

    /**
     * @OA\Delete(
     *   path="/api/product/{product}",
     *   tags={"Products"},
     *   summary="Delete product",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="product", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Product $product)
    {
        $result = $this->repository->destroy($product->getId($request));
        $this->bumpProductsCacheVersion();
        return $result;
    }

    /**
     * @OA\Put(
     *   path="/api/product/{id}/{status}",
     *   tags={"Products"},
     *   summary="Update product status",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function status($id, $status)
    {
        $result = $this->repository->status($id, $status);
        $this->bumpProductsCacheVersion();
        return $result;
    }

    public function permanentlyDisable($id, $disable)
    {
        $result = $this->repository->permanentlyDisable($id, $disable);
        $this->bumpProductsCacheVersion();
        return $result;
    }

    /**
     * @OA\Put(
     *   path="/api/product/approve/{id}/{status}",
     *   tags={"Products"},
     *   summary="Approve product",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function approve($id, $status)
    {
        $result = $this->repository->approve($id, $status);
        $this->bumpProductsCacheVersion();
        return $result;
    }

    /**
     * @OA\Post(
     *   path="/api/product/deleteAll",
     *   tags={"Products"},
     *   summary="Bulk delete products",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        $result = $this->repository->deleteAll($request->ids);
        $this->bumpProductsCacheVersion();
        return $result;
    }

    /**
     * @OA\Post(
     *   path="/api/product/csv/import",
     *   tags={"Products"},
     *   summary="Import products (CSV)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Import started")
     * )
     */
    public function import()
    {
        $result = $this->repository->import();
        $this->bumpProductsCacheVersion();
        return $result;
    }

    public function getProductsExportUrl(Request $request)
    {
        return $this->repository->getProductsExportUrl($request);
    }

    /**
     * @OA\Post(
     *   path="/api/product/csv/export",
     *   tags={"Products"},
     *   summary="Export products (CSV)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Export URL")
     * )
     */
    public function export()
    {
        return $this->repository->export();
    }

    /**
     * @OA\Post(
     *   path="/api/product/replicate",
     *   tags={"Products"},
     *   summary="Replicate product(s)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Replicated")
     * )
     * @OA\Post(
     *   path="/api/productPagination/replicate",
     *   tags={"Products"},
     *   summary="Replicate product(s) using paginated selection",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Replicated")
     * )
     */
    public function replicate(Request $request)
    {
        try {
            // Basic validation - just check we have an array
            if (!$request->has('ids') || !is_array($request->ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request: ids array is required'
                ], 422);
            }

            $clones = $this->repository->replicate($request->ids);
            $this->bumpProductsCacheVersion();

            return response()->json([
                'success' => true,
                'message' => count($clones) . ' product(s) duplicated successfully',
                'data' => $clones
            ]);
        } catch (\Exception $e) {
            // Check if this is an Elasticsearch/Scout error but products were still created
            $isElasticsearchError = str_contains($e->getMessage(), 'Unauthorized') &&
                                   str_contains($e->getMessage(), 'products_search');

            if ($isElasticsearchError) {
                // Products were likely duplicated successfully, just ES sync failed
                Log::warning('Product replication succeeded but Elasticsearch sync failed', [
                    'error' => $e->getMessage(),
                    'ids' => $request->ids ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => count($request->ids ?? []) . ' product(s) duplicated successfully (search index sync skipped)',
                    'warning' => 'Products created but not indexed to search'
                ]);
            }

            Log::error('Product replication failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'ids' => $request->ids ?? null
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate products: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProductBySlug_($slug)
    {
        $version = $this->getProductsCacheVersion();
        $cacheKey = "product_slug:{$slug}:v{$version}";
        return Cache::remember($cacheKey, now()->addMinutes(10), function() use ($slug) {
            return $this->repository->getProductBySlug($slug);
        });
    }

    /**
     * @OA\Get(
     *   path="/api/product/slug/{slug}",
     *   tags={"Products"},
     *   summary="Get product by slug",
     *   @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function getProductBySlug($slug)
    {
        if (!is_string($slug) || !preg_match('/^[A-Za-z0-9\-\_]+$/', $slug)) {
            return response()->json(['message' => 'Invalid slug.'], 400);
        }

        $version = $this->getProductsCacheVersion();
        $cacheKey = "product_slug:{$slug}:v{$version}";

        $result = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($slug) {
                return $this->repository->getProductBySlug($slug);
        });

        if (!$result) {
                // negative-cache the miss so bad slugs don’t hammer DB
                Cache::put($cacheKey, null, now()->addMinutes(5));
                return response()->json(['message' => 'Product not found.', 'slug' => $slug], 404);
        }

        return response()
            ->json($result)
            ->setPublic()
            ->setMaxAge(600);
    }

    public function getProductByRating($ratings, $product)
    {
        return $product->where(function($query) use ($ratings) {
            foreach ($ratings as $rating) {
                $query->orWhere(function($query) use ($rating) {
                    $query->whereHas('reviews', function($query) use ($rating) {
                        $query->select('product_id')
                            ->groupBy('product_id')
                            ->havingRaw('AVG(rating) >= ?', [$rating])
                            ->havingRaw('AVG(rating) < ?', [$rating + 1]);
                    });
                });
            }
        });
    }

    private function getProductsCacheVersion(): int
    {
        return (int) Cache::get('products_cache_version', 1);
    }

    private function bumpProductsCacheVersion(): void
    {
        $ver = (int) Cache::get('products_cache_version', 1);
        Cache::put('products_cache_version', $ver + 1, now()->addDays(365));
    }

    private function makeProductListCacheKey(Request $request): string
    {
        $version = $this->getProductsCacheVersion();
        $role = Helpers::isUserLogin() ? Helpers::getCurrentRoleName() : 'guest';
        $vendorStore = ($role == RoleEnum::VENDOR) ? Helpers::getCurrentVendorStoreId() : null;
        $params = $request->all();
        ksort($params);
        $hash = md5(json_encode($params));
        return "products:list:v{$version}:role:{$role}:store:" . ($vendorStore ?? 'na') . ":h:{$hash}";
    }

    private function makeProductByTagCacheKey(Request $request): string
    {
        $version = $this->getProductsCacheVersion();
        $role = Helpers::isUserLogin() ? Helpers::getCurrentRoleName() : 'guest';
        $vendorStore = ($role == RoleEnum::VENDOR) ? Helpers::getCurrentVendorStoreId() : null;
        $params = $request->all();
        ksort($params);
        $hash = md5(json_encode($params));
        return "products:by-tag:v{$version}:role:{$role}:store:" . ($vendorStore ?? 'na') . ":h:{$hash}";
    }

    public function getCategoryTree(){
        return Category::where('status', 1)
            ->whereNull('parent_id')
            ->with('subcategories')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get products by SKUs
     *
     * @OA\Get(
     *   path="/api/product/by-skus",
     *   tags={"Products"},
     *   summary="Get products by SKUs",
     *   description="Retrieve products by providing comma-separated SKUs. Returns Name, Thumbnail Image, Price, Sale Price, and SKU.",
     *   @OA\Parameter(
     *     name="skus",
     *     in="query",
     *     required=true,
     *     description="Comma-separated list of SKUs (e.g., SKU123,SKU456,SKU789)",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(response=200, description="Products found"),
     *   @OA\Response(response=400, description="No SKUs provided"),
     *   @OA\Response(response=404, description="No products found")
     * )
     */
    public function getProductsBySKUs(Request $request)
    {
        try {
            // Get SKUs from query parameter
            $skusParam = $request->input('skus');

            if (empty($skusParam)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No SKUs provided. Please provide SKUs as comma-separated values.',
                    'example' => '/api/product/by-skus?skus=SKU123,SKU456,SKU789'
                ], 400);
            }

            // Split SKUs by comma and clean them
            $skus = array_map('trim', explode(',', $skusParam));
            $skus = array_filter($skus); // Remove empty values

            if (empty($skus)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid SKUs provided after processing.'
                ], 400);
            }

            // Query products by SKUs
            $products = Product::with(['product_thumbnail'])
                ->whereIn('sku', $skus)
                ->where('status', 1) // Only active products
                ->select('id', 'name', 'sku', 'slug', 'price', 'sale_price', 'product_thumbnail_id')
                ->get();

            if ($products->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No products found for the provided SKUs.',
                    'skus_searched' => $skus
                ], 404);
            }

            // Format the response
            $formattedProducts = $products->map(function ($product) {
                $thumbnail = null;
                if ($product->product_thumbnail) {
                    // Get the best available image URL
                    $imageUrl = $product->product_thumbnail->takealot_url
                             ?? $product->product_thumbnail->image_url
                             ?? $product->product_thumbnail->original_url;

                    // Proxy external images to avoid CORS issues in PDF/Image export
                    if ($imageUrl && !str_contains($imageUrl, 'proxy-image') && (str_contains($imageUrl, 'takealot.com') || str_contains($imageUrl, 'media.raines.africa'))) {
                        $imageUrl = url('/proxy-image?url=' . urlencode($imageUrl));
                    }

                    $thumbnail = [
                        'id' => $product->product_thumbnail->id,
                        'original_url' => $imageUrl,
                        'image_url' => $imageUrl,
                        'name' => $product->product_thumbnail->name,
                    ];
                }

                return [
                    'id'         => $product->id,
                    'name'       => $product->name,
                    'sku'        => $product->sku,
                    'slug'       => $product->slug,
                    'price'      => $product->price,
                    'sale_price' => $product->sale_price,
                    'thumbnail'  => $thumbnail,
                ];
            });

            return response()->json([
                'success' => true,
                'total_found' => $products->count(),
                'total_requested' => count($skus),
                'products' => $formattedProducts
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching products.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search products for promo templates (no Elasticsearch, direct DB query)
     * Properly filters by sale_price and returns proxied images
     */
    public function searchForPromo(Request $request)
    {
        try {
            $query = Product::with(['product_thumbnail']);

            // Search by name, SKU, or search_keyword
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'iLIKE', "%{$search}%")
                      ->orWhere('sku', 'iLIKE', "%{$search}%")
                      ->orWhere('search_keywords', 'iLIKE', "%{$search}%");
                });
            }

            // Filter by sale products - check if sale_price is not null
            if ($request->filled('on_sale') && $request->on_sale == 1) {
                $query->whereNotNull('sale_price')
                      ->where('sale_price', '>', 0);
            }

            // Only active products
            $query->where('status', 1);

            // Get products
            $products = $query->limit(50)->get();

            // Format with proxied image URLs
            $formattedProducts = $products->map(function ($product) {
                $thumbnail = null;
                if ($product->product_thumbnail) {
                    $imageUrl = $product->product_thumbnail->takealot_url
                             ?? $product->product_thumbnail->image_url
                             ?? $product->product_thumbnail->original_url;

                    // Proxy external images
                    if ($imageUrl && !str_contains($imageUrl, 'proxy-image') && (str_contains($imageUrl, 'takealot.com') || str_contains($imageUrl, 'media.raines.africa'))) {
                        $imageUrl = url('/proxy-image?url=' . urlencode($imageUrl));
                    }

                    $thumbnail = [
                        'id' => $product->product_thumbnail->id,
                        'original_url' => $imageUrl,
                        'image_url' => $imageUrl,
                        'name' => $product->product_thumbnail->name,
                    ];
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'product_thumbnail' => $thumbnail,
                ];
            });

            return response()->json([
                'data' => $formattedProducts,
                'total' => $products->count()
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'data' => [],
                'total' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log an intentional search query for analytics (used by non-Elasticsearch path).
     * Mirrors the same logic in SearchController::products().
     */
    private function logSearchQuery(Request $request, int $totalResults): void
    {
        $q = trim((string) ($request->search ?? ''));

        if (empty($q) || $q === '*' || mb_strlen($q) < 3) {
            return;
        }

        $isIntentionalSearch = $request->input('submit') == '1'
            || $request->input('submit') === 'true'
            || $request->input('submit') === true;

        if (!$isIntentionalSearch) {
            return;
        }

        try {
            $ip = $request->ip() ?? '0.0.0.0';
            $ua = $request->userAgent() ?? '';
            $sessionId = md5($ip . '|' . $ua);
            $normalizedQuery = strtolower(trim($q));

            $isDuplicate = SearchQuery::where('session_id', $sessionId)
                ->where('normalized_query', $normalizedQuery)
                ->where('created_at', '>', now()->subSeconds(30))
                ->exists();

            if (!$isDuplicate) {
                SearchQuery::create([
                    'query' => $q,
                    'normalized_query' => $normalizedQuery,
                    'results_count' => $totalResults,
                    'user_id' => auth()->id(),
                    'session_id' => $sessionId,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'filters' => [
                        'category' => $request->input('category'),
                        'brand' => $request->input('brand'),
                        'price' => $request->input('price'),
                        'rating' => $request->input('rating'),
                        'min' => $request->input('min'),
                        'max' => $request->input('max'),
                        'attribute' => $request->input('attribute'),
                        'tag' => $request->input('tag'),
                        'store_id' => $request->input('store_id'),
                        'on_sale' => $request->input('on_sale'),
                        'is_featured' => $request->input('is_featured'),
                        'is_trending' => $request->input('is_trending'),
                    ],
                    'sort_by' => $request->input('sortBy'),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to log search query', [
                'query' => $q,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
