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
            // Добавляем поле только если его еще нет (избегаем дублирования с create_bookings_table.php)
            if (!Schema::hasColumn('bookings', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('cancelled_at');
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
            if (Schema::hasColumn('bookings', 'reminder_sent_at')) {
                $table->dropColumn('reminder_sent_at');
            }
        });
    }
};