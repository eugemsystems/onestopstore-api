<?php

return [
    'host' => env('ELASTICSEARCH_HOST','http://localhost:9200'),
    'user' => env('ELASTICSEARCH_USER'),
    'password' => env('ELASTICSEARCH_PASSWORD'),
    'cloud_id' => env('ELASTICSEARCH_CLOUD_ID'),
    'api_key' => env('ELASTICSEARCH_API_KEY'),
    'ssl_verification' => env('ELASTICSEARCH_SSL_VERIFICATION', true),
    'index' => env('ES_PRODUCTS_INDEX', 'products_search'),
    'queue' => [
        'timeout' => env('SCOUT_QUEUE_TIMEOUT'),
    ],
    'indices' => [
        'mappings' => [
            'default' => [
                'properties' => [
                    'id' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
        ],
        'settings' => [
            'default' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ],
        ],
    ],
    'chunk' => 50, // Increase from default 500
    'bulk' => [
        'actions' => 50, // Increase batch size
    ],
];
