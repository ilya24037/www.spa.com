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
        Schema::create('work_zones', function (Blueprint $table) {
            $table->id();
            
            // Связь с мастером
            $table->foreignId('master_profile_id')->constrained()->onDelete('cascade');
            
            // Локация (как на Avito - по районам)
            $table->string('city')->default('Москва');
            $table->string('district'); // Район
            $table->string('metro_station')->nullable(); // Станция метро
            
            // Условия выезда
            $table->decimal('extra_charge', 8, 2)->default(0); // Доплата за выезд
            $table->enum('extra_charge_type', ['fixed', 'percentage'])->default('fixed'); // Тип доплаты
            $table->integer('min_order_amount')->default(0); // Минимальная сумма заказа
            $table->integer('max_distance_km')->nullable(); // Максимальное расстояние
            
            // Время работы в районе
            $table->time('work_from')->default('09:00'); // Работаю с
            $table->time('work_to')->default('21:00'); // Работаю до
            $table->json('work_days')->nullable(); // Дни недели [1,2,3,4,5]
            
            // Активность
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Приоритет района
            
            $table->timestamps();
            
            // Индексы для поиска
            $table->index(['master_profile_id', 'is_active']);
            $table->index(['city', 'district']);
            $table->index('metro_station');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_zones');
    }
};