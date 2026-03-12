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
        Schema::table('certificates', function (Blueprint $table) {
            $table->text('x509_certificate')->nullable()->after('certificate_key'); // Certificado X.509 en formato PEM
            $table->text('private_key')->nullable()->after('x509_certificate'); // Clave privada encriptada
            $table->string('common_name')->nullable()->after('private_key'); // CN del certificado
            $table->string('organization')->nullable()->after('common_name'); // Organización
            $table->string('organizational_unit')->nullable()->after('organization'); // Unidad organizacional
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['x509_certificate', 'private_key', 'common_name', 'organization', 'organizational_unit']);
        });
    }
};
