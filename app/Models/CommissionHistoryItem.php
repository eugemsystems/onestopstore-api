<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommissionHistoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'commission_history_id',
        'product_id',
        'product_name',
        'product_sku',
        'product_price',
        'quantity',
        'subtotal',
        'commission_rate',
        'commission_source',
        'category_id',
        'category_name',
        'admin_commission',
        'vendor_commission',
    ];

    protected $casts = [
        'commission_history_id' => 'integer',
        'product_id' => 'integer',
        'product_price' => 'float',
        'quantity' => 'integer',
        'subtotal' => 'float',
        'commission_rate' => 'float',
        'category_id' => 'integer',
        'admin_commission' => 'float',
        'vendor_commission' => 'float',
    ];

    /**
     * Get the commission history that owns this item
     */
    public function commissionHistory(): BelongsTo
    {
        return $this->belongsTo(CommissionHistory::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

