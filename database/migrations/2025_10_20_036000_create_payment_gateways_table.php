<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            
            // Identificador del gateway
            $table->string('name'); // PaymentsWay, Stripe, etc.
            $table->string('slug')->unique(); // paymentsway, stripe
            $table->string('driver'); // Clase que implementa el gateway
            
            // Configuración
            $table->json('config'); // Credenciales encriptadas (merchant_id, api_key, etc.)
            
            // Estado
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Gateway predeterminado
            
            // Métodos de pago soportados
            $table->json('supported_methods')->nullable(); // ['card', 'pse', 'cash']
            
            // Metadata
            $table->json('meta')->nullable();
            
            $table->timestamps();
            
            $table->index('slug');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};