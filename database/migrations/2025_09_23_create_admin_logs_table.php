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
        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('action'); // approve, reject, block, edit, delete, bulk_action
            $table->string('model_type'); // Ad, User, MasterProfile, etc.
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable(); // Старые значения для отслеживания изменений
            $table->json('new_values')->nullable(); // Новые значения
            $table->json('metadata')->nullable(); // Дополнительная информация (IP, user agent, etc.)
            $table->text('description')->nullable(); // Описание действия
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Индексы для быстрого поиска
            $table->index(['admin_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
    }
};