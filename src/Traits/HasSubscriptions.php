<?php

namespace Rusbelito\Billing\Traits;

use Rusbelito\Billing\Models\Subscription;
use Rusbelito\Billing\Models\Usage;
use Rusbelito\Billing\Models\CouponUsage;

trait HasSubscriptions
{
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription()
    {
        return $this->subscriptions()
                    ->where('status', 'active')
                    ->latest()
                    ->first();
    }

    public function usages()
    {
        return $this->hasMany(Usage::class);
    }

    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function transactions()
    {
        return $this->hasMany(\Rusbelito\Billing\Models\Transaction::class);
    }

    public function billingAddresses()
    {
        return $this->hasMany(\Rusbelito\Billing\Models\BillingAddress::class);
    }

    public function invoices()
    {
        return $this->hasMany(\Rusbelito\Billing\Models\Invoice::class);
    }

    public function defaultBillingAddress()
    {
        return $this->hasOne(\Rusbelito\Billing\Models\BillingAddress::class)->where('is_default', true);
    }

    public function paymentMethods()
    {
        return $this->hasMany(\Rusbelito\Billing\Models\PaymentMethod::class);
    }

    public function defaultPaymentMethod()
    {
        return $this->hasOne(\Rusbelito\Billing\Models\PaymentMethod::class)->where('is_default', true);
    }

    public function paymentAttempts()
    {
        return $this->hasMany(\Rusbelito\Billing\Models\PaymentAttempt::class);
    }

    // Relaciones de referidos
    public function referralCode()
    {
        return $this->hasOne(\Rusbelito\Billing\Models\ReferralCode::class);
    }

    public function referrals()
    {
        return $this->hasMany(\Rusbelito\Billing\Models\Referral::class, 'referrer_id');
    }

    public function referredBy()
    {
        return $this->hasOne(\Rusbelito\Billing\Models\Referral::class, 'referred_id');
    }

    public function referralRewards()
    {
        return $this->hasMany(\Rusbelito\Billing\Models\ReferralReward::class);
    }

    public function activeReferralRewards()
    {
        return $this->hasMany(\Rusbelito\Billing\Models\ReferralReward::class)
            ->whereIn('status', ['pending', 'active']);
    }
}