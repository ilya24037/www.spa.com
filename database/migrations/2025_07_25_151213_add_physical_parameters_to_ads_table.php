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
        Schema::table('ads', function (Blueprint $table) {
            // Физические параметры мастера
            $table->integer('age')->nullable()->comment('Возраст мастера');
            $table->integer('height')->nullable()->comment('Рост в сантиметрах');
            $table->integer('weight')->nullable()->comment('Вес в килограммах');
            $table->integer('breast_size')->nullable()->comment('Размер груди');
            $table->string('hair_color', 50)->nullable()->comment('Цвет волос');
            $table->string('eye_color', 50)->nullable()->comment('Цвет глаз');
            $table->string('nationality', 50)->nullable()->comment('Национальность');
            
            // Индексы для фильтрации
            $table->index(['age'], 'ads_age_index');
            $table->index(['height'], 'ads_height_index');
            $table->index(['hair_color'], 'ads_hair_color_index');
            $table->index(['nationality'], 'ads_nationality_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Удаляем индексы
            $table->dropIndex('ads_age_index');
            $table->dropIndex('ads_height_index');
            $table->dropIndex('ads_hair_color_index');
            $table->dropIndex('ads_nationality_index');
            
            // Удаляем колонки
            $table->dropColumn([
                'age',
                'height', 
                'weight',
                'breast_size',
                'hair_color',
                'eye_color',
                'nationality'
            ]);
        });
    }
};
