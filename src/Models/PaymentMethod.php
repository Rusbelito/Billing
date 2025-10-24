<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'payment_gateway_id',
        'type',
        'gateway_token',
        'gateway_customer_id',
        'card_brand',
        'card_last_four',
        'card_exp_month',
        'card_exp_year',
        'bank_name',
        'bank_code',
        'is_default',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    // Métodos útiles

    /**
     * Marcar como método predeterminado
     */
    public function setAsDefault(): void
    {
        // Desmarcar otros métodos del usuario
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Verificar si la tarjeta está expirada
     */
    public function isExpired(): bool
    {
        if ($this->type !== 'card') {
            return false;
        }

        $expYear = (int) $this->card_exp_year;
        $expMonth = (int) $this->card_exp_month;

        $now = now();
        $currentYear = $now->year;
        $currentMonth = $now->month;

        if ($expYear < $currentYear) {
            return true;
        }

        if ($expYear === $currentYear && $expMonth < $currentMonth) {
            return true;
        }

        return false;
    }

    /**
     * Obtener nombre amigable del método
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'card') {
            return "{$this->card_brand} •••• {$this->card_last_four}";
        }

        if ($this->type === 'bank_account') {
            return "{$this->bank_name}";
        }

        return ucfirst($this->type);
    }

    /**
     * Verificar si está próximo a expirar (dentro de 30 días)
     */
    public function isExpiringSoon(): bool
    {
        if ($this->type !== 'card') {
            return false;
        }

        $expYear = (int) $this->card_exp_year;
        $expMonth = (int) $this->card_exp_month;

        $expirationDate = now()->setYear($expYear)->setMonth($expMonth)->endOfMonth();

        return $expirationDate->diffInDays(now()) <= 30;
    }
}