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
        Schema::create('access_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->onDelete('cascade');
            $table->string('key')->unique(); // Key única generada
            $table->string('service_slug'); // Servicio/URL al que se accede
            $table->string('target_url')->nullable(); // URL específica del destino
            $table->ipAddress('client_ip')->nullable(); // IP del cliente que solicitó la key
            $table->dateTime('expires_at'); // Fecha de expiración de la key
            $table->boolean('is_used')->default(false); // Si ya fue usada
            $table->dateTime('used_at')->nullable(); // Cuándo fue usada
            $table->text('metadata')->nullable(); // JSON para datos adicionales
            $table->timestamps();
            
            $table->index('key');
            $table->index(['certificate_id', 'service_slug']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_keys');
    }
};
