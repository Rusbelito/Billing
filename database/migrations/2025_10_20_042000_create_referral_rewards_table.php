<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            
            // A quién se le otorga la recompensa
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Referidor
            $table->foreignId('referral_id')->constrained('referrals')->cascadeOnDelete();
            $table->foreignId('referral_program_id')->constrained('referral_programs')->cascadeOnDelete();
            
            // Tipo y valor de recompensa
            $table->enum('reward_type', [
                'fixed_discount',
                'percentage_revenue',
                'one_time_credit',
                'account_credit',
                'plan_upgrade',
                'permanent_discount'
            ]);
            
            $table->decimal('reward_value', 10, 2); // Valor original
            $table->decimal('remaining_value', 10, 2)->nullable(); // Valor restante (para créditos)
            
            // Duración
            $table->integer('duration_months')->nullable();
            $table->integer('duration_cycles')->nullable();
            $table->integer('cycles_applied')->default(0); // Cuántos ciclos ya se aplicó
            
            // Estado
            $table->enum('status', [
                'pending',    // Ganada pero no aplicada
                'active',     // Activa y aplicándose
                'completed',  // Ya se completó (agotó duración o crédito)
                'expired',    // Expiró sin usarse
                'cancelled'   // Cancelada (ej: referido hizo chargeback)
            ])->default('pending');
            
            // Fechas
            $table->timestamp('earned_at'); // Cuándo se ganó
            $table->timestamp('activated_at')->nullable(); // Cuándo se activó
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Para plan_upgrade
            $table->foreignId('upgrade_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            
            // Metadata
            $table->json('meta')->nullable();
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index('reward_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_rewards');
    }
};