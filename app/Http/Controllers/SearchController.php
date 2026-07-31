<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SearchQuery;
use App\Services\ElasticsearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Search", description="Elasticsearch-powered product search and filtering")
 */
class SearchController extends Controller
{

    /**
     * @OA\Get(
     *   path="/api/search",
     *   tags={"Search"},
     *   summary="Search products using Elasticsearch",
     *   description="Primary search endpoint using Elasticsearch for fast, relevant product search with filtering and sorting",
     *   @OA\Parameter(
     *     name="q",
     *     in="query",
     *     description="Search query term",
     *     required=false,
     *     @OA\Schema(type="string", example="laptop")
     *   ),
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Alias for q parameter",
     *     required=false,
     *     @OA\Schema(type="string", example="laptop")
     *   ),
     *   @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="Page number",
     *     required=false,
     *     @OA\Schema(type="integer", minimum=1, default=1, example=1)
     *   ),
     *   @OA\Parameter(
     *     name="size",
     *     in="query",
     *     description="Results per page (max: 100)",
     *     required=false,
     *     @OA\Schema(type="integer", minimum=1, maximum=100, default=20, example=20)
     *   ),
     *   @OA\Parameter(
     *     name="category",
     *     in="query",
     *     description="Filter by category slug(s), comma-separated",
     *     required=false,
     *     @OA\Schema(type="string", example="electronics,computers")
     *   ),
     *   @OA\Parameter(
     *     name="brand",
     *     in="query",
     *     description="Filter by brand slug(s), comma-separated",
     *     required=false,
     *     @OA\Schema(type="string", example="samsung,apple")
     *   ),
     *   @OA\Parameter(
     *     name="price",
     *     in="query",
     *     description="Price range(s), comma-separated (e.g. 100-500,500-1000)",
     *     required=false,
     *     @OA\Schema(type="string", example="100-500,500-1000")
     *   ),
     *   @OA\Parameter(
     *     name="rating",
     *     in="query",
     *     description="Minimum rating (1-5)",
     *     required=false,
     *     @OA\Schema(type="integer", minimum=1, maximum=5, example=4)
     *   ),
     *   @OA\Parameter(
     *     name="sortBy",
     *     in="query",
     *     description="Sort option",
     *     required=false,
     *     @OA\Schema(
     *       type="string",
     *       enum={"RELEVANCE", "LOW_TO_HIGH", "HIGH_TO_LOW", "RATING_HIGH_TO_LOW", "NEWEST", "SALE"},
     *       example="LOW_TO_HIGH"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="status",
     *     in="query",
     *     description="Product status (1=active, 0=inactive)",
     *     required=false,
     *     @OA\Schema(type="integer", enum={0, 1}, default=1, example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful search results",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=12345),
     *           @OA\Property(property="name", type="string", example="Samsung Galaxy S24"),
     *           @OA\Property(property="slug", type="string", example="samsung-galaxy-s24"),
     *           @OA\Property(property="price", type="number", format="float", example=999.99),
     *           @OA\Property(property="sale_price", type="number", format="float", example=899.99),
     *           @OA\Property(property="discount", type="integer", example=10),
     *           @OA\Property(property="stock_status", type="string", example="in_stock"),
     *           @OA\Property(
     *             property="brand",
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Samsung"),
     *             @OA\Property(property="slug", type="string", example="samsung")
     *           ),
     *           @OA\Property(
     *             property="categories",
     *             type="array",
     *             @OA\Items(type="string", example="Electronics")
     *           )
     *         )
     *       ),
     *       @OA\Property(property="current_page", type="integer", example=1),
     *       @OA\Property(property="last_page", type="integer", example=10),
     *       @OA\Property(property="per_page", type="integer", example=20),
     *       @OA\Property(property="total", type="integer", example=195)
     *     )
     *   ),
     *   @OA\Response(response=400, description="Bad Request"),
     *   @OA\Response(response=500, description="Server Error")
     * )
     */
    public function products(Request $req)
    {
        $q    = trim((string) ($req->query('q', '') ?: $req->query('search', '') ?: $req->query('keyword', '')));
        $size = max(1, min((int) $req->query('size', 20), 100));
        $page = max(1, (int) $req->query('page', 1));


        // Treat wildcard or empty as "match all" for collections browsing
        $matchAll = ($q === '' || $q === '*');

        // If empty and no wildcard intended, return empty
        if ($q === '' && !$req->has('category') && !$req->has('brand') && !$req->has('rating') && !$req->has('price')) {
            return response()->json([
                'data'         => [],
                'current_page' => 1,
                'last_page'    => 0,
                'per_page'     => $size,
                'total'        => 0,
            ]);
        }

        try {
            // ---------- Elasticsearch (relevance-first; no rescore, no sort) ----------
            $client = \App\Services\ElasticsearchService::client();
            $index  = \App\Services\ElasticsearchService::indexName();

            $from     = ($page - 1) * $size;
            $qIsShort = mb_strlen($q) <= 3;
            $tokens = preg_split('/\s+/', trim($q));
            $tokenCount = count(array_filter($tokens));
            // Increase threshold for "heavy" queries - allow up to 15 words before switching strategy
            // This prevents exact product name searches from being treated as heavy queries
            $isHeavy = ($tokenCount > 15) || (mb_strlen($q) > 100);

            // Hard filters: only live/approved SKUs; never show parts; must have a price
            $baseFilter = [
                ['term'  => ['status'      => 1]],
                ['term'  => ['is_approved' => 1]],
                ['range' => ['price'       => ['gt' => 0]]],
            ];

            // Additional filters from request
            // Category filter
            if ($req->has('category') && $req->category) {
                $categorySlugs = explode(',', $req->category);
                $baseFilter[] = ['terms' => ['category_slugs' => $categorySlugs]]; // Use category_slugs field
            }

            // Brand filter
            if ($req->has('brand') && $req->brand) {
                $brandSlugs = explode(',', $req->brand);
                $baseFilter[] = ['terms' => ['brand.slug.keyword' => $brandSlugs]];
            }

            // Price range filter
            if ($req->has('price') && $req->price) {
                $ranges = explode(',', $req->price);
                $priceConditions = [];
                foreach($ranges as $range) {
                    $values = explode('-', trim($range));
                    if (count($values) > 1) {
                        $min = (float) $values[0];
                        $max = (float) $values[1];

                        // Check sale_price first (if not null), otherwise check price
                        // Use bool query with should to match either sale_price or price in range
                        $priceConditions[] = [
                            'bool' => [
                                'should' => [
                                    [
                                        'bool' => [
                                            'must' => [
                                                ['exists' => ['field' => 'sale_price']],
                                                ['range' => ['sale_price' => ['gte' => $min, 'lte' => $max, 'gt' => 0]]]
                                            ]
                                        ]
                                    ],
                                    [
                                        'bool' => [
                                            'must' => [
                                                ['range' => ['price' => ['gte' => $min, 'lte' => $max]]]
                                            ],
                                            'must_not' => [
                                                ['range' => ['sale_price' => ['gt' => 0]]]
                                            ]
                                        ]
                                    ]
                                ],
                                'minimum_should_match' => 1
                            ]
                        ];
                    } elseif (count($values) == 1) {
                        // Single value - treated as minimum
                        $min = (float) $values[0];
                        $priceConditions[] = [
                            'bool' => [
                                'should' => [
                                    [
                                        'bool' => [
                                            'must' => [
                                                ['exists' => ['field' => 'sale_price']],
                                                ['range' => ['sale_price' => ['gte' => $min, 'gt' => 0]]]
                                            ]
                                        ]
                                    ],
                                    [
                                        'bool' => [
                                            'must' => [
                                                ['range' => ['price' => ['gte' => $min]]]
                                            ],
                                            'must_not' => [
                                                ['range' => ['sale_price' => ['gt' => 0]]]
                                            ]
                                        ]
                                    ]
                                ],
                                'minimum_should_match' => 1
                            ]
                        ];
                    }
                }
                if (count($priceConditions) > 0) {
                    $baseFilter[] = ['bool' => ['should' => $priceConditions, 'minimum_should_match' => 1]];
                }
            }

            // Min/Max price filter
            if ($req->has('min') && $req->has('max')) {
                $min = (float)$req->min;
                $max = (float)$req->max;

                // Check sale_price (if exists and > 0), otherwise check price
                $baseFilter[] = [
                    'bool' => [
                        'should' => [
                            [
                                'bool' => [
                                    'must' => [
                                        ['exists' => ['field' => 'sale_price']],
                                        ['range' => ['sale_price' => ['gte' => $min, 'lte' => $max, 'gt' => 0]]]
                                    ]
                                ]
                            ],
                            [
                                'bool' => [
                                    'must' => [
                                        ['range' => ['price' => ['gte' => $min, 'lte' => $max]]]
                                    ],
                                    'must_not' => [
                                        ['range' => ['sale_price' => ['gt' => 0]]]
                                    ]
                                ]
                            ]
                        ],
                        'minimum_should_match' => 1
                    ]
                ];
            }

            // Rating filter — use exact star bands [r, r+1) so "1 star" doesn't match everything
            if ($req->has('rating') && $req->rating) {
                $ratings = explode(',', $req->rating);
                $ratingConditions = [];
                foreach ($ratings as $rating) {
                    $r = (float) $rating;
                    $ratingConditions[] = ['range' => ['reviews_avg_rating' => ['gte' => $r, 'lt' => $r + 1.0]]];
                }
                if (count($ratingConditions) > 0) {
                    $baseFilter[] = ['bool' => ['should' => $ratingConditions, 'minimum_should_match' => 1]];
                }
            }

            // Attribute filter
            if ($req->has('attribute') && $req->attribute) {
                $attributeSlugs = explode(',', $req->attribute);
                $baseFilter[] = ['terms' => ['variations.attribute_values.slug.keyword' => $attributeSlugs]];
            }

            // Tag filter
            if ($req->has('tag') && $req->tag) {
                $tagSlugs = explode(',', $req->tag);
                $baseFilter[] = ['terms' => ['tags.keyword' => $tagSlugs]];
            }

            // Store filter
            if ($req->has('store_id') && $req->store_id) {
                $baseFilter[] = ['term' => ['store_id' => (int)$req->store_id]];
            }

            // On sale filter
            if ($req->has('on_sale') && $req->on_sale == 1) {
                $baseFilter[] = ['term'  => ['is_sale_enable' => 1]];
                $baseFilter[] = ['range' => ['sale_price' => ['gt' => 0]]];
            }

            // Featured filter
            if ($req->has('is_featured') && $req->is_featured == 1) {
                $baseFilter[] = ['term' => ['is_featured' => 1]];
            }

            // Trending filter
            if ($req->has('is_trending') && $req->is_trending == 1) {
                $baseFilter[] = ['term' => ['is_trending' => 1]];
            }

            // Country-specific product visibility based on currency.
            //
            // Rules:
            //   - Products with all three flags false = "global" → always included regardless of currency.
            //   - Products with zambia_only=true    → only when currency=ZMW
            //   - Products with zimbabwe_only=true  → only when currency=ZW
            //   - Products with sa_only=true        → only when currency=ZAR
            //   - No currency supplied              → show everything (no filter applied).
            $currency = strtoupper(trim((string) ($req->query('currency', ''))));

            // Reusable "global product" clause: all three flags are false
            $globalClause = [
                'bool' => [
                    'filter' => [
                        ['term' => ['zambia_only'   => false]],
                        ['term' => ['zimbabwe_only' => false]],
                        ['term' => ['sa_only'       => false]],
                    ],
                ],
            ];

            if ($currency === 'ZMW') {
                // Zambia: show zambia_only products + global products
                $baseFilter[] = [
                    'bool' => [
                        'should'               => [
                            ['term' => ['zambia_only' => true]],
                            $globalClause,
                        ],
                        'minimum_should_match' => 1,
                    ],
                ];
            } elseif ($currency === 'ZW' || $currency === 'USD') {
                // Zimbabwe: show zimbabwe_only products + global products
                $baseFilter[] = [
                    'bool' => [
                        'should'               => [
                            ['term' => ['zimbabwe_only' => true]],
                            $globalClause,
                        ],
                        'minimum_should_match' => 1,
                    ],
                ];
            } elseif ($currency === 'ZAR') {
                // South Africa: show sa_only products + global products
                $baseFilter[] = [
                    'bool' => [
                        'should'               => [
                            ['term' => ['sa_only' => true]],
                            $globalClause,
                        ],
                        'minimum_should_match' => 1,
                    ],
                ];
            }
            // No currency = no filter; all products are returned.

            // Delivery/Estimated delivery text filter
            if ($req->has('delivery') && $req->delivery) {
                $deliveryTexts = explode(',', $req->delivery);


                // Build flexible delivery filter with multiple matching strategies
                $deliveryShould = [];

                foreach ($deliveryTexts as $text) {
                    // 1. Exact match on keyword field (case-sensitive)
                    $deliveryShould[] = ['term' => ['estimated_delivery_text.keyword' => $text]];

                    // 2. Exact match on text field (analyzed, case-insensitive)
                    $deliveryShould[] = ['match' => ['estimated_delivery_text' => ['query' => $text, 'operator' => 'and']]];

                    // 3. Try lowercase version
                    $deliveryShould[] = ['term' => ['estimated_delivery_text.keyword' => strtolower($text)]];

                    // 4. Phrase match for better flexibility
                    $deliveryShould[] = ['match_phrase' => ['estimated_delivery_text' => $text]];
                }

                $baseFilter[] = [
                    'bool' => [
                        'should' => $deliveryShould,
                        'minimum_should_match' => 1
                    ]
                ];
            }

            // MUST: strict token overlap across fields (keeps results on-topic)
            $mustCrossFields = [];

            if ($matchAll) {
                // Browsing mode: match all products, rely on filters
                $mustCrossFields = ['match_all' => (object)[]];
            } else {
                // Search mode: match search query
                // For single-word queries, require stricter matching
                // For long queries (product names), be more flexible
                if ($tokenCount === 1) {
                    $minShouldMatch = '100%';
                } elseif ($tokenCount > 10) {
                    $minShouldMatch = '60%';
                } else {
                    $minShouldMatch = '75%';
                }

                // When sorting, use stricter matching - require term to appear in name or SKU
                // When not sorting (relevance), allow matching across all fields
                if ($req->filled('sortBy')) {
                    // STRICT: Term must appear in name OR sku (not scattered across fields)
                    // Heavy boost for exact/phrase matches to prioritize actual products over accessories
                    // e.g., "Stove" should rank much higher than "Stove Cover"
                    $mustCrossFields = [
                        'bool' => [
                            'should' => [
                                // Exact match in name (highest priority)
                                ['term' => ['name.keyword' => ['value' => $q, 'boost' => 1000]]],
                                // Phrase match in name (very high - "Stove" in "Defy Stove")
                                ['match_phrase' => ['name' => ['query' => $q, 'boost' => 500]]],
                                // Word match in name (products with the term)
                                ['match' => ['name' => ['query' => $q, 'operator' => 'and', 'boost' => 20]]],
                                // SKU matches
                                ['term' => ['sku.keyword' => ['value' => $q, 'boost' => 800]]],
                                ['match' => ['sku' => ['query' => $q, 'operator' => 'and', 'boost' => 12]]],
                                // Brand matches
                                ['match' => ['brand.name' => ['query' => $q, 'operator' => 'and', 'boost' => 10]]],
                            ],
                            'minimum_should_match' => 1
                        ]
                    ];
                } else {
                    // FLEXIBLE: for 1-3 tokens use best_fields+fuzzy (exact match in one field);
                    // for 4+ tokens use cross_fields+OR so brand terms ("Samsung") and model
                    // terms ("Galaxy S24 Ultra") can satisfy the query across different fields
                    // instead of requiring every word to appear in a single field.
                    if ($tokenCount <= 3) {
                        $mustCrossFields = [
                            'multi_match' => [
                                'query'    => $q,
                                'type'     => 'best_fields',
                                'fields'   => ['name^20', 'sku^12', 'brand.name^10', 'categories^10', 'tags^6', 'combined^1'],
                                'operator' => 'and',
                                'fuzziness'=> 'AUTO',
                            ]
                        ];
                    } else {
                        $mustCrossFields = [
                            'multi_match' => [
                                'query'                => $q,
                                'type'                 => 'cross_fields',
                                'fields'               => ['name^3', 'sku^2', 'brand.name^2', 'categories^1', 'tags^1', 'combined^2'],
                                'operator'             => 'or',
                                'minimum_should_match' => $minShouldMatch,
                            ]
                        ];
                    }
                }

            }

            // SHOULD: boosts that rank the best matches to the top (only for search mode)
            $should = [];

            if (!$matchAll) {
                // EXACT MATCH BOOSTING (VERY HIGH PRIORITY)
                // Exact SKU match (highest priority)
                $should[] = ['term' => ['sku.keyword' => ['value' => $q, 'boost' => 500]]];

                // Exact name match (second highest priority)
                $should[] = ['term' => ['name.keyword' => ['value' => $q, 'boost' => 400]]];

                // Case-insensitive exact matches
                $should[] = ['match' => ['sku' => ['query' => $q, 'operator' => 'and', 'boost' => 300]]];
                $should[] = ['match' => ['name' => ['query' => $q, 'operator' => 'and', 'boost' => 250]]];

                if ($isHeavy) {
                    // For very long queries, use OR operator with high minimum_should_match
                    // This allows matching even if not ALL words are present (more flexible for long product names)
                    $should[] = [
                        'multi_match' => [
                            'query'    => $q,
                            'type'     => 'most_fields',
                            'fields'   => ['name^15', 'sku^12', 'brand.name^10', 'combined^1'],
                            'operator' => 'or',
                            'minimum_should_match' => '60%', // At least 60% of words must match
                        ]
                    ];
                } else {
                    // Phrase in name (very strong)
                    $should[] = ['match_phrase' => ['name' => ['query' => $q, 'boost' => 200]]];

                    // Brand exact match (high boost)
                    $should[] = ['match_phrase' => ['brand.name' => ['query' => $q, 'boost' => 150]]];

                    // Friendly OR match for recall
                    // Only add fuzziness when NOT sorting (for exact results when sorting)
                    $friendlyMatch = [
                        'query'     => $q,
                        'fields'    => ['name^15', 'sku^12', 'brand.name^10', 'categories^10', 'tags^5', 'combined^1'],
                        'operator'  => 'or',
                    ];
                    if (!$req->filled('sortBy')) {
                        $friendlyMatch['fuzziness'] = 'AUTO'; // Only fuzzy when not sorting
                    }
                    $should[] = ['multi_match' => $friendlyMatch];

                    // Category/tag/brand agreement (all terms)
                    $should[] = ['match' => ['categories'  => ['query' => $q, 'operator' => 'and', 'boost' => 8]]];
                    $should[] = ['match' => ['tags'        => ['query' => $q, 'operator' => 'and', 'boost' => 6]]];
                    $should[] = ['match' => ['brand.name'  => ['query' => $q, 'operator' => 'and', 'boost' => 10]]];

                    // Exact-ish SKU pop
                    $should[] = ['match_phrase' => ['sku' => ['query' => $q, 'boost' => 120]]];
                }

                // Short queries get a gentle name prefix bias (if analysis allows it)
                if ($qIsShort && !$isHeavy) {
                    $should[] = ['match_phrase_prefix' => [
                        'name' => ['query' => $q, 'max_expansions' => 20, 'boost' => 40]
                    ]];
                }
            }

            // AI-expanded keywords from the frontend: inject as additional should boosts
            // so matches on alternate names/synonyms surface more relevant products.
            if (!$matchAll && $req->filled('ai_keywords')) {
                foreach (array_slice(array_filter(array_map('trim', explode('|||', $req->ai_keywords))), 0, 8) as $kw) {
                    if (strtolower($kw) === strtolower($q)) continue;
                    $should[] = ['match_phrase' => ['name' => ['query' => $kw, 'boost' => 15]]];
                    $should[] = ['multi_match' => [
                        'query'  => $kw,
                        'fields' => ['name^5', 'combined^2', 'categories^2', 'brand.name^2', 'tags^1'],
                        'type'   => 'best_fields',
                        'boost'  => 8,
                    ]];
                }
            }

            $boolQuery = [
                'filter'   => $baseFilter,
                'must'     => [$mustCrossFields],
                'must_not' => [['term' => ['is_part' => true]]],
                'should'   => $should,
                // Don't use minimum_should_match here - should clauses are for BOOSTING, not filtering
                // The 'must' clause already handles the filtering
            ];

            // Function-score to stabilize tie-breaks (no sort used -> purely score ordered)
            $functions = [
                // mild price factor just to spread ties, not dominate
                ['field_value_factor' => [
                    'field'    => 'price',
                    'modifier' => 'log1p',
                    'factor'   => 0.02,
                    'missing'  => 0,
                ]],
                ['filter' => ['term' => ['is_featured'  => 1]],          'weight' => 3],
                ['filter' => ['term' => ['is_trending'  => 1]],          'weight' => 2],
                ['filter' => ['term' => ['stock_status' => 'in_stock']], 'weight' => 1.5],

                // Delivery priority: Same Day Delivery floats to the top
                ['filter' => ['match_phrase' => ['estimated_delivery_text' => 'Same Day Delivery']], 'weight' => 5],

                // Back Order / Backorder sinks to the bottom (weight < 1 reduces their score)
                ['filter' => [
                    'bool' => [
                        'should' => [
                            ['match_phrase' => ['estimated_delivery_text' => 'Back Order']],
                            ['match_phrase' => ['estimated_delivery_text' => 'Backorder']],
                            ['match_phrase' => ['estimated_delivery_text' => 'back order']],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ], 'weight' => 0.15],
            ];

            // Add sorting if specified
            $sort = [];
            // Check if sortBy has a valid value (not empty string)
            $hasSortBy = $req->filled('sortBy'); // filled() returns true only if key exists AND has non-empty value

            if ($hasSortBy) {

                switch ($req->sortBy) {
                    case 'A_TO_Z':
                    case 'asc':
                        $sort[] = ['name.keyword' => ['order' => 'asc']];
                        break;
                    case 'Z_TO_A':
                    case 'desc':
                        $sort[] = ['name.keyword' => ['order' => 'desc']];
                        break;
                    case 'LOW_TO_HIGH':
                    case 'PRICE_LOW_TO_HIGH':
                        // Use Painless script: effective_price = sale_price when it's > 0 and < price, else price.
                        // Direct sale_price sort breaks because non-sale products have sale_price=null in the index,
                        // which causes missing=_last to dump them all unsorted at the end.
                        $sort[] = [
                            '_script' => [
                                'type'   => 'number',
                                'order'  => 'asc',
                                'script' => [
                                    'lang'   => 'painless',
                                    'source' => "double sp = doc.containsKey('sale_price') && !doc['sale_price'].empty ? doc['sale_price'].value : 0; double p = doc.containsKey('price') && !doc['price'].empty ? doc['price'].value : 0; return (sp > 0 && sp < p) ? sp : p;",
                                ],
                            ],
                        ];
                        break;
                    case 'HIGH_TO_LOW':
                    case 'PRICE_HIGH_TO_LOW':
                        $sort[] = [
                            '_script' => [
                                'type'   => 'number',
                                'order'  => 'desc',
                                'script' => [
                                    'lang'   => 'painless',
                                    'source' => "double sp = doc.containsKey('sale_price') && !doc['sale_price'].empty ? doc['sale_price'].value : 0; double p = doc.containsKey('price') && !doc['price'].empty ? doc['price'].value : 0; return (sp > 0 && sp < p) ? sp : p;",
                                ],
                            ],
                        ];
                        break;
                    case 'DISCOUNT_HIGH_TO_LOW':
                        $sort[] = ['discount' => ['order' => 'desc', 'missing' => '_last']];
                        break;
                    case 'RATING_HIGH_TO_LOW':
                    case 'STARS_HIGH_TO_LOW':
                        // Sort by average rating (highest first), then by number of reviews
                        $sort[] = ['reviews_avg_rating' => ['order' => 'desc', 'missing' => '_last']];
                        $sort[] = ['reviews_count' => ['order' => 'desc', 'missing' => '_last']];
                        break;
                    case 'RATING_LOW_TO_HIGH':
                    case 'STARS_LOW_TO_HIGH':
                        // Sort by average rating (lowest first)
                        $sort[] = ['reviews_avg_rating' => ['order' => 'asc', 'missing' => '_last']];
                        break;
                    case 'ON_SALE':
                    case 'SALE':
                        // Sort by discount desc (on-sale products have higher discount values)
                        $sort[] = ['is_sale_enable' => ['order' => 'desc', 'missing' => '_last']];
                        $sort[] = ['discount'       => ['order' => 'desc', 'missing' => '_last']];
                        break;
                    case 'RELEVANCE':
                        // Sort by Elasticsearch relevance score (default)
                        $sort[] = ['_score' => ['order' => 'desc']];
                        break;
                    case 'NEWEST':
                    case 'NEWEST_FIRST':
                        // Sort by creation date (newest first)
                        $sort[] = ['created_at' => ['order' => 'desc']];
                        break;
                    case 'OLDEST':
                    case 'OLDEST_FIRST':
                        // Sort by creation date (oldest first)
                        $sort[] = ['created_at' => ['order' => 'asc']];
                        break;
                    case 'POPULAR':
                    case 'MOST_POPULAR':
                        // Sort by order count and amount (most popular first)
                        $sort[] = ['orders_count' => ['order' => 'desc', 'missing' => '_last']];
                        $sort[] = ['order_amount' => ['order' => 'desc', 'missing' => '_last']];
                        break;
                    case 'ASCENDING':
                        $sort[] = ['created_at' => ['order' => 'asc']];
                        break;
                    case 'DESCENDING':
                        $sort[] = ['created_at' => ['order' => 'desc']];
                        break;
                    default:
                        // Invalid sortBy value - apply default based on context
                        if ($matchAll) {
                            // Browsing: sort by id
                            $sort[] = ['id' => ['order' => 'asc']];
                        }
                        // Searching: use relevance (no sort)
                        break;
                }
            } else {
                // No sortBy or empty sortBy: apply default based on context
                if ($matchAll) {
                    // Browsing (no search query): sort by id ascending (database order)
                    $sort[] = ['id' => ['order' => 'asc']];
                }
            }

            $body = [
                'track_total_hits' => true,
                'track_scores' => !empty($sort), // Force score calculation when sorting (needed for min_score filter)
                'from'    => $from,
                'size'    => $size,
                '_source' => ['*'], // Get ALL fields from Elasticsearch
                'query'   => [
                    'function_score' => [
                        'query'      => ['bool' => $boolQuery],
                        'functions'  => $functions,
                        'boost_mode' => 'multiply',
                        'score_mode' => 'sum',
                    ]
                ],
            ];

            // Apply sort to Elasticsearch body (was computed above but never added to the request)
            if (!empty($sort)) {
                $body['sort'] = $sort;
            }

            // Add min_score to filter out irrelevant results
            // Use a lower threshold when sorting to avoid filtering valid results
            // but still filter out completely unrelated products
            // IMPORTANT: Don't apply min_score when using filters (delivery, category, brand, etc.)
            // because filtered browsing should show ALL matching products
            $hasActiveFilters = $req->has('delivery') || $req->has('category') || $req->has('brand') ||
                               $req->has('price') || $req->has('rating') || $req->has('attribute');

            if (!$matchAll && !$hasActiveFilters) {
                if (empty($sort)) {
                    // Pure relevance search: moderate threshold
                    $body['min_score'] = 0.5;
                } else {
                    // Sorting overrides relevance — use a low threshold so valid products
                    // aren't excluded before the sort runs. 100.0 was filtering out legitimate
                    // matches whose ES scores were below the phrase-match peak.
                    $body['min_score'] = 1.0;
                }
            }
            // When filters are active, don't use min_score - show all filtered results


            $res   = $client->search(['index' => $index, 'body' => $body])->asArray();

            $total = $res['hits']['total']['value'] ?? 0;

            // Log search query for analytics - ONLY log intentional searches (Enter key or Search button click)
            // Strategy: Only log when user explicitly submits the search (not auto-search/live typing)
            // Frontend should pass &submit=1 or &submit=true when user presses Enter or clicks Search button
            //
            // Required conditions:
            // 1. Actual search query exists (not empty or wildcard)
            // 2. Query is at least 3 characters
            // 3. Request has 'submit' parameter set to true/1 (Enter key or Search button)
            // 4. This exact query hasn't been logged in last 30 seconds from this session
            if (!empty($q) && $q !== '*' && mb_strlen($q) >= 3) {
                // Check if this is an intentional search (Enter key or Search button click)
                $isIntentionalSearch = $req->input('submit') == '1' ||
                                       $req->input('submit') === 'true' ||
                                       $req->input('submit') === true;

                if ($isIntentionalSearch) {
                    try {
                        $ip            = $req->ip() ?? '0.0.0.0';
                        $ua            = $req->userAgent() ?? '';
                        $sessionId     = md5($ip . '|' . $ua);
                        $normalizedQuery = strtolower(trim($q));

                        // Fast dedup check stays synchronous — uses composite index
                        // (session_id, normalized_query, created_at) so it's a single index scan.
                        $isDuplicate = SearchQuery::where('session_id', $sessionId)
                            ->where('normalized_query', $normalizedQuery)
                            ->where('created_at', '>', now()->subSeconds(30))
                            ->exists();

                        if (!$isDuplicate) {
                            // Push the INSERT to the analytics queue — does not block the response
                            $payload = [
                                'query'          => $q,
                                'normalized_query'=> $normalizedQuery,
                                'results_count'  => $total,
                                'user_id'        => auth()->id(),
                                'session_id'     => $sessionId,
                                'ip_address'     => $req->ip(),
                                'user_agent'     => $req->userAgent(),
                                'filters'        => [
                                    'category'    => $req->input('category'),
                                    'brand'       => $req->input('brand'),
                                    'price'       => $req->input('price'),
                                    'rating'      => $req->input('rating'),
                                    'min'         => $req->input('min'),
                                    'max'         => $req->input('max'),
                                    'attribute'   => $req->input('attribute'),
                                    'tag'         => $req->input('tag'),
                                    'store_id'    => $req->input('store_id'),
                                    'on_sale'     => $req->input('on_sale'),
                                    'is_featured' => $req->input('is_featured'),
                                    'is_trending' => $req->input('is_trending'),
                                ],
                                'sort_by'        => $req->input('sortBy'),
                            ];
                            dispatch(function () use ($payload) {
                                SearchQuery::create($payload);
                            })->onQueue('analytics');
                        }
                    } catch (\Exception $e) {
                        // Silently fail — never break search if analytics logging fails
                    }
                }
            }

            // Extract products directly from Elasticsearch hits
            $hits = $res['hits']['hits'] ?? [];

            // Enhanced logging to verify sort order
            $firstFewProducts = array_slice(array_map(function($hit) {
                $source = $hit['_source'] ?? [];
                return [
                    'id' => $hit['_id'],
                    'name' => $source['name'] ?? 'N/A',
                    'price' => $source['price'] ?? 0,
                    'sale_price' => $source['sale_price'] ?? 0,
                    'score' => $hit['_score'] ?? null,
                    'sort' => $hit['sort'] ?? null
                ];
            }, $hits), 0, 5);

            if (empty($hits)) {
                return response()->json([
                    'data'         => [],
                    'current_page' => $page,
                    'last_page'    => 0,
                    'per_page'     => $size,
                    'total'        => $total,
                ]);
            }

            // ---------- Transform ES data directly (NO DB QUERIES) ----------
            $out = [];
            foreach ($hits as $hit) {
                $source = $hit['_source'] ?? [];

                // Return full ES data as-is (already in database format)
                // Just ensure all expected fields exist with proper defaults
                // IMPORTANT: Defaults first, then source, so source values override defaults
                $product = array_merge([
                    // Defaults for any missing fields
                    'user_review' => null,
                    'related_products' => [],
                    'cross_sell_products' => [],
                    'shipping_options' => ['has_expedited_shipping' => 0],
                    'review_ratings' => [0, 0, 0, 0, 0],
                    'orders_count' => 0,
                    'order_amount' => 0,
                    'product_thumbnail' => null,
                    'product_meta_image' => null,
                ], $source);

                // Convert date fields back to MySQL format for consistency
                if (!empty($product['created_at'])) {
                    $product['created_at'] = str_replace('T', ' ', $product['created_at']);
                }
                if (!empty($product['sale_starts_at'])) {
                    $product['sale_starts_at'] = str_replace('T', ' ', $product['sale_starts_at']);
                }
                if (!empty($product['sale_expired_at'])) {
                    $product['sale_expired_at'] = str_replace('T', ' ', $product['sale_expired_at']);
                }

                // Ensure categories is an empty array if not set (as per agreement, categories simplified)
                if (!isset($product['categories'])) {
                    $product['categories'] = [];
                }

                // Ensure tags is an array
                if (!isset($product['tags']) || !is_array($product['tags'])) {
                    $product['tags'] = [];
                }

                // Compute layby_eligibility from stored data
                if (!isset($product['layby_eligibility'])) {
                    $isLaybyEligible = $product['layby_eligible'] ?? false;
                    if (!$isLaybyEligible) {
                        // Fallback: check price if layby_eligible not indexed yet
                        $effectivePrice = !empty($product['sale_price']) ? (float)$product['sale_price'] : (float)($product['price'] ?? 0);
                        $isLaybyEligible = $effectivePrice >= 100;
                    }
                    $isSaleProduct = !empty($product['sale_price']) && (float)$product['sale_price'] > 0;
                    $effectivePrice = $isSaleProduct ? (float)$product['sale_price'] : (float)($product['price'] ?? 0);

                    $depositPercentage = $isSaleProduct
                        ? (int)getLaybySetting('sale_products_deposit_percentage', 30)
                        : (int)getLaybySetting('regular_products_deposit_percentage', 30);
                    $availableDurations = $isSaleProduct
                        ? [(int)getLaybySetting('sale_products_duration_months', 3)]
                        : array_map('intval', explode(',', getLaybySetting('regular_products_duration_months', '6')));

                    $product['layby_eligibility'] = [
                        'eligible' => $isLaybyEligible,
                        'price' => $effectivePrice,
                        'price_usd' => $effectivePrice,
                        'currency' => 'USD',
                        'threshold' => 100,
                        'is_sale_product' => $isSaleProduct,
                        'deposit_percentage' => $depositPercentage,
                        'available_durations' => $availableDurations,
                    ];
                }

                $out[] = $product;
            }

            return response()->json([
                'data'         => $out,
                'current_page' => $page,
                'last_page'    => $total === 0 ? 0 : (int) ceil($total / max(1, $size)),
                'per_page'     => $size,
                'total'        => $total,
            ]);
        } catch (\Throwable $e) {
            \Log::error('SearchController::products failed', [
                'message' => $e->getMessage(),
                'q'       => $q ?? '',
                'params'  => $req->all(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error'   => 'search_failed',
                'message' => 'Internal Server Error',
                'details' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Get categories from Elasticsearch indexed products (aggregation)
     * Returns categories that actually have products
     */
    public function categories(Request $req)
    {
        try {
            $client = \App\Services\ElasticsearchService::client();
            $index  = \App\Services\ElasticsearchService::indexName();

            // Get category aggregation from products
            $body = [
                'size' => 0, // We don't need the actual products
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['status' => 1]],
                            ['term' => ['is_approved' => 1]],
                        ]
                    ]
                ],
                'aggs' => [
                    'categories' => [
                        'terms' => [
                            'field' => 'category_slugs',
                            'size' => 1000, // Get up to 1000 categories
                        ]
                    ]
                ]
            ];

            $res = $client->search(['index' => $index, 'body' => $body])->asArray();

            $buckets = $res['aggregations']['categories']['buckets'] ?? [];

            // Get category slugs that have products
            $categorySlugsWithProducts = [];
            foreach ($buckets as $bucket) {
                $categorySlugsWithProducts[$bucket['key']] = $bucket['doc_count'];
            }

            // Get actual categories from database to get proper names and hierarchy
            $dbCategories = \DB::table('categories')
                ->where('status', 1)
                ->whereIn('slug', array_keys($categorySlugsWithProducts))
                ->select('id', 'name', 'slug', 'parent_id')
                ->get();

            // Transform to frontend format with product counts from ES
            $categories = [];
            foreach ($dbCategories as $cat) {
                $categories[] = [
                    'id' => $cat->id,
                    'slug' => $cat->slug,
                    'name' => $cat->name,
                    'type' => 'product',
                    'parent_id' => $cat->parent_id,
                    'product_count' => $categorySlugsWithProducts[$cat->slug] ?? 0,
                    'subcategories' => [],
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $categories,
                'total' => count($categories),
            ]);
        } catch (\Throwable $e) {

            // Fallback to database categories if ES fails
            $dbCategories = \DB::table('categories')
                ->where('status', 1)
                ->select('id', 'name', 'slug', 'parent_id')
                ->get();

            $categories = [];
            foreach ($dbCategories as $cat) {
                $categories[] = [
                    'id' => $cat->id,
                    'slug' => $cat->slug,
                    'name' => $cat->name,
                    'type' => 'product',
                    'parent_id' => $cat->parent_id,
                    'product_count' => 0,
                    'subcategories' => [],
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $categories,
                'total' => count($categories),
            ]);
        }
    }

    /**
     * Match query to category (returns parent + children IDs separately)
     */
    private function findCategoryMatch($categories, string $q): array
    {
        $needle = strtolower(trim($q));
        $needleTokens = preg_split('/\s+/', $needle);

        foreach ($categories as $cat) {
            $catName = strtolower($cat->name);
            $catTokens = preg_split('/\s+/', $catName);

            // 1. Exact-ish match
            if ($needle === $catName ||
                $needle === \Illuminate\Support\Str::singular($catName) ||
                $needle === \Illuminate\Support\Str::plural($catName)) {
                return [
                    'parent'    => $cat->id,
                    'children'  => $this->collectDescendants($cat->subcategories ?? []),
                    'is_parent' => true
                ];
            }

            // 2. Token overlap (all query tokens exist in category name)
            if (count(array_intersect($needleTokens, $catTokens)) === count($needleTokens)) {
                return [
                    'parent'    => null,
                    'children'  => [$cat->id],
                    'is_parent' => false
                ];
            }

            // 3. Recurse into children
            if (!empty($cat->subcategories)) {
                $match = $this->findCategoryMatch($cat->subcategories, $q);
                if (!empty($match)) {
                    return $match;
                }
            }
        }

        return [];
    }

    private function collectDescendants($subcats): array
    {
        $ids = [];
        foreach ($subcats as $sc) {
            $ids[] = $sc->id;
            if (!empty($sc->subcategories)) {
                $ids = array_merge($ids, $this->collectDescendants($sc->subcategories));
            }
        }
        return $ids;
    }

}
