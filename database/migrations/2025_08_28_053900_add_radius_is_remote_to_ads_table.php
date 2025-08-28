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
            // Проверяем и добавляем radius если его нет
            if (!Schema::hasColumn('ads', 'radius')) {
                $table->integer('radius')->nullable()->after('geo');
            }
            
            // Проверяем и добавляем is_remote если его нет
            if (!Schema::hasColumn('ads', 'is_remote')) {
                $table->boolean('is_remote')->default(false)->after('radius');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['radius', 'is_remote']);
        });
    }
};