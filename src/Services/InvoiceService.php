<?php

namespace Rusbelito\Billing\Services;

use App\Models\User;
use Rusbelito\Billing\Models\Invoice;
use Rusbelito\Billing\Models\InvoiceItem;
use Rusbelito\Billing\Models\Transaction;
use Rusbelito\Billing\Models\Subscription;
use Rusbelito\Billing\Models\BillingAddress;
use Carbon\Carbon;

class InvoiceService
{
    /**
     * Generar número de factura
     */
    protected function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $prefix = config('billing.invoice_prefix', 'INV');
        
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $number);
    }

    /**
     * Crear factura desde una transacción one-time
     */
    public function createFromTransaction(Transaction $transaction): Invoice
    {
        $user = $transaction->user;
        $billingAddress = $user->billingAddresses()->where('is_default', true)->first();

        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'user_id' => $user->id,
            'billing_address_id' => $billingAddress?->id,
            'transaction_id' => $transaction->id,
            'type' => 'one_time',
            'issued_at' => now(),
            'subtotal' => $transaction->amount,
            'discount' => $transaction->discount,
            'tax' => 0, // Calcular si es necesario
            'total' => $transaction->total,
            'coupon_id' => $transaction->coupon_id,
            'status' => $transaction->isCompleted() ? 'paid' : 'draft',
            'paid_at' => $transaction->isCompleted() ? now() : null,
        ]);

        // Crear ítem de la factura
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $transaction->purchasable->name ?? 'Compra única',
            'itemable_type' => $transaction->purchasable_type,
            'itemable_id' => $transaction->purchasable_id,
            'quantity' => 1,
            'unit_price' => $transaction->amount,
            'discount' => $transaction->discount,
            'subtotal' => $transaction->amount,
            'total' => $transaction->total,
        ]);

        return $invoice->fresh(['items']);
    }

    /**
     * Crear factura mensual desde suscripción
     */
    public function createFromSubscription(
        Subscription $subscription,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?string $couponCode = null
    ): Invoice {
        $user = $subscription->user;
        $plan = $subscription->plan;
        $billingAddress = $user->billingAddresses()->where('is_default', true)->first();

        $billingService = app(BillingService::class);

        // Calcular total del mes
        if ($couponCode) {
            $billing = $billingService->calculateMonthlyTotalWithCoupon($user, $couponCode, $periodStart, $periodEnd);
            $subtotal = $billing['subtotal'];
            $discount = $billing['coupon']['discount_amount'];
            $total = $billing['total'];
            $couponId = app(CouponService::class)->find($couponCode)->id;
        } else {
            $billing = $billingService->calculateMonthlyTotal($user, $periodStart, $periodEnd);
            $subtotal = $billing['total'];
            $discount = 0;
            $total = $billing['total'];
            $couponId = null;
        }

        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'user_id' => $user->id,
            'billing_address_id' => $billingAddress?->id,
            'subscription_id' => $subscription->id,
            'type' => $subscription->billing_mode,
            'issued_at' => now(),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => 0,
            'total' => $total,
            'coupon_id' => $couponId,
            'status' => 'draft',
            'meta' => [
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ],
        ]);

        // Crear ítems de la factura
        $items = [];

        // 1. Plan base (si aplica)
        if ($billing['plan_price'] > 0) {
            $items[] = [
                'invoice_id' => $invoice->id,
                'description' => "Plan {$plan->name} - " . $periodStart->format('M Y'),
                'itemable_type' => get_class($plan),
                'itemable_id' => $plan->id,
                'quantity' => 1,
                'unit_price' => $billing['plan_price'],
                'subtotal' => $billing['plan_price'],
                'total' => $billing['plan_price'],
            ];
        }

        // 2. Consumo (si aplica)
        if ($billing['consumption_total'] > 0) {
            $items[] = [
                'invoice_id' => $invoice->id,
                'description' => "Consumo - " . $periodStart->format('M Y'),
                'details' => 'Uso de servicios del período',
                'quantity' => 1,
                'unit_price' => $billing['consumption_total'],
                'subtotal' => $billing['consumption_total'],
                'total' => $billing['consumption_total'],
            ];
        }

        InvoiceItem::insert($items);

        return $invoice->fresh(['items']);
    }

    /**
     * Obtener facturas de un usuario
     */
    public function getUserInvoices(User $user, ?string $status = null)
    {
        $query = Invoice::where('user_id', $user->id)->with(['items', 'billingAddress']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest('issued_at')->get();
    }

    /**
     * Marcar factura como pagada
     */
    public function markAsPaid(Invoice $invoice): Invoice
    {
        $invoice->markAsPaid();
        
        // Si tiene transacción asociada, marcarla también
        if ($invoice->transaction) {
            $invoice->transaction->markAsCompleted();
        }

        return $invoice;
    }
}