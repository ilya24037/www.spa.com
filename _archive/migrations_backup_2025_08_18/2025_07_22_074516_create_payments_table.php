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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_plan_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_id')->unique(); // ID платежа в системе
            $table->decimal('amount', 10, 2); // Сумма платежа
            $table->string('currency', 3)->default('RUB'); // Валюта
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->enum('payment_method', ['card', 'qr', 'sbp'])->nullable(); // Способ оплаты
            $table->string('description')->nullable(); // Описание платежа
            $table->json('metadata')->nullable(); // Дополнительные данные
            $table->timestamp('paid_at')->nullable(); // Когда оплачен
            $table->timestamp('expires_at')->nullable(); // Когда истекает оплата
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['user_id', 'status']);
            $table->index(['ad_id', 'status']);
            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
