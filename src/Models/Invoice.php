<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'user_id',
        'billing_address_id',
        'transaction_id',
        'subscription_id',
        'type',
        'issued_at',
        'due_at',
        'paid_at',
        'subtotal',
        'discount',
        'tax',
        'total',
        'coupon_id',
        'status',
        'electronic_invoice_id',
        'cufe',
        'certified_at',
        'electronic_data',
        'meta',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'certified_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'electronic_data' => 'array',
        'meta' => 'array',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(BillingAddress::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // Métodos de estado
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    // Métodos de transición de estado
    public function markAsIssued(): void
    {
        $this->update([
            'status' => 'issued',
            'issued_at' => $this->issued_at ?? now(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function markAsOverdue(): void
    {
        $this->update(['status' => 'overdue']);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function markAsRefunded(): void
    {
        $this->update(['status' => 'refunded']);
    }

    // Facturación electrónica
    public function isCertified(): bool
    {
        return !is_null($this->cufe);
    }

    public function markAsCertified(string $cufe, string $electronicInvoiceId, array $data = []): void
    {
        $this->update([
            'cufe' => $cufe,
            'electronic_invoice_id' => $electronicInvoiceId,
            'certified_at' => now(),
            'electronic_data' => $data,
        ]);
    }
}