<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            
            // Descripción del ítem
            $table->string('description'); // Ej: "Plan Pro - Octubre 2025", "Producto X"
            $table->text('details')->nullable(); // Detalles adicionales
            
            // Relación polimórfica (puede ser Plan, Product, Service, etc.)
            $table->nullableMorphs('itemable');
            
            // Cantidades y precios
            $table->decimal('quantity', 10, 2)->default(1); // Cantidad
            $table->decimal('unit_price', 10, 2); // Precio unitario
            $table->decimal('discount', 10, 2)->default(0); // Descuento en este ítem
            $table->decimal('tax_rate', 5, 2)->default(0); // % de impuesto (ej: 19 para IVA)
            $table->decimal('tax_amount', 10, 2)->default(0); // Monto del impuesto
            $table->decimal('subtotal', 10, 2); // Subtotal (cantidad * precio)
            $table->decimal('total', 10, 2); // Total del ítem
            
            // Metadata
            $table->json('meta')->nullable();
            
            $table->timestamps();
            
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};