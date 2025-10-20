<?php

namespace Rusbelito\Billing\Providers;

use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Rusbelito\Billing\Services\BillingService::class, function () {
            return new \Rusbelito\Billing\Services\BillingService();
        });
    }

    public function boot(): void
    {
        // Cargar migraciones desde la ruta correcta
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Publicar configuración
        $this->publishes([
            __DIR__ . '/../config/billing.php' => config_path('billing.php'),
        ], 'billing-config');
    }
}