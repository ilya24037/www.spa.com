<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Консолидированная миграция: опции внешности
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('appearance', 50)->nullable()->after('eye_color')->comment('Внешность (тип: Славянская, Азиатская и т.д.)');
            $table->boolean('has_girlfriend')->default(false)->after('appearance')->comment('Есть подруга (для индивидуального формата работы)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['appearance', 'has_girlfriend']);
        });
    }
};
