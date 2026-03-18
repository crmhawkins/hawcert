<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Restringe qué credenciales (servicios con credenciales) puede usar cada certificado.
     */
    public function up(): void
    {
        Schema::create('certificate_credential', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->onDelete('cascade');
            $table->foreignId('credential_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['certificate_id', 'credential_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_credential');
    }
};
