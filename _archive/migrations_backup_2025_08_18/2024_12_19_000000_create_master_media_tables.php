<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Таблица для фотографий мастеров
        Schema::create('master_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained()->onDelete('cascade');
            
            // Простая структура - только имя файла
            $table->string('filename');          // photo_1.jpg
            
            // Метаданные
            $table->string('mime_type');         // image/jpeg, image/png
            $table->integer('file_size');        // Размер в байтах
            $table->integer('width');            // Ширина оригинала
            $table->integer('height');           // Высота оригинала
            
            // Настройки
            $table->boolean('is_main')->default(false);     // Главное фото
            $table->integer('sort_order')->default(0);      // Порядок сортировки
            $table->boolean('is_approved')->default(false); // Модерация
            
            $table->timestamps();
            
            // Индексы
            $table->index(['master_profile_id', 'is_main']);
            $table->index(['master_profile_id', 'sort_order']);
        });

        // Таблица для видео мастеров
        Schema::create('master_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained()->onDelete('cascade');
            
            // Простая структура - имена файлов
            $table->string('filename');          // intro.mp4
            $table->string('poster_filename');   // intro_poster.jpg
            
            // Метаданные
            $table->string('mime_type');         // video/mp4, video/webm
            $table->integer('file_size');        // Размер в байтах
            $table->integer('duration');         // Длительность в секундах
            $table->integer('width');            // Ширина видео
            $table->integer('height');           // Высота видео
            
            // Настройки
            $table->boolean('is_main')->default(false);     // Главное видео
            $table->integer('sort_order')->default(0);      // Порядок сортировки
            $table->boolean('is_approved')->default(false); // Модерация
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            
            $table->timestamps();
            
            // Индексы
            $table->index(['master_profile_id', 'is_main']);
            $table->index(['master_profile_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_videos');
        Schema::dropIfExists('master_photos');
    }
};