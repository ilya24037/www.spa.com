<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('mediable_type', 50); // 'ad', 'master', 'review'
            $table->unsignedBigInteger('mediable_id');
            
            // Информация о файле
            $table->enum('type', ['photo', 'video'])->default('photo');
            $table->string('filename');
            $table->string('path', 500);
            $table->string('url', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            
            // Метаданные
            $table->string('mime_type', 100)->nullable();
            $table->integer('size_bytes')->nullable();
            $table->smallInteger('width')->nullable();
            $table->smallInteger('height')->nullable();
            $table->integer('duration_seconds')->nullable(); // для видео
            
            // Настройки
            $table->smallInteger('position')->default(0);
            $table->boolean('is_main')->default(false);
            $table->boolean('is_verified')->default(false);
            
            $table->timestamps();
            
            $table->index(['mediable_type', 'mediable_id']);
            $table->index('position');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};