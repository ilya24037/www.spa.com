<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            // === ОСНОВНЫЕ ПОЛЯ ===
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('category')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('specialty')->nullable();
            
            // === ПАРАМЕТРЫ МАСТЕРА (из формы) ===
            $table->string('age')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->string('breast_size')->nullable();
            $table->string('hair_color')->nullable();
            $table->string('eye_color')->nullable();
            $table->string('nationality')->nullable();
            $table->string('appearance')->nullable();
            
            // === JSON ПОЛЯ ДЛЯ ГИБКОСТИ (как в текущей БД) ===
            $table->json('clients')->nullable(); // ["men", "women", "couples"]
            $table->json('service_provider')->nullable(); // ["women", "trans"]
            $table->json('service_location')->nullable(); // ["incall", "outcall"]
            $table->json('services')->nullable(); // {"massage": ["classic", "thai"]}
            $table->json('features')->nullable(); // ["feature1", "feature2"]
            $table->json('schedule')->nullable(); // {"mon": "10:00-20:00"}
            $table->json('prices')->nullable(); // {"apartments_1h": 5000, "outcall_1h": 7000}
            $table->json('geo')->nullable(); // {"lat": 55.75, "lng": 37.61, "city": "Moscow"}
            $table->json('photos')->nullable(); // ["photo1.jpg", "photo2.jpg"]
            $table->json('video')->nullable(); // ["video1.mp4"]
            
            // === ДОПОЛНИТЕЛЬНЫЕ ТЕКСТОВЫЕ ПОЛЯ ===
            $table->text('services_additional_info')->nullable();
            $table->text('additional_features')->nullable();
            $table->text('schedule_notes')->nullable();
            
            // === РАБОТА И ОПЫТ ===
            $table->string('work_format')->nullable(); // individual/group
            $table->string('experience')->nullable();
            $table->string('education_level')->nullable();
            
            // === ЦЕНЫ И УСЛОВИЯ ===
            $table->decimal('price', 10, 2)->nullable();
            $table->string('price_unit')->nullable(); // hour/session/day
            $table->boolean('is_starting_price')->default(false);
            $table->decimal('price_per_hour', 10, 2)->nullable();
            $table->decimal('outcall_price', 10, 2)->nullable();
            $table->decimal('express_price', 10, 2)->nullable();
            $table->decimal('price_two_hours', 10, 2)->nullable();
            $table->decimal('price_night', 10, 2)->nullable();
            $table->integer('min_duration')->nullable();
            $table->integer('contacts_per_hour')->nullable();
            $table->json('pricing_data')->nullable();
            
            // === КОНТАКТЫ ===
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('telegram')->nullable();
            $table->string('contact_method')->nullable();
            
            // === ЛОКАЦИЯ И ВЫЕЗД ===
            $table->string('address')->nullable();
            $table->string('travel_area')->nullable();
            $table->json('custom_travel_areas')->nullable();
            $table->string('travel_radius')->nullable();
            $table->decimal('travel_price', 10, 2)->nullable();
            $table->string('travel_price_type')->nullable();
            
            // === ПРОМО И СКИДКИ ===
            $table->string('discount')->nullable();
            $table->string('new_client_discount')->nullable();
            $table->text('gift')->nullable();
            
            // === ДОПОЛНИТЕЛЬНЫЕ НАСТРОЙКИ ===
            $table->boolean('has_girlfriend')->default(false);
            $table->boolean('online_booking')->default(false);
            
            // === СТАТУСЫ И МОДЕРАЦИЯ ===
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // === СТАТИСТИКА ===
            $table->integer('views_count')->default(0);
            $table->integer('contacts_shown')->default(0);
            $table->integer('favorites_count')->default(0);
            
            $table->timestamps();
            
            // === ИНДЕКСЫ ДЛЯ ПРОИЗВОДИТЕЛЬНОСТИ ===
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('category');
            $table->index('created_at');
            $table->index('views_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};