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
            // Добавляем поля для медиа
            $table->json('photos')->nullable()->after('contact_method')->comment('Массив фотографий объявления');
            $table->json('video')->nullable()->after('photos')->comment('Видео объявления');
            
            // Добавляем настройки медиа
            $table->boolean('show_photos_in_gallery')->default(true)->after('video')->comment('Показывать фото в галерее');
            $table->boolean('allow_download_photos')->default(false)->after('show_photos_in_gallery')->comment('Разрешить скачивание фото');
            $table->boolean('watermark_photos')->default(true)->after('allow_download_photos')->comment('Добавлять водяной знак');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn([
                'photos',
                'video', 
                'show_photos_in_gallery',
                'allow_download_photos',
                'watermark_photos'
            ]);
        });
    }
};
