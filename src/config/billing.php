<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración general del sistema de facturación
    |
    */

    'billing_modes' => [
        'subscription' => 'Suscripción',
        'consumption' => 'Consumo',
        'mixed' => 'Mixto',
        'donation' => 'Donación',
    ],

    'subscription_statuses' => [
        'active' => 'Activa',
        'cancelled' => 'Cancelada',
        'paused' => 'Pausada',
    ],

    'plan_types' => [
        'subscription' => 'Suscripción',
        'donation' => 'Donación',
        'consumption' => 'Consumo',
        'mixed' => 'Mixto',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Cupones
    |--------------------------------------------------------------------------
    */
    'coupons' => [
        'enabled' => true,
        'discount_types' => ['percentage', 'fixed'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Facturación
    |--------------------------------------------------------------------------
    */
    'invoicing' => [
        'enabled' => true,
        'auto_generate' => true,
        'generate_pdf' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de PaymentsWay
    |--------------------------------------------------------------------------
    */
    'paymentsway' => [
        'enabled' => false,
        'api_key' => env('PAYMENTSWAY_API_KEY'),
        'secret_key' => env('PAYMENTSWAY_SECRET_KEY'),
        'webhook_secret' => env('PAYMENTSWAY_WEBHOOK_SECRET'),
    ],
];