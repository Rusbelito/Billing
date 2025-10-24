<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingAddress extends Model
{
    protected $fillable = [
        'user_id',
        'legal_name',
        'tax_id',
        'tax_id_type',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'email',
        'phone',
        'fiscal_data',
        'is_default',
    ];

    protected $casts = [
        'fiscal_data' => 'array',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Obtener dirección completa formateada
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Marcar como dirección predeterminada
     */
    public function setAsDefault(): void
    {
        // Desmarcar otras direcciones del usuario
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}