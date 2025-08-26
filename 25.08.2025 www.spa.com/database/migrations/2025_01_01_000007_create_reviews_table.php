<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->string('reviewable_type', 50); // 'master', 'ad'
            $table->unsignedBigInteger('reviewable_id');
            
            // Оценка и комментарий
            $table->tinyInteger('rating')->unsigned();
            $table->text('comment')->nullable();
            
            // Связь с бронированием
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            
            // Модерация
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->timestamp('verified_at')->nullable();
            
            // Ответ мастера
            $table->text('master_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['reviewable_type', 'reviewable_id']);
            $table->index('rating');
            $table->index('is_visible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};