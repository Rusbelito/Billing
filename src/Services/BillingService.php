<?php

namespace Rusbelito\Billing\Services;

use App\Models\User;
use Rusbelito\Billing\Models\Usage;
use Rusbelito\Billing\Models\UsagePrice;
use Carbon\Carbon;

class BillingService
{
    protected CouponService $couponService;

    public function __construct(CouponService $couponService = null)
    {
        $this->couponService = $couponService ?? new CouponService();
    }

    public function calculateUsageTotal(User $user, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $planId = optional($user->currentSubscription()?->plan)->id;

        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now()->endOfMonth();

        $usages = $user->usages()
            ->whereBetween('recorded_at', [$from, $to])
            ->get()
            ->groupBy('action_key');

        $total = 0;

        foreach ($usages as $action => $usageGroup) {
            $totalQuantity = $usageGroup->sum('quantity');

            $price = UsagePrice::where('action_key', $action)
                ->where(function ($query) use ($planId) {
                    $query->where('plan_id', $planId)
                          ->orWhereNull('plan_id');
                })
                ->orderByRaw('plan_id IS NULL')
                ->first();

            if (! $price) {
                continue;
            }

            $units = (int) floor($totalQuantity / $price->unit_count);
            $subtotal = $units * $price->unit_price;

            $total += $subtotal;
        }

        return round($total, 6);
    }

    public function calculateMonthlyTotal(User $user, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now()->endOfMonth();

        $subscription = $user->currentSubscription();
        $plan = $subscription?->plan;

        $planPrice = $plan?->price ?? 0;
        $billingMode = $subscription?->billing_mode ?? 'subscription';

        $consumption = 0;

        if (in_array($billingMode, ['consumption', 'mixed'])) {
            $consumption = $this->calculateUsageTotal($user, $from, $to);
        }

        $total = 0;

        if ($billingMode === 'subscription') {
            $total = $planPrice;
        } elseif ($billingMode === 'consumption') {
            $total = $consumption;
        } elseif ($billingMode === 'mixed') {
            $total = $planPrice + $consumption;
        }

        return [
            'plan_price' => $planPrice,
            'consumption_total' => $consumption,
            'total' => round($total, 6),
            'billing_mode' => $billingMode,
            'period' => [
                'from' => $from,
                'to' => $to,
            ]
        ];
    }

    /**
     * Calcular total mensual CON CUPÓN
     */
    public function calculateMonthlyTotalWithCoupon(
        User $user,
        string $couponCode,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): array {
        $billing = $this->calculateMonthlyTotal($user, $from, $to);
        
        $subscription = $user->currentSubscription();
        $plan = $subscription?->plan;

        try {
            $couponResult = $this->couponService->apply(
                $couponCode,
                $user,
                $billing['total'],
                $plan?->id,
                $subscription?->billing_mode,
                Subscription::class,
                $subscription?->id
            );

            return [
                'plan_price' => $billing['plan_price'],
                'consumption_total' => $billing['consumption_total'],
                'subtotal' => $billing['total'],
                'coupon' => [
                    'code' => $couponResult['coupon_code'],
                    'discount_type' => $couponResult['discount_type'],
                    'discount_value' => $couponResult['discount_value'],
                    'discount_amount' => $couponResult['discount_amount'],
                ],
                'total' => $couponResult['final_amount'],
                'billing_mode' => $billing['billing_mode'],
                'period' => $billing['period'],
            ];
        } catch (\Rusbelito\Billing\Exceptions\CouponException $e) {
            throw $e;
        }
    }
}