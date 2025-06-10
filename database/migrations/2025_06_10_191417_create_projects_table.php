<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Владелец проекта
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])->default('planning');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->decimal('spent_budget', 10, 2)->default(0);
            $table->integer('progress')->default(0); // Автоматически вычисляемый прогресс 0-100
            $table->integer('health_score')->default(100); // Здоровье проекта 0-100
            $table->json('settings')->nullable(); // Настройки проекта
            $table->json('metadata')->nullable(); // Дополнительные данные
            $table->timestamps();
            
            // Индексы
            $table->index('status');
            $table->index('user_id');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};