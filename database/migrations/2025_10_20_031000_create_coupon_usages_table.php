<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('coupon_id')->constrained('billing_coupons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Referencia a qué se aplicó el cupón (NULLABLE)
            $table->nullableMorphs('billable'); // Puede ser Subscription, Invoice, Transaction, etc.
            
            // Datos del descuento aplicado
            $table->decimal('discount_amount', 10, 2); // Cantidad descontada
            $table->decimal('original_amount', 10, 2); // Monto antes del descuento
            $table->decimal('final_amount', 10, 2); // Monto después del descuento
            
            $table->timestamps();
            
            $table->index('coupon_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};