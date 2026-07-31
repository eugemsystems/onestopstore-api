<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL','raines-africa-notifications'),
        ],
    ],

    'crm' => [
        'base_url'       => env('CRM_BASE_URL'),
        'webhook_url'    => env('CRM_WEBHOOK_URL'),
        'webhook_secret' => env('CRM_WEBHOOK_SECRET'),
        'inbound_secret' => env('CRM_INBOUND_SECRET'),
        'enabled'        => (bool) env('CRM_WEBHOOK_ENABLED', false),
    ],

    // DPO (Zambia) gateway
    'dpo' => [
        'base_url'         => env('DPO_BASE_URL', 'https://secure.3gdirectpay.com'),
        'company_token'    => env('DPO_COMPANY_TOKEN'),
        'service_type'     => env('DPO_SERVICE_TYPE', ''),
        'ptl'              => env('DPO_PTL', 5),
        'init_endpoint'    => env('DPO_INIT_ENDPOINT', '/API/v6/Transactions/CreatePayment'),
        'redirect_template'=> env('DPO_REDIRECT_TEMPLATE', '/pay/v1/redirect/{token}'),
        'return_url'       => env('DPO_RETURN_URL'),
        'cancel_url'       => env('DPO_CANCEL_URL'),
    ],

    // MSG91 WhatsApp Business API
    'msg91' => [
        'auth_key'              => env('MSG91_AUTH_KEY'),
        'base_url'              => env('MSG91_BASE_URL', 'https://control.msg91.com/api/v5'),
        'whatsapp_sender_id'    => env('MSG91_WHATSAPP_SENDER_ID'), // Your WhatsApp Business Number
        'integrated_number'     => env('MSG91_WHATSAPP_SENDER_ID'), // Same as sender_id
        'namespace'             => env('MSG91_NAMESPACE', '53e292d4_2ada_44e0_8aa6_8321e21292fb'),
        'whatsapp_enabled'      => (bool) env('MSG91_WHATSAPP_ENABLED', false), // Default: disabled
        'order_header_image'    => env('MSG91_ORDER_HEADER_IMAGE'),

        // Flexible Templates (2 templates cover everything)
        'templates' => [
            // Order-related notifications (order updates, status changes, shipping, etc.)
            'order_notification'          => env('MSG91_TEMPLATE_ORDER_NOTIFICATION', 'order_notification'),
            'order_out_for_delivery'      => env('MSG91_TEMPLATE_ORDER_OUT_FOR_DELIVERY', 'order_out_for_delivery'),
            'order_ready_collection'      => env('MSG91_TEMPLATE_ORDER_READY_COLLECTION', 'order_ready_collection'),

            // Account-related notifications (payments, refunds, points, welcome, etc.)
            'account_notification'        => env('MSG91_TEMPLATE_ACCOUNT_NOTIFICATION', 'account_notification'),
        ],
    ],

    // AI Analytics — set AI_PROVIDER in .env to switch between providers
    'ai_providers' => [
        'anthropic' => [
            'key'   => env('ANTHROPIC_API_KEY', ''),
            'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
        ],
        'groq' => [
            'key'   => env('GROQ_API_KEY', ''),
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        ],
        'gemini' => [
            'key'   => env('GEMINI_API_KEY', ''),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        ],
        'openrouter' => [
            'key'   => env('OPENROUTER_API_KEY', ''),
            'model' => env('OPENROUTER_MODEL', 'meta-llama/llama-3.1-8b-instruct:free'),
        ],
    ],
];
