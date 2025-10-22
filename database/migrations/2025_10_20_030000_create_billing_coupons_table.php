<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_coupons', function (Blueprint $table) {
            $table->id();
            
            // Identificadores
            $table->string('code')->unique(); // Ej: "SAVE50"
            $table->string('description')->nullable();
            
            // Tipo de descuento
            $table->enum('discount_type', ['percentage', 'fixed']); // porcentaje o monto fijo
            $table->decimal('discount_value', 10, 2); // 50 para 50% o 50 para $50
            
            // Validaciones
            $table->timestamp('starts_at')->nullable(); // Desde cuándo es válido
            $table->timestamp('expires_at')->nullable(); // Hasta cuándo es válido
            $table->decimal('minimum_amount', 10, 2)->nullable(); // Monto mínimo de compra
            
            // Límites de uso
            $table->enum('usage_type', ['single', 'reusable', 'limited'])->default('reusable');
            // single = usa una sola vez
            // reusable = múltiples usuarios, ilimitado
            // limited = múltiples usuarios, pero con límite
            
            $table->unsignedInteger('max_uses')->nullable(); // Para 'limited': máximo de usos
            $table->unsignedInteger('current_uses')->default(0); // Usos actuales
            
            // Aplicabilidad
            $table->boolean('is_active')->default(true);
            $table->json('applicable_plans')->nullable(); // null = todos, [1,2,3] = específicos
            $table->json('applicable_billing_modes')->nullable(); // null = todos
            
            // Metadata
            $table->json('meta')->nullable(); // Datos adicionales
            
            $table->timestamps();
            
            $table->index('code');
            $table->index('expires_at');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_coupons');
    }
};