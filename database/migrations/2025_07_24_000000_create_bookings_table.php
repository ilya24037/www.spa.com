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
            
            // Основная информация о бронировании
            $table->string('booking_number')->unique()->comment('Уникальный номер бронирования');
            
            // Связи
            $table->unsignedBigInteger('client_id')->nullable()->comment('ID клиента (пользователя)');
            $table->unsignedBigInteger('master_profile_id')->comment('ID мастера');
            $table->unsignedBigInteger('service_id')->comment('ID услуги');
            
            // Время и дата
            $table->date('booking_date')->comment('Дата бронирования');
            $table->time('start_time')->comment('Время начала');
            $table->time('end_time')->comment('Время окончания');
            $table->integer('duration')->comment('Длительность в минутах');
            
            // Адрес (для выездных услуг)
            $table->string('address')->nullable()->comment('Адрес оказания услуги');
            $table->string('address_details')->nullable()->comment('Детали адреса (подъезд, этаж)');
            $table->boolean('is_home_service')->default(false)->comment('Выездная услуга');
            
            // Стоимость
            $table->decimal('service_price', 10, 2)->comment('Стоимость услуги');
            $table->decimal('travel_fee', 10, 2)->default(0)->comment('Стоимость выезда');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('Размер скидки');
            $table->decimal('total_price', 10, 2)->comment('Общая стоимость');
            
            // Оплата
            $table->enum('payment_method', ['cash', 'card', 'online', 'transfer'])->comment('Способ оплаты');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending')->comment('Статус оплаты');
            $table->timestamp('paid_at')->nullable()->comment('Время оплаты');
            
            // Статус
            $table->enum('status', [
                'pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'
            ])->default('pending')->comment('Статус бронирования');
            
            // Данные клиента
            $table->string('client_name')->comment('Имя клиента');
            $table->string('client_phone')->comment('Телефон клиента');
            $table->string('client_email')->nullable()->comment('Email клиента');
            $table->text('client_comment')->nullable()->comment('Комментарий клиента');
            
            // Служебные поля
            $table->timestamp('confirmed_at')->nullable()->comment('Время подтверждения');
            $table->timestamp('cancelled_at')->nullable()->comment('Время отмены');
            $table->string('cancellation_reason')->nullable()->comment('Причина отмены');
            $table->unsignedBigInteger('cancelled_by')->nullable()->comment('Кто отменил');
            
            // Напоминания и отзывы
            $table->boolean('reminder_sent')->default(false)->comment('Напоминание отправлено');
            $table->timestamp('reminder_sent_at')->nullable()->comment('Время отправки напоминания');
            $table->boolean('review_requested')->default(false)->comment('Запрос отзыва отправлен');
            $table->timestamp('review_requested_at')->nullable()->comment('Время запроса отзыва');
            
            // Дополнительные данные
            $table->string('source')->default('website')->comment('Источник бронирования');
            $table->json('extra_data')->nullable()->comment('Дополнительные данные');
            
            $table->timestamps();
            
            // Индексы
            $table->index(['master_profile_id', 'booking_date'], 'bookings_master_date_index');
            $table->index(['client_id', 'status'], 'bookings_client_status_index');
            $table->index('booking_number', 'bookings_number_index');
            $table->index('status', 'bookings_status_index');
            
            // Внешние ключи
            $table->foreign('client_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('master_profile_id')->references('id')->on('master_profiles')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
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