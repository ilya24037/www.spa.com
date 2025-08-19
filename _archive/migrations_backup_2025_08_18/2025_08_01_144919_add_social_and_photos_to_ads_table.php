<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Консолидированная миграция: социальные контакты и фото
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->comment('Номер WhatsApp для связи');
            $table->string('telegram')->nullable()->comment('Username Telegram для связи');
            $table->json('photos')->nullable()->comment('Фотографии объявления в JSON формате');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'telegram', 'photos']);
        });
    }
};
