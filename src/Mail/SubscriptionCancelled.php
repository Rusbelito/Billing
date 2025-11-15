<?php

namespace Rusbelito\Billing\Mail;

use Rusbelito\Billing\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancelled extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Subscription $subscription
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Suscripción Cancelada',
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
        $subscription = $this->subscription;

        $reason = $subscription->meta['cancellation_reason'] ?? 'No especificada';
        $details = $subscription->meta['cancellation_details'] ?? '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #FF9800; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .subscription-details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #FF9800; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .info-box { background: #fff3e0; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Suscripción Cancelada</h1>
        </div>
        <div class="content">
            <p>Hola,</p>
            <p>Lamentamos informarte que tu suscripción ha sido cancelada.</p>

            <div class="subscription-details">
                <h3>Detalles de la Suscripción</h3>
                <p><strong>Plan:</strong> {$plan->name}</p>
                <p><strong>Estado:</strong> Cancelada</p>
                <p><strong>Razón:</strong> {$reason}</p>
HTML;

        if ($details) {
            $html = <<<HTML
                <p><strong>Detalles:</strong> {$details}</p>
HTML;
        } else {
            $html = '';
        }

        return $html . <<<HTML
            </div>

            <div class="info-box">
                <p><strong>¿Qué significa esto?</strong></p>
                <ul>
                    <li>Tu acceso al servicio finalizará según los términos de tu suscripción</li>
                    <li>No se realizarán más cobros automáticos</li>
                    <li>Puedes reactivar tu suscripción en cualquier momento</li>
                </ul>
            </div>

            <p>Si tienes alguna pregunta o deseas reactivar tu suscripción, no dudes en contactarnos.</p>

            <p>¡Esperamos verte de nuevo pronto!</p>
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
