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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 50)->unique();
            
            // Связи
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Полиморфная связь
            $table->morphs('transactionable');
            
            // Основные поля
            $table->string('type', 20);
            $table->string('direction', 10);
            $table->string('status', 20)->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('RUB');
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->string('description');
            
            // Платежная информация
            $table->string('gateway', 50)->nullable();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->json('gateway_response')->nullable();
            
            // Баланс
            $table->decimal('balance_before', 10, 2)->nullable();
            $table->decimal('balance_after', 10, 2)->nullable();
            
            // Даты
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // Дополнительно
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['direction', 'status']);
            $table->index(['payment_id']);
            $table->index(['subscription_id']);
            $table->index(['gateway', 'gateway_transaction_id']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};