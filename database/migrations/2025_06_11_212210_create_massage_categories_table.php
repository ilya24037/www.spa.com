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
        Schema::create('massage_categories', function (Blueprint $table) {
            $table->id();
            
            // Основная информация
            $table->string('name'); // Название категории
            $table->string('slug')->unique(); // URL-friendly название
            $table->text('description')->nullable(); // Описание категории
            $table->string('icon')->nullable(); // Иконка категории
            $table->string('image')->nullable(); // Изображение категории
            
            // Иерархия категорий (как на Avito)
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('massage_categories')->onDelete('cascade');
            
            // Популярность и сортировка (как на Ozon)
            $table->integer('sort_order')->default(0); // Порядок сортировки
            $table->integer('services_count')->default(0); // Количество услуг в категории
            $table->boolean('is_popular')->default(false); // Популярная категория
            $table->boolean('is_featured')->default(false); // Рекомендуемая категория
            
            // Статус
            $table->boolean('is_active')->default(true);
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            
            // Дополнительные настройки
            $table->json('properties')->nullable(); // Специфичные свойства категории
            $table->decimal('min_price', 10, 2)->default(0); // Минимальная цена в категории
            $table->decimal('avg_price', 10, 2)->default(0); // Средняя цена в категории
            
            $table->timestamps();
            
            // Индексы
            $table->index('slug');
            $table->index('parent_id');
            $table->index('sort_order');
            $table->index('is_active');
            $table->index('is_popular');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('massage_categories');
    }
};