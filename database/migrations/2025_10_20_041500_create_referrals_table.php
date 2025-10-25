<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            
            // Quién refirió a quién
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete(); // Usuario que refirió
            $table->foreignId('referred_id')->constrained('users')->cascadeOnDelete(); // Usuario referido
            $table->foreignId('referral_code_id')->constrained('referral_codes')->cascadeOnDelete();
            $table->foreignId('referral_program_id')->constrained('referral_programs')->cascadeOnDelete();
            
            // Estado del referido
            $table->enum('status', [
                'registered',    // Se registró pero no ha hecho nada
                'subscribed',    // Se suscribió a un plan
                'converted',     // Completó la condición (primera compra, etc.)
                'active',        // Sigue activo
                'churned'        // Canceló/inactivo
            ])->default('registered');
            
            // Fechas importantes
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('churned_at')->nullable();
            
            // Información adicional
            $table->decimal('total_revenue_generated', 10, 2)->default(0); // Ingresos generados por este referido
            $table->integer('months_active')->default(0); // Meses que lleva activo
            
            // Metadata
            $table->json('meta')->nullable();
            
            $table->timestamps();
            
            $table->index('referrer_id');
            $table->index('referred_id');
            $table->index('status');
            $table->unique(['referrer_id', 'referred_id']); // Un usuario solo puede ser referido una vez por el mismo referidor
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};