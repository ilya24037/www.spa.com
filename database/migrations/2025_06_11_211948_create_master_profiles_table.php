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
        Schema::create('master_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Основная информация
            $table->string('display_name'); // Имя для отображения
            $table->string('slug')->unique(); // URL-friendly имя (как на Avito)
            $table->text('bio')->nullable(); // О себе
            $table->string('avatar')->nullable(); // Фото профиля
            
            // Контакты
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('telegram')->nullable();
            $table->boolean('show_contacts')->default(false); // Показывать контакты только после бронирования
            
            // Опыт и сертификаты
            $table->integer('experience_years')->default(0);
            $table->json('certificates')->nullable(); // Массив сертификатов
            $table->json('education')->nullable(); // Образование
            
            // Локация (как на Avito)
            $table->string('city')->default('Москва');
            $table->string('district')->nullable(); // Район
            $table->string('metro_station')->nullable(); // Ближайшее метро
            $table->boolean('home_service')->default(true); // Выезд на дом
            $table->boolean('salon_service')->default(false); // Прием в салоне
            $table->string('salon_address')->nullable();
            
            // Рейтинг и статистика (как на Ozon)
            $table->decimal('rating', 3, 2)->default(0.00); // Средний рейтинг
            $table->integer('reviews_count')->default(0);
            $table->integer('completed_bookings')->default(0);
            $table->integer('views_count')->default(0); // Просмотры профиля
            
            // Статус и модерация
            $table->enum('status', ['draft', 'pending', 'active', 'blocked'])->default('draft');
            $table->boolean('is_verified')->default(false); // Верифицированный мастер
            $table->boolean('is_premium')->default(false); // Премиум размещение
            $table->timestamp('premium_until')->nullable();
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index('slug');
            $table->index('city');
            $table->index('district');
            $table->index('status');
            $table->index('rating');
            $table->index('is_premium');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_profiles');
    }
};