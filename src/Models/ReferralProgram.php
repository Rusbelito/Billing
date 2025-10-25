<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralProgram extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'reward_type',
        'reward_value',
        'reward_currency',
        'reward_duration_months',
        'reward_duration_cycles',
        'upgrade_plan_id',
        'trigger_event',
        'trigger_value',
        'max_referrals_per_user',
        'max_total_uses',
        'current_total_uses',
        'applicable_plans',
        'starts_at',
        'expires_at',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'reward_value' => 'decimal:2',
        'applicable_plans' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    // Relaciones
    public function upgradePlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'upgrade_plan_id');
    }

    public function referralCodes(): HasMany
    {
        return $this->hasMany(ReferralCode::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class);
    }

    // Métodos útiles
    
    /**
     * Verificar si está activo
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->isBefore($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && now()->isAfter($this->expires_at)) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si alcanzó el límite máximo
     */
    public function hasReachedLimit(): bool
    {
        if (!$this->max_total_uses) {
            return false;
        }

        return $this->current_total_uses >= $this->max_total_uses;
    }

    /**
     * Incrementar contador de usos
     */
    public function incrementUsage(): void
    {
        $this->increment('current_total_uses');
    }

    /**
     * Verificar si aplica a un plan
     */
    public function isApplicableToPlan(?int $planId = null): bool
    {
        if (empty($this->applicable_plans)) {
            return true;
        }

        return in_array($planId, $this->applicable_plans);
    }

    /**
     * Obtener programa predeterminado
     */
    public static function getDefault(): ?self
    {
        return self::where('is_active', true)
                   ->orderBy('created_at')
                   ->first();
    }
}