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
            // Проверяем и добавляем недостающие поля
            if (!Schema::hasColumn('ads', 'is_starting_price')) {
                $table->boolean('is_starting_price')->default(false)->after('price_unit');
            }
            
            if (!Schema::hasColumn('ads', 'discount')) {
                $table->integer('discount')->nullable()->after('is_starting_price');
            }
            
            if (!Schema::hasColumn('ads', 'gift')) {
                $table->string('gift', 500)->nullable()->after('discount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['is_starting_price', 'discount', 'gift']);
        });
    }
};
