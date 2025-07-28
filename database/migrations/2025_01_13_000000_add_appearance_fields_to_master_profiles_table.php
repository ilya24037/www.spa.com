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
            $table->string('hair_color', 50)->nullable()->comment('Цвет волос');
            $table->string('eye_color', 50)->nullable()->comment('Цвет глаз');
            $table->string('nationality', 50)->nullable()->comment('Национальность');
            
            // Индексы для поиска
            $table->index(['hair_color'], 'master_profiles_hair_color_index');
            $table->index(['nationality'], 'master_profiles_nationality_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            // Удаляем индексы
            $table->dropIndex('master_profiles_hair_color_index');
            $table->dropIndex('master_profiles_nationality_index');
            
            // Удаляем поля
            $table->dropColumn([
                'hair_color',
                'eye_color',
                'nationality'
            ]);
        });
    }
}; 