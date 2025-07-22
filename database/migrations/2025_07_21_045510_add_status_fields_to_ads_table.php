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
        // Добавляем новые поля
        Schema::table('ads', function (Blueprint $table) {
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
        
        // Обновляем enum статуса через ALTER TABLE
        DB::statement("ALTER TABLE `ads` MODIFY COLUMN `status` ENUM('waiting_payment', 'active', 'draft', 'archived', 'expired', 'rejected', 'blocked') NOT NULL DEFAULT 'draft'");
        
        // Теперь можем обновить данные
        // Несколько активных объявлений переводим в waiting_payment для демонстрации
        DB::table('ads')->where('status', 'active')->limit(2)->update(['status' => 'waiting_payment']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем статусы
        DB::table('ads')->where('status', 'waiting_payment')->update(['status' => 'active']);
        
        // Возвращаем старый enum
        DB::statement("ALTER TABLE `ads` MODIFY COLUMN `status` ENUM('draft', 'active', 'paused', 'archived', 'inactive', 'old') NOT NULL DEFAULT 'draft'");
        
        Schema::table('ads', function (Blueprint $table) {
            $columnsToDelete = ['is_paid', 'paid_at', 'expires_at', 'views_count', 'contacts_shown', 'favorites_count'];
            foreach ($columnsToDelete as $column) {
                if (Schema::hasColumn('ads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
