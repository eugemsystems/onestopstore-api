<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master Kill Switch
    |--------------------------------------------------------------------------
    |
    | Set ANALYTICS_ENABLED=false in .env to disable all tracking instantly.
    | All tracking endpoints return 200 immediately — no DB, no queue, no CPU.
    |
    */

    'enabled' => env('ANALYTICS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Bot Detection & Filtering
    |--------------------------------------------------------------------------
    |
    | Enable bot detection to filter out bot traffic from analytics
    |
    */

    'bot_detection' => [
        // Enable/disable bot filtering
        'enabled' => env('ANALYTICS_FILTER_BOTS', true),

        // Log bot requests for monitoring
        'log_bot_requests' => env('ANALYTICS_LOG_BOT_REQUESTS', false),

        // Log channel for bot requests
        'log_channel' => env('ANALYTICS_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Analytics tracking uses queued jobs
    |
    */

    'queue' => [
        'name' => env('ANALYTICS_QUEUE', 'analytics'),
        'connection' => env('ANALYTICS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'redis')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep analytics data (in days)
    |
    */

    'retention' => [
        'page_views' => env('ANALYTICS_RETENTION_PAGE_VIEWS', 90),
        'sessions' => env('ANALYTICS_RETENTION_SESSIONS', 90),
        'events' => env('ANALYTICS_RETENTION_EVENTS', 30),
        'cart_abandonments' => env('ANALYTICS_RETENTION_CART', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limits for analytics endpoints
    |
    */

    'rate_limit' => [
        'max_attempts' => env('ANALYTICS_RATE_LIMIT', 120),
        'decay_minutes' => env('ANALYTICS_RATE_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize analytics performance
    |
    */

    'performance' => [
        // Skip processing for these low-value events
        'ignored_events' => [
            'page_visible',
            'page_hidden',
            'heartbeat',
        ],

        // Batch insert size
        'batch_size' => env('ANALYTICS_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | GeoIP Service Configuration
    |--------------------------------------------------------------------------
    */

    'geoip' => [
        'enabled' => env('ANALYTICS_GEOIP_ENABLED', true),
        'cache_ttl' => env('ANALYTICS_GEOIP_CACHE_TTL', 604800), // 7 days default
        'timeout' => env('ANALYTICS_GEOIP_TIMEOUT', 2),
        'fallback_on_error' => env('ANALYTICS_GEOIP_FALLBACK', true),

        // Multiple providers with fallback chain
        'providers' => [
            [
                'name' => 'ipapi',
                'enabled' => env('GEOIP_IPAPI_ENABLED', true),
                'endpoint' => env('GEOIP_IPAPI_ENDPOINT', 'https://ipapi.co'),
                'token' => env('GEOIP_IPAPI_TOKEN'),
                'daily_limit' => env('GEOIP_IPAPI_DAILY_LIMIT', 1000),
                'format' => 'ipapi', // ipapi.co format
            ],
            [
                'name' => 'ip-api',
                'enabled' => env('GEOIP_IPAPI_COM_ENABLED', true),
                'endpoint' => env('GEOIP_IPAPI_COM_ENDPOINT', 'http://ip-api.com'),
                'token' => null, // free tier, no token
                'daily_limit' => env('GEOIP_IPAPI_COM_DAILY_LIMIT', 45), // 45 req/min = ~64k/day
                'format' => 'ip-api', // ip-api.com format
            ],
            [
                'name' => 'ipgeolocation',
                'enabled' => env('GEOIP_IPGEOLOCATION_ENABLED', false),
                'endpoint' => env('GEOIP_IPGEOLOCATION_ENDPOINT', 'https://api.ipgeolocation.io'),
                'token' => env('GEOIP_IPGEOLOCATION_TOKEN'),
                'daily_limit' => env('GEOIP_IPGEOLOCATION_DAILY_LIMIT', 1000),
                'format' => 'ipgeolocation',
            ],
            [
                'name' => 'ipstack',
                'enabled' => env('GEOIP_IPSTACK_ENABLED', false),
                'endpoint' => env('GEOIP_IPSTACK_ENDPOINT', 'http://api.ipstack.com'),
                'token' => env('GEOIP_IPSTACK_TOKEN'),
                'daily_limit' => env('GEOIP_IPSTACK_DAILY_LIMIT', 1000),
                'format' => 'ipstack',
            ],
        ],
    ],
];

