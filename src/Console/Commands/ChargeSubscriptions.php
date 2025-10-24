<?php

namespace Rusbelito\Billing\Console\Commands;

use Illuminate\Console\Command;
use Rusbelito\Billing\Services\SubscriptionPaymentService;

class ChargeSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:charge-subscriptions
                            {--dry-run : Simulate without charging}
                            {--subscription= : Charge specific subscription ID}';

    /**
     * The console command description.
     */
    protected $description = 'Charge active subscriptions that are due for payment';

    protected SubscriptionPaymentService $paymentService;

    public function __construct(SubscriptionPaymentService $paymentService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting subscription charges...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $specificSubscriptionId = $this->option('subscription');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No charges will be processed');
            $this->newLine();
        }

        // Obtener suscripciones a cobrar
        if ($specificSubscriptionId) {
            $subscription = \Rusbelito\Billing\Models\Subscription::find($specificSubscriptionId);
            if (!$subscription) {
                $this->error("Subscription {$specificSubscriptionId} not found");
                return 1;
            }
            $subscriptions = collect([$subscription]);
        } else {
            $subscriptions = $this->paymentService->getSubscriptionsDueForPayment();
        }

        if ($subscriptions->isEmpty()) {
            $this->info('✅ No subscriptions due for payment');
            return 0;
        }

        $this->info("Found {$subscriptions->count()} subscription(s) to charge");
        $this->newLine();

        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        $progressBar = $this->output->createProgressBar($subscriptions->count());
        $progressBar->start();

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;
            $plan = $subscription->plan;

            if ($dryRun) {
                $this->line("Would charge: {$user->email} - {$plan->name} (${$plan->price})");
                $progressBar->advance();
                continue;
            }

            // Verificar método de pago
            if (!$user->defaultPaymentMethod) {
                $this->newLine();
                $this->warn("⚠️  Skipped: {$user->email} - No payment method");
                $skippedCount++;
                $progressBar->advance();
                continue;
            }

            // Intentar cobro
            $result = $this->paymentService->chargeSubscription($subscription);

            $this->newLine();
            if ($result['success']) {
                $this->info("✅ Success: {$user->email} - {$plan->name} (${$result['invoice']->total})");
                $successCount++;
            } else {
                $this->error("❌ Failed: {$user->email} - {$result['message']}");
                $failedCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Resumen
        $this->info('📊 Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $successCount],
                ['Failed', $failedCount],
                ['Skipped', $skippedCount],
                ['Total', $subscriptions->count()],
            ]
        );

        return $failedCount > 0 ? 1 : 0;
    }
}