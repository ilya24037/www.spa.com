<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Только если таблица services существует
        if (Schema::hasTable('services')) {
            // Обновляем все услуги, у которых duration_minutes = NULL, устанавливаем 60 минут
            DB::table('services')
                ->whereNull('duration_minutes')
                ->update(['duration_minutes' => 60]);
                
            // Делаем поле обязательным с значением по умолчанию
            Schema::table('services', function (Blueprint $table) {
                if (Schema::hasColumn('services', 'duration_minutes')) {
                    $table->integer('duration_minutes')->default(60)->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Только если таблица services существует
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                if (Schema::hasColumn('services', 'duration_minutes')) {
                    $table->integer('duration_minutes')->nullable()->change();
                }
            });
        }
    }
}; 