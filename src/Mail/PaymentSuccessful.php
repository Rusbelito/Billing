<?php

namespace Rusbelito\Billing\Mail;

use Rusbelito\Billing\Models\Invoice;
use Rusbelito\Billing\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessful extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Invoice $invoice,
        public Subscription $subscription
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pago Exitoso - Factura ' . $this->invoice->invoice_number,
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

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .invoice-details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #4CAF50; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .amount { font-size: 24px; color: #4CAF50; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pago Exitoso</h1>
        </div>
        <div class="content">
            <p>Hola,</p>
            <p>Tu pago ha sido procesado exitosamente. Gracias por tu suscripción.</p>

            <div class="invoice-details">
                <h3>Detalles de la Factura</h3>
                <p><strong>Número de Factura:</strong> {$invoice->invoice_number}</p>
                <p><strong>Plan:</strong> {$plan->name}</p>
                <p><strong>Fecha:</strong> {$invoice->created_at->format('d/m/Y')}</p>
                <p><strong>Monto:</strong> <span class="amount">\${$invoice->total}</span></p>
                <p><strong>Estado:</strong> Pagado</p>
            </div>

            <p>Tu suscripción ha sido renovada hasta el {$this->subscription->ends_at->format('d/m/Y')}.</p>

            <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
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
