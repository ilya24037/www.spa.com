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
        Schema::create('review_reactions', function (Blueprint $table) {
            $table->id();
            
            // Связи
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Тип реакции
            $table->enum('type', ['helpful', 'not_helpful']);
            
            $table->timestamps();
            
            // Уникальный индекс - один пользователь = одна реакция на отзыв
            $table->unique(['review_id', 'user_id']);
            
            // Индексы
            $table->index(['review_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_reactions');
    }
};