<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Tipo de transacción
            $table->enum('type', ['one_time', 'subscription', 'consumption', 'donation'])->default('one_time');
            
            // Referencia a lo que se compró (puede ser un producto, plan, servicio, etc.)
            // morphs() ya crea el índice automáticamente, no necesitamos agregarlo después
            $table->morphs('purchasable'); // purchasable_type, purchasable_id
            
            // Montos
            $table->decimal('amount', 10, 2); // Monto original
            $table->decimal('discount', 10, 2)->default(0); // Descuento aplicado
            $table->decimal('total', 10, 2); // Total final a pagar
            
            // Estado del pago
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            
            // Cupón aplicado (opcional)
            $table->foreignId('coupon_id')->nullable()->constrained('billing_coupons')->nullOnDelete();
            
            // Metadata
            $table->json('meta')->nullable(); // Datos adicionales como gateway, reference, etc.
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('type');
            $table->index('status');
            // NO agregamos index(['purchasable_type', 'purchasable_id']) porque morphs() ya lo crea
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};