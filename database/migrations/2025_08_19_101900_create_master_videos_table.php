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
        Schema::create('master_videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_profile_id');
            $table->string('filename');           // video_1.mp4
            $table->string('mime_type', 100)->nullable();
            $table->integer('file_size')->nullable();
            $table->smallInteger('width')->nullable();
            $table->smallInteger('height')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('thumbnail_filename')->nullable();
            $table->boolean('is_main')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
            
            // Индексы
            $table->index('master_profile_id');
            $table->index('is_main');
            $table->index('sort_order');
            $table->index('is_approved');
            
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
        Schema::dropIfExists('master_videos');
    }
};
