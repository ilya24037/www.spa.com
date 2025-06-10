<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->date('completed_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'delayed'])->default('pending');
            $table->integer('weight')->default(1); // Вес этапа для расчета прогресса
            $table->integer('order')->default(0); // Порядок отображения
            $table->boolean('is_critical')->default(false); // Критический этап
            $table->json('deliverables')->nullable(); // Результаты этапа
            $table->timestamps();
            
            // Индексы
            $table->index(['project_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};