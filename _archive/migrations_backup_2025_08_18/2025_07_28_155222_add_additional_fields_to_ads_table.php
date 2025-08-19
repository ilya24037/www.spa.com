<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Консолидированная миграция: дополнительные поля профиля
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('education_level', 10)->nullable()->after('experience')->comment('Уровень образования (2-7)');
            $table->json('features')->nullable()->after('education_level')->comment('Особенности мастера (курю, выпиваю, целуюсь и т.д.)');
            $table->text('additional_features')->nullable()->after('features')->comment('Дополнительные особенности (текстовое поле)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['education_level', 'features', 'additional_features']);
        });
    }
};
