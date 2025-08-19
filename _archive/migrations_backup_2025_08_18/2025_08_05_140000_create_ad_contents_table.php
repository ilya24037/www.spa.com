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
        Schema::create('ad_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('specialty')->nullable();
            $table->text('additional_features')->nullable();
            $table->text('services_additional_info')->nullable();
            $table->text('schedule_notes')->nullable();
            $table->timestamps();
            
            $table->unique('ad_id');
            $table->fullText(['title', 'description']);
        });

        // Перенос данных из основной таблицы (только если таблица ads существует)
        if (Schema::hasTable('ads')) {
            DB::statement("
                INSERT INTO ad_contents (ad_id, title, description, specialty, additional_features, created_at, updated_at)
                SELECT id, title, description, specialty, additional_features, created_at, updated_at
                FROM ads
                WHERE title IS NOT NULL AND description IS NOT NULL
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
                JOIN ad_contents c ON a.id = c.ad_id
                SET a.title = c.title,
                    a.description = c.description,
                    a.specialty = c.specialty,
                    a.additional_features = c.additional_features
            ");
        }

        Schema::dropIfExists('ad_contents');
    }
};