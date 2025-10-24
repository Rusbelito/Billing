<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'driver',
        'config',
        'is_active',
        'is_default',
        'supported_methods',
        'meta',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'supported_methods' => 'array',
        'meta' => 'array',
    ];

    // Relaciones
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    // Métodos útiles
    
    /**
     * Obtener configuración desencriptada
     */
    public function getConfig(string $key = null)
    {
        $config = $this->config;

        if ($key) {
            return $config[$key] ?? null;
        }

        return $config;
    }

    /**
     * Establecer configuración encriptada
     */
    public function setConfig(array $config): void
    {
        $this->update(['config' => $config]);
    }

    /**
     * Marcar como predeterminado
     */
    public function setAsDefault(): void
    {
        // Desmarcar otros gateways
        self::where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Verificar si soporta un método de pago
     */
    public function supportsMethod(string $method): bool
    {
        if (empty($this->supported_methods)) {
            return true; // Si no hay restricción, soporta todos
        }

        return in_array($method, $this->supported_methods);
    }

    /**
     * Obtener gateway predeterminado
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)
                   ->where('is_active', true)
                   ->first();
    }
}