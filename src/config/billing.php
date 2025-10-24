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
    | Tipos de Transacciones
    |--------------------------------------------------------------------------
    */
    'transaction_types' => [
        'one_time' => 'Pago Único',
        'subscription' => 'Suscripción',
        'consumption' => 'Consumo',
        'donation' => 'Donación',
    ],

    'transaction_statuses' => [
        'pending' => 'Pendiente',
        'completed' => 'Completada',
        'failed' => 'Fallida',
        'refunded' => 'Reembolsada',
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
        'invoice_prefix' => env('INVOICE_PREFIX', 'INV'),
    ],

    'invoice_statuses' => [
        'draft' => 'Borrador',
        'issued' => 'Emitida',
        'paid' => 'Pagada',
        'overdue' => 'Vencida',
        'cancelled' => 'Cancelada',
        'refunded' => 'Reembolsada',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de PaymentsWay
    |--------------------------------------------------------------------------
    */
    'paymentsway' => [
        'enabled' => env('PAYMENTSWAY_ENABLED', false),
        'merchant_id' => env('PAYMENTSWAY_MERCHANT_ID'),
        'terminal_id' => env('PAYMENTSWAY_TERMINAL_ID'),
        'form_id' => env('PAYMENTSWAY_FORM_ID'),
        'api_key' => env('PAYMENTSWAY_API_KEY'),
        'webhook_secret' => env('PAYMENTSWAY_WEBHOOK_SECRET'),
        'widget_url' => 'https://merchant.paymentsway.co/assetsWidget',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    */
    'payment_gateways' => [
        'paymentsway' => [
            'name' => 'PaymentsWay',
            'driver' => \Rusbelito\Billing\Services\PaymentsWayService::class,
            'supported_methods' => ['card', 'pse', 'cash'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Payments
    |--------------------------------------------------------------------------
    */
    'automatic_payments' => [
        'enabled' => env('BILLING_AUTO_CHARGE_ENABLED', true),
        'max_payment_retries' => 3,
        'retry_backoff_days' => [1, 2, 4], // Esperar 1, 2, 4 días entre reintentos
        'cancel_subscription_after_max_retries' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'payment_success' => true,
        'payment_failed' => true,
        'subscription_renewed' => true,
        'subscription_cancelled' => true,
        'card_expiring_soon' => true,
        'card_expiring_days' => 30, // Notificar 30 días antes
    ],
];