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
            $table->integer('age')->nullable()->comment('Возраст мастера');
            $table->integer('height')->nullable()->comment('Рост в сантиметрах');
            $table->integer('weight')->nullable()->comment('Вес в килограммах');
            $table->integer('breast_size')->nullable()->comment('Размер груди');
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
            $table->dropColumn(['age', 'height', 'weight', 'breast_size']);
        });
        }
    }
};