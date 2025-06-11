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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            // Связи
            $table->foreignId('master_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('massage_category_id')->constrained()->onDelete('restrict');
            
            // Основная информация
            $table->string('name'); // Название услуги
            $table->text('description')->nullable(); // Подробное описание
            $table->integer('duration_minutes'); // Продолжительность в минутах
            
            // Цены (как на Avito - гибкая система)
            $table->decimal('price', 10, 2); // Основная цена
            $table->decimal('price_home', 10, 2)->nullable(); // Цена с выездом
            $table->decimal('price_sale', 10, 2)->nullable(); // Цена со скидкой
            $table->integer('sale_percentage')->default(0); // Процент скидки
            $table->timestamp('sale_until')->nullable(); // Скидка действует до
            
            // Дополнительные опции
            $table->boolean('is_complex')->default(false); // Комплексная услуга
            $table->json('included_services')->nullable(); // Что входит в комплекс
            $table->json('contraindications')->nullable(); // Противопоказания
            $table->json('preparation')->nullable(); // Подготовка к процедуре
            
            // Статистика (как на Ozon)
            $table->integer('bookings_count')->default(0); // Количество бронирований
            $table->decimal('rating', 3, 2)->default(0.00); // Рейтинг услуги
            $table->integer('views_count')->default(0); // Просмотры
            
            // Статус и модерация
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->boolean('is_featured')->default(false); // Рекомендуемая услуга
            $table->boolean('is_new')->default(true); // Новая услуга (для бейджа)
            
            // SEO
            $table->string('slug')->unique(); // URL услуги
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            // Настройки бронирования
            $table->boolean('instant_booking')->default(true); // Мгновенное бронирование
            $table->integer('advance_booking_hours')->default(2); // За сколько часов можно забронировать
            $table->integer('cancellation_hours')->default(24); // За сколько часов можно отменить
            
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['master_profile_id', 'status']);
            $table->index('massage_category_id');
            $table->index('price');
            $table->index('rating');
            $table->index('bookings_count');
            $table->index('is_featured');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};