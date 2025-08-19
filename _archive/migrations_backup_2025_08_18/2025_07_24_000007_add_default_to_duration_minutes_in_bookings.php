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
        Schema::table('bookings', function (Blueprint $table) {
            // Проверяем есть ли поле duration_minutes, если да - устанавливаем default
            if (Schema::hasColumn('bookings', 'duration_minutes')) {
                $table->integer('duration_minutes')->default(60)->change();
            } else {
                // Если поля нет, добавляем его
                $table->integer('duration_minutes')->default(60)->after('duration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->change();
            }
        });
    }
}; 