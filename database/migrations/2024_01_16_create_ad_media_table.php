<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->json('photos')->nullable();
            $table->json('video')->nullable();
            $table->boolean('show_photos_in_gallery')->default(true);
            $table->boolean('allow_download_photos')->default(false);
            $table->boolean('watermark_photos')->default(true);
            $table->timestamps();
            
            $table->unique('ad_id');
        });

        // Перенос данных из основной таблицы
        DB::statement("
            INSERT INTO ad_media (ad_id, photos, video, show_photos_in_gallery, allow_download_photos, watermark_photos, created_at, updated_at)
            SELECT id, photos, video, show_photos_in_gallery, allow_download_photos, watermark_photos, created_at, updated_at
            FROM ads
            WHERE photos IS NOT NULL OR video IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возврат данных в основную таблицу
        DB::statement("
            UPDATE ads a
            JOIN ad_media m ON a.id = m.ad_id
            SET a.photos = m.photos,
                a.video = m.video,
                a.show_photos_in_gallery = m.show_photos_in_gallery,
                a.allow_download_photos = m.allow_download_photos,
                a.watermark_photos = m.watermark_photos
        ");

        Schema::dropIfExists('ad_media');
    }
};