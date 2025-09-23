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
        // Для SQLite (тестирование) просто обновляем статусы
        if (config('database.default') === 'sqlite' || DB::getDriverName() === 'sqlite') {
            // SQLite не поддерживает изменение типа колонки
            // Но для тестов это не критично - статусы всё равно работают как строки
            DB::table('ads')
                ->where('status', 'paused')
                ->update(['status' => 'pending_moderation']);
        } else {
            // Для MySQL обновляем enum статусов
            DB::statement("ALTER TABLE ads MODIFY COLUMN status ENUM('draft', 'active', 'paused', 'archived', 'pending_moderation', 'waiting_payment', 'rejected', 'blocked', 'expired') NOT NULL DEFAULT 'draft'");

            // Обновляем существующие объявления со статусом 'paused' на 'pending_moderation'
            DB::table('ads')
                ->where('status', 'paused')
                ->update(['status' => 'pending_moderation']);
        }

        // Логируем результат
        $updated = DB::table('ads')->where('status', 'pending_moderation')->count();
        \Log::info("Migrated {$updated} ads from 'paused' to 'pending_moderation' status");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем объявления обратно на paused перед откатом
        DB::table('ads')
            ->where('status', 'pending_moderation')
            ->update(['status' => 'paused']);

        // Для MySQL возвращаем старый enum без pending_moderation
        if (config('database.default') !== 'sqlite' && DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE ads MODIFY COLUMN status ENUM('draft', 'active', 'paused', 'archived') NOT NULL DEFAULT 'draft'");
        }
    }
};
