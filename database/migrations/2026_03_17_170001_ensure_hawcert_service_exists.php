<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea el servicio HawCert (plataforma principal) si no existe.
     */
    public function up(): void
    {
        $slug = \App\Models\Certificate::HAWCERT_SERVICE_SLUG;
        $exists = DB::table('services')->where('slug', $slug)->exists();
        if (!$exists) {
            DB::table('services')->insert([
                'name' => 'HawCert',
                'slug' => $slug,
                'description' => 'Plataforma principal HawCert',
                'endpoint' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('services')->where('slug', \App\Models\Certificate::HAWCERT_SERVICE_SLUG)->delete();
    }
};
