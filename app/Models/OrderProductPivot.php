<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderProductPivot extends Pivot
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_products';

    /**
     * The Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'variation_id',
        'selected_attribute_ids',
        'variation_display_name',
        'quantity',
        'single_price',
        'tax',
        'shipping_cost',
        'fast_shipping_cost',
        'item_shipping_method',
        'has_fast_shipping',
        'refund_status',
        'subtotal',
        'item_status',
        'cancellation_reason',
        'eta',
        'estimated_delivery_text',
        'qr_code',
        // Product snapshot — frozen at order creation time
        'product_name',
        'product_sku',
        'product_slug',
        'product_image_url',
        'product_price',
        'product_sale_price',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'variation_id' => 'integer',
        'selected_attribute_ids' => 'array',
        'quantity' => 'integer',
        'single_price' => 'float',
        'tax' => 'float',
        'shipping_cost' => 'float',
        'fast_shipping_cost' => 'float',
        'has_fast_shipping' => 'boolean',
        'subtotal' => 'float',
        'product_price' => 'float',
        'product_sale_price' => 'float',
        //'eta' => 'string',
    ];
}
