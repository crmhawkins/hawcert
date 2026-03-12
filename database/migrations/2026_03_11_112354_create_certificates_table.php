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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('certificate_key')->unique(); // Clave única del certificado
            $table->string('name'); // Nombre descriptivo del certificado
            $table->text('description')->nullable();
            $table->dateTime('valid_from'); // Fecha de inicio de validez
            $table->dateTime('valid_until'); // Fecha de expiración
            $table->boolean('is_active')->default(true);
            $table->text('metadata')->nullable(); // JSON para datos adicionales
            $table->timestamps();
            
            $table->index('certificate_key');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
