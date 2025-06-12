<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            // Координаты для карты
            $table->decimal('latitude', 10, 8)->nullable()->after('salon_address');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            // Индекс для геопоиска
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};