<?php

namespace App\Models;

use App\Helpers\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'variation_id',
        'consumer_id',
        'quantity',
        'sub_total',
        'item_shipping_method',
        'item_shipping_cost',
    ];

    protected $with = [
        'product',
        'variation'
    ];

    protected $casts = [
        'variation_id' => 'integer',
        'product_id' => 'integer',
        'consumer_id' => 'integer',
        'quantity' => 'integer',
        'item_shipping_cost' => 'float',
    ];

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            // Only stamp the owner on creation; re-stamping on every save would
            // hand a row to whoever (or whatever job) happens to save it.
            if (!$model->exists || empty($model->consumer_id)) {
                $model->consumer_id = Helpers::getCurrentUserId();
            }
        });
    }

    /**
     * @return Int
     */
    public function getId($request)
    {
        return ($request->id) ? $request->id : $request->route('cart')->id;
    }

    /**
     * @return BelongsTo
     */
    public function consumer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo
     */
    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }
}
