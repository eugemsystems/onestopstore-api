<?php
/**
 * Pesepay Setting & API Credentials
 */

return [
    'integration_key' => env('PESEPAY_INTEGRATION_KEY'),
    'encryption_key' => env('PESEPAY_ENCRYPTION_KEY'),
    'return_url' => env('PESEPAY_RETURN_URL', 'http://localhost:3000/en/account/order/details'),
    'result_url' => env('PESEPAY_RESULT_URL', 'http://localhost:3000/en/account/order/details'),
];
