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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')
                ->constrained('ads')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->text('reason');
            $table->text('resolution_note')->nullable();
            $table->enum('status', ['pending', 'resolved', 'rejected'])
                ->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Индексы для производительности
            $table->index(['ad_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index('status');

            // Уникальный индекс: один пользователь - одна жалоба на объявление
            $table->unique(['ad_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};