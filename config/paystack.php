<?php

return [
    /**
     * Public Key From Paystack Dashboard
     */
    'publicKey' => env('PAYSTACK_PUBLIC_KEY'),

    /**
     * Secret Key From Paystack Dashboard
     */
    'secretKey' => env('PAYSTACK_SECRET_KEY'),

    /**
     * Paystack Payment URL
     */
    'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),

    /**
     * Optional email to merchant
     */
    'merchantEmail' => env('MERCHANT_EMAIL'),

    /**
     * Webhook URL
     */
    'webhookUrl' => env('PAYSTACK_WEBHOOK_URL'),

    /**
     * Currency
     */
    'currency' => env('PAYSTACK_CURRENCY', 'NGN'),

    /**
     * Transaction Charges
     */
    'charges' => [
        'percentage' => env('PAYSTACK_CHARGE_PERCENTAGE', 1.5),
        'threshold' => env('PAYSTACK_CHARGE_THRESHOLD', 2500),
        'cap' => env('PAYSTACK_CHARGE_CAP', 2000),
    ],
];
