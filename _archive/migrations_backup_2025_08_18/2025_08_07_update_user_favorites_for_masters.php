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
        Schema::table('user_favorites', function (Blueprint $table) {
            // Делаем ad_id nullable
            $table->unsignedBigInteger('ad_id')->nullable()->change();
            
            // Добавляем поле для мастеров
            $table->unsignedBigInteger('master_profile_id')->nullable()->after('ad_id');
            
            // Добавляем индекс для master_profile_id
            $table->index('master_profile_id');
            
            // Добавляем внешний ключ для master_profile_id (если таблица существует)
            if (Schema::hasTable('master_profiles')) {
                $table->foreign('master_profile_id')->references('id')->on('master_profiles')->onDelete('cascade');
            }
            
            // Убираем старый уникальный ключ
            $table->dropUnique(['user_id', 'ad_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_favorites', function (Blueprint $table) {
            // Восстанавливаем старый уникальный ключ
            $table->unique(['user_id', 'ad_id']);
            
            // Убираем внешний ключ для master_profile_id
            if (Schema::hasTable('master_profiles')) {
                $table->dropForeign(['master_profile_id']);
            }
            
            // Убираем индекс
            $table->dropIndex(['master_profile_id']);
            
            // Убираем поле
            $table->dropColumn('master_profile_id');
            
            // Возвращаем ad_id как NOT NULL
            $table->unsignedBigInteger('ad_id')->nullable(false)->change();
        });
    }
};