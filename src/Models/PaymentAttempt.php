<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'payment_gateway_id',
        'payment_method_id',
        'invoice_id',
        'transaction_id',
        'subscription_id',
        'amount',
        'currency',
        'gateway_transaction_id',
        'gateway_order_number',
        'status',
        'gateway_status_code',
        'gateway_message',
        'gateway_response',
        'ip_address',
        'error_message',
        'retry_count',
        'attempted_at',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'attempted_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    // Métodos de estado
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    // Métodos de transición
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'attempted_at' => now(),
        ]);
    }

    public function markAsSuccess(string $gatewayTransactionId, array $gatewayResponse = []): void
    {
        $this->update([
            'status' => 'success',
            'gateway_transaction_id' => $gatewayTransactionId,
            'gateway_response' => $gatewayResponse,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage, array $gatewayResponse = []): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'gateway_response' => $gatewayResponse,
            'completed_at' => now(),
        ]);
    }

    /**
     * Incrementar contador de reintentos
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Generar order_number único
     */
    public static function generateOrderNumber(): string
    {
        return 'ORD-' . now()->format('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }
}