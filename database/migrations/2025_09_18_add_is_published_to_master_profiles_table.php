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
        Schema::table('master_profiles', function (Blueprint $table) {
            // Добавляем поле is_published для системы модерации
            $table->boolean('is_published')->default(false)->after('status')
                ->comment('Флаг публикации после модерации: false = на модерации, true = опубликовано');

            // Добавляем поле для времени модерации
            $table->timestamp('moderated_at')->nullable()->after('is_published')
                ->comment('Время прохождения модерации');

            // Добавляем индекс для быстрого поиска опубликованных профилей
            $table->index(['status', 'is_published'], 'idx_master_profiles_visibility');
        });

        // Обновляем существующие профили как опубликованные
        \DB::table('master_profiles')
            ->where('status', 'active')
            ->update(['is_published' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_master_profiles_visibility');
            $table->dropColumn(['is_published', 'moderated_at']);
        });
    }
};