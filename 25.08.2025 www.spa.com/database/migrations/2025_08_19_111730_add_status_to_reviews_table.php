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
        Schema::table('reviews', function (Blueprint $table) {
            // Добавляем поле status для enum ReviewStatus
            $table->enum('status', ['pending', 'approved', 'rejected', 'hidden', 'flagged'])
                  ->default('pending')
                  ->after('rating')
                  ->comment('Статус модерации отзыва');
            
            // Добавляем индекс для быстрого поиска по статусу
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropIndex(['status']);
        });
    }
};
