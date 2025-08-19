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
            
            // Связи
            $table->unsignedBigInteger('master_profile_id')->comment('ID мастера');
            
            // День недели (0=воскресенье, 1=понедельник, ..., 6=суббота)
            $table->tinyInteger('day_of_week')->comment('День недели (0-6)');
            
            // Время работы
            $table->time('start_time')->comment('Время начала работы');
            $table->time('end_time')->comment('Время окончания работы');
            
            // Перерыв
            $table->time('break_start')->nullable()->comment('Время начала перерыва');
            $table->time('break_end')->nullable()->comment('Время окончания перерыва');
            
            // Настройки
            $table->boolean('is_working_day')->default(true)->comment('Рабочий день');
            $table->integer('slot_duration')->default(60)->comment('Длительность слота в минутах');
            $table->integer('buffer_time')->default(15)->comment('Время между сеансами в минутах');
            $table->boolean('is_flexible')->default(true)->comment('Гибкое расписание');
            
            // Дополнительно
            $table->text('notes')->nullable()->comment('Заметки к расписанию');
            
            $table->timestamps();
            
            // Индексы
            $table->index(['master_profile_id', 'day_of_week'], 'schedules_master_day_index');
            $table->index(['master_profile_id', 'is_working_day'], 'schedules_master_working_index');
            
            // Внешние ключи
            $table->foreign('master_profile_id')->references('id')->on('master_profiles')->onDelete('cascade');
            
            // Уникальный ключ для предотвращения дублирования
            $table->unique(['master_profile_id', 'day_of_week'], 'schedules_master_day_unique');
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