<?php

namespace Rusbelito\Billing\Console\Commands;

use Illuminate\Console\Command;
use Rusbelito\Billing\Models\PaymentAttempt;
use Rusbelito\Billing\Services\SubscriptionPaymentService;

class RetryFailedPayments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:retry-failed-payments
                            {--days=3 : Only retry payments failed within X days}
                            {--max-retries=3 : Maximum retry attempts}
                            {--dry-run : Simulate without processing}';

    /**
     * The console command description.
     */
    protected $description = 'Retry failed payment attempts for subscriptions';

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
        $this->info('🔄 Starting failed payment retries...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $maxRetries = (int) $this->option('max-retries');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No retries will be processed');
            $this->newLine();
        }

        // Obtener intentos fallidos
        $failedAttempts = PaymentAttempt::where('status', 'failed')
            ->where('retry_count', '<', $maxRetries)
            ->whereNotNull('subscription_id')
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['user', 'subscription.plan', 'paymentMethod'])
            ->get();

        if ($failedAttempts->isEmpty()) {
            $this->info('✅ No failed payments to retry');
            return 0;
        }

        $this->info("Found {$failedAttempts->count()} failed payment(s) to retry");
        $this->newLine();

        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $cancelledSubscriptions = 0;

        $progressBar = $this->output->createProgressBar($failedAttempts->count());
        $progressBar->start();

        foreach ($failedAttempts as $attempt) {
            $user = $attempt->user;
            $subscription = $attempt->subscription;

            if (!$subscription || !$subscription->isActive()) {
                $this->newLine();
                $this->warn("⚠️  Skipped: Subscription not active");
                $skippedCount++;
                $progressBar->advance();
                continue;
            }

            if ($dryRun) {
                $this->line("Would retry: {$user->email} - Attempt #{$attempt->id} (Retry {$attempt->retry_count}/{$maxRetries})");
                $progressBar->advance();
                continue;
            }

            // Aplicar backoff exponencial
            $waitHours = pow(2, $attempt->retry_count) * 24; // 1 día, 2 días, 4 días, etc.
            $nextRetryAt = $attempt->created_at->addHours($waitHours);

            if (now()->lt($nextRetryAt)) {
                $this->newLine();
                $this->warn("⚠️  Skipped: {$user->email} - Too soon to retry (next attempt at {$nextRetryAt->format('Y-m-d H:i')})");
                $skippedCount++;
                $progressBar->advance();
                continue;
            }

            // Intentar reintento
            $result = $this->paymentService->retryFailedPayment($attempt);

            $this->newLine();
            if ($result['success']) {
                $this->info("✅ Success: {$user->email} - Payment retry successful");
                $successCount++;
            } else {
                $this->error("❌ Failed: {$user->email} - {$result['message']}");
                $failedCount++;

                // Si alcanzó el máximo de reintentos, cancelar suscripción
                if ($result['error'] === 'max_retries_reached') {
                    $this->paymentService->cancelSubscriptionDueToPaymentFailure(
                        $subscription,
                        'Maximum payment retry attempts reached'
                    );
                    $this->warn("⚠️  Subscription cancelled due to payment failures");
                    $cancelledSubscriptions++;
                }
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
                ['Subscriptions Cancelled', $cancelledSubscriptions],
                ['Total', $failedAttempts->count()],
            ]
        );

        return $failedCount > 0 ? 1 : 0;
    }
}