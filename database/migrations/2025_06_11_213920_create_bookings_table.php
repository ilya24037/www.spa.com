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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            
            // Уникальный номер заказа (как на Ozon)
            $table->string('booking_number')->unique(); // Например: BK-2025-000001
            
            // Связи
            $table->foreignId('client_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('master_profile_id')->constrained()->onDelete('restrict');
            $table->foreignId('service_id')->constrained()->onDelete('restrict');
            
            // Дата и время
            $table->date('booking_date'); // Дата бронирования
            $table->time('start_time'); // Время начала
            $table->time('end_time'); // Время окончания
            $table->integer('duration_minutes'); // Продолжительность
            
            // Локация
            $table->enum('location_type', ['home', 'salon'])->default('home'); // Где проводится
            $table->string('address')->nullable(); // Адрес клиента
            $table->string('city')->default('Москва');
            $table->string('district')->nullable();
            $table->string('metro')->nullable();
            $table->text('address_details')->nullable(); // Домофон, этаж и т.д.
            
            // Цены и оплата
            $table->decimal('service_price', 10, 2); // Цена услуги
            $table->decimal('travel_fee', 10, 2)->default(0); // Доплата за выезд
            $table->decimal('discount_amount', 10, 2)->default(0); // Скидка
            $table->decimal('total_price', 10, 2); // Итоговая цена
            $table->enum('payment_method', ['cash', 'card', 'online', 'transfer'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            
            // Статус бронирования (как на Avito)
            $table->enum('status', [
                'pending',      // Ожидает подтверждения
                'confirmed',    // Подтверждено мастером
                'in_progress',  // Выполняется
                'completed',    // Завершено
                'cancelled',    // Отменено
                'no_show'       // Клиент не пришел
            ])->default('pending');
            
            // Контактные данные клиента
            $table->string('client_name');
            $table->string('client_phone');
            $table->string('client_email')->nullable();
            $table->text('client_comment')->nullable(); // Пожелания клиента
            
            // Подтверждение и отмена
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            
            // Напоминания (как на Ozon)
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            
            // Отзыв
            $table->boolean('review_requested')->default(false);
            $table->timestamp('review_requested_at')->nullable();
            
            // Дополнительная информация
            $table->json('extra_data')->nullable(); // Любые дополнительные данные
            $table->string('source')->default('website'); // Источник заказа
            
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index('booking_number');
            $table->index(['client_id', 'status']);
            $table->index(['master_profile_id', 'status']);
            $table->index(['booking_date', 'start_time']);
            $table->index('status');
            $table->index('payment_status');
            $table->index(['master_profile_id', 'booking_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};