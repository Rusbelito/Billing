<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            
            // Tipo de método
            $table->enum('type', ['card', 'bank_account', 'wallet'])->default('card');
            
            // Token del gateway
            $table->string('gateway_token')->nullable(); // Token de PaymentsWay
            $table->string('gateway_customer_id')->nullable(); // ID del cliente en el gateway
            
            // Información de la tarjeta (solo últimos 4 dígitos y marca)
            $table->string('card_brand')->nullable(); // Visa, Mastercard, etc.
            $table->string('card_last_four')->nullable(); // 4242
            $table->string('card_exp_month')->nullable(); // 12
            $table->string('card_exp_year')->nullable(); // 2025
            
            // Información de cuenta bancaria (para PSE)
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            
            // Estado
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->json('meta')->nullable();
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('gateway_token');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};