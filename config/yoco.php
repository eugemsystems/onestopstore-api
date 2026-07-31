<?php

return [
    // Set to 'https://payments.yoco.com/api' for live. For sandbox/testing use ngrok/tunneling to receive webhooks.
    'base_url'       => env('YOCO_BASE_URL', 'https://payments.yoco.com/api'),

    // Secret key from Yoco dashboard
    'secret_key'     => env('YOCO_SECRET_KEY', ''),

    // Optional webhook secret if configured in Yoco
    'webhook_secret' => env('YOCO_WEBHOOK_SECRET', null),

    // Optional return/cancel URLs; if not provided in request, these are used.
    // Recommended to point to your frontend domain.
    'return_url'     => env('YOCO_RETURN_URL'),
    'cancel_url'     => env('YOCO_CANCEL_URL'),

    // Control whether to store checkout intent rows (status "created").
    // Set YOCO_LOG_INTENT=true in .env to enable intent logging; otherwise only final results are logged.
    'log_intent'     => env('YOCO_LOG_INTENT', false),

    // If false (default), polling via verifyPayment will NOT finalize the order.
    // Only webhook (payment.succeeded/failed) will update payment status.
    // Set YOCO_ALLOW_POLL_FINALIZE=true to allow finalize via polling.
    'allow_poll_finalize' => env('YOCO_ALLOW_POLL_FINALIZE', false),
];
