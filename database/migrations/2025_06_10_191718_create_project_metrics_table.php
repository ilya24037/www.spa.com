<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('tasks_total')->default(0);
            $table->integer('tasks_completed')->default(0);
            $table->integer('tasks_in_progress')->default(0);
            $table->integer('tasks_overdue')->default(0);
            $table->decimal('budget_spent_percentage', 5, 2)->default(0);
            $table->decimal('time_spent_percentage', 5, 2)->default(0);
            $table->integer('team_velocity')->default(0); // Задач в день
            $table->integer('health_score')->default(100);
            $table->json('custom_metrics')->nullable();
            $table->timestamps();
            
            // Уникальный индекс на проект и дату
            $table->unique(['project_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_metrics');
    }
};