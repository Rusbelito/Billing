<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            
            // Numeración
            $table->string('invoice_number')->unique(); // INV-2025-0001
            
            // Usuario
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_address_id')->nullable()->constrained('billing_addresses')->nullOnDelete();
            
            // Relación con transacción (si es one-time)
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            
            // Relación con suscripción (si es mensual)
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            
            // Tipo de factura
            $table->enum('type', ['one_time', 'subscription', 'consumption', 'mixed'])->default('one_time');
            
            // Fechas
            $table->timestamp('issued_at'); // Fecha de emisión
            $table->timestamp('due_at')->nullable(); // Fecha de vencimiento (si aplica)
            $table->timestamp('paid_at')->nullable(); // Fecha de pago
            
            // Montos
            $table->decimal('subtotal', 10, 2); // Antes de descuentos
            $table->decimal('discount', 10, 2)->default(0); // Descuento total
            $table->decimal('tax', 10, 2)->default(0); // Impuestos (IVA, etc.)
            $table->decimal('total', 10, 2); // Total final
            
            // Cupón aplicado
            $table->foreignId('coupon_id')->nullable()->constrained('billing_coupons')->nullOnDelete();
            
            // Estado
            $table->enum('status', ['draft', 'issued', 'paid', 'overdue', 'cancelled', 'refunded'])->default('draft');
            
            // Facturación electrónica (preparado para paquete futuro)
            $table->string('electronic_invoice_id')->nullable(); // ID del proveedor FE
            $table->string('cufe')->nullable(); // Código único de factura electrónica
            $table->timestamp('certified_at')->nullable(); // Cuándo se certificó
            $table->json('electronic_data')->nullable(); // Datos adicionales de FE
            
            // Metadata
            $table->json('meta')->nullable();
            $table->text('notes')->nullable(); // Notas adicionales
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('invoice_number');
            $table->index('status');
            $table->index('issued_at');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};