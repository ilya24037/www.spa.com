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
            // Делаем поля nullable для черновиков
            $table->string('specialty')->nullable()->change();
            $table->json('service_location')->nullable()->change();
            $table->string('work_format')->nullable()->change();
            $table->string('experience')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->decimal('price', 10, 2)->nullable()->change();
            $table->string('price_unit')->nullable()->change();
            $table->string('address', 500)->nullable()->change();
            $table->string('travel_area')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->string('contact_method')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Возвращаем обратно NOT NULL для обязательных полей
            $table->string('specialty')->nullable(false)->change();
            $table->json('service_location')->nullable(false)->change();
            $table->string('work_format')->nullable(false)->change();
            $table->string('experience')->nullable(false)->change();
            $table->text('description')->nullable(false)->change();
            $table->decimal('price', 10, 2)->nullable(false)->change();
            $table->string('price_unit')->nullable(false)->change();
            $table->string('address', 500)->nullable(false)->change();
            $table->string('travel_area')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
            $table->string('contact_method')->nullable(false)->change();
        });
    }
};
