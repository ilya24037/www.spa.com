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
        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            
            // Связь с мастером
            $table->foreignId('master_profile_id')->constrained()->onDelete('cascade');
            
            // Даты исключения
            $table->date('date'); // Конкретная дата
            $table->date('date_from')->nullable(); // Период с
            $table->date('date_to')->nullable(); // Период до
            
            // Тип исключения
            $table->enum('type', [
                'holiday',      // Праздничный день
                'vacation',     // Отпуск
                'sick_leave',   // Больничный
                'day_off',      // Выходной
                'busy',         // Занят
                'special'       // Особый график
            ]);
            
            // Специальное расписание на эту дату
            $table->time('start_time')->nullable(); // Если работает, то с
            $table->time('end_time')->nullable(); // Если работает, то до
            $table->boolean('is_working')->default(false); // Работает в этот день
            
            // Причина/комментарий
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Индексы
            $table->index(['master_profile_id', 'date']);
            $table->index(['master_profile_id', 'type']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_exceptions');
    }
};