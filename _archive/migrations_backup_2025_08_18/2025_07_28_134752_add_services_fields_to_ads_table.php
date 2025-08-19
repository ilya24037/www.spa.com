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
            // Добавляем поля для услуг
            $table->json('services')->nullable()->comment('Выбранные услуги (массив)');
            $table->text('services_additional_info')->nullable()->comment('Дополнительная информация об услугах');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['services', 'services_additional_info']);
        });
    }
};
