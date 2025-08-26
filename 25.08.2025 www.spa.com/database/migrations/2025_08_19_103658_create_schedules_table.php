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
            $table->unsignedBigInteger('master_profile_id');
            $table->tinyInteger('day_of_week')->comment('1-7 (Понедельник-Воскресенье)');
            $table->time('start_time')->nullable()->comment('Время начала работы');
            $table->time('end_time')->nullable()->comment('Время окончания работы');
            $table->boolean('is_working_day')->default(true)->comment('Рабочий день');
            $table->time('break_start')->nullable()->comment('Начало перерыва');
            $table->time('break_end')->nullable()->comment('Конец перерыва');
            $table->integer('slot_duration')->default(60)->comment('Длительность слота в минутах');
            $table->integer('buffer_time')->default(15)->comment('Буферное время между слотами в минутах');
            $table->boolean('is_flexible')->default(false)->comment('Гибкий график');
            $table->text('notes')->nullable()->comment('Заметки');
            $table->timestamps();
            
            // Индексы
            $table->index('master_profile_id');
            $table->index('day_of_week');
            $table->index('is_working_day');
            $table->index(['master_profile_id', 'day_of_week']);
            
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
        Schema::dropIfExists('schedules');
    }
};
