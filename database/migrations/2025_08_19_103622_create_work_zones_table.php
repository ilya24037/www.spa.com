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
            $table->unsignedBigInteger('master_profile_id');
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('metro_station', 100)->nullable();
            $table->decimal('extra_charge', 10, 2)->nullable()->comment('Доплата за выезд');
            $table->enum('extra_charge_type', ['fixed', 'percentage'])->default('fixed')->comment('Тип доплаты');
            $table->integer('min_order_amount')->nullable()->comment('Минимальная сумма заказа');
            $table->integer('max_distance_km')->nullable()->comment('Максимальное расстояние в км');
            $table->time('work_from')->nullable()->comment('Время начала работы');
            $table->time('work_to')->nullable()->comment('Время окончания работы');
            $table->json('work_days')->nullable()->comment('Дни недели работы');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0)->comment('Приоритет зоны');
            $table->timestamps();
            
            // Индексы
            $table->index('master_profile_id');
            $table->index('city');
            $table->index('district');
            $table->index('metro_station');
            $table->index('is_active');
            $table->index('priority');
            
            // Внешний ключ
            $table->foreign('master_profile_id')
                  ->references('id')
                  ->on('master_profiles')
                  ->onDelete('cascade');
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
