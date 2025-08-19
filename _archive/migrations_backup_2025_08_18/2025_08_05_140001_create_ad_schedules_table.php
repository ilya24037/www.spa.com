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
        Schema::create('ad_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade');
            $table->json('schedule')->nullable();
            $table->text('schedule_notes')->nullable();
            $table->json('working_days')->nullable();
            $table->json('working_hours')->nullable();
            $table->timestamps();
            
            $table->unique('ad_id');
        });

        // Перенос данных из основной таблицы (только если таблица ads существует)
        if (Schema::hasTable('ads')) {
            DB::statement("
                INSERT INTO ad_schedules (ad_id, schedule, schedule_notes, created_at, updated_at)
                SELECT id, schedule, schedule_notes, created_at, updated_at
                FROM ads
                WHERE schedule IS NOT NULL OR schedule_notes IS NOT NULL
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возврат данных в основную таблицу (только если таблица ads существует)
        if (Schema::hasTable('ads')) {
            DB::statement("
                UPDATE ads a
                JOIN ad_schedules s ON a.id = s.ad_id
                SET a.schedule = s.schedule,
                    a.schedule_notes = s.schedule_notes
            ");
        }

        Schema::dropIfExists('ad_schedules');
    }
};