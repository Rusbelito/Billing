<?php

namespace Rusbelito\Billing\Services;

use Rusbelito\Billing\Models\PaymentAttempt;
use Rusbelito\Billing\Models\Invoice;
use Rusbelito\Billing\Models\Transaction;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Procesar webhook de PaymentsWay
     */
    public function handlePaymentsWayWebhook(array $payload): array
    {
        Log::info('PaymentsWay Webhook Received', $payload);

        $transactionId = $payload['id'] ?? null;
        $statusId = $payload['idstatus']['id'] ?? null;
        $statusName = $payload['idstatus']['nombre'] ?? null;
        $externalOrder = $payload['externalorder'] ?? null;
        $amount = $payload['amount'] ?? null;

        // Buscar el intento de pago
        $attempt = PaymentAttempt::where('gateway_order_number', $externalOrder)->first();

        if (!$attempt) {
            Log::warning('Payment attempt not found', ['external_order' => $externalOrder]);
            return [
                'success' => false,
                'message' => 'Payment attempt not found',
            ];
        }

        // Actualizar intento según el estado
        $this->updatePaymentAttempt($attempt, $statusId, $statusName, $transactionId, $payload);

        // Determinar qué status code devolver (200 solo para exitosa)
        if ($statusId == 34) { // Exitosa
            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'Payment processed successfully',
            ];
        }

        // Para otros estados devolver 201
        return [
            'success' => true,
            'status_code' => 201,
            'message' => 'Webhook received',
        ];
    }

    /**
     * Actualizar intento de pago según el estado
     */
    protected function updatePaymentAttempt(PaymentAttempt $attempt, int $statusId, string $statusName, ?string $transactionId, array $payload): void
    {
        switch ($statusId) {
            case 34: // Exitosa
                $this->handleSuccessfulPayment($attempt, $transactionId, $payload);
                break;

            case 35: // Pendiente
                $this->handlePendingPayment($attempt, $payload);
                break;

            case 36: // Fallida
                $this->handleFailedPayment($attempt, $payload);
                break;

            case 38: // Cancelada
                $this->handleCancelledPayment($attempt, $payload);
                break;

            case 39: // Reembolsada
                $this->handleRefundedPayment($attempt, $payload);
                break;

            case 40: // Pendiente efectivo
                $this->handlePendingCashPayment($attempt, $payload);
                break;

            default:
                Log::warning('Unknown payment status', [
                    'status_id' => $statusId,
                    'status_name' => $statusName,
                ]);
                break;
        }
    }

    /**
     * Manejar pago exitoso
     */
    protected function handleSuccessfulPayment(PaymentAttempt $attempt, ?string $transactionId, array $payload): void
    {
        $attempt->markAsSuccess($transactionId, $payload);

        // Actualizar invoice si existe
        if ($attempt->invoice) {
            $attempt->invoice->markAsPaid();
        }

        // Actualizar transaction si existe
        if ($attempt->transaction) {
            $attempt->transaction->markAsCompleted();
        }

        // Actualizar suscripción si existe
        if ($attempt->subscription) {
            $attempt->subscription->update([
                'starts_at' => $attempt->subscription->starts_at ?? now(),
                'ends_at' => now()->addMonth(),
            ]);
        }

        Log::info('Payment successful', [
            'attempt_id' => $attempt->id,
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * Manejar pago pendiente
     */
    protected function handlePendingPayment(PaymentAttempt $attempt, array $payload): void
    {
        $attempt->update([
            'status' => 'processing',
            'gateway_response' => $payload,
        ]);

        Log::info('Payment pending', ['attempt_id' => $attempt->id]);
    }

    /**
     * Manejar pago fallido
     */
    protected function handleFailedPayment(PaymentAttempt $attempt, array $payload): void
    {
        $errorMessage = $payload['innerexception']['causal'] ?? 'Pago rechazado';
        
        $attempt->markAsFailed($errorMessage, $payload);

        Log::warning('Payment failed', [
            'attempt_id' => $attempt->id,
            'error' => $errorMessage,
        ]);
    }

    /**
     * Manejar pago cancelado
     */
    protected function handleCancelledPayment(PaymentAttempt $attempt, array $payload): void
    {
        $attempt->update([
            'status' => 'cancelled',
            'gateway_response' => $payload,
            'completed_at' => now(),
        ]);

        Log::info('Payment cancelled', ['attempt_id' => $attempt->id]);
    }

    /**
     * Manejar reembolso
     */
    protected function handleRefundedPayment(PaymentAttempt $attempt, array $payload): void
    {
        $attempt->update([
            'status' => 'cancelled',
            'gateway_response' => $payload,
        ]);

        // Actualizar invoice
        if ($attempt->invoice) {
            $attempt->invoice->markAsRefunded();
        }

        // Actualizar transaction
        if ($attempt->transaction) {
            $attempt->transaction->markAsRefunded();
        }

        Log::info('Payment refunded', ['attempt_id' => $attempt->id]);
    }

    /**
     * Manejar pago en efectivo pendiente
     */
    protected function handlePendingCashPayment(PaymentAttempt $attempt, array $payload): void
    {
        $attempt->update([
            'status' => 'processing',
            'gateway_response' => $payload,
            'gateway_message' => 'Pendiente de pago en efectivo',
        ]);

        Log::info('Cash payment pending', ['attempt_id' => $attempt->id]);
    }
}