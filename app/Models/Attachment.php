<?php

namespace App\Models;

use App\Helpers\Helpers;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Attachment extends Media implements HasMedia
{
    use HasFactory,SoftDeletes, InteractsWithMedia;

    protected $table = 'attachments';

    // `id` is a normal auto-incrementing bigint (see migration) -- `uuid` is
    // a separate column, not the primary key. This previously declared
    // `$incrementing = false` / `$keyType = 'string'` as if `id` itself were
    // a UUID, which tells Eloquent not to read the generated id back after
    // INSERT. The database row got a real id, but every in-memory
    // `Attachment::create(...)` call returned a model whose `->id` was
    // always null -- silently breaking every caller that immediately used
    // it (product gallery/thumbnail attach, blog images, store logos,
    // layby documents, etc.), while the attachment itself still showed up
    // fine in any fresh query (e.g. the admin Media Library list).
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
    */

    protected $fillable = [
        'id',
        'uuid',
        'name','image_url','takealot_url',
        'file_name',
        'collection_name',
        'model_id',
        'model_type',
        'order_column',
        'disk',
        'conversions_disk',
        'mime_type',
        'size',
        'custom_properties',
        'generated_conversions',
        'responsive_images',
        'manipulations',
        'original_url',
        'preview_url',
        'created_by_id'
    ];

    protected $casts = [
        'custom_properties' => 'json',
        'generated_conversions' => 'json',
        'responsive_images' => 'json',
        'manipulations' => 'json',
    ];

    protected $visible = [
        'id',
        'uuid','image_url',
        'name','takealot_url',
        'file_name',
        'disk',
        'original_url',
        'created_by_id',
        'created_at',
    ];

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->model_id  = $model->uuid;

            if(Auth::check()){
                $model->created_by_id = Helpers::getCurrentUserId() ?? Helpers::getAdmin()->id;
            }else{
                $model->created_by_id = 1;
            }

        });
    }

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->uuid) ? $request->uuid : $request->route('attachment')->uuid;
    }

    /**
     * @return HasMany
     */
    public function review_image(): HasMany
    {
        return $this->hasMany(Review::class, 'review_image_id');
    }

    /**
     * @return HasMany
     */
    public function category_image(): HasMany
    {
        return $this->hasMany(Category::class, 'category_image_uuid','uuid');
    }

    /**
     * @return HasMany
     */
    public function category_icon(): HasMany
    {
        return $this->hasMany(Category::class, 'category_icon_uuid', 'uuid');
    }
}
