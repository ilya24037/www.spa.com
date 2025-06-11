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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            
            // Связь с мастером
            $table->foreignId('master_profile_id')->constrained()->onDelete('cascade');
            
            // День недели (1-7, где 1 - понедельник)
            $table->tinyInteger('day_of_week');
            
            // Рабочее время
            $table->time('start_time'); // Начало работы
            $table->time('end_time'); // Конец работы
            $table->boolean('is_working_day')->default(true); // Рабочий день
            
            // Перерывы (как на Ozon - учитываем обед)
            $table->time('break_start')->nullable(); // Начало перерыва
            $table->time('break_end')->nullable(); // Конец перерыва
            
            // Специальные условия
            $table->boolean('is_flexible')->default(false); // Гибкий график
            $table->text('notes')->nullable(); // Примечания (например, "только по записи")
            
            // Слоты для бронирования
            $table->integer('slot_duration')->default(60); // Длительность слота в минутах
            $table->integer('slots_available')->default(8); // Количество слотов в день
            
            // Буферное время между клиентами
            $table->integer('buffer_time')->default(15); // Минут между клиентами
            
            $table->timestamps();
            
            // Уникальный индекс - один день недели на мастера
            $table->unique(['master_profile_id', 'day_of_week']);
            
            // Индексы
            $table->index(['master_profile_id', 'is_working_day']);
            $table->index('day_of_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};