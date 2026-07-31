<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Cviebrock\EloquentSluggable\Sluggable;

class Brand extends Model
{
    use HasFactory, SoftDeletes, Sluggable;

    protected $table = 'product_brands';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'brand_image_id',
        'meta_title',
        'meta_description',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * Return the sluggable configuration array for this model.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }

    /**
     * Get the products for the brand.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    /**
     * Get the brand image.
     */
    public function brand_image(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'brand_image_id');
    }

    /**
     * Get active brands only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}

