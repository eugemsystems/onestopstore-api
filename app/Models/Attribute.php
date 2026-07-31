<?php

namespace App\Models;

use App\Helpers\Helpers;
use App\Observers\AttributeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy([AttributeObserver::class])]
class Attribute extends Model
{
    use Sluggable, HasFactory, HasRoles, SoftDeletes;

    /**
     * The Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'style',
        'status',
        'created_by_id'
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if(config('app.env') === 'local'){
                $model->created_by_id = 1;
            }else{
                $model->created_by_id = Helpers::getCurrentUserId();
            }
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
        return ($request->id) ? $request->id : $request->route('attribute')->id;
    }

    /**
     * @return HasMany
     */
    public function attribute_values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id');
    }

    /**
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attributes');
    }
}
