<?php

namespace Rusbelito\Billing\Console\Commands;

use Illuminate\Console\Command;
use Rusbelito\Billing\Services\SubscriptionPaymentService;

class NotifyExpiringCards extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:notify-expiring-cards';

    /**
     * The console command description.
     */
    protected $description = 'Notify users with payment methods expiring soon';

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
        $this->info('🔔 Checking for expiring payment methods...');
        $this->newLine();

        $count = $this->paymentService->notifyExpiringCards();

        if ($count === 0) {
            $this->info('✅ No expiring cards found');
        } else {
            $this->info("✅ Sent {$count} notification(s)");
        }

        return 0;
    }
}