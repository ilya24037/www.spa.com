<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Консолидированная миграция: outcall опции и такси
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->json('outcall_locations')->nullable()->after('service_location')->comment('Дополнительные опции выезда (квартира, гостиница и т.д.)');
            $table->string('taxi_option', 20)->nullable()->after('outcall_locations')->comment('Опция такси: separately/included');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['taxi_option', 'outcall_locations']);
        });
    }
};
