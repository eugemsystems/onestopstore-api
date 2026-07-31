<?php

namespace App\Models;

use App\Helpers\Helpers;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Cviebrock\EloquentSluggable\Sluggable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model implements HasMedia
{
    use Sluggable, SoftDeletes, HasApiTokens, HasFactory, Notifiable, InteractsWithMedia;

    /**
     * The stores that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_name',
        'description',
        'slug',
        'status',
        'country_id',
        'state_id',
        'store_logo_id',
        'store_cover_id',
        'city',
        'address',
        'pincode',
        'hide_vendor_email',
        'hide_vendor_phone',
        'facebook',
        'twitter',
        'instagram',
        'youtube',
        'pinterest',
        'vendor_id',
        'is_approved',
        'is_banned',
        'ban_reason',
        'banned_at',
        'banned_by',
        'created_by_id',
        // New vendor registration fields
        'is_vat_registered',
        'vat_number',
        'identification_type',
        'id_number',
        'legal_name',
        'trading_name',
        'monthly_revenue',
        'has_physical_stores',
        'number_of_stores',
        'is_supplier_to_retailers',
        'has_marketplace_accounts',
        'number_of_products',
        'primary_category',
        'stock_holding',
        'product_source',
        'product_branding',
        'owned_brands',
        'reseller_brands',
        'website',
        'social_media_page',
        'product_catalog_id',
        'business_summary',
        'product_uniqueness',
        'intended_products',
        'certifications',
        'referral_source',
    ];

    protected $with = [
        'store_logo',
        'vendor:id,name,email,country_code,phone,status',
        'country:id,name',
        'state:id,name'
    ];

    protected $withCount = [
        'orders',
        'reviews',
        'products'
    ];

    protected $appends = [
        'product_images',
        'order_amount',
        'rating_count'
    ];

    protected $casts = [
        'country_id' => 'integer',
        'state_id' => 'integer',
        'store_logo_id' => 'integer',
        'store_cover_id' => 'integer',
        'vendor_id' => 'integer',
        'hide_vendor_email' => 'integer',
        'hide_vendor_phone' => 'integer',
        'status' => 'integer',
        'products_count' => 'integer',
        'is_approved' => 'integer',
        'reviews_count' => 'integer',
        'rating_count' => 'float',
        'is_banned' => 'boolean',
        'banned_at' => 'datetime'
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'store_name',
                'onUpdate' => true,
            ]
        ];
    }

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->created_by_id = Helpers::getCurrentUserId() ?? $model->vendor_id;
        });

        static::deleted(function($store) {
            $store->products()->delete();
            $store->vendor()->delete();
        });
    }

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('store')->id;
    }

    /**
     * @return BelongsTo
     */
    public function store_logo(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'store_logo_id');
    }

    /**
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'store_id');
    }

    /**
     * @return BelongsTo
     */
    public function store_cover(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'store_cover_id');
    }

    /**
     * @return BelongsTo
     */
    public function product_catalog(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'product_catalog_id');
    }

    /**
     * @return BelongsTo
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'store_id');
    }

    /**
     * @return BelongsTo
     */
    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    /**
     * @return BelongsTo
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * @return BelongsTo
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    /**
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'store_id');
    }

    public function getProductImagesAttribute()
    {
        return Helpers::getStoreWiseLastThreeProductImages($this->id);
    }

    public function getOrdersCountAttribute()
    {
        $request = app('request');
        return (int) Helpers::getStoreOrderCount($this->id, $request->filter_by);
    }

    public function getProductsCountAttribute()
    {
        $request = app('request');
        return (int) Helpers::getProductCountByStoreId($this->id, $request->filter_by);
    }

    public function getOrderAmountAttribute()
    {
        $request = app('request');
        return (float) Helpers::countStoreOrderAmount($this->id, $request->filter_by);
    }

    public function getRatingCountAttribute()
    {
        return (float) $this->reviews->avg('rating');
    }
}
