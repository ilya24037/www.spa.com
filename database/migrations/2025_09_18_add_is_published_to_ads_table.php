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
        Schema::table('ads', function (Blueprint $table) {
            // Добавляем поле is_published для системы модерации
            $table->boolean('is_published')->default(false)->after('status')
                ->comment('Флаг публикации после модерации: false = на модерации, true = опубликовано');

            // Добавляем поле для времени модерации
            $table->timestamp('moderated_at')->nullable()->after('is_published')
                ->comment('Время прохождения модерации');

            // Добавляем индекс для быстрого поиска опубликованных объявлений
            $table->index(['status', 'is_published'], 'idx_ads_visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex('idx_ads_visibility');
            $table->dropColumn(['is_published', 'moderated_at']);
        });
    }
};