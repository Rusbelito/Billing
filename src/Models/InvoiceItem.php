<?php

namespace Rusbelito\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'details',
        'itemable_type',
        'itemable_id',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'tax_amount',
        'subtotal',
        'total',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'meta' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Calcular totales automáticamente
     */
    public static function calculateTotals(float $quantity, float $unitPrice, float $discount = 0, float $taxRate = 0): array
    {
        $subtotal = $quantity * $unitPrice;
        $subtotalAfterDiscount = $subtotal - $discount;
        $taxAmount = ($subtotalAfterDiscount * $taxRate) / 100;
        $total = $subtotalAfterDiscount + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ];
    }
}