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
            $table->foreignId('master_profile_id')
                  ->constrained('master_profiles')
                  ->onDelete('cascade');
            
            // План и статус
            $table->string('plan', 20)->default('free');
            $table->string('status', 20)->default('pending');
            
            // Финансы
            $table->integer('price')->default(0);
            $table->integer('period_months')->default(1);
            
            // Даты
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            
            // Настройки
            $table->boolean('auto_renew')->default(true);
            
            // Платежная информация
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id', 100)->nullable();
            
            // Дополнительные данные
            $table->json('metadata')->nullable();
            
            // Отмена
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index(['master_profile_id', 'status']);
            $table->index('status');
            $table->index('plan');
            $table->index('end_date');
            $table->index(['status', 'end_date']);
            $table->index('trial_ends_at');
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