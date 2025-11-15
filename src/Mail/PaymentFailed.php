<?php

namespace Rusbelito\Billing\Mail;

use Rusbelito\Billing\Models\Invoice;
use Rusbelito\Billing\Models\PaymentAttempt;
use Rusbelito\Billing\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailed extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Invoice $invoice,
        public Subscription $subscription,
        public PaymentAttempt $attempt
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Fallo en el Pago - Factura ' . $this->invoice->invoice_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtmlContent(),
        );
    }

    /**
     * Build HTML content for the email.
     */
    protected function buildHtmlContent(): string
    {
        $plan = $this->subscription->plan;
        $invoice = $this->invoice;
        $attempt = $this->attempt;

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f44336; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .invoice-details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #f44336; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .amount { font-size: 24px; color: #f44336; font-weight: bold; }
        .error-box { background: #ffebee; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .action-button { display: inline-block; background: #2196F3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Problema con tu Pago</h1>
        </div>
        <div class="content">
            <p>Hola,</p>
            <p>Lamentablemente, no pudimos procesar tu pago. Por favor, revisa la información a continuación.</p>

            <div class="invoice-details">
                <h3>Detalles de la Factura</h3>
                <p><strong>Número de Factura:</strong> {$invoice->invoice_number}</p>
                <p><strong>Plan:</strong> {$plan->name}</p>
                <p><strong>Fecha:</strong> {$invoice->created_at->format('d/m/Y')}</p>
                <p><strong>Monto:</strong> <span class="amount">\${$invoice->total}</span></p>
                <p><strong>Estado:</strong> Pendiente</p>
            </div>

            <div class="error-box">
                <p><strong>Razón del fallo:</strong> {$attempt->error_message}</p>
            </div>

            <p><strong>¿Qué hacer ahora?</strong></p>
            <ul>
                <li>Verifica que tu método de pago tenga fondos suficientes</li>
                <li>Asegúrate de que la información de tu tarjeta esté actualizada</li>
                <li>Intenta con otro método de pago</li>
            </ul>

            <p>Intentaremos procesar el pago nuevamente en los próximos días.</p>

            <p>Si necesitas ayuda, no dudes en contactarnos.</p>
        </div>
        <div class="footer">
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
