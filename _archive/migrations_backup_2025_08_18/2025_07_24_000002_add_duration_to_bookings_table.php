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
            // Добавляем поле только если его еще нет
            if (!Schema::hasColumn('bookings', 'duration')) {
                $table->integer('duration')->after('end_time')->comment('Длительность в минутах');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Удаляем поле только если оно существует
            if (Schema::hasColumn('bookings', 'duration')) {
                $table->dropColumn('duration');
            }
        });
    }
}; 