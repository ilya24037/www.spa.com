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
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            
            // Основная информация
            $table->string('name'); // Название плана
            $table->string('slug')->unique(); // URL-friendly название
            $table->text('description')->nullable(); // Описание плана
            
            // Типы планов (как на Avito)
            $table->enum('type', [
                'free',         // Бесплатный
                'basic',        // Базовый
                'professional', // Профессиональный
                'premium'       // Премиум
            ]);
            
            // Цены
            $table->decimal('price_monthly', 10, 2)->default(0); // Цена за месяц
            $table->decimal('price_quarterly', 10, 2)->default(0); // Цена за 3 месяца
            $table->decimal('price_yearly', 10, 2)->default(0); // Цена за год
            $table->integer('discount_quarterly')->default(0); // Скидка при оплате за 3 месяца %
            $table->integer('discount_yearly')->default(0); // Скидка при оплате за год %
            
            // Лимиты и возможности
            $table->integer('bookings_limit')->default(-1); // Лимит бронирований в месяц (-1 = безлимит)
            $table->integer('services_limit')->default(-1); // Лимит услуг
            $table->integer('photos_limit')->default(5); // Лимит фотографий
            $table->integer('priority_in_search')->default(0); // Приоритет в поиске (0-100)
            
            // Функции (как на Ozon Premium)
            $table->boolean('has_badge')->default(false); // Бейдж "PRO" или "Premium"
            $table->boolean('has_analytics')->default(false); // Доступ к аналитике
            $table->boolean('has_promotion')->default(false); // Продвижение в топ
            $table->boolean('has_instant_booking')->default(true); // Мгновенное бронирование
            $table->boolean('has_calendar_sync')->default(false); // Синхронизация календаря
            $table->boolean('has_sms_notifications')->default(false); // SMS уведомления
            $table->boolean('has_priority_support')->default(false); // Приоритетная поддержка
            $table->boolean('has_custom_url')->default(false); // Персональный URL
            $table->boolean('has_remove_ads')->default(false); // Убрать рекламу конкурентов
            
            // Промо-возможности
            $table->integer('boost_days_monthly')->default(0); // Дней поднятия в топ в месяц
            $table->integer('highlight_days_monthly')->default(0); // Дней выделения цветом
            
            // Комиссия платформы
            $table->decimal('platform_fee_percentage', 5, 2)->default(20); // Комиссия с заказа %
            $table->decimal('min_platform_fee', 8, 2)->default(100); // Минимальная комиссия
            
            // Статус и сортировка
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false); // Популярный тариф
            $table->integer('sort_order')->default(0);
            
            // Пробный период
            $table->integer('trial_days')->default(0); // Дней бесплатного периода
            
            $table->timestamps();
            
            // Индексы
            $table->index('slug');
            $table->index('type');
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};