<?php

namespace Rusbelito\Billing\Providers;

use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Rusbelito\Billing\Services\CouponService::class, function () {
            return new \Rusbelito\Billing\Services\CouponService();
        });

        $this->app->singleton(\Rusbelito\Billing\Services\BillingService::class, function ($app) {
            return new \Rusbelito\Billing\Services\BillingService($app->make(\Rusbelito\Billing\Services\CouponService::class));
        });

        $this->app->singleton(\Rusbelito\Billing\Services\TransactionService::class, function ($app) {
            return new \Rusbelito\Billing\Services\TransactionService($app->make(\Rusbelito\Billing\Services\CouponService::class));
        });

        $this->app->singleton(\Rusbelito\Billing\Services\InvoiceService::class, function () {
            return new \Rusbelito\Billing\Services\InvoiceService();
        });

        $this->app->singleton(\Rusbelito\Billing\Services\PaymentsWayService::class, function () {
            return new \Rusbelito\Billing\Services\PaymentsWayService();
        });

        $this->app->singleton(\Rusbelito\Billing\Services\WebhookService::class, function () {
            return new \Rusbelito\Billing\Services\WebhookService();
        });

        $this->app->singleton(\Rusbelito\Billing\Services\SubscriptionPaymentService::class, function ($app) {
            return new \Rusbelito\Billing\Services\SubscriptionPaymentService(
                $app->make(\Rusbelito\Billing\Services\InvoiceService::class),
                $app->make(\Rusbelito\Billing\Services\PaymentsWayService::class)
            );
        });

        $this->app->singleton(\Rusbelito\Billing\Services\ReferralService::class, function () {
            return new \Rusbelito\Billing\Services\ReferralService();
        });
    }

    public function boot(): void
    {
        // Cargar migraciones desde la ruta correcta
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Cargar rutas
        $this->loadRoutesFrom(__DIR__ . '/../routes/webhooks.php');
        
        // Registrar comandos
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Rusbelito\Billing\Console\Commands\ChargeSubscriptions::class,
                \Rusbelito\Billing\Console\Commands\RetryFailedPayments::class,
                \Rusbelito\Billing\Console\Commands\NotifyExpiringCards::class,
            ]);
        }
        
        // Publicar configuración
        $this->publishes([
            __DIR__ . '/../config/billing.php' => config_path('billing.php'),
        ], 'billing-config');
    }
}