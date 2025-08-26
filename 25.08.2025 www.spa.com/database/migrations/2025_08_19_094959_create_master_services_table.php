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
        Schema::create('master_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_profile_id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('price_to', 10, 2)->nullable();
            $table->integer('duration_minutes')->nullable()->comment('Длительность в минутах');
            $table->string('category', 100)->nullable();
            $table->string('subcategory', 100)->nullable();
            $table->json('features')->nullable()->comment('Особенности услуги');
            $table->integer('preparation_time')->nullable()->comment('Время подготовки в минутах');
            $table->integer('cleanup_time')->nullable()->comment('Время уборки в минутах');
            $table->boolean('is_popular')->default(false)->comment('Популярная услуга');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('requirements')->nullable()->comment('Требования к клиенту');
            $table->json('contraindications')->nullable()->comment('Противопоказания');
            $table->timestamps();
            
            // Индексы
            $table->index('master_profile_id');
            $table->index('is_active');
            $table->index(['master_profile_id', 'is_active']);
            $table->index(['price']);
            $table->index('sort_order');
            $table->index('category');
            $table->index('is_popular');
            
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
        Schema::dropIfExists('master_services');
    }
};
