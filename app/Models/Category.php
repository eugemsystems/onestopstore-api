<?php

namespace App\Models;

use App\Helpers\Helpers;
use App\Observers\CategoryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Cviebrock\EloquentSluggable\Sluggable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy([CategoryObserver::class])]
class Category extends Model implements HasMedia
{
    use Sluggable, HasFactory, SoftDeletes, HasRoles, InteractsWithMedia;

    /**
     * The Categories that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'category_image_id',
        'category_icon_id',
        'category_image_uuid',
        'category_icon_uuid',
        'status',
        'type',
        'parent_id',
        'commission_rate',
        'sort_order',
        'created_by_id'
    ];

    protected $with = [
        'category_image:id,uuid,name,disk,file_name,image_url',
        'category_icon:id,uuid,name,disk,file_name,image_url'
    ];

    protected $withCount = [
        //'blogs',
        //'products'
    ];

    protected $casts = [
        'status' => 'integer',
        'parent_id' => 'integer',
        'category_image_uuid' => 'string',
        'blogs_count' => 'integer',
        'products_count' => 'integer',
        'commission_rate' =>  'float',
        'category_icon_uuid' => 'string',
        'sort_order' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->created_by_id = Helpers::getCurrentUserId();
        });
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => true,
            ]
        ];
    }

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('category')->id;
    }

    /**
     * @return HasMany
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order', 'asc')
            ->with('subcategories:id,name,slug,parent_id,sort_order','parent:id,name,slug,parent_id,sort_order');
    }

    /**
     * Alias for subcategories relationship
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * @return BelongsTo
     */
    public function category_image(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'category_image_uuid','uuid');
    }

    /**
     * @return BelongsTo
     */
    public function category_icon(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'category_icon_uuid', 'uuid');
    }

    /**
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    /**
     * @return BelongsToMany
     */
    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class, 'blog_categories');
    }

    /**
     * Get the Parent Categories.
     */
    public function scopeParent(Builder $query, bool $parent): Builder
    {
        if ($parent) {
            return $query->whereNull('parent_id');
        }

        return $query;
    }
}
