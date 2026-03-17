<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->string('auth_type', 32)->default('form')->after('website_url_pattern');
        });

        Schema::table('credentials', function (Blueprint $table) {
            $table->text('username_value')->nullable()->change();
            $table->text('password_value')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropColumn('auth_type');
        });

        Schema::table('credentials', function (Blueprint $table) {
            $table->text('username_value')->nullable(false)->change();
            $table->text('password_value')->nullable(false)->change();
        });
    }
};
