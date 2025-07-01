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
        Schema::create('master_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('ID профиля мастера');
            $table->string('path')
                ->comment('Путь к файлу фото');
            $table->boolean('is_main')
                ->default(false)
                ->comment('Главное фото');
            $table->integer('order')
                ->default(0)
                ->comment('Порядок сортировки');
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['master_profile_id', 'is_main']);
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_photos');
    }
};