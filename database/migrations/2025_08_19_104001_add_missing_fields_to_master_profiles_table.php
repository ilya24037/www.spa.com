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
        Schema::table('master_profiles', function (Blueprint $table) {
            // Добавляем только недостающие поля
            $table->string('city')->nullable()->after('display_name');
            $table->string('district')->nullable()->after('city');
            $table->string('metro_station')->nullable()->after('district');
            $table->boolean('home_service')->default(false)->after('metro_station');
            $table->boolean('salon_service')->default(false)->after('home_service');
            $table->string('salon_address')->nullable()->after('salon_service');
            $table->decimal('price_from', 10, 2)->nullable()->after('salon_address');
            $table->boolean('is_online')->default(false)->after('price_from');
            $table->string('folder_name')->nullable()->after('is_online');
            $table->decimal('latitude', 10, 8)->nullable()->after('folder_name');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            // Индексы
            $table->index('city');
            $table->index('district');
            $table->index('metro_station');
            $table->index('is_online');
            $table->index('price_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            // Удаляем добавленные поля
            $table->dropColumn([
                'city', 'district', 'metro_station', 'home_service', 'salon_service',
                'salon_address', 'price_from', 'is_online', 'folder_name', 'latitude', 'longitude'
            ]);
            
            // Удаляем индексы
            $table->dropIndex(['city']);
            $table->dropIndex(['district']);
            $table->dropIndex(['metro_station']);
            $table->dropIndex(['is_online']);
            $table->dropIndex(['price_from']);
        });
    }
};
