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
        // Создаём таблицу master_services только если её нет
        if (!Schema::hasTable('master_services')) {
            Schema::create('master_services', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('master_profile_id');
                $table->string('service_name', 255);
                $table->text('service_description')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->integer('duration_minutes')->nullable()->comment('Длительность в минутах');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_featured')->default(false)->comment('Рекомендуемая услуга');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                
                // Индексы
                $table->index('master_profile_id');
                $table->index('is_active');
                $table->index(['master_profile_id', 'is_active']);
                $table->index(['price']);
                $table->index('sort_order');
                
                // Внешний ключ (если таблица master_profiles существует)
                if (Schema::hasTable('master_profiles')) {
                    $table->foreign('master_profile_id')
                          ->references('id')
                          ->on('master_profiles')
                          ->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_services');
    }
};