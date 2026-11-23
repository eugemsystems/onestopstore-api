<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One purchasable delivery option of a product (e.g. "Fastest Delivery" / "Best Price").
 * Names are admin-editable; the product's own price/stock are derived from its active options.
 */
class ProductDeliveryOption extends Model
{
    use HasFactory, SoftDeletes;

    /** Default names suggested in the admin when options are first added. */
    public const DEFAULT_LABELS = ['Fastest Delivery', 'Best Price'];

    protected $table = 'product_delivery_options';

    protected $fillable = [
        'product_id', 'label', 'delivery_text', 'delivery_days', 'price', 'sale_price',
        'quantity', 'stock_status', 'sku', 'sort_order', 'is_default', 'status',
    ];

    protected $casts = [
        'price'         => 'float',
        'sale_price'    => 'float',
        'quantity'      => 'integer',
        'delivery_days' => 'integer',
        'sort_order'    => 'integer',
        'is_default'    => 'boolean',
        'status'        => 'boolean',
    ];

    protected $appends = ['effective_price'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Sale price when set and lower than the regular price, otherwise the regular price. */
    public function getEffectivePriceAttribute(): float
    {
        $sale = (float) ($this->sale_price ?? 0);
        $price = (float) $this->price;
        return ($sale > 0 && $sale < $price) ? $sale : $price;
    }

    public function isInStock(): bool
    {
        return $this->status && $this->stock_status === 'in_stock' && (int) $this->quantity > 0;
    }

    /**
     * Re-derive the parent product's price/sale_price/quantity/stock_status from its active options
     * so search, filters, sorting, feeds and layby keep working unchanged.
     */
    public static function syncProduct(int $productId): void
    {
        $product = Product::withoutGlobalScopes()->find($productId);
        if (!$product) {
            return;
        }

        $options = static::where('product_id', $productId)->where('status', 1)->get();
        if ($options->isEmpty()) {
            return; // no options: leave the product's own pricing untouched
        }

        // Cheapest in-stock option drives the displayed price; if none in stock use cheapest overall.
        $pool = $options->filter->isInStock();
        if ($pool->isEmpty()) {
            $pool = $options;
        }
        $lowest = $pool->sortBy('effective_price')->first();

        $quantity = (int) $options->filter->isInStock()->sum('quantity');

        // Quiet update + one explicit save so observers (ES reindex, cache refresh) run exactly once.
        $product->price = $lowest->price;
        $product->sale_price = ($lowest->sale_price && $lowest->sale_price < $lowest->price) ? $lowest->sale_price : null;
        $product->is_sale_enable = $product->sale_price ? 1 : $product->is_sale_enable;
        $product->quantity = $quantity;
        $product->stock_status = $quantity > 0 ? 'in_stock' : 'out_of_stock';
        $product->save();
    }
}
