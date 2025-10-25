<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReferralCode extends Model
{
    protected $fillable = [
        'user_id',
        'referral_program_id',
        'code',
        'total_referrals',
        'successful_referrals',
        'clicks',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function referralProgram(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    // Métodos útiles

    /**
     * Generar código único
     */
    public static function generateUniqueCode(?string $baseName = null): string
    {
        do {
            if ($baseName) {
                // Usar nombre del usuario
                $code = strtoupper(Str::slug($baseName, '')) . rand(100, 999);
            } else {
                // Código aleatorio
                $code = strtoupper(Str::random(8));
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Incrementar clicks
     */
    public function incrementClicks(): void
    {
        $this->increment('clicks');
    }

    /**
     * Incrementar total de referidos
     */
    public function incrementReferrals(): void
    {
        $this->increment('total_referrals');
    }

    /**
     * Incrementar referidos exitosos
     */
    public function incrementSuccessfulReferrals(): void
    {
        $this->increment('successful_referrals');
    }

    /**
     * Tasa de conversión
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->total_referrals === 0) {
            return 0;
        }

        return ($this->successful_referrals / $this->total_referrals) * 100;
    }

    /**
     * Obtener URL de referido
     */
    public function getReferralUrlAttribute(): string
    {
        return config('app.url') . '/register?ref=' . $this->code;
    }
}