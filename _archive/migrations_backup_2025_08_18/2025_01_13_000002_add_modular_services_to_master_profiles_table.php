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
            // Проверяем, не существует ли уже поле services_additional_info
            if (!Schema::hasColumn('master_profiles', 'services_additional_info')) {
                $table->text('services_additional_info')->nullable()->comment('Дополнительная информация об услугах');
            }
        });
        
        // Отдельно проверяем поле services (оно уже должно существовать)
        if (!Schema::hasColumn('master_profiles', 'services')) {
            Schema::table('master_profiles', function (Blueprint $table) {
                $table->json('services')->nullable()->comment('Модульные услуги мастера (JSON структура по категориям)');
            });
        }
        
        // Примечание: Индекс на TEXT поле без длины не создаем
        // Для полнотекстового поиска используем FULLTEXT индекс отдельно если нужно
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
            // Удаляем только поле services_additional_info если оно существует
            if (Schema::hasColumn('master_profiles', 'services_additional_info')) {
                $table->dropColumn('services_additional_info');
            }
            
            // Поле services не удаляем - оно может использоваться в других местах
        });
        }
    }
};