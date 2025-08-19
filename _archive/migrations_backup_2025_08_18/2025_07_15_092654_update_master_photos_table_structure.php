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
        // Только если таблица master_photos существует
        if (!Schema::hasTable('master_photos')) {
            return;
        }
        
        // Сначала обновляем NULL значения
        DB::table('master_photos')
            ->whereNull('file_size')
            ->update(['file_size' => 0]);
            
        DB::table('master_photos')
            ->whereNull('mime_type')
            ->update(['mime_type' => 'image/jpeg']);

        Schema::table('master_photos', function (Blueprint $table) {
            // Добавляем недостающие колонки (только если их нет)
            if (!Schema::hasColumn('master_photos', 'width')) {
                $table->integer('width')->after('mime_type')->nullable()->comment('Ширина изображения');
            }
            if (!Schema::hasColumn('master_photos', 'height')) {
                $table->integer('height')->after('width')->nullable()->comment('Высота изображения');
            }
            if (!Schema::hasColumn('master_photos', 'sort_order')) {
                $table->integer('sort_order')->after('is_main')->default(0)->comment('Порядок сортировки');
            }
            if (!Schema::hasColumn('master_photos', 'is_approved')) {
                $table->boolean('is_approved')->after('sort_order')->default(false)->comment('Одобрено модератором');
            }
            
            // Обновляем существующие поля (теперь без NULL)
            $table->integer('file_size')->nullable(false)->change();
            $table->string('mime_type')->nullable(false)->change();
            
            // Добавляем новый индекс (только если он еще не существует)
            // Индекс уже создается в миграции 2024_12_19_000000_create_master_media_tables.php
            // Поэтому пропускаем его создание здесь
            // $table->index(['master_profile_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_photos', function (Blueprint $table) {
            // Удаляем добавленные колонки
            $table->dropColumn(['width', 'height', 'sort_order', 'is_approved']);
            
            // Возвращаем nullable для существующих полей
            $table->integer('file_size')->nullable()->change();
            $table->string('mime_type')->nullable()->change();
            
            // Удаляем индекс
            $table->dropIndex(['master_profile_id', 'sort_order']);
        });
    }
}; 