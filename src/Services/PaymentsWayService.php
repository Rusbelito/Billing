<?php

namespace Rusbelito\Billing\Services;

use App\Models\User;
use Rusbelito\Billing\Models\PaymentGateway;
use Rusbelito\Billing\Models\PaymentMethod;
use Rusbelito\Billing\Models\PaymentAttempt;
use Rusbelito\Billing\Models\Invoice;
use Rusbelito\Billing\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentsWayService
{
    protected PaymentGateway $gateway;
    protected array $config;

    public function __construct(?PaymentGateway $gateway = null)
    {
        $this->gateway = $gateway ?? PaymentGateway::where('slug', 'paymentsway')->firstOrFail();
        $this->config = $this->gateway->config;
    }

    /**
     * Generar datos para Widget con tokenización
     */
    public function generateWidgetData(User $user, float $amount, string $description, bool $enableTokenization = false): array
    {
        $orderNumber = PaymentAttempt::generateOrderNumber();

        $data = [
            'merchant_id' => $this->config['merchant_id'],
            'form_id' => $this->config['form_id'],
            'terminal_id' => $this->config['terminal_id'],
            'order_number' => $orderNumber,
            'amount' => $amount,
            'currency' => 'COP',
            'order_description' => $description,
            'apikey' => $this->config['api_key'],
            'ip' => request()->ip(),
            'additionalData' => [
                'user_id' => $user->id,
                'enable_tokenization' => $enableTokenization,
            ],
        ];

        // Si hay datos de facturación, agregarlos
        $billingAddress = $user->defaultBillingAddress;
        if ($billingAddress) {
            $data['person'] = [
                'name' => explode(' ', $billingAddress->legal_name)[0] ?? '',
                'lastName' => implode(' ', array_slice(explode(' ', $billingAddress->legal_name), 1)) ?: '',
                'email' => $billingAddress->email,
                'identification' => $billingAddress->tax_id,
                'identificationType' => $this->mapTaxIdType($billingAddress->tax_id_type),
                'sameData' => true,
            ];
        }

        return $data;
    }

    /**
     * Tokenizar método de pago
     */
    public function tokenizePaymentMethod(User $user, array $cardData): PaymentMethod
    {
        // Crear persona en PaymentsWay
        $personResponse = $this->createPerson($user, $cardData);

        if (!$personResponse['success']) {
            throw new \Exception('Error al crear persona: ' . $personResponse['message']);
        }

        $personId = $personResponse['person_id'];

        // Tokenizar tarjeta
        $tokenResponse = $this->tokenizeCard($personId, $user, $cardData);

        if (!$tokenResponse['success']) {
            throw new \Exception('Error al tokenizar: ' . $tokenResponse['message']);
        }

        // Guardar método de pago
        return PaymentMethod::create([
            'user_id' => $user->id,
            'payment_gateway_id' => $this->gateway->id,
            'type' => 'card',
            'gateway_token' => $tokenResponse['token'],
            'gateway_customer_id' => $personId,
            'card_brand' => $cardData['card_brand'] ?? 'Unknown',
            'card_last_four' => substr($cardData['card_pan'], -4),
            'card_exp_month' => $cardData['card_exp_month'],
            'card_exp_year' => $cardData['card_exp_year'],
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Crear persona en PaymentsWay
     */
    protected function createPerson(User $user, array $data): array
    {
        $billingAddress = $user->defaultBillingAddress;

        $response = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
        ])->post('https://serviceregister.paymentsway.co/ClientAPI/CrearPersona', [
            'firstname' => $data['firstname'] ?? $user->name,
            'lastname' => $data['lastname'] ?? '',
            'ididentificationtype' => $this->mapTaxIdType($data['identification_type'] ?? 'nit'),
            'identification' => $data['identification'] ?? $billingAddress?->tax_id,
            'email' => $user->email,
            'phone' => $data['phone'] ?? $billingAddress?->phone ?? '',
            'city' => $billingAddress?->city ?? '',
            'address' => $billingAddress?->address_line_1 ?? '',
            'zipcode' => $billingAddress?->postal_code ?? '',
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'person_id' => $response->json('id'),
                'response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('message') ?? 'Error desconocido',
            'response' => $response->json(),
        ];
    }

    /**
     * Tokenizar tarjeta
     */
    protected function tokenizeCard(string $personId, User $user, array $cardData): array
    {
        $billingAddress = $user->defaultBillingAddress;

        $response = Http::withHeaders([
            'Authorization' => $this->config['api_key'],
        ])->post('https://serviceregister.paymentsway.co/ClientAPI/TokenizarDatosPersona', [
            'documento' => $billingAddress?->tax_id ?? $cardData['identification'],
            'identification_type' => $this->mapTaxIdType($cardData['identification_type'] ?? 'nit'),
            'idperson' => $personId,
            'url' => config('app.url'),
            'form_id' => $this->config['form_id'],
            'amount' => '100', // Monto mínimo para tokenizar
            'external_order' => 'TOKEN-' . Str::random(10),
            'ip' => request()->ip(),
            'additionalData' => [],
            'currencycode' => 'COP',
            'description' => 'Tokenización de tarjeta',
            'installments' => 1,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'token' => $response->json('data.token'),
                'response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('message') ?? 'Error al tokenizar',
            'response' => $response->json(),
        ];
    }

    /**
     * Cobrar usando token guardado
     */
    public function chargeWithToken(PaymentMethod $paymentMethod, float $amount, string $description, ?Invoice $invoice = null): PaymentAttempt
    {
        $user = $paymentMethod->user;
        $orderNumber = PaymentAttempt::generateOrderNumber();

        // Crear intento de pago
        $attempt = PaymentAttempt::create([
            'user_id' => $user->id,
            'payment_gateway_id' => $this->gateway->id,
            'payment_method_id' => $paymentMethod->id,
            'invoice_id' => $invoice?->id,
            'amount' => $amount,
            'currency' => 'COP',
            'gateway_order_number' => $orderNumber,
            'status' => 'pending',
            'ip_address' => request()->ip(),
        ]);

        $attempt->markAsProcessing();

        try {
            // Aquí iría la llamada a la API de PaymentsWay para cobrar con token
            // Por ahora simularemos la respuesta
            
            // TODO: Implementar llamada real a API de cobro con token
            // $response = $this->chargeTokenizedCard($paymentMethod, $amount, $description, $orderNumber);

            // Simulación
            $response = [
                'success' => true,
                'transaction_id' => 'PW-' . Str::random(10),
                'status_code' => '00',
                'message' => 'Aprobada',
            ];

            if ($response['success']) {
                $attempt->markAsSuccess($response['transaction_id'], $response);

                // Actualizar factura si existe
                if ($invoice) {
                    $invoice->markAsPaid();
                }

                return $attempt;
            }

            $attempt->markAsFailed($response['message'], $response);
            return $attempt;

        } catch (\Exception $e) {
            $attempt->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Mapear tipo de identificación
     */
    protected function mapTaxIdType(string $type): string
    {
        $map = [
            'nit' => '6',
            'dni' => '4',
            'passport' => '1',
            'rfc' => '4',
            'tax_id' => '4',
            'other' => '4',
        ];

        return $map[$type] ?? '4';
    }

    /**
     * Verificar estado de transacción
     */
    public function checkTransactionStatus(string $externalOrder): array
    {
        $response = Http::withHeaders([
            'Authorization' => $this->config['api_key'],
        ])->get('https://serviceregister.paymentsway.co/ClientAPI/ObtenerTransaccionByExternalOrder', [
            'external_order' => $externalOrder,
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'message' => 'No se pudo obtener el estado',
        ];
    }
}