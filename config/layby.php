<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Layby Configuration
    |--------------------------------------------------------------------------
    |
    | Configure layby/layaway settings for your store
    |
    */

    'enabled' => env('LAYBY_ENABLED', true),

    // Minimum product price in USD for layby eligibility
    'min_amount_usd' => env('LAYBY_MIN_AMOUNT', 300),

    // Deposit percentage required upfront
    'deposit_percentage' => 20,

    // Available payment durations in months
    'durations' => [3, 6, 12],

    // Terms and Conditions
    'terms_and_conditions' => [
        'Eligibility' => 'Layby is available for products over $300 USD equivalent. You must be a registered user and logged in to apply.',

        'Deposit Requirement' => 'A 20% deposit of the product price is required upfront. The deposit is non-refundable once the layby is approved.',

        'Payment Plans' => 'You can choose between 3, 6, or 12-month payment plans. Equal monthly payments will be due after the initial deposit.',

        'Approval Process' => 'All layby applications are subject to approval. We will review your application within 1-2 business days and notify you via email.',

        'Payments' => 'Monthly payments must be made on or before the due date. Late payments may result in additional fees or cancellation of the layby.',

        'Product Delivery' => 'The product will only be delivered once the full payment has been completed. The product remains the property of ' . config('app.name') . ' until fully paid.',

        'Cancellation' => 'You may cancel your layby at any time. However, the deposit is non-refundable. Any additional payments made will be refunded minus a processing fee.',

        'No Interest or Hidden Fees' => 'There are no interest charges or hidden fees on layby purchases. You only pay the product price split into installments.',

        'Payment Methods' => 'We accept various payment methods including bank transfer, card payments, and other supported payment gateways.',

        'Default' => 'If payments are not made within the agreed timeframe, we reserve the right to cancel the layby and forfeit the deposit and any payments made.',
    ],

    // Email notifications
    'emails' => [
        'application_received' => true,
        'application_approved' => true,
        'application_rejected' => true,
        'payment_received' => true,
        'payment_reminder' => true,
        'layby_completed' => true,
    ],

    // Payment reminder settings (days before due date)
    'payment_reminder_days' => [7, 3, 1],

    // Late payment grace period (days)
    'late_payment_grace_days' => 5,

    // Processing fee for cancellation (percentage)
    'cancellation_fee_percentage' => 5,
];

