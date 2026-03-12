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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Nombre del servicio (ej: "api", "dashboard", "reports")
            $table->string('slug')->unique(); // Slug para URLs
            $table->text('description')->nullable();
            $table->string('endpoint')->nullable(); // Endpoint base del servicio
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
