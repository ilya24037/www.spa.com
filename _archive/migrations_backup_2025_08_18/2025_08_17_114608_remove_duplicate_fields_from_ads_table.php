<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Удаляем дублирующие поля цен и адресов
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Удаляем дублирующие поля цен
            // Оставляем только: prices (JSON), price_unit, is_starting_price
            $table->dropColumn([
                'price',              // дублирует prices
                'price_per_hour',     // дублирует prices.apartments_1h
                'outcall_price',      // дублирует prices.outcall_1h
                'express_price',      // дублирует prices.apartments_express
                'price_two_hours',    // дублирует prices.apartments_2h
                'price_night',        // дублирует prices.apartments_night
            ]);
            
            // Удаляем дублирующие поля адресов
            // Оставляем только: address, geo
            $table->dropColumn([
                'service_location',   // дублирует geo/address
                'outcall_locations',  // дублирует travel_area
                'travel_area',        // можно хранить в geo
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Восстанавливаем поля цен
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('price_per_hour', 10, 2)->nullable();
            $table->decimal('outcall_price', 10, 2)->nullable();
            $table->decimal('express_price', 10, 2)->nullable();
            $table->decimal('price_two_hours', 10, 2)->nullable();
            $table->decimal('price_night', 10, 2)->nullable();
            
            // Восстанавливаем поля адресов
            $table->json('service_location')->nullable();
            $table->json('outcall_locations')->nullable();
            $table->string('travel_area')->nullable();
        });
    }
};