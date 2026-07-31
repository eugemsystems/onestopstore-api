<?php

return [
    // Your merchant credentials
    'merchant_id'  => env('PAYFAST_MERCHANT_ID', ''),
    'merchant_key' => env('PAYFAST_MERCHANT_KEY', ''),
    'passphrase'   => env('PAYFAST_PASSPHRASE', ''),

    // Toggle sandbox mode
    'sandbox'      => env('PAYFAST_SANDBOX', true),

    // URLs
    'return_url'   => env('PAYFAST_RETURN_URL', ''),
    'cancel_url'   => env('PAYFAST_CANCEL_URL', ''),
    'notify_url'   => env('PAYFAST_NOTIFY_URL', ''),
];
