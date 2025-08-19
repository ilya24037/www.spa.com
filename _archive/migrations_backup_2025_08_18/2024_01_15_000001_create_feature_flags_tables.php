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
        // Таблица для хранения feature flags
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('config');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('name');
        });

        // Таблица для логирования использования
        Schema::create('feature_flag_usage', function (Blueprint $table) {
            $table->id();
            $table->string('feature');
            $table->boolean('enabled');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at');
            
            $table->index(['feature', 'created_at']);
            $table->index(['user_id', 'feature']);
            $table->index('created_at');
        });

        // Таблица для A/B тестов
        Schema::create('feature_flag_experiments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('feature');
            $table->string('variant'); // control, treatment_a, treatment_b, etc
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->json('metrics')->nullable();
            $table->timestamps();
            
            $table->unique(['name', 'user_id']);
            $table->unique(['name', 'session_id']);
            $table->index(['name', 'variant']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flag_experiments');
        Schema::dropIfExists('feature_flag_usage');
        Schema::dropIfExists('feature_flags');
    }
};