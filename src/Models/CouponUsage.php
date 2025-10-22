<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CouponUsage extends Model
{
    protected $table = 'coupon_usages';

    protected $fillable = [
        'coupon_id',
        'user_id',
        'billable_type',
        'billable_id',
        'discount_amount',
        'original_amount',
        'final_amount',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function billable(): MorphTo
    {
        return $this->morphTo();
    }
}