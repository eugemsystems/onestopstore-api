<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => config('app.env') === 'local'
        ? array_values(array_filter(array_unique(array_merge(
            array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:3001,http://localhost:8000,http://localhost:8002'))),
            [env('APP_URL'), env('FRONTEND_URL'), env('BACKEND_URL'), env('IMAGE_API_URL')]
        ))))
        : [
        'https://onestopstore.co.zw',
        'https://www.onestopstore.co.zw',
        'https://admin.onestopstore.co.zw',
        'https://www.admin.onestopstore.co.zw',
        'https://media.onestopstore.co.zw',
        'https://www.media.onestopstore.co.zw',
    ],

    'allowed_origins_patterns' => config('app.env') === 'local'
        ? [
            '#^https?://localhost(:[0-9]+)?$#i',
            '#^https?://127\\.0\\.0\\.1(:[0-9]+)?$#i',
        ]
        : [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
