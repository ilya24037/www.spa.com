<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['todo', 'in_progress', 'review', 'done', 'blocked'])->default('todo');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->integer('estimated_hours')->nullable();
            $table->integer('actual_hours')->default(0);
            $table->datetime('start_date')->nullable();
            $table->datetime('due_date')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->integer('progress')->default(0); // 0-100
            $table->integer('order')->default(0);
            $table->json('checklist')->nullable(); // Подзадачи/чеклист
            $table->json('tags')->nullable();
            $table->json('dependencies')->nullable(); // ID зависимых задач
            $table->timestamps();
            
            // Индексы
            $table->index(['project_id', 'status']);
            $table->index('assigned_to');
            $table->index('due_date');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};