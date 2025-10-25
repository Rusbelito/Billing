<?php

namespace Rusbelito\Billing\Livewire;

use Livewire\Component;
use Rusbelito\Billing\Models\Subscription;
use Rusbelito\Billing\Models\Invoice;
use Rusbelito\Billing\Models\PaymentAttempt;
use Rusbelito\Billing\Models\Referral;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public $metrics = [];
    public $period = 'month'; // month, week, year

    public function mount()
    {
        $this->loadMetrics();
    }

    public function updatedPeriod()
    {
        $this->loadMetrics();
    }

    protected function loadMetrics()
    {
        $startDate = match($this->period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        $this->metrics = [
            // Ingresos
            'total_revenue' => $this->getTotalRevenue($startDate),
            'revenue_change' => $this->getRevenueChange($startDate),
            
            // MRR (Monthly Recurring Revenue)
            'mrr' => $this->getMRR(),
            'mrr_change' => $this->getMRRChange(),
            
            // Suscripciones
            'active_subscriptions' => $this->getActiveSubscriptions(),
            'subscriptions_change' => $this->getSubscriptionsChange($startDate),
            
            // Usuarios
            'total_users' => $this->getTotalUsers(),
            'new_users' => $this->getNewUsers($startDate),
            
            // Tasa de éxito de pagos
            'payment_success_rate' => $this->getPaymentSuccessRate($startDate),
            
            // Churn rate
            'churn_rate' => $this->getChurnRate(),
            
            // Referidos
            'total_referrals' => $this->getTotalReferrals(),
            'referral_conversion_rate' => $this->getReferralConversionRate(),
            
            // Últimas transacciones
            'recent_transactions' => $this->getRecentTransactions(),
            
            // Gráfico de ingresos
            'revenue_chart' => $this->getRevenueChartData($startDate),
        ];
    }

    protected function getTotalRevenue($startDate)
    {
        return PaymentAttempt::where('status', 'success')
            ->where('completed_at', '>=', $startDate)
            ->sum('amount');
    }

    protected function getRevenueChange($startDate)
    {
        $current = $this->getTotalRevenue($startDate);
        
        $previousStart = match($this->period) {
            'week' => now()->subWeeks(2),
            'month' => now()->subMonths(2),
            'year' => now()->subYears(2),
            default => now()->subMonths(2),
        };
        
        $previous = PaymentAttempt::where('status', 'success')
            ->whereBetween('completed_at', [$previousStart, $startDate])
            ->sum('amount');

        if ($previous == 0) return 0;
        
        return round((($current - $previous) / $previous) * 100, 2);
    }

    protected function getMRR()
    {
        return Subscription::where('status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');
    }

    protected function getMRRChange()
    {
        $currentMRR = $this->getMRR();
        
        $lastMonthMRR = Subscription::where('status', 'active')
            ->where('created_at', '<', now()->subMonth())
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->sum('plans.price');

        if ($lastMonthMRR == 0) return 0;
        
        return round((($currentMRR - $lastMonthMRR) / $lastMonthMRR) * 100, 2);
    }

    protected function getActiveSubscriptions()
    {
        return Subscription::where('status', 'active')->count();
    }

    protected function getSubscriptionsChange($startDate)
    {
        $new = Subscription::where('created_at', '>=', $startDate)->count();
        $cancelled = Subscription::where('status', 'cancelled')
            ->where('updated_at', '>=', $startDate)
            ->count();
        
        return $new - $cancelled;
    }

    protected function getTotalUsers()
    {
        return \App\Models\User::count();
    }

    protected function getNewUsers($startDate)
    {
        return \App\Models\User::where('created_at', '>=', $startDate)->count();
    }

    protected function getPaymentSuccessRate($startDate)
    {
        $total = PaymentAttempt::where('attempted_at', '>=', $startDate)->count();
        if ($total == 0) return 0;
        
        $successful = PaymentAttempt::where('status', 'success')
            ->where('completed_at', '>=', $startDate)
            ->count();
        
        return round(($successful / $total) * 100, 2);
    }

    protected function getChurnRate()
    {
        $startOfMonth = now()->startOfMonth();
        $activeStart = Subscription::where('status', 'active')
            ->where('created_at', '<', $startOfMonth)
            ->count();
        
        if ($activeStart == 0) return 0;
        
        $cancelled = Subscription::where('status', 'cancelled')
            ->where('updated_at', '>=', $startOfMonth)
            ->count();
        
        return round(($cancelled / $activeStart) * 100, 2);
    }

    protected function getTotalReferrals()
    {
        return Referral::count();
    }

    protected function getReferralConversionRate()
    {
        $total = Referral::count();
        if ($total == 0) return 0;
        
        $converted = Referral::whereIn('status', ['converted', 'active'])->count();
        
        return round(($converted / $total) * 100, 2);
    }

    protected function getRecentTransactions()
    {
        return PaymentAttempt::with(['user', 'invoice'])
            ->latest()
            ->take(10)
            ->get();
    }

    protected function getRevenueChartData($startDate)
    {
        $data = PaymentAttempt::where('status', 'success')
            ->where('completed_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(completed_at) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d')),
            'values' => $data->pluck('total'),
        ];
    }

    public function render()
    {
        return view('billing::livewire.dashboard');
    }
}