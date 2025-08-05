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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_id', 50)->unique();
            
            // Связи
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Полиморфная связь (для разных типов подписок)
            $table->morphs('subscribable');
            
            // План подписки
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('plan_name', 100);
            
            // Статус и даты
            $table->string('status', 20)->default('pending');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Цена и период
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('RUB');
            $table->string('interval', 20);
            $table->integer('interval_count')->default(1);
            
            // Функции и лимиты
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->json('usage')->nullable();
            
            // Настройки
            $table->boolean('auto_renew')->default(true);
            $table->string('payment_method', 50)->nullable();
            
            // Платежная информация
            $table->string('gateway', 50)->nullable();
            $table->string('gateway_subscription_id', 100)->nullable();
            $table->string('gateway_customer_id', 100)->nullable();
            $table->json('gateway_data')->nullable();
            
            // Последний платеж
            $table->foreignId('last_payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->timestamp('next_payment_at')->nullable();
            
            // Дополнительно
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index(['user_id', 'status']);
            $table->index(['status', 'ends_at']);
            $table->index(['status', 'next_payment_at']);
            $table->index(['plan_name', 'status']);
            $table->index(['gateway', 'gateway_subscription_id']);
            $table->index(['auto_renew', 'next_payment_at']);
            $table->index('trial_ends_at');
            $table->index('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};