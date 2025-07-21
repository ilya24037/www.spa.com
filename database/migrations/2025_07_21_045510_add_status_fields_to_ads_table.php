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
        // Сначала обновляем существующие статусы на допустимые значения
        DB::table('ads')->where('status', 'inactive')->update(['status' => 'draft']);
        DB::table('ads')->where('status', 'old')->update(['status' => 'archived']);
        DB::table('ads')->where('status', 'paused')->update(['status' => 'draft']);
        
        // Меняем все объявления на waiting_payment временно, чтобы продемонстрировать функционал
        DB::table('ads')->where('status', 'active')->update(['status' => 'waiting_payment']);
        
        Schema::table('ads', function (Blueprint $table) {
            // Добавляем новые поля для управления объявлениями, если их еще нет
            if (!Schema::hasColumn('ads', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('status');
            }
            if (!Schema::hasColumn('ads', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('is_paid');
            }
            if (!Schema::hasColumn('ads', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('ads', 'views_count')) {
                $table->integer('views_count')->default(0)->after('expires_at');
            }
            if (!Schema::hasColumn('ads', 'contacts_shown')) {
                $table->integer('contacts_shown')->default(0)->after('views_count');
            }
            if (!Schema::hasColumn('ads', 'favorites_count')) {
                $table->integer('favorites_count')->default(0)->after('contacts_shown');
            }
        });
        
        // Теперь меняем enum статуса
        DB::statement("ALTER TABLE `ads` MODIFY COLUMN `status` ENUM('waiting_payment', 'active', 'draft', 'archived', 'expired', 'rejected', 'blocked') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем старые статусы
        DB::table('ads')->where('status', 'waiting_payment')->update(['status' => 'active']);
        
        Schema::table('ads', function (Blueprint $table) {
            if (Schema::hasColumn('ads', 'is_paid')) {
                $table->dropColumn('is_paid');
            }
            if (Schema::hasColumn('ads', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            if (Schema::hasColumn('ads', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
            if (Schema::hasColumn('ads', 'views_count')) {
                $table->dropColumn('views_count');
            }
            if (Schema::hasColumn('ads', 'contacts_shown')) {
                $table->dropColumn('contacts_shown');
            }
            if (Schema::hasColumn('ads', 'favorites_count')) {
                $table->dropColumn('favorites_count');
            }
        });
        
        // Возвращаем старый enum
        DB::statement("ALTER TABLE `ads` MODIFY COLUMN `status` ENUM('draft', 'active', 'paused', 'archived', 'inactive', 'old') NOT NULL DEFAULT 'draft'");
    }
};
