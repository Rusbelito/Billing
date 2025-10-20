<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usage_prices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
    $table->string('action_key'); // Ej: create_post, generate_ai, etc.
    $table->unsignedInteger('unit_count')->default(1); // Cuántas acciones por unidad
    $table->decimal('unit_price', 12, 6); // Precio por unidad de uso
    $table->timestamps();

    $table->unique(['plan_id', 'action_key']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_prices');
    }
};
