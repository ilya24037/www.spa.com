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
            // Физические параметры мастера
            $table->integer('age')->nullable()->comment('Возраст мастера');
            $table->integer('height')->nullable()->comment('Рост в сантиметрах');
            $table->integer('weight')->nullable()->comment('Вес в килограммах');
            $table->integer('breast_size')->nullable()->comment('Размер груди');
            
            // Индексы для поиска по параметрам
            $table->index(['age'], 'master_profiles_age_index');
            $table->index(['height'], 'master_profiles_height_index');
            $table->index(['weight'], 'master_profiles_weight_index');
            $table->index(['breast_size'], 'master_profiles_breast_size_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            // Удаляем индексы
            $table->dropIndex('master_profiles_age_index');
            $table->dropIndex('master_profiles_height_index');
            $table->dropIndex('master_profiles_weight_index');
            $table->dropIndex('master_profiles_breast_size_index');
            
            // Удаляем поля
            $table->dropColumn([
                'age',
                'height',
                'weight',
                'breast_size'
            ]);
        });
    }
};
