<?php

use Illuminate\Support\Facades\Route;
use Rusbelito\Billing\Http\Controllers\WebhookController;

Route::prefix('webhooks/billing')->group(function () {
    Route::post('paymentsway', [WebhookController::class, 'paymentsway'])
        ->name('billing.webhooks.paymentsway');
});