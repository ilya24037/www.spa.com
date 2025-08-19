<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Консолидированная миграция: медиа поля и удобства
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Путь к папке пользователя для медиа
            $table->string('user_folder')->nullable()->after('video');
            
            // JSON с путями к медиа файлам (для отслеживания миграции)
            $table->json('media_paths')->nullable()->after('user_folder');
            
            // Удобства и оборудование (из миграции amenities)
            $table->json('amenities')->nullable()->after('media_paths');
            
            // Индекс для быстрого поиска
            $table->index('user_folder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex(['user_folder']);
            $table->dropColumn(['user_folder', 'media_paths', 'amenities']);
        });
    }
};
