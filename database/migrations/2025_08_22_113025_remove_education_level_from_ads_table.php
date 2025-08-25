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
            // Удаляем поле education_level если оно существует
            if (Schema::hasColumn('ads', 'education_level')) {
                $table->dropColumn('education_level');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Восстанавливаем поле education_level при откате
            $table->string('education_level')->nullable()->after('experience');
        });
    }
};
