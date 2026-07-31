<?php

namespace App\Http\Resources\Crm;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        $order = $this->resource;

        $order->loadMissing([
            'order_status',
            'transactions',        // your model is OrderTransaction; repository often aliases to 'transactions'
            'consumer',            // if user relation is named differently, adjust (e.g., consumer -> user)
            'billing_address',
            'shipping_address',
            'products' => function ($q) {
                $q->with(['variations.attribute_values.attribute'])
                  ->withPivot(['variation_id','quantity','single_price','shipping_cost','tax','subtotal','fast_shipping_cost','item_shipping_method','has_fast_shipping','item_status','cancellation_reason','eta','selected_attribute_ids','variation_display_name']);
            },
            'sub_orders.products' => function ($q) {
                $q->with(['variations.attribute_values.attribute'])
                  ->withPivot(['variation_id','quantity','single_price','shipping_cost','tax','subtotal','fast_shipping_cost','item_shipping_method','has_fast_shipping','item_status','cancellation_reason','eta','selected_attribute_ids','variation_display_name','estimated_delivery_text']);
            },
            'status_histories',
        ]);

        // Collect ALL products from main order AND sub-orders
        $allProducts = collect();

        // Add main order products
        if ($order->products) {
            $allProducts = $allProducts->merge($order->products);
        }

        // Add sub-order products
        if ($order->sub_orders) {
            foreach ($order->sub_orders as $subOrder) {
                if ($subOrder->products) {
                    $allProducts = $allProducts->merge($subOrder->products);
                }
            }
        }

        // Preload all variations referenced by ALL order items (main + sub-orders)
        $variationMap = collect();
        $variationIds = $allProducts->pluck('pivot.variation_id')->filter()->unique()->values();
        if ($variationIds->isNotEmpty()) {
            $variationMap = \App\Models\Variation::withTrashed()
                ->with(['variation_image','attribute_values.attribute'])
                ->whereIn('id', $variationIds->all())
                ->get()
                ->keyBy('id');
        }

        return [
            'id'               => $order->id,
            'order_number'     => $order->order_number,
            'store_id'         => $order->store_id,
            'consumer_id'      => $order->consumer_id,
            'status'           => optional($order->order_status)->name,
            'order_status_id'  => $order->order_status_id,
            'currency'         => $order->currency ?? null,
            'currency_symbol'  => $order->currency_symbol ?? null,
            'exchange_rate'    => $order->exchange_rate ?? 1,
            'subtotal'         => $order->amount ?? null,  // 'amount' is the subtotal
            'discount_total'   => $order->coupon_total_discount ?? null,
            'tax_total'        => $order->tax_total ?? null,
            'shipping_total'   => $order->shipping_total ?? null,
            'fast_shipping_total' => $order->fast_shipping_total ?? null,
            'delivery_price'   => $order->delivery_price ?? null,
            'total'            => $order->total ?? null,  // 'total' is the grand total
            'payment_method'   => $order->payment_method ?? null,
            'payment_status'   => $order->payment_status ?? null,
            'wallet_balance'   => $order->wallet_balance ?? null,
            'points_amount'    => $order->points_amount ?? null,
            'invoice_url'      => $order->invoice_url,
            'coupon_id'        => $order->coupon_id,
            'parent_id'        => $order->parent_id,
            'delivered_at'     => $order->delivered_at,
            'created_at'       => $order->created_at,
            'updated_at'       => $order->updated_at,

            'consumer' => $order->consumer ? [
                'id'    => $order->consumer->id,
                'name'  => $order->consumer->name ?? null,
                'email' => $order->consumer->email ?? null,
                'phone' => $order->consumer->phone ?? null,
            ] : null,

            'billing_address' => $order->billing_address ? [
                'id'       => $order->billing_address->id,
                'first'    => $order->billing_address->first_name,
                'last'     => $order->billing_address->last_name,
                'email'    => $order->billing_address->email,
                'phone'    => $order->billing_address->phone,
                'line1'    => $order->billing_address->address,
                'line2'    => $order->billing_address->address2,
                'city'     => $order->billing_address->city,
                // flatten related models safely:
                'state_id'    => optional($order->billing_address->state)->id,
                'state_name'  => optional($order->billing_address->state)->name,
                'country_id'  => optional($order->billing_address->country)->id,
                'country_name'=> optional($order->billing_address->country)->name,
                'postcode' => $order->billing_address->postcode,
            ] : null,

            'shipping_address' => $order->shipping_address ? [
                'id'       => $order->shipping_address->id,
                'first'    => $order->shipping_address->first_name,
                'last'     => $order->shipping_address->last_name,
                'email'    => $order->shipping_address->email,
                'phone'    => $order->shipping_address->phone,
                'line1'    => $order->shipping_address->address,
                'line2'    => $order->shipping_address->address2,
                'city'     => $order->shipping_address->city,
                'state_id'    => optional($order->shipping_address->state)->id,
                'state_name'  => optional($order->shipping_address->state)->name,
                'country_id'  => optional($order->shipping_address->country)->id,
                'country_name'=> optional($order->shipping_address->country)->name,
                'postcode' => $order->shipping_address->postcode,
            ] : null,

            'items' => $allProducts->map(function ($p) use ($order, $variationMap) {
                    // stable fallback id when pivot has no id
                    $fallbackId = function() use ($order, $p) {
                        $seed = $order->id.'|'.$p->id.'|'.($p->pivot->variation_id ?? 0);
                        // 32-bit unsigned CRC -> int
                        return (int) sprintf('%u', crc32($seed));
                    };

                    $pivotId = $p->pivot->id ?? $fallbackId();

                    $variation = null;
                    if ($p->pivot->variation_id ?? null) {
                        $variation = $variationMap->get($p->pivot->variation_id);
                        if (!$variation && isset($p->variations)) {
                            $variation = $p->variations->firstWhere('id', $p->pivot->variation_id);
                        }
                    }

                    // Use variation prices if variation exists, otherwise use product prices
                    $itemPrice = $variation ? ($variation->price ?? $p->price) : ($p->price ?? null);
                    $itemSalePrice = $variation ? ($variation->sale_price ?? $p->sale_price) : ($p->sale_price ?? null);

                    return [
                        'id'             => $pivotId,
                        'order_id'       => $order->id,
                        'product_id'     => $p->id,
                        'variation_id'   => $p->pivot->variation_id ?? null,
                        'name'           => $p->name ?? $p->pivot->product_name ?? null,
                        'slug'           => $p->slug ?? $p->pivot->product_slug ?? null,
                        'image_url'      => optional($p->product_thumbnail)->image_url
                                            ?? optional($p->product_thumbnail)->original_url
                                            ?? $p->pivot->product_image_url
                                            ?? null,
                        'price'          => $itemPrice ?? $p->pivot->product_price ?? null,
                        'sale_price'     => $itemSalePrice ?? $p->pivot->product_sale_price ?? null,
                        'sku'            => $variation->sku ?? $p->sku ?? $p->pivot->product_sku ?? null,
                        'variation'      => $variation ? [
                            'id'         => $variation->id,
                            'sku'        => $variation->sku ?? null,
                            'name'       => $p->pivot->variation_display_name ?? $variation->name ?? null,
                            'price'      => $variation->price ?? null,
                            'sale_price' => $variation->sale_price ?? null,
                            'image_url'  => optional($variation->variation_image)->image_url ?? optional($variation->variation_image)->original_url ?? null,
                            'attributes' => $variation->attribute_values?->map(function ($av) {
                                return [
                                    'id'        => $av->id,
                                    'value'     => $av->value,
                                    'hex_color' => $av->hex_color,
                                    'attribute' => $av->attribute ? [
                                        'id'   => $av->attribute->id,
                                        'name' => $av->attribute->name,
                                    ] : null,
                                ];
                            })->values() ?? [],
                        ] : null,
                        'variation_display_name' => $p->pivot->variation_display_name ?? null,
                        'selected_attribute_ids' => $p->pivot->selected_attribute_ids ?? null,
                        'quantity'       => (int) ($p->pivot->quantity ?? 1),
                        'single_price'   => $p->pivot->single_price ?? null,
                        'shipping_cost'  => $p->pivot->shipping_cost ?? null,
                        'fast_shipping_cost' => $p->pivot->fast_shipping_cost ?? 0,
                        'item_shipping_method' => $p->pivot->item_shipping_method ?? null,
                        'has_fast_shipping' => $p->pivot->has_fast_shipping ?? false,
                        'tax'            => $p->pivot->tax ?? null,
                        'subtotal'       => $p->pivot->subtotal ?? null,
                        'item_status'    => $p->pivot->item_status ?? null,
                        'cancellation_reason' => $p->pivot->cancellation_reason ?? null,
                        'eta'            => $p->pivot->eta ?? null,
                        'estimated_delivery_text' => $p->pivot->estimated_delivery_text ?? null,
                        // Snapshot fields (pass through for CRM storage)
                        'product_name'       => $p->pivot->product_name ?? $p->name ?? null,
                        'product_sku'        => $p->pivot->product_sku ?? $p->sku ?? null,
                        'product_slug'       => $p->pivot->product_slug ?? $p->slug ?? null,
                        'product_image_url'  => $p->pivot->product_image_url ?? null,
                        'product_price'      => $p->pivot->product_price ?? null,
                        'product_sale_price' => $p->pivot->product_sale_price ?? null,
                    ];
                })->values() ?? [],

            'transactions' => $order->transactions?->map(function ($t) {
                    return [
                        'id'        => $t->id,
                        'order_id'  => $t->order_id,
                        'gateway'   => $t->gateway ?? null,
                        'status'    => $t->status ?? null,
                        'amount'    => $t->amount ?? null,
                        'currency'  => $t->currency ?? null,
                        'reference' => $t->reference ?? null,
                        'meta'      => $t->meta ?? null,
                        'created_at'=> $t->created_at,
                        'updated_at'=> $t->updated_at,
                    ];
                })->values() ?? [],

            'status_histories' => $order->status_histories?->map(function ($h) {
                    return [
                        'id'             => $h->id,
                        'old_status_id'  => $h->old_status_id,
                        'old_status'     => optional($h->old_status)->name,
                        'new_status_id'  => $h->new_status_id,
                        'new_status'     => optional($h->new_status)->name,
                        'updated_by_id'  => $h->updated_by_id,
                        'updated_by'     => $h->updated_by ? [
                            'id'    => $h->updated_by->id,
                            'name'  => $h->updated_by->name,
                            'email' => $h->updated_by->email,
                        ] : null,
                        'created_at'     => $h->created_at,
                    ];
                })->values() ?? [],
        ];
    }
}
