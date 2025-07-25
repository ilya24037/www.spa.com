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
        // Добавляем новые поля в таблицу services для расширенного функционала
        Schema::table('services', function (Blueprint $table) {
            $table->string('category_type')->default('massage'); // massage, erotic, bdsm, additional
            $table->boolean('is_adult_only')->default(false);
            $table->integer('age_restriction')->nullable(); // минимальный возраст
            $table->decimal('additional_cost', 8, 2)->nullable(); // доплата к базовой цене
            $table->json('restrictions')->nullable(); // ограничения и требования
            $table->json('tags')->nullable(); // теги для группировки
            $table->string('intensity_level')->nullable(); // level: soft, medium, hard
            $table->boolean('requires_experience')->default(false);
            $table->text('preparation_required')->nullable(); // подготовка клиента
        });

        // Создаем таблицу для детализированных категорий услуг
        Schema::create('extended_service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type'); // sex, bdsm, massage, additional
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_adult_only')->default(false);
            $table->integer('min_age')->default(18);
            $table->decimal('base_additional_cost', 8, 2)->default(0);
            $table->json('default_restrictions')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable(); // для UI группировки
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('sort_order');
        });

        // Связь между услугами и расширенными категориями
        Schema::create('service_extended_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('extended_category_id')->constrained('extended_service_categories')->onDelete('cascade');
            $table->decimal('custom_additional_cost', 8, 2)->nullable(); // персональная доплата
            $table->json('custom_restrictions')->nullable(); // персональные ограничения
            $table->timestamps();
            
            $table->unique(['service_id', 'extended_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_extended_categories');
        Schema::dropIfExists('extended_service_categories');
        
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'category_type',
                'is_adult_only',
                'age_restriction',
                'additional_cost',
                'restrictions',
                'tags',
                'intensity_level',
                'requires_experience',
                'preparation_required'
            ]);
        });
    }
};