<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway used by the application.
    |
    */
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'paystack'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Configuration for supported payment gateways.
    |
    */
    'gateways' => [
        'paystack' => [
            'name' => 'PayStack',
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
            'currency' => 'NGN',
            'test_mode' => env('PAYSTACK_TEST_MODE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | General payment configuration settings.
    |
    */
    'currency' => env('PAYMENT_CURRENCY', 'GHS'),
    'currency_symbol' => env('PAYMENT_CURRENCY_SYMBOL', 'GH₵'),

    /*
    |--------------------------------------------------------------------------
    | Payment Timeout
    |--------------------------------------------------------------------------
    |
    | How long to wait for payment completion (in minutes).
    |
    */
    'timeout' => env('PAYMENT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payment webhook handling.
    |
    */
    'webhook' => [
        'verify_signature' => env('PAYMENT_WEBHOOK_VERIFY_SIGNATURE', true),
        'allowed_ips' => [
            '52.31.139.75',
            '52.49.173.169',
            '52.214.14.220',
        ], // PayStack webhook IPs
    ],
];
