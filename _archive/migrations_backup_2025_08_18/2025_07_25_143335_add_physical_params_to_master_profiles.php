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
        // Только если таблица master_profiles существует
        if (Schema::hasTable('master_profiles')) {
            Schema::table('master_profiles', function (Blueprint $table) {
            //
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Только если таблица master_profiles существует
        if (Schema::hasTable('master_profiles')) {
            Schema::table('master_profiles', function (Blueprint $table) {
            //
        });
        }
    }
};