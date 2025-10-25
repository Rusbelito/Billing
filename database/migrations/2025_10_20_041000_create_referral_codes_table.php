<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referral_program_id')->nullable()->constrained('referral_programs')->nullOnDelete();
            
            // Código único
            $table->string('code')->unique(); // Ej: "JUAN2025"
            
            // Estadísticas
            $table->integer('total_referrals')->default(0); // Total de referidos
            $table->integer('successful_referrals')->default(0); // Referidos que completaron condición
            $table->integer('clicks')->default(0); // Veces que se usó el link
            
            // Estado
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->json('meta')->nullable();
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_codes');
    }
};