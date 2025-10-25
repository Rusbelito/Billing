<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralReward extends Model
{
    protected $fillable = [
        'user_id',
        'referral_id',
        'referral_program_id',
        'reward_type',
        'reward_value',
        'remaining_value',
        'duration_months',
        'duration_cycles',
        'cycles_applied',
        'status',
        'earned_at',
        'activated_at',
        'expires_at',
        'completed_at',
        'upgrade_plan_id',
        'meta',
    ];

    protected $casts = [
        'reward_value' => 'decimal:2',
        'remaining_value' => 'decimal:2',
        'earned_at' => 'datetime',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function referralProgram(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class);
    }

    public function upgradePlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'upgrade_plan_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ReferralRewardApplication::class);
    }

    // Métodos de estado
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    // Métodos de transición
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Aplicar descuento y actualizar valores
     */
    public function applyDiscount(float $amount): float
    {
        if ($this->reward_type === 'fixed_discount') {
            $discount = min($this->reward_value, $amount);
        } else if ($this->reward_type === 'percentage_revenue') {
            $discount = ($amount * $this->reward_value) / 100;
        } else if ($this->reward_type === 'account_credit') {
            $discount = min($this->remaining_value, $amount);
            $this->decrement('remaining_value', $discount);
        } else {
            $discount = 0;
        }

        // Incrementar ciclos aplicados
        $this->increment('cycles_applied');

        // Verificar si se completó
        if ($this->duration_cycles && $this->cycles_applied >= $this->duration_cycles) {
            $this->complete();
        }

        if ($this->reward_type === 'account_credit' && $this->remaining_value <= 0) {
            $this->complete();
        }

        return round($discount, 2);
    }

    /**
     * Verificar si puede aplicarse
     */
    public function canApply(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->expires_at && now()->isAfter($this->expires_at)) {
            $this->expire();
            return false;
        }

        if ($this->duration_cycles && $this->cycles_applied >= $this->duration_cycles) {
            return false;
        }

        if ($this->reward_type === 'account_credit' && $this->remaining_value <= 0) {
            return false;
        }

        return true;
    }
}