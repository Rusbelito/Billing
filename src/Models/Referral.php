<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referral_code_id',
        'referral_program_id',
        'status',
        'registered_at',
        'subscribed_at',
        'converted_at',
        'churned_at',
        'total_revenue_generated',
        'months_active',
        'meta',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'converted_at' => 'datetime',
        'churned_at' => 'datetime',
        'total_revenue_generated' => 'decimal:2',
        'meta' => 'array',
    ];

    // Relaciones
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'referred_id');
    }

    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function referralProgram(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class);
    }

    // Métodos de estado
    public function isRegistered(): bool
    {
        return $this->status === 'registered';
    }

    public function isSubscribed(): bool
    {
        return $this->status === 'subscribed';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isChurned(): bool
    {
        return $this->status === 'churned';
    }

    // Métodos de transición
    public function markAsSubscribed(): void
    {
        $this->update([
            'status' => 'subscribed',
            'subscribed_at' => now(),
        ]);
    }

    public function markAsConverted(): void
    {
        $this->update([
            'status' => 'converted',
            'converted_at' => now(),
        ]);
    }

    public function markAsActive(): void
    {
        $this->update(['status' => 'active']);
    }

    public function markAsChurned(): void
    {
        $this->update([
            'status' => 'churned',
            'churned_at' => now(),
        ]);
    }

    /**
     * Agregar ingresos generados
     */
    public function addRevenue(float $amount): void
    {
        $this->increment('total_revenue_generated', $amount);
    }

    /**
     * Incrementar meses activos
     */
    public function incrementMonthsActive(): void
    {
        $this->increment('months_active');
    }
}