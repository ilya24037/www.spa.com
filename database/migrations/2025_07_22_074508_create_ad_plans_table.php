<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название плана (7 дней, 14 дней, 30 дней)
            $table->integer('days'); // Количество дней
            $table->decimal('price', 10, 2); // Цена плана
            $table->string('description')->nullable(); // Описание плана
            $table->boolean('is_popular')->default(false); // Популярный план
            $table->boolean('is_active')->default(true); // Активен ли план
            $table->integer('sort_order')->default(0); // Порядок сортировки
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_plans');
    }
};
