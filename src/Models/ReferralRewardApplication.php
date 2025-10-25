<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralRewardApplication extends Model
{
    protected $fillable = [
        'referral_reward_id',
        'user_id',
        'invoice_id',
        'transaction_id',
        'applied_amount',
        'original_amount',
        'final_amount',
        'meta',
    ];

    protected $casts = [
        'applied_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'meta' => 'array',
    ];

    // Relaciones
    public function referralReward(): BelongsTo
    {
        return $this->belongsTo(ReferralReward::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}