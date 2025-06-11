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
        Schema::create('master_subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Связи
            $table->foreignId('master_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_plan_id')->constrained()->onDelete('restrict');
            
            // Период подписки
            $table->enum('billing_period', ['monthly', 'quarterly', 'yearly']);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_trial')->default(false); // Пробный период
            
            // Статус подписки
            $table->enum('status', [
                'active',       // Активна
                'expired',      // Истекла
                'cancelled',    // Отменена
                'suspended',    // Приостановлена
                'pending'       // Ожидает оплаты
            ])->default('pending');
            
            // Оплата
            $table->decimal('amount_paid', 10, 2); // Сумма оплаты
            $table->string('payment_method')->nullable(); // Способ оплаты
            $table->string('transaction_id')->nullable(); // ID транзакции
            $table->timestamp('paid_at')->nullable();
            
            // Автопродление
            $table->boolean('auto_renewal')->default(true);
            $table->timestamp('next_payment_date')->nullable();
            $table->integer('failed_payment_attempts')->default(0);
            
            // История использования
            $table->integer('bookings_used')->default(0); // Использовано бронирований
            $table->integer('boosts_used')->default(0); // Использовано поднятий
            $table->integer('highlights_used')->default(0); // Использовано выделений
            
            // Отмена
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            
            // Промокод
            $table->string('promo_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            
            $table->timestamps();
            
            // Индексы
            $table->index(['master_profile_id', 'status']);
            $table->index(['status', 'end_date']);
            $table->index('end_date');
            $table->index('next_payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_subscriptions');
    }
};