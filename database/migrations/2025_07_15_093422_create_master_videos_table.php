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
        Schema::create('master_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained()->onDelete('cascade');
            
            // Простая структура - имена файлов
            $table->string('filename')->comment('Имя файла видео (intro.mp4)');
            $table->string('poster_filename')->comment('Имя файла постера (intro_poster.jpg)');
            
            // Метаданные
            $table->string('mime_type')->comment('MIME тип файла');
            $table->integer('file_size')->comment('Размер файла в байтах');
            $table->integer('duration')->comment('Длительность в секундах');
            $table->integer('width')->comment('Ширина видео');
            $table->integer('height')->comment('Высота видео');
            
            // Настройки
            $table->boolean('is_main')->default(false)->comment('Главное видео');
            $table->integer('sort_order')->default(0)->comment('Порядок сортировки');
            $table->boolean('is_approved')->default(false)->comment('Одобрено модератором');
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->comment('Статус обработки');
            
            $table->timestamps();
            
            // Индексы
            $table->index(['master_profile_id', 'is_main']);
            $table->index(['master_profile_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_videos');
    }
};
