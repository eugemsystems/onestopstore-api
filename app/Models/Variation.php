<?php

namespace App\Models;

use App\Helpers\Helpers;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\ApiCacheRefresher;

class Variation extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /**
     * The variations that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'price',
        'sku',
        'status',
        'quantity',
        'discount',
        'sale_price',
        'product_id',
        'stock_status',
        'attribute_value_id',
        'variation_image_id',
        'zambia_only',
        'zimbabwe_only',
        'sa_only',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'float',
        'sale_price' => 'float',
        'discount' => 'float',
        'product_id' => 'integer',
        'status' => 'integer',
        'attribute_value_id' => 'integer',
        'variation_image_id' => 'integer',
        'zambia_only' => 'boolean',
        'zimbabwe_only' => 'boolean',
        'sa_only' => 'boolean',
    ];

    protected $with = [
        'variation_image',
        'attribute_values'
    ];

    protected $appends = [
        'layby_eligibility',
    ];



    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('tax')->id;
    }

    /**
     * @return BelongsTo
     */
    public function variation_image(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'variation_image_id');
    }

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * @return BelongsToMany
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_products');
    }

    /**
     * @return HasMany
     */
    public function cart(): HasMany
    {
        return $this->hasMany(Cart::class, 'variation_id');
    }

    /**
     * @return BelongsToMany
     */
    public function attribute_values(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'variation_attribute_values');
    }

    /**
     * Get layby eligibility information for this variation
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

        $eligible = $priceInUSD >= 100
            && !$this->sa_only
            && !($this->relationLoaded('product') ? (bool)$this->product?->sa_only : false);

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

    public static function boot()
    {
        parent::boot();
        static::saved(function ($variation) {
            DB::afterCommit(function () use ($variation) {
                if (!$variation->product_id) return;
                $p = \App\Models\Product::with('categories')->find($variation->product_id);
                if (!$p) return;

                $urls = [];
                if ($p->slug) {
                    $urls[] = '/api/product/slug/' . rawurlencode($p->slug);
                }
                $urls[] = "/api/question-and-answer?product_id={$p->id}";
                $urls[] = '/api/product?status=1&trending=1&page=1';
                $urls[] = '/api/product?status=1&page=1';
                foreach ($p->categories()->pluck('slug') as $catSlug) {
                    $urls[] = "/api/product?category={$catSlug}&page=1";
                }
                ApiCacheRefresher::refresh($urls);
            });
        });
    }
}
