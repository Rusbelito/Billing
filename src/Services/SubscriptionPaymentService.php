<?php

namespace Rusbelito\Billing\Services;

use Rusbelito\Billing\Models\Subscription;
use Rusbelito\Billing\Models\PaymentAttempt;
use Rusbelito\Billing\Models\Invoice;
use Rusbelito\Billing\Mail\PaymentSuccessful;
use Rusbelito\Billing\Mail\PaymentFailed;
use Rusbelito\Billing\Mail\SubscriptionCancelled;
use Rusbelito\Billing\Mail\CardExpiringSoon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SubscriptionPaymentService
{
    protected InvoiceService $invoiceService;
    protected PaymentsWayService $paymentsWayService;

    public function __construct(
        InvoiceService $invoiceService = null,
        PaymentsWayService $paymentsWayService = null
    ) {
        $this->invoiceService = $invoiceService ?? app(InvoiceService::class);
        $this->paymentsWayService = $paymentsWayService ?? app(PaymentsWayService::class);
    }

    /**
     * Cobrar una suscripción específica
     */
    public function chargeSubscription(Subscription $subscription, ?string $couponCode = null): array
    {
        $user = $subscription->user;
        $paymentMethod = $user->defaultPaymentMethod;

        // Validaciones previas
        if (!$paymentMethod) {
            return [
                'success' => false,
                'error' => 'no_payment_method',
                'message' => 'Usuario no tiene método de pago',
            ];
        }

        if ($paymentMethod->isExpired()) {
            return [
                'success' => false,
                'error' => 'payment_method_expired',
                'message' => 'Método de pago expirado',
            ];
        }

        if ($subscription->status !== 'active') {
            return [
                'success' => false,
                'error' => 'subscription_not_active',
                'message' => 'Suscripción no está activa',
            ];
        }

        try {
            // Generar factura del período
            $invoice = $this->invoiceService->createFromSubscription(
                $subscription,
                $subscription->ends_at ?? now()->startOfMonth(),
                now()->endOfMonth(),
                $couponCode
            );

            Log::info('Invoice generated for subscription', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total,
            ]);

            // Intentar cobro
            $attempt = $this->paymentsWayService->chargeWithToken(
                $paymentMethod,
                $invoice->total,
                "Factura {$invoice->invoice_number} - {$subscription->plan->name}",
                $invoice
            );

            if ($attempt->isSuccess()) {
                // Cobro exitoso - renovar suscripción
                $subscription->renew();

                Log::info('Subscription charged successfully', [
                    'subscription_id' => $subscription->id,
                    'invoice_id' => $invoice->id,
                    'attempt_id' => $attempt->id,
                ]);

                // Enviar email de confirmación
                $this->sendPaymentSuccessEmail($user, $invoice, $subscription);

                return [
                    'success' => true,
                    'invoice' => $invoice,
                    'attempt' => $attempt,
                    'subscription' => $subscription->fresh(),
                ];
            }

            // Cobro fallido
            Log::warning('Subscription charge failed', [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'attempt_id' => $attempt->id,
                'error' => $attempt->error_message,
            ]);

            // Enviar email de fallo
            $this->sendPaymentFailedEmail($user, $invoice, $subscription, $attempt);

            return [
                'success' => false,
                'error' => 'charge_failed',
                'message' => $attempt->error_message,
                'invoice' => $invoice,
                'attempt' => $attempt,
            ];

        } catch (\Exception $e) {
            Log::error('Exception charging subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'exception',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener suscripciones que deben cobrarse hoy
     */
    public function getSubscriptionsDueForPayment(): \Illuminate\Support\Collection
    {
        return Subscription::where('status', 'active')
            ->whereDate('ends_at', '<=', now())
            ->with(['user.defaultPaymentMethod', 'plan'])
            ->get();
    }

    /**
     * Reintentar cobro fallido
     */
    public function retryFailedPayment(PaymentAttempt $attempt): array
    {
        // Validar que es reintenteble
        if (!$attempt->isFailed()) {
            return [
                'success' => false,
                'error' => 'not_failed',
                'message' => 'El intento no está en estado fallido',
            ];
        }

        $maxRetries = config('billing.max_payment_retries', 3);
        if ($attempt->retry_count >= $maxRetries) {
            return [
                'success' => false,
                'error' => 'max_retries_reached',
                'message' => 'Se alcanzó el máximo de reintentos',
            ];
        }

        $subscription = $attempt->subscription;
        if (!$subscription) {
            return [
                'success' => false,
                'error' => 'no_subscription',
                'message' => 'No hay suscripción asociada',
            ];
        }

        // Incrementar contador de reintentos
        $attempt->incrementRetry();

        Log::info('Retrying failed payment', [
            'attempt_id' => $attempt->id,
            'retry_count' => $attempt->retry_count,
        ]);

        // Reintentar cobro
        return $this->chargeSubscription($subscription);
    }

    /**
     * Cancelar suscripción por fallo de pago
     */
    public function cancelSubscriptionDueToPaymentFailure(Subscription $subscription, string $reason): void
    {
        $subscription->update([
            'status' => 'cancelled',
            'meta' => array_merge($subscription->meta ?? [], [
                'cancellation_reason' => 'payment_failure',
                'cancellation_details' => $reason,
                'cancelled_at' => now()->toDateTimeString(),
            ]),
        ]);

        Log::warning('Subscription cancelled due to payment failure', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'reason' => $reason,
        ]);

        // Enviar email de cancelación
        $this->sendSubscriptionCancelledEmail($subscription->user, $subscription);
    }

    /**
     * Verificar tarjetas que expiran pronto
     */
    public function notifyExpiringCards(): int
    {
        $expiringMethods = \Rusbelito\Billing\Models\PaymentMethod::where('is_active', true)
            ->where('type', 'card')
            ->get()
            ->filter(function ($method) {
                return $method->isExpiringSoon();
            });

        $count = 0;
        foreach ($expiringMethods as $method) {
            $this->sendCardExpiringEmail($method->user, $method);
            $count++;
        }

        Log::info('Card expiration notifications sent', ['count' => $count]);

        return $count;
    }

    /**
     * Enviar email de pago exitoso
     */
    protected function sendPaymentSuccessEmail($user, Invoice $invoice, Subscription $subscription): void
    {
        Mail::to($user)->send(new PaymentSuccessful($invoice, $subscription));

        Log::info('Payment success email queued', [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Enviar email de pago fallido
     */
    protected function sendPaymentFailedEmail($user, Invoice $invoice, Subscription $subscription, PaymentAttempt $attempt): void
    {
        Mail::to($user)->send(new PaymentFailed($invoice, $subscription, $attempt));

        Log::info('Payment failed email queued', [
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'error' => $attempt->error_message,
        ]);
    }

    /**
     * Enviar email de suscripción cancelada
     */
    protected function sendSubscriptionCancelledEmail($user, Subscription $subscription): void
    {
        Mail::to($user)->send(new SubscriptionCancelled($subscription));

        Log::info('Subscription cancelled email queued', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Enviar email de tarjeta por expirar
     */
    protected function sendCardExpiringEmail($user, $paymentMethod): void
    {
        Mail::to($user)->send(new CardExpiringSoon($paymentMethod));

        Log::info('Card expiring email queued', [
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
        ]);
    }
}