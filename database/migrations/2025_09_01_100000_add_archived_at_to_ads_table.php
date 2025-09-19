<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавление поля archived_at для функции архивации объявлений
     * Безопасная миграция без потери данных
     */
    public function up(): void
    {
        if (!Schema::hasColumn('ads', 'archived_at')) {
            Schema::table('ads', function (Blueprint $table) {
                // Добавляем поле для даты архивации
                $table->timestamp('archived_at')->nullable()->after('expires_at');

                // Индекс для быстрого поиска архивированных объявлений
                $table->index('archived_at');
            });
        }
    }

    /**
     * Откатить изменения
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};