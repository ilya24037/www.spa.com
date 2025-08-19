<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // Личные данные мастера (используются в объявлениях)
            $table->string('display_name')->nullable();
            $table->string('age')->nullable();
            $table->string('height')->nullable();
            $table->string('weight')->nullable();
            $table->string('breast_size')->nullable();
            $table->string('hair_color')->nullable();
            $table->string('eye_color')->nullable();
            $table->string('nationality')->nullable();
            $table->string('appearance')->nullable();
            
            // Статистика
            $table->decimal('rating', 2, 1)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->integer('completed_bookings')->default(0);
            
            // Верификация
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            // Подписка/премиум
            $table->boolean('is_premium')->default(false);
            $table->timestamp('premium_until')->nullable();
            
            $table->timestamps();
            
            $table->index('rating');
            $table->index('is_verified');
            $table->index('is_premium');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_profiles');
    }
};