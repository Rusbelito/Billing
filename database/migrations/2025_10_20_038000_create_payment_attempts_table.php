<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            
            // Relación con lo que se está pagando
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            
            // Información del intento
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('COP');
            
            // ID del gateway
            $table->string('gateway_transaction_id')->nullable(); // ID de PaymentsWay
            $table->string('gateway_order_number')->unique(); // order_number enviado a PaymentsWay
            
            // Estado
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'cancelled'])->default('pending');
            
            // Respuesta del gateway
            $table->string('gateway_status_code')->nullable(); // Código de PaymentsWay (00, 01, etc.)
            $table->text('gateway_message')->nullable(); // Mensaje del gateway
            $table->json('gateway_response')->nullable(); // Respuesta completa del gateway
            
            // Información adicional
            $table->string('ip_address')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0); // Número de reintentos
            
            // Timestamps
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('invoice_id');
            $table->index('subscription_id');
            $table->index('status');
            $table->index('gateway_transaction_id');
            $table->index('gateway_order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};