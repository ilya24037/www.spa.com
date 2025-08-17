<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Добавляем индексы для оптимизации производительности
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Индекс для фильтрации по статусу
            $table->index('status', 'idx_status');
            
            // Составной индекс для запросов пользователя по статусу
            $table->index(['user_id', 'status'], 'idx_user_status');
            
            // Индекс для сортировки по дате создания
            $table->index('created_at', 'idx_created');
            
            // Индекс для активных объявлений с датой истечения
            $table->index(['status', 'expires_at'], 'idx_status_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex('idx_status');
            $table->dropIndex('idx_user_status');
            $table->dropIndex('idx_created');
            $table->dropIndex('idx_status_expires');
        });
    }
};
