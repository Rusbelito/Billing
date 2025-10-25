<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_programs', function (Blueprint $table) {
            $table->id();
            
            // Identificación
            $table->string('name'); // Ej: "Programa Amigos"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Tipo de recompensa
            $table->enum('reward_type', [
                'fixed_discount',      // Descuento fijo por tiempo
                'percentage_revenue',  // % de lo que paga el referido
                'one_time_credit',     // Crédito único
                'account_credit',      // Saldo a favor
                'plan_upgrade',        // Upgrade temporal gratis
                'permanent_discount'   // Descuento permanente
            ]);
            
            // Valor de la recompensa
            $table->decimal('reward_value', 10, 2); // Monto o porcentaje
            $table->string('reward_currency', 3)->default('COP')->nullable();
            
            // Duración de la recompensa (si aplica)
            $table->integer('reward_duration_months')->nullable(); // Ej: 6 meses
            $table->integer('reward_duration_cycles')->nullable(); // Ej: 6 ciclos de facturación
            
            // Para plan_upgrade
            $table->foreignId('upgrade_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            
            // Condiciones para activar recompensa
            $table->enum('trigger_event', [
                'referral_registered',    // Cuando se registra
                'referral_subscribed',    // Cuando se suscribe
                'referral_first_payment', // Primera compra exitosa
                'referral_active_months'  // Después de X meses activo
            ])->default('referral_first_payment');
            
            $table->integer('trigger_value')->nullable(); // Ej: 2 meses activo
            
            // Límites
            $table->integer('max_referrals_per_user')->nullable(); // Máximo de referidos por usuario
            $table->integer('max_total_uses')->nullable(); // Máximo de usos del programa
            $table->integer('current_total_uses')->default(0);
            
            // Aplicabilidad
            $table->json('applicable_plans')->nullable(); // null = todos los planes
            
            // Fechas
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Estado
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->json('meta')->nullable();
            
            $table->timestamps();
            
            $table->index('slug');
            $table->index('is_active');
            $table->index('reward_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_programs');
    }
};