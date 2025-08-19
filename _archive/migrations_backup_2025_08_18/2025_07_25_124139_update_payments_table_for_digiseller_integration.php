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
        Schema::table('payments', function (Blueprint $table) {
            // Добавляем новые поля для DigiSeller интеграции
            
            // ID в внешних системах (DigiSeller, WebMoney)
            $table->string('external_payment_id')->nullable()->after('payment_id');
            
            // Система скидок
            $table->decimal('discount_amount', 10, 2)->default(0)->after('amount');
            $table->decimal('final_amount', 10, 2)->after('discount_amount');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('final_amount');
            $table->string('promo_code')->nullable()->after('discount_percent');
            
            // Расширяем список способов оплаты
            $table->dropColumn('payment_method');
        });
        
        // Добавляем новый enum с большим списком методов
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', [
                'webmoney',        // WebMoney
                'bank_card',       // Банковские карты  
                'bitcoin',         // Bitcoin
                'ethereum',        // Ethereum
                'qiwi',           // QIWI
                'yandex_money',   // Яндекс.Деньги
                'activation_code', // Активационный код
                'balance',        // С баланса пользователя
                'card',           // Старый формат для совместимости
                'qr',             // QR код
                'sbp'             // СБП
            ])->after('status');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            // Тип покупки
            $table->enum('purchase_type', [
                'ad_placement',    // Размещение объявления
                'ad_promotion',    // Продвижение объявления  
                'balance_top_up',  // Пополнение баланса
                'premium_plan',    // Премиум план
                'featured_ad'      // Выделение объявления
            ])->default('ad_placement')->after('payment_method');
            
            // Данные активационного кода
            $table->string('activation_code')->nullable()->unique()->after('metadata');
            $table->boolean('activation_code_used')->default(false)->after('activation_code');
            
            // Новые статусы
            $table->dropColumn('status');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', [
                'pending',      // Ожидает оплаты
                'processing',   // Обрабатывается
                'completed',    // Завершен успешно
                'failed',       // Ошибка
                'refunded',     // Возвращен  
                'cancelled'     // Отменен
            ])->default('pending')->after('currency');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            // Новые индексы
            $table->index(['payment_method', 'status']);
            $table->index(['purchase_type', 'created_at']);
            $table->index('external_payment_id');
            $table->index('activation_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Откатываем изменения
            $table->dropColumn([
                'external_payment_id',
                'discount_amount', 
                'final_amount',
                'discount_percent',
                'promo_code',
                'purchase_type',
                'activation_code',
                'activation_code_used'
            ]);
            
            $table->dropIndex(['payments_payment_method_status_index']);
            $table->dropIndex(['payments_purchase_type_created_at_index']); 
            $table->dropIndex(['payments_external_payment_id_index']);
            $table->dropIndex(['payments_activation_code_index']);
        });
    }
};
