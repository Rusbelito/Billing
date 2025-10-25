<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_reward_applications', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('referral_reward_id')->constrained('referral_rewards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Dónde se aplicó
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            
            // Monto aplicado
            $table->decimal('applied_amount', 10, 2);
            $table->decimal('original_amount', 10, 2); // Monto antes del descuento
            $table->decimal('final_amount', 10, 2); // Monto después del descuento
            
            // Metadata
            $table->json('meta')->nullable();
            
            $table->timestamps();
            
            $table->index('referral_reward_id');
            $table->index('user_id');
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_reward_applications');
    }
};