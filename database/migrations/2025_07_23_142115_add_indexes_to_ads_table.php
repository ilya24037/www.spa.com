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
            // Индекс для быстрого поиска по пользователю и статусу
            $table->index(['user_id', 'status'], 'ads_user_status_index');
            
            // Индекс для сортировки по дате создания
            $table->index(['user_id', 'status', 'created_at'], 'ads_user_status_created_index');
            
            // Индекс для поиска по статусу
            $table->index('status', 'ads_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex('ads_user_status_index');
            $table->dropIndex('ads_user_status_created_index');
            $table->dropIndex('ads_status_index');
        });
    }
};
