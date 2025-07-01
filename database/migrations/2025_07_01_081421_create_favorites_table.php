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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            
            // 🔥 ДОБАВЬТЕ ЭТИ ПОЛЯ
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('ID пользователя, который добавил в избранное');
                
            $table->foreignId('master_profile_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('ID профиля мастера');
            
            // Уникальный индекс, чтобы нельзя было добавить одного мастера дважды
            $table->unique(['user_id', 'master_profile_id']);
            
            // Индексы для быстрого поиска
            $table->index('user_id');
            $table->index('master_profile_id');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};