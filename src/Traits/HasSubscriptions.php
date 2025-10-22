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
}