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
            // JSON поле для всех особенностей (чекбоксы)
            $table->json('features')->nullable()->comment('Особенности мастера (JSON)');
            
            // Медицинская справка
            $table->enum('medical_certificate', ['yes', 'no'])->nullable()->comment('Наличие медицинской справки');
            
            // Работа в критические дни
            $table->enum('works_during_period', ['yes', 'no'])->nullable()->comment('Работает ли в критические дни');
            
            // Дополнительные особенности (свободный текст)
            $table->text('additional_features')->nullable()->comment('Дополнительные особенности (свободный текст)');
            
            // Индексы для поиска
            $table->index(['medical_certificate'], 'master_profiles_medical_certificate_index');
            $table->index(['works_during_period'], 'master_profiles_works_during_period_index');
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
            // Удаляем индексы
            $table->dropIndex('master_profiles_medical_certificate_index');
            $table->dropIndex('master_profiles_works_during_period_index');
            
            // Удаляем поля
            $table->dropColumn([
                'features',
                'medical_certificate',
                'works_during_period',
                'additional_features'
            ]);
        });
        }
    }
};