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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            
            // Связи
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('master_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            
            // Рейтинги (как на Ozon - детализированные)
            $table->tinyInteger('rating_overall')->unsigned(); // Общая оценка 1-5
            $table->tinyInteger('rating_quality')->unsigned(); // Качество услуги 1-5
            $table->tinyInteger('rating_punctuality')->unsigned(); // Пунктуальность 1-5
            $table->tinyInteger('rating_communication')->unsigned(); // Общение 1-5
            $table->tinyInteger('rating_price_quality')->unsigned(); // Цена/качество 1-5
            
            // Текст отзыва
            $table->text('comment'); // Основной текст отзыва
            $table->text('pros')->nullable(); // Достоинства
            $table->text('cons')->nullable(); // Недостатки
            
            // Рекомендация (как на Avito)
            $table->boolean('would_recommend')->default(true); // Рекомендует ли
            $table->boolean('would_book_again')->default(true); // Обратится ли снова
            
            // Модерация (как на маркетплейсах)
            $table->enum('status', [
                'pending',      // На модерации
                'approved',     // Одобрен
                'rejected',     // Отклонен
                'hidden'        // Скрыт
            ])->default('pending');
            $table->string('rejection_reason')->nullable(); // Причина отклонения
            $table->timestamp('moderated_at')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users');
            
            // Ответ мастера (как на Ozon)
            $table->text('master_response')->nullable();
            $table->timestamp('master_responded_at')->nullable();
            
            // Полезность отзыва (как на маркетплейсах)
            $table->integer('helpful_count')->default(0); // Полезный отзыв
            $table->integer('not_helpful_count')->default(0); // Бесполезный отзыв
            
            // Верификация
            $table->boolean('is_verified_booking')->default(true); // Подтвержденное бронирование
            $table->boolean('is_anonymous')->default(false); // Анонимный отзыв
            
            // Дополнительные флаги
            $table->boolean('has_photos')->default(false); // Есть фото
            $table->boolean('is_featured')->default(false); // Рекомендуемый отзыв
            
            // SEO
            $table->string('slug')->unique()->nullable();
            
            $table->timestamps();
            
            // Индексы для быстрой фильтрации
            $table->index(['master_profile_id', 'status', 'rating_overall']);
            $table->index(['service_id', 'status']);
            $table->index('status');
            $table->index('rating_overall');
            $table->index('is_featured');
            $table->index('created_at');
            
            // Уникальный индекс - один отзыв на бронирование
            $table->unique('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};