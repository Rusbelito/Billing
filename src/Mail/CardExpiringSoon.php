<?php

namespace Rusbelito\Billing\Mail;

use Rusbelito\Billing\Models\PaymentMethod;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CardExpiringSoon extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public PaymentMethod $paymentMethod
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu Tarjeta Está por Expirar',
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
        $method = $this->paymentMethod;
        $cardBrand = $method->card_brand ?? 'Tarjeta';
        $lastFour = $method->card_last_four ?? '****';
        $expiryMonth = str_pad($method->card_exp_month ?? '00', 2, '0', STR_PAD_LEFT);
        $expiryYear = $method->card_exp_year ?? '0000';

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
        .card-details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #FF9800; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .warning-box { background: #fff3e0; padding: 15px; margin: 20px 0; border-radius: 5px; border: 1px solid #FFB74D; }
        .card-number { font-size: 18px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Tarjeta por Expirar</h1>
        </div>
        <div class="content">
            <p>Hola,</p>
            <p>Te informamos que uno de tus métodos de pago está por expirar pronto.</p>

            <div class="card-details">
                <h3>Detalles del Método de Pago</h3>
                <p><strong>Tipo:</strong> {$cardBrand}</p>
                <p class="card-number"><strong>Número:</strong> **** **** **** {$lastFour}</p>
                <p><strong>Fecha de Expiración:</strong> {$expiryMonth}/{$expiryYear}</p>
            </div>

            <div class="warning-box">
                <p><strong>⚠️ Acción Requerida</strong></p>
                <p>Para evitar interrupciones en tu servicio, por favor actualiza tu método de pago antes de que expire.</p>
            </div>

            <p><strong>¿Qué hacer?</strong></p>
            <ul>
                <li>Accede a tu cuenta y actualiza la información de tu tarjeta</li>
                <li>Agrega un nuevo método de pago</li>
                <li>Verifica que tu información de facturación esté actualizada</li>
            </ul>

            <p><strong>¿Por qué es importante?</strong></p>
            <p>Si tu tarjeta expira, no podremos procesar los pagos de tu suscripción, lo que podría resultar en la interrupción del servicio.</p>

            <p>Si ya actualizaste tu método de pago, puedes ignorar este mensaje.</p>

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
