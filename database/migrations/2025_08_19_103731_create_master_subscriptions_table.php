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
            $table->unsignedBigInteger('master_profile_id');
            $table->enum('plan', ['basic', 'premium', 'vip'])->default('basic');
            $table->enum('status', ['pending', 'active', 'cancelled', 'expired'])->default('pending');
            $table->integer('price')->comment('Цена в копейках');
            $table->integer('period_months')->default(1)->comment('Период в месяцах');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('trial_ends_at')->nullable()->comment('Дата окончания пробного периода');
            $table->boolean('auto_renew')->default(true)->comment('Автопродление');
            $table->string('payment_method', 100)->nullable()->comment('Способ оплаты');
            $table->string('transaction_id', 100)->nullable()->comment('ID транзакции');
            $table->json('metadata')->nullable()->comment('Дополнительные данные');
            $table->timestamp('cancelled_at')->nullable()->comment('Дата отмены');
            $table->string('cancellation_reason', 255)->nullable()->comment('Причина отмены');
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('master_profile_id');
            $table->index('plan');
            $table->index('status');
            $table->index('end_date');
            $table->index(['master_profile_id', 'status']);
            
            // Внешний ключ
            $table->foreign('master_profile_id')
                  ->references('id')
                  ->on('master_profiles')
                  ->onDelete('cascade');
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
