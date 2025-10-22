<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $table = 'billing_coupons';

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'starts_at',
        'expires_at',
        'minimum_amount',
        'usage_type',
        'max_uses',
        'current_uses',
        'is_active',
        'applicable_plans',
        'applicable_billing_modes',
        'meta',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'applicable_plans' => 'array',
        'applicable_billing_modes' => 'array',
        'meta' => 'array',
    ];

    // Relaciones
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    // Métodos útiles
    /**
     * ¿Está activo y no expirado?
     */
    public function isValid(): bool
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
     * ¿Alcanzó el límite de uso?
     */
    public function hasReachedLimit(): bool
    {
        if ($this->usage_type === 'single') {
            return $this->current_uses >= 1;
        }

        if ($this->usage_type === 'limited') {
            return $this->current_uses >= $this->max_uses;
        }

        // 'reusable' no tiene límite
        return false;
    }

    /**
     * ¿Es aplicable a este plan?
     */
    public function isApplicableToPlan(?int $planId = null): bool
    {
        // Si no hay restricción de planes, aplica a todos
        if (empty($this->applicable_plans)) {
            return true;
        }

        // Si hay restricción, verifica si el plan está en la lista
        return in_array($planId, $this->applicable_plans);
    }

    /**
     * ¿Es aplicable a este modo de facturación?
     */
    public function isApplicableToMode(?string $billingMode = null): bool
    {
        // Si no hay restricción, aplica a todos
        if (empty($this->applicable_billing_modes)) {
            return true;
        }

        // Si hay restricción, verifica si el modo está en la lista
        return in_array($billingMode, $this->applicable_billing_modes);
    }

    /**
     * Incrementar contador de uso
     */
    public function incrementUse(): void
    {
        $this->increment('current_uses');
    }

    /**
     * Calcular descuento
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === 'percentage') {
            return ($amount * $this->discount_value) / 100;
        }

        // fixed
        return min($this->discount_value, $amount);
    }
}