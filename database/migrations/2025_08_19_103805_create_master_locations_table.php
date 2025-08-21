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
        Schema::create('master_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_profile_id');
            $table->enum('type', ['salon', 'home', 'outcall', 'mobile'])->default('outcall');
            $table->string('name', 255)->nullable()->comment('Название места');
            $table->text('address')->nullable()->comment('Полный адрес');
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('metro_station', 100)->nullable();
            $table->decimal('lat', 10, 8)->nullable()->comment('Широта');
            $table->decimal('lng', 11, 8)->nullable()->comment('Долгота');
            $table->text('description')->nullable()->comment('Описание места');
            $table->json('amenities')->nullable()->comment('Удобства');
            $table->json('photos')->nullable()->comment('Фотографии места');
            $table->json('working_hours')->nullable()->comment('Часы работы');
            $table->string('phone', 20)->nullable()->comment('Телефон места');
            $table->boolean('is_primary')->default(false)->comment('Основное место работы');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // Индексы
            $table->index('master_profile_id');
            $table->index('type');
            $table->index('city');
            $table->index('district');
            $table->index('metro_station');
            $table->index('is_primary');
            $table->index('is_active');
            $table->index('sort_order');
            $table->index(['master_profile_id', 'type']);
            
            // Внешний ключ
            $table->foreign('master_profile_id')
                  ->references('id')
                  ->on('master_profiles')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_locations');
    }
};
