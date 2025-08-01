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
        Schema::table('ads', function (Blueprint $table) {
            // Основные поля цены
            $table->decimal('price_per_hour', 10, 2)->nullable()->comment('Цена за час');
            $table->decimal('outcall_price', 10, 2)->nullable()->comment('Доплата за выезд');
            $table->decimal('express_price', 10, 2)->nullable()->comment('Экспресс услуга');
            $table->decimal('price_two_hours', 10, 2)->nullable()->comment('Цена за 2 часа');
            $table->decimal('price_night', 10, 2)->nullable()->comment('Цена за ночь');
            
            // Минимальная продолжительность (в минутах)
            $table->integer('min_duration')->nullable()->comment('Минимальная продолжительность в минутах');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn([
                'price_per_hour',
                'outcall_price', 
                'express_price',
                'price_two_hours',
                'price_night',
                'min_duration'
            ]);
        });
    }
};
