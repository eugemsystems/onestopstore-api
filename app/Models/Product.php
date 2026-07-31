<?php

namespace App\Models;

use App\Helpers\Helpers;
use App\Services\ApiCacheRefresher;
use App\Services\ElasticsearchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Cviebrock\EloquentSluggable\Sluggable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model implements HasMedia
{
    use Sluggable, HasApiTokens, HasFactory, SoftDeletes, Notifiable, InteractsWithMedia, Searchable;

    protected $primaryKey = 'id';

    /**
     * The Products that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'name',
        'short_description',
        'description',
        'specifications', // HTML table with product specifications from Fast Import
        'type',
        'unit',
        'quantity',
        'weight',
        'price',
        'sale_price',
        'discount',
        'sku',
        'product_thumbnail_id',
        'stock_status',
        'meta_title',
        'meta_description',
        'product_meta_image_id',
        'size_chart_image_id',
        'is_sale_enable',
        'sale_starts_at',
        'sale_expired_at',
        'is_trending',
        'is_external',
        'slug',
        'is_return',
        'is_free_shipping',
        'is_featured',
        'shipping_days',
        'has_expedited_shipping',
        'standard_shipping_days',
        'expedited_shipping_days',
        'standard_shipping_price',
        'expedited_shipping_price',
        'is_random_related_products',
        'tax_id',
        'store_id',
        'brand_id',
        'status',
        'is_approved',
        'is_permanently_disabled',
        'estimated_delivery_text',
        'return_policy_text',
        'warranty', // Warranty text from Fast Import
        'safe_checkout',
        'secure_checkout',
        'social_share',
        'encourage_order',
        'encourage_view',
        'external_url',
        'external_button_text',
        'created_by_id',
        'original_currency_code',
        'original_price',
        'original_sale_price',
        'is_gift_card',
        'voucher_validity_days',
        'zambia_only',
        'zimbabwe_only',
        'sa_only',
    ];

    protected $with = [
        'variations',
        'product_thumbnail',
        'product_meta_image',
        'product_galleries',
        'brand',
        //'attributes.attribute_values',
        'variations.attribute_values',
    ];
    protected $withCount = [
        // 'orders' removed: getOrdersCountAttribute() accessor already overrides this value
        // and makes its own filtered DB call — keeping it here fired a useless extra subquery
        // on every product query. orders_count is preserved via $appends below.
        'reviews',
    ];

    protected $appends = [
        'user_review',
        //'can_review',
        'rating_count',
        'order_amount',
        'orders_count',   // moved from $withCount — accessor caches the value now
        'review_ratings',
        'related_products',
        'cross_sell_products',
        'shipping_options',
        'layby_eligibility',
    ];

    protected $hidden = [
        'deleted_at',
        'updated_at',
        'description',
        'cross_products',
        'similar_products',
        'meta_description',
    ];

    protected $casts = [
        'product_thumbnail_id' => 'integer',
        'size_chart_image_id' => 'integer',
        'quantity' => 'integer',
        'weight' => 'integer',
        'price' => 'float',
        'sale_price' => 'float',
        'discount' => 'integer',
        'shipping_days' => 'integer',
        'show_stock_quantity' => 'integer',
        'tax_id' => 'integer',
        'store_id' => 'integer',
        'brand_id' => 'integer',
        'is_cod' => 'integer',
        'is_external' => 'integer',
        'is_free_shipping' => 'integer',
        'is_featured' => 'integer',
        'is_return' => 'integer',
        'is_changeable' => 'integer',
        'is_sale_enable' => 'integer',
        'is_random_related_products' => 'integer',
        'status' => 'integer',
        'is_trending' => 'integer',
        'is_approved' => 'integer',
        'reviews_count' => 'integer',
        'rating_count' => 'float',
        'has_expedited_shipping' => 'integer',
        'standard_shipping_days' => 'string',
        'expedited_shipping_days' => 'string',
        'standard_shipping_price' => 'float',
        'expedited_shipping_price' => 'float',
        'safe_checkout' => 'integer',
        'secure_checkout' => 'integer',
        'social_share' => 'integer',
        'encourage_order' => 'integer',
        'encourage_view' => 'integer',
        'cross_sell_products' => 'array',
        'sale_starts_at' => 'datetime',
        'sale_expired_at' => 'datetime',
        'is_gift_card' => 'boolean',
        'voucher_validity_days' => 'integer',
        'zambia_only' => 'boolean',
        'zimbabwe_only' => 'boolean',
        'sa_only' => 'boolean',
    ];


    public function searchableAs()
    {
        return 'products_index';
    }

    /**
     * Determine if the model should be searchable.
     * Only index active and approved products.
     *
     * @return bool
     */
    public function shouldBeSearchable()
    {
        return $this->status == 1 && $this->is_approved == 1;
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        // Load all relationships needed
        $this->loadMissing([
            'variations',
            'product_thumbnail',
            'product_meta_image',
            'product_galleries',
            'attributes',
            'categories',
            'tags',
            'brand',
            'store',
            'tax',
        ]);

        // Build the document explicitly to ensure ALL fields are included
        // This matches the structure used by ReindexProducts command
        $data = [
            // Basic product info
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description, // ← Explicitly include (was hidden)
            'type' => $this->type,
            'unit' => $this->unit,
            'weight' => $this->weight,
            'quantity' => $this->quantity,

            // Pricing
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'discount' => $this->discount,
            'is_sale_enable' => $this->is_sale_enable,
            'sale_starts_at' => $this->sale_starts_at,
            'sale_expired_at' => $this->sale_expired_at,

            // Features/flags
            'is_featured' => $this->is_featured,
            'is_trending' => $this->is_trending,
            'is_return' => $this->is_return,
            'is_cod' => $this->is_cod,
            'is_free_shipping' => $this->is_free_shipping,
            'is_external' => $this->is_external,
            'is_random_related_products' => $this->is_random_related_products,
            'is_gift_card' => $this->is_gift_card,
            'voucher_validity_days' => $this->voucher_validity_days,
            'zambia_only' => (bool) $this->zambia_only,
            'zimbabwe_only' => (bool) $this->zimbabwe_only,
            'sa_only' => (bool) $this->sa_only,

            // Shipping
            'shipping_days' => $this->shipping_days,
            'standard_shipping_days' => $this->standard_shipping_days,
            'expedited_shipping_days' => $this->expedited_shipping_days,
            'standard_shipping_price' => $this->standard_shipping_price,
            'expedited_shipping_price' => $this->expedited_shipping_price,
            'has_expedited_shipping' => $this->has_expedited_shipping,

            // Status
            'stock_status' => $this->stock_status,
            'status' => $this->status,
            'is_approved' => $this->is_approved,

            // External product
            'external_url' => $this->external_url,
            'external_button_text' => $this->external_button_text,

            // SKU
            'sku' => $this->sku,

            // Meta & content
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description, // ← Explicitly include (was hidden)
            'estimated_delivery_text' => $this->estimated_delivery_text,
            'return_policy_text' => $this->return_policy_text,
            'layby_eligible' => (function() {
                $price = $this->sale_price ? (float)$this->sale_price : (float)($this->price ?? 0);
                return $price >= 100;
            })(),

            // Product page features
            'safe_checkout' => $this->safe_checkout,
            'secure_checkout' => $this->secure_checkout,
            'social_share' => $this->social_share,
            'encourage_order' => $this->encourage_order,
            'encourage_view' => $this->encourage_view,

            // IDs
            'product_thumbnail_id' => $this->product_thumbnail_id,
            'product_meta_image_id' => $this->product_meta_image_id,
            'size_chart_image_id' => $this->size_chart_image_id,
            'store_id' => $this->store_id,
            'created_by_id' => $this->created_by_id,
            'tax_id' => $this->tax_id,
            'brand_id' => $this->brand_id,

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at, // ← Explicitly include (was hidden)
            'deleted_at' => $this->deleted_at, // ← Explicitly include (was hidden)
            'visible_time' => $this->visible_time,

            // Relationships (convert to arrays)
            // Ensure categories is always an array, never null
            'categories' => ($this->relationLoaded('categories') && $this->categories && $this->categories->count() > 0)
                ? array_values($this->categories->pluck('name')->all())
                : [],
            'category_slugs' => ($this->relationLoaded('categories') && $this->categories && $this->categories->count() > 0)
                ? array_values($this->categories->pluck('slug')->all())
                : [],
            'tags' => ($this->relationLoaded('tags') && $this->tags && $this->tags->count() > 0)
                ? array_values($this->tags->pluck('name')->all())
                : [],
            'brand' => ($this->relationLoaded('brand') && $this->brand) ? $this->brand->toArray() : null,
            'product_thumbnail' => ($this->relationLoaded('product_thumbnail') && $this->product_thumbnail)
                ? $this->product_thumbnail->toArray()
                : null,
            'product_meta_image' => ($this->relationLoaded('product_meta_image') && $this->product_meta_image)
                ? $this->product_meta_image->toArray()
                : null,
            'product_galleries' => ($this->relationLoaded('product_galleries') && $this->product_galleries && $this->product_galleries->count() > 0)
                ? array_values($this->product_galleries->toArray())
                : [],
            'variations' => ($this->relationLoaded('variations') && $this->variations && $this->variations->count() > 0)
                ? array_values($this->variations->toArray())
                : [],
            'tax' => ($this->relationLoaded('tax') && $this->tax) ? $this->tax->toArray() : null,
            'store' => ($this->relationLoaded('store') && $this->store) ? $this->store->toArray() : null,

            // Search optimization field
            'combined' => trim(implode(' ', array_filter([
                $this->name ?? '',
                $this->sku ?? '',
                ($this->relationLoaded('categories') && $this->categories && $this->categories->count() > 0)
                    ? implode(' ', $this->categories->pluck('name')->toArray())
                    : '',
                ($this->relationLoaded('tags') && $this->tags && $this->tags->count() > 0)
                    ? implode(' ', $this->tags->pluck('name')->toArray())
                    : '',
                ($this->relationLoaded('brand') && $this->brand) ? $this->brand->name : '',
                $this->short_description ?? '',
            ]))),

            // Reviews data (critical for API responses)
            // Use direct DB queries to get accurate counts (not limited by relationship loading)
            'reviews_count' => \DB::table('reviews')
                ->where('product_id', $this->id)
                ->whereNull('deleted_at')
                ->count(),
            'rating_count' => \DB::table('reviews')
                ->where('product_id', $this->id)
                ->whereNull('deleted_at')
                ->avg('rating') ? round(\DB::table('reviews')
                    ->where('product_id', $this->id)
                    ->whereNull('deleted_at')
                    ->avg('rating'), 2) : 0,
            'reviews_avg_rating' => \DB::table('reviews')
                ->where('product_id', $this->id)
                ->whereNull('deleted_at')
                ->avg('rating') ? round(\DB::table('reviews')
                    ->where('product_id', $this->id)
                    ->whereNull('deleted_at')
                    ->avg('rating'), 2) : 0,
            'review_ratings' => $this->getReviewRatingsAttribute(),
            // Load only first 100 reviews for the reviews array (to avoid ES document size issues)
            'reviews' => \DB::table('reviews')
                ->where('product_id', $this->id)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get()
                ->map(function($review) {
                    return [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'description' => $review->description,
                        'consumer_id' => $review->consumer_id,
                        // Convert to ISO 8601 format for Elasticsearch
                        'created_at' => $review->created_at ? \Carbon\Carbon::parse($review->created_at)->toIso8601String() : null,
                    ];
                })->toArray(),

            // Orders count
            'orders_count' => 0,
            'order_amount' => 0,

            // Additional fields for frontend compatibility
            'thumbnail' => $this->product_thumbnail ? $this->product_thumbnail->file_name : null,
        ];

        return $data;
    }


    public static function boot()
    {
        parent::boot();
        static::saving(function ($product) {
            $product->created_by_id = Helpers::getCurrentUserId() ?? 1;

            // Only access relationships if they're loaded (don't trigger queries during save)
            $categories = '';
            $tags = '';
            $brand = '';

            if ($product->relationLoaded('categories') && $product->categories) {
                $categories = $product->categories->pluck('name')->implode(' ');
            }
            if ($product->relationLoaded('tags') && $product->tags) {
                $tags = $product->tags->pluck('name')->implode(' ');
            }
            if ($product->relationLoaded('brand') && $product->brand) {
                $brand = $product->brand->name;
            }

            // Combine fields (you can add more fields if needed).
            $keywords = "{$product->name} {$product->sku} {$categories} {$tags} {$brand}";

            // Normalize the keywords.
            $product->search_keywords = ucwords($keywords);
        });

        static::saved(function ($product) {
            DB::afterCommit(function () use ($product) {
                // Reload fresh state after commit with ALL relationships
                $fresh = self::with([
                    'categories',
                    'tags',
                    'brand',
                    'product_thumbnail',
                    'product_meta_image',
                    'product_galleries',
                    'variations',
                    'store',
                    'tax',
                ])->find($product->id);
                if (!$fresh) return;

                $urls = [];

                // Product detail by slug
                if ($fresh->slug) {
                    $urls[] = '/api/product/slug/' . rawurlencode($fresh->slug);
                }

                // Q&A for product
                $urls[] = "/api/question-and-answer?product_id={$fresh->id}";

                // Hot product lists we cache
                $urls[] = '/api/product?status=1&trending=1&page=1';
                $urls[] = '/api/product?status=1&page=1';

                // First page of category listings this product belongs to
                foreach ($fresh->categories()->pluck('slug') as $catSlug) {
                    $urls[] = "/api/product?category={$catSlug}&page=1";
                }

                $fresh->indexToElasticsearch(); // ← Use $fresh instead of $product

                \App\Services\ApiCacheRefresher::refresh($urls);
            });
        });

        static::deleted(function ($product) {
            DB::afterCommit(function () use ($product) {
                $product->removeFromElasticsearch();
            });
        });

        static::deleting(function ($product) {

        });

    }

//    public static function boot()
//    {
//        parent::boot();
//
//        static::saving(function ($model) {
//            $model->created_by_id = Helpers::getCurrentUserId();
//            // If you’re updating an existing model, clear its cache:
//            if ($model->exists) {
//                Cache::forget('product:' . $model->id);
//            }
//        });
//
//        static::deleting(function ($model) {
//            Cache::forget('product:' . $model->id);
//        });
//    }


    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => false,
            ]
        ];
    }

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('product')->id;
    }

    /**
     * @return BelongsTo
     */
    public function product_thumbnail(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'product_thumbnail_id');
    }

    /**
     * @return BelongsTo
     */
    public function size_chart_image(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'size_chart_image_id');
    }

    /**
     * @return BelongsTo
     */
    public function product_meta_image(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'product_meta_image_id');
    }

    /**
     * @return BelongsTo
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * @return BelongsTo
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * @return BelongsTo
     */
    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumer_id');
    }

    /**
     * @return BelongsTo
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    /**
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    /**
     * @return BelongsToMany
     */
    public function product_galleries(): BelongsToMany
    {
        return $this->belongsToMany(Attachment::class, 'product_images','product_id', 'attachment_id');
    }

    /**
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    /**
     * @return HasMany
     */
    public function variations(): HasMany
    {
        // Return only active, non-deleted variations for frontend use
        return $this->hasMany(Variation::class, 'product_id')
            ->where('status', 1)
            ->whereNull('deleted_at');
    }

    /**
     * @return HasMany
     * Get all variations including inactive ones (for admin use)
     */
    public function allVariations(): HasMany
    {
        return $this->hasMany(Variation::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'product_id');
    }

    /**
     * @return HasMany
     */
    public function cart(): HasMany
    {
        return $this->hasMany(Cart::class, 'product_id');
    }

    /**
     * @return BelongsToMany
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')->with('attribute_values');
    }

    /**
     * @return BelongsToMany
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_products');
    }

    /**
     * @return BelongsToMany
     */
    public function similar_products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'related_products', 'related_product_id');
    }

    /**
     * @return BelongsToMany
     */
    public function cross_products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'cross_sell_products', 'cross_sell_product_id');
    }

    public function getRelatedProductsAttribute()
    {
        return DB::table('related_products')
            ->where('related_product_id', $this->id)
            ->whereNull('deleted_at')
            ->pluck('product_id')
            ->all();
    }

    public function getCrossSellProductsAttribute()
    {
        return DB::table('cross_sell_products')
            ->where('cross_sell_product_id', $this->id)
            ->whereNull('deleted_at')
            ->pluck('product_id')
            ->all();
    }

    public function getOrderAmountAttribute()
    {
        $filterBy = app('request')->filter_by ?? 'all';
        return (float) Cache::remember(
            "product_order_amount_{$this->id}_{$filterBy}",
            now()->addMinutes(15),
            fn () => Helpers::countOrderAmount($this->id, app('request')->filter_by)
        );
    }

    public function getOrdersCountAttribute()
    {
        $filterBy = app('request')->filter_by ?? 'all';
        return (int) Cache::remember(
            "product_orders_count_{$this->id}_{$filterBy}",
            now()->addMinutes(15),
            fn () => Helpers::getOrderCount($this->id, app('request')->filter_by)
        );
    }

    public function getReviewRatingsAttribute()
    {
        return Cache::remember(
            "product_review_ratings_{$this->id}",
            now()->addMinutes(30),
            fn () => Helpers::getReviewRatings($this->id)
        );
    }

    public function getRatingCountAttribute()
    {
        $avg = \DB::table('reviews')
            ->where('product_id', $this->id)
            ->whereNull('deleted_at')
            ->avg('rating');

        return $avg ? round((float) $avg, 1) : 0;
    }

    public function getCanReviewAttribute()
    {
        if (Helpers::isUserLogin()) {
            return Helpers::canReview(Helpers::getCurrentUserId(), $this->id);
        }

        return false;
    }

    public function getUserReviewAttribute()
    {
        if (Helpers::isUserLogin()) {
            return Helpers::user_review(Helpers::getCurrentUserId(), $this->id);
        }
        return null;
    }

    // Alias for admin UI compatibility: expose has_expedited mapped from has_expedited_shipping
    public function getHasExpeditedAttribute()
    {
        return (int) ($this->has_expedited_shipping ?? 0);
    }

    public function getShippingOptionsAttribute()
    {
        $data = [
            'has_expedited_shipping'   => (int) ($this->has_expedited_shipping ?? 0),
            'standard_shipping_days'   => $this->standard_shipping_days,
            'expedited_shipping_days'  => $this->expedited_shipping_days,
            'standard_shipping_price'  => $this->standard_shipping_price,
            'expedited_shipping_price' => $this->expedited_shipping_price,
        ];
        // Keep has_expedited_shipping always, filter out null others
        return array_filter($data, function ($v, $k) {
            return $k === 'has_expedited_shipping' ? true : $v !== null;
        }, ARRAY_FILTER_USE_BOTH);
    }

    public function indexToElasticsearch()
    {
        $client = ElasticsearchService::client();
        $index  = ElasticsearchService::indexName();

        // Use toSearchableArray() to get complete document with all fields
        $document = $this->toSearchableArray();

        $client->index([
            'index' => $index,
            'id'    => $this->id,
            'body'  => $document,  // ← Use complete document from toSearchableArray()
        ]);
    }

    public function removeFromElasticsearch()
    {
        $client = ElasticsearchService::client();
        $index  = ElasticsearchService::indexName();

        $client->delete([
            'index' => $index,
            'id'    => $this->id,
        ]);
    }

    /**
     * Get vouchers associated with this product (if it's a gift card)
     */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * Check if this product is a gift card
     */
    public function isGiftCard()
    {
        return (bool) $this->is_gift_card;
    }

    /**
     * Get layby eligibility information for this product
     * This calculates eligibility for the base product without variations
     */
    public function getLaybyEligibilityAttribute()
    {
        $price = $this->price;
        $isSaleProduct = false;

        if ($this->sale_price) {
            $price = $this->sale_price;
            $isSaleProduct = true;
        }

        $currency = session('currency', config('app.default_currency', 'USD'));
        $exchangeRate = session('exchange_rate', 1);
        $priceInUSD = $price / $exchangeRate;

        $eligible = $priceInUSD >= 100 && !$this->sa_only;

        // Get settings based on product type
        $depositPercentage = $isSaleProduct
            ? (int)getLaybySetting('sale_products_deposit_percentage', 30)
            : (int)getLaybySetting('regular_products_deposit_percentage', 30);

        $availableDurations = $isSaleProduct
            ? [(int)getLaybySetting('sale_products_duration_months', 3)]
            : explode(',', getLaybySetting('regular_products_duration_months', '6'));

        $availableDurations = array_map('intval', $availableDurations);

        return [
            'eligible' => $eligible,
            'price' => $price,
            'price_usd' => round($priceInUSD, 2),
            'currency' => $currency,
            'threshold' => 100,
            'is_sale_product' => $isSaleProduct,
            'deposit_percentage' => $depositPercentage,
            'available_durations' => $availableDurations,
        ];
    }
}
