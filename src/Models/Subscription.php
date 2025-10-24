<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Subscription extends Model
{

    protected $fillable = [
        'user_id',
        'plan_id',
        'billing_mode',
        'status',
        'starts_at',
        'ends_at',
        'meta',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Renovar suscripción
     */
    public function renew(): void
    {
        $this->update([
            'starts_at' => $this->ends_at ?? now(),
            'ends_at' => ($this->ends_at ?? now())->addMonth(),
        ]);
    }

    /**
     * Cancelar suscripción
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'meta' => array_merge($this->meta ?? [], [
                'cancellation_reason' => $reason,
                'cancelled_at' => now()->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Pausar suscripción
     */
    public function pause(): void
    {
        $this->update([
            'status' => 'paused',
            'meta' => array_merge($this->meta ?? [], [
                'paused_at' => now()->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Reactivar suscripción
     */
    public function resume(): void
    {
        $this->update([
            'status' => 'active',
            'meta' => array_merge($this->meta ?? [], [
                'resumed_at' => now()->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Verificar si está activa
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verificar si está cancelada
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Verificar si está pausada
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Verificar si vence hoy o ya venció
     */
    public function isDue(): bool
    {
        if (!$this->ends_at) {
            return false;
        }

        return $this->ends_at->lte(now());
    }

    /**
     * Días restantes hasta vencimiento
     */
    public function daysUntilDue(): ?int
    {
        if (!$this->ends_at) {
            return null;
        }

        return now()->diffInDays($this->ends_at, false);
    }
}

