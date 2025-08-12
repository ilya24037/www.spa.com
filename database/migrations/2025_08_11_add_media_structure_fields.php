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
        // Добавляем поле для хранения структурированных путей к медиа
        Schema::table('ads', function (Blueprint $table) {
            $table->string('user_folder')->nullable()->after('user_id')
                ->comment('Папка пользователя для медиа файлов');
            $table->json('media_paths')->nullable()->after('video')
                ->comment('Структурированные пути к медиа файлам');
        });

        // Добавляем индекс для быстрого поиска
        Schema::table('ads', function (Blueprint $table) {
            $table->index('user_folder');
        });

        // Таблица для отслеживания медиа файлов
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type', 20); // photo, video, document
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->json('variants')->nullable(); // Разные размеры изображений
            $table->json('metadata')->nullable(); // EXIF, размеры и т.д.
            $table->string('hash', 64)->nullable(); // SHA256 для дедупликации
            $table->boolean('is_processed')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'ad_id']);
            $table->index('type');
            $table->index('hash');
            $table->index('created_at');
        });

        // Таблица для версионирования (если нужно хранить историю)
        Schema::create('media_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained()->onDelete('cascade');
            $table->integer('version');
            $table->string('path');
            $table->unsignedBigInteger('size');
            $table->json('changes')->nullable();
            $table->timestamps();
            
            $table->unique(['media_file_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex(['user_folder']);
            $table->dropColumn(['user_folder', 'media_paths']);
        });
        
        Schema::dropIfExists('media_versions');
        Schema::dropIfExists('media_files');
    }
};