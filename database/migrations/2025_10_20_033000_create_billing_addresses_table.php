<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_addresses', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Información personal/empresa
            $table->string('legal_name'); // Razón social o nombre completo
            $table->string('tax_id')->nullable(); // NIT, RFC, Tax ID, etc.
            $table->enum('tax_id_type', ['nit', 'rfc', 'tax_id', 'dni', 'other'])->default('nit');
            
            // Dirección
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable(); // Departamento/Estado
            $table->string('postal_code')->nullable();
            $table->string('country', 2)->default('CO'); // ISO 3166-1 alpha-2
            
            // Contacto
            $table->string('email');
            $table->string('phone')->nullable();
            
            // Información fiscal adicional (flexible por país)
            $table->json('fiscal_data')->nullable(); // Régimen, tipo contribuyente, etc.
            
            // Control
            $table->boolean('is_default')->default(false); // Dirección principal
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_addresses');
    }
};