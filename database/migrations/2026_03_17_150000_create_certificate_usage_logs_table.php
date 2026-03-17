<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->onDelete('cascade');
            $table->string('event_type', 64); // 'validation', 'key_validation', 'credentials'
            $table->string('site', 500)->nullable(); // servicio slug, URL o nombre del sitio
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['certificate_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_usage_logs');
    }
};
