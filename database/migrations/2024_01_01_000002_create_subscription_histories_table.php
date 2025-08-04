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
        Schema::create('subscription_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_subscription_id')
                  ->constrained('master_subscriptions')
                  ->onDelete('cascade');
            
            // Действие и описание
            $table->string('action', 50);
            $table->text('description')->nullable();
            
            // Состояние на момент действия
            $table->string('plan', 20);
            $table->string('status', 20);
            
            // Дополнительные данные
            $table->json('metadata')->nullable();
            
            // Только created_at
            $table->timestamp('created_at');
            
            // Индексы
            $table->index('master_subscription_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_histories');
    }
};