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
        Schema::create('user_balances', function (Blueprint $table) {
            $table->id();
            
            // Связь с пользователем
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Баланс в разных валютах (как в DigiSeller)
            $table->decimal('rub_balance', 12, 2)->default(0); // Рубли
            $table->decimal('usd_balance', 12, 2)->default(0); // Доллары
            $table->decimal('eur_balance', 12, 2)->default(0); // Евро
            
            // Бонусный баланс (промо-коды, кэшбек)
            $table->decimal('bonus_balance', 12, 2)->default(0);
            
            // Замороженные средства (pending платежи)
            $table->decimal('frozen_balance', 12, 2)->default(0);
            
            // Статистика пополнений
            $table->decimal('total_deposited', 12, 2)->default(0); // Всего пополнено
            $table->decimal('total_spent', 12, 2)->default(0);     // Всего потрачено
            $table->integer('deposits_count')->default(0);        // Количество пополнений
            
            // Система лояльности (как скидки в DigiSeller)
            $table->decimal('loyalty_discount_percent', 5, 2)->default(0); // Персональная скидка
            $table->enum('loyalty_level', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            
            // Временные метки
            $table->timestamp('last_deposit_at')->nullable();
            $table->timestamp('last_spend_at')->nullable();
            $table->timestamps();
            
            // Уникальный баланс для каждого пользователя
            $table->unique('user_id');
            
            // Индексы
            $table->index(['user_id', 'rub_balance']);
            $table->index('loyalty_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_balances');
    }
};
