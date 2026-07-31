<?php

return [
    'order' => [
        'pivot' => [
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
            'item_status',
            'cancellation_reason',
            'eta',
            'estimated_delivery_text',
            'subtotal',
            'qr_code',
            // Product snapshot — frozen at order creation time
            'product_name',
            'product_sku',
            'product_slug',
            'product_image_url',
            'product_price',
            'product_sale_price',
        ],
        'with' => [
            // CRITICAL: Include consumer and order_status to prevent Flutter null casting errors
            'consumer:id,name,email,phone,country_code,profile_image_id',
            'order_status:id,name,slug,sequence',
            // Must include all FK columns needed for Product's $with relationships
            'products:id,name,product_thumbnail_id,product_meta_image_id,size_chart_image_id,store_id',
            'sub_orders','sub_orders.products:id,name,product_thumbnail_id,product_meta_image_id,size_chart_image_id,store_id',
            'billing_address',
            'shipping_address',
        ]
    ],
    'seeders' => [
        'RoleSeeder',
        'ThemeSeeder',
        'DefaultImagesSeeder',
        'HomePageSeeder',
        'CountriesSeeder',
        'SettingSeeder',
        'ThemeOptionSeeder',
        'OrderStatusSeeder',
        'StateSeeder'
    ],
    'user' => [
        'with' => [
            'point',
            'wallet',
            'address',
            'vendor_wallet',
            'profile_image',
            'payment_account'
        ]
    ],
    'store' => [
        'with' => [
            'store_logo',
            'store_cover',
            'vendor',
            'country',
            'state'
        ]
    ],
    'product' => [
        'with' => [
            'product_galleries',
            'size_chart_image',
            'store:id,store_name,slug,description,store_logo_id,hide_vendor_email,hide_vendor_phone,vendor_id',
            'attributes',
            'categories',
            'variations',
            'tags',
        ],
        'visible' => [
            'description',
            'cross_products',
            'meta_description',
        ],
        'appends' => [
            'user_review',
            'can_review',
            'rating_count',
            'order_amount',
            'review_ratings',
            'related_products',
            'cross_sell_products',
            // expose alias expected by admin UI
            'has_expedited',
            // expose consolidated shipping options for product detail views
            'shipping_options',
            // layby eligibility information
            'layby_eligibility',
        ]
    ]
];
