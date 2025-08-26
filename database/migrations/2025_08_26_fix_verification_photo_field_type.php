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
            // Изменяем тип поля verification_photo на MEDIUMTEXT для хранения base64 изображений
            // MEDIUMTEXT может хранить до 16MB данных
            $table->mediumText('verification_photo')->nullable()->change();
            
            // Также изменим verification_video на случай если понадобится
            $table->mediumText('verification_video')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Возвращаем обратно на varchar (не рекомендуется)
            $table->string('verification_photo')->nullable()->change();
            $table->string('verification_video')->nullable()->change();
        });
    }
};