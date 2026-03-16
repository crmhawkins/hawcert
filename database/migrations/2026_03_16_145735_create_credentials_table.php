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
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('certificate_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('website_name'); // Nombre descriptivo (ej: "IONOS")
            $table->string('website_url_pattern'); // Patrón de URL (ej: "*ionos.com*", "https://www.ionos.com/*")
            $table->string('username_field_selector'); // Selector CSS (ej: "#username", "input[name='email']")
            $table->string('password_field_selector'); // Selector CSS (ej: "#password", "input[type='password']")
            $table->text('username_value'); // Valor encriptado
            $table->text('password_value'); // Valor encriptado
            $table->string('submit_button_selector')->nullable(); // Selector CSS del botón de envío
            $table->boolean('auto_fill')->default(true); // Rellenar automáticamente
            $table->boolean('auto_submit')->default(false); // Enviar automáticamente después de rellenar
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable(); // Notas adicionales
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'is_active']);
            $table->index(['certificate_id', 'is_active']);
            $table->index('website_url_pattern');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
