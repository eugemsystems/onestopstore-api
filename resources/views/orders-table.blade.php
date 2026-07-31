{{-- Modern order items table with variation support --}}
<style>
    .product-item-row { border-bottom: 1px solid #e5e7eb; }
    .product-item-row:last-child { border-bottom: none; }
    .product-item-row.fast-shipping { background: #fffbeb; border-left: 3px solid #f59e0b; }
    .product-name { font-weight: 600; color: #111827; margin-bottom: 4px; }
    .variation-badges { margin-top: 6px; display: flex; flex-wrap: wrap; gap: 4px; }
    .var-badge {
        display: inline-block;
        padding: 2px 8px;
        background: #dcfce7;
        color: #166534;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }
    .fast-badge {
        display: inline-block;
        padding: 2px 8px;
        background: #fef3c7;
        color: #92400e;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    .item-meta { font-size: 12px; color: #6b7280; margin-top: 2px; }
</style>

<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
            <th style="padding: 12px; text-align: left; font-weight: 500; color: #374151;">Product</th>
            <th style="padding: 12px; text-align: center; font-weight: 500; color: #374151;">Qty</th>
            <th style="padding: 12px; text-align: right; font-weight: 500; color: #374151;">Unit Price</th>
            <th style="padding: 12px; text-align: right; font-weight: 500; color: #374151;">Total</th>
        </tr>
    </thead>
    <tbody>
        @php
            $sym = $symbol ?? ($order->currency_symbol ?? '');
            $code = $code ?? ($order->currency ?? '');
            $rate = (float) ($order->exchange_rate ?? 1);
        @endphp
        @foreach($order->products as $product)
            @php
                // Amounts are already converted - use pivot single_price (order-time price)
                $unit = ($product->pivot->single_price ?? $product->price);
                $line = ($product->pivot->quantity * $unit);

                // Get variation display name from pivot
                $variationDisplayName = $product->pivot->variation_display_name ?? null;
                $variationParts = $variationDisplayName ? explode(' - ', $variationDisplayName) : [];

                // Check for fast shipping
                $hasFastShipping = ($product->pivot->has_fast_shipping ?? false) || ($product->pivot->fast_shipping_cost ?? 0) > 0;
                $fastShippingCost = (float)($product->pivot->fast_shipping_cost ?? 0);
            @endphp
            <tr class="product-item-row {{ $hasFastShipping ? 'fast-shipping' : '' }}">
                <td style="padding: 12px;">
                    <div class="product-name">
                        {{ $product->name }}
                        @if($hasFastShipping)
                            <span style="color: #f59e0b; font-size: 16px;">⚡</span>
                        @endif
                    </div>

                    @if(!empty($variationParts))
                        <div class="variation-badges">
                            @foreach($variationParts as $part)
                                <span class="var-badge">{{ trim($part) }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if($hasFastShipping)
                        <div style="margin-top: 4px;">
                            <span class="fast-badge">
                                ⚡ Fast Shipping{{ $fastShippingCost > 0 ? ' (' . $sym . number_format($fastShippingCost, 2) . ')' : '' }}
                            </span>
                        </div>
                    @endif

                    @if(!empty($product->sku))
                        <div class="item-meta">SKU: {{ $product->sku }}</div>
                    @endif
                </td>
                <td style="padding: 12px; text-align: center; color: #374151;">
                    {{ $product->pivot->quantity }}
                </td>
                <td style="padding: 12px; text-align: right; color: #374151;">
                    {{ $sym }}{{ number_format($unit, 2, '.', ',') }} <small style="color: #6b7280;">{{ $code }}</small>
                </td>
                <td style="padding: 12px; text-align: right; font-weight: 600; color: #111827;">
                    {{ $sym }}{{ number_format($line, 2, '.', ',') }} <small style="color: #6b7280;">{{ $code }}</small>
                </td>
            </tr>
        @endforeach

        {{-- Display Sub-Order Products --}}
        @if(isset($order->sub_orders) && $order->sub_orders && $order->sub_orders->count() > 0)
            @foreach($order->sub_orders as $subOrder)
                @if($subOrder->products && $subOrder->products->count() > 0)
                    {{-- Sub-Order Header Row --}}
                    <tr style="background: linear-gradient(135deg, #e0e7ff 0%, #dbeafe 100%);">
                        <td colspan="4" style="padding: 8px 12px; font-weight: 600; color: #4f46e5; font-size: 13px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            @if($subOrder->store_id)
                                Vendor Order #{{ $subOrder->order_number }}
                            @else
                                Additional Items #{{ $subOrder->order_number }}
                            @endif
                        </td>
                    </tr>

                    {{-- Sub-Order Products --}}
                    @foreach($subOrder->products as $product)
                        @php
                            $unit = ($product->pivot->single_price ?? $product->price);
                            $line = ($product->pivot->quantity * $unit);
                            $variationDisplayName = $product->pivot->variation_display_name ?? null;
                            $variationParts = $variationDisplayName ? explode(' - ', $variationDisplayName) : [];
                            $hasFastShipping = ($product->pivot->has_fast_shipping ?? false) || ($product->pivot->fast_shipping_cost ?? 0) > 0;
                            $fastShippingCost = (float)($product->pivot->fast_shipping_cost ?? 0);
                        @endphp
                        <tr class="product-item-row {{ $hasFastShipping ? 'fast-shipping' : '' }}" style="background: #f9fafb;">
                            <td style="padding: 12px;">
                                <div class="product-name">
                                    {{ $product->name }}
                                    @if($hasFastShipping)
                                        <span style="color: #f59e0b; font-size: 16px;">⚡</span>
                                    @endif
                                </div>

                                @if(!empty($variationParts))
                                    <div class="variation-badges">
                                        @foreach($variationParts as $part)
                                            <span class="var-badge">{{ trim($part) }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($hasFastShipping)
                                    <div style="margin-top: 4px;">
                                        <span class="fast-badge">
                                            ⚡ Fast Shipping{{ $fastShippingCost > 0 ? ' (' . $sym . number_format($fastShippingCost, 2) . ')' : '' }}
                                        </span>
                                    </div>
                                @endif

                                @if(!empty($product->sku))
                                    <div class="item-meta">SKU: {{ $product->sku }}</div>
                                @endif
                            </td>
                            <td style="padding: 12px; text-align: center; color: #374151;">
                                {{ $product->pivot->quantity }}
                            </td>
                            <td style="padding: 12px; text-align: right; color: #374151;">
                                {{ $sym }}{{ number_format($unit, 2, '.', ',') }} <small style="color: #6b7280;">{{ $code }}</small>
                            </td>
                            <td style="padding: 12px; text-align: right; font-weight: 600; color: #111827;">
                                {{ $sym }}{{ number_format($line, 2, '.', ',') }} <small style="color: #6b7280;">{{ $code }}</small>
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        @endif
    </tbody>
</table>
