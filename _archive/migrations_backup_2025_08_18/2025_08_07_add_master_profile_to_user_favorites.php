<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Сначала делаем ad_id nullable
        Schema::table('user_favorites', function (Blueprint $table) {
            $table->unsignedBigInteger('ad_id')->nullable()->change();
        });
        
        // Добавляем колонку для мастеров если её нет
        if (!Schema::hasColumn('user_favorites', 'master_profile_id')) {
            Schema::table('user_favorites', function (Blueprint $table) {
                $table->unsignedBigInteger('master_profile_id')->nullable()->after('ad_id');
                $table->index('master_profile_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('user_favorites', function (Blueprint $table) {
            if (Schema::hasColumn('user_favorites', 'master_profile_id')) {
                $table->dropIndex(['master_profile_id']);
                $table->dropColumn('master_profile_id');
            }
            $table->unsignedBigInteger('ad_id')->nullable(false)->change();
        });
    }
};