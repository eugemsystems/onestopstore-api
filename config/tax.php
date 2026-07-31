<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Tax Rate
    |--------------------------------------------------------------------------
    |
    | This is the default tax rate (in percentage) that will be applied when
    | creating orders if no product-specific tax is configured.
    | This value is used when the admin checks "Apply Tax" checkbox and
    | products don't have individual tax rates assigned.
    |
    | Example: 15 = 15% tax
    |
    */
    'default_rate' => env('DEFAULT_TAX_RATE', 15),

    /*
    |--------------------------------------------------------------------------
    | Country-Specific VAT Rates
    |--------------------------------------------------------------------------
    |
    | Zimbabwe VAT: 15% (ZWL / USD orders)
    | Zambia VAT:   16% (ZMW orders)
    |
    */
    'zimbabwe_rate' => env('ZIMBABWE_TAX_RATE', 15),
    'zambia_rate'   => env('ZAMBIA_TAX_RATE', 16),

    /*
    |--------------------------------------------------------------------------
    | Tax Calculation Mode
    |--------------------------------------------------------------------------
    |
    | Determines how tax is calculated:
    | - 'product': Uses individual product tax rates (from taxes table)
    | - 'default': Always uses the default rate above
    | - 'mixed': Uses product rates if available, otherwise default
    |
    */
    'calculation_mode' => env('TAX_CALCULATION_MODE', 'mixed'),

    /*
    |--------------------------------------------------------------------------
    | Include Tax in Product Prices
    |--------------------------------------------------------------------------
    |
    | If true, product prices are considered to include tax already.
    | If false, tax is added on top of product prices.
    |
    */
    'price_includes_tax' => env('PRICE_INCLUDES_TAX', false),

    /*
    |--------------------------------------------------------------------------
    | Tax Rounding
    |--------------------------------------------------------------------------
    |
    | Number of decimal places to round tax calculations to.
    | Common values: 2 (cents), 0 (whole currency units)
    |
    */
    'rounding_decimals' => env('TAX_ROUNDING_DECIMALS', 2),
];

