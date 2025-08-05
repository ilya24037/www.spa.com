<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Enums\MediaType;
use App\Enums\MediaStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Убеждаемся что таблица media существует и имеет правильную структуру
        if (!Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();
                $table->string('mediable_type');
                $table->unsignedBigInteger('mediable_id');
                $table->string('collection_name')->default('default');
                $table->string('name');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->string('disk')->default('masters_private');
                $table->unsignedBigInteger('size')->default(0);
                $table->string('type'); // MediaType enum
                $table->string('status')->default('processed'); // MediaStatus enum
                $table->string('alt_text')->nullable();
                $table->text('caption')->nullable();
                $table->json('metadata')->nullable();
                $table->json('conversions')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['mediable_type', 'mediable_id']);
                $table->index(['collection_name']);
                $table->index(['type']);
                $table->index(['status']);
            });
        }

        // 2. Миграция данных из master_photos в media
        if (Schema::hasTable('master_photos')) {
            echo "Migrating photos to media table...\n";
            
            $photos = DB::table('master_photos')->get();
            
            foreach ($photos as $photo) {
                $metadata = [
                    'width' => $photo->width,
                    'height' => $photo->height,
                    'mime_type' => $photo->mime_type,
                    'is_main' => $photo->is_main,
                    'is_approved' => $photo->is_approved
                ];

                DB::table('media')->insert([
                    'mediable_type' => 'App\\Domain\\Master\\Models\\MasterProfile',
                    'mediable_id' => $photo->master_profile_id,
                    'collection_name' => 'photos',
                    'name' => pathinfo($photo->filename, PATHINFO_FILENAME),
                    'file_name' => "master_{$photo->master_profile_id}/photos/{$photo->filename}",
                    'mime_type' => $photo->mime_type,
                    'disk' => 'masters_private',
                    'size' => $photo->file_size ?? 0,
                    'type' => MediaType::IMAGE->value,
                    'status' => MediaStatus::PROCESSED->value,
                    'metadata' => json_encode($metadata),
                    'sort_order' => $photo->sort_order ?? 0,
                    'created_at' => $photo->created_at,
                    'updated_at' => $photo->updated_at
                ]);
            }
            
            echo "Migrated " . count($photos) . " photos\n";
        }

        // 3. Миграция данных из master_videos в media
        if (Schema::hasTable('master_videos')) {
            echo "Migrating videos to media table...\n";
            
            $videos = DB::table('master_videos')->get();
            
            foreach ($videos as $video) {
                $metadata = [
                    'width' => $video->width,
                    'height' => $video->height,
                    'duration' => $video->duration,
                    'mime_type' => $video->mime_type,
                    'poster_filename' => $video->poster_filename,
                    'is_main' => $video->is_main ?? false,
                    'is_approved' => $video->is_approved ?? true,
                    'processing_status' => $video->processing_status ?? 'completed'
                ];

                // Создаем conversions для постера если есть
                $conversions = [];
                if ($video->poster_filename) {
                    $conversions['poster'] = [
                        'file_name' => "master_{$video->master_profile_id}/video/{$video->poster_filename}",
                        'mime_type' => 'image/jpeg',
                        'width' => $video->width ?? null,
                        'height' => $video->height ?? null
                    ];
                }

                DB::table('media')->insert([
                    'mediable_type' => 'App\\Domain\\Master\\Models\\MasterProfile',
                    'mediable_id' => $video->master_profile_id,
                    'collection_name' => 'videos',
                    'name' => pathinfo($video->filename, PATHINFO_FILENAME),
                    'file_name' => "master_{$video->master_profile_id}/video/{$video->filename}",
                    'mime_type' => $video->mime_type,
                    'disk' => 'masters_private',
                    'size' => $video->file_size ?? 0,
                    'type' => MediaType::VIDEO->value,
                    'status' => MediaStatus::PROCESSED->value,
                    'metadata' => json_encode($metadata),
                    'conversions' => !empty($conversions) ? json_encode($conversions) : null,
                    'sort_order' => $video->sort_order ?? 0,
                    'created_at' => $video->created_at,
                    'updated_at' => $video->updated_at
                ]);
            }
            
            echo "Migrated " . count($videos) . " videos\n";
        }

        // 4. Создаем backup таблицы перед удалением (для безопасности)
        if (Schema::hasTable('master_photos')) {
            echo "Creating backup of master_photos...\n";
            DB::statement('CREATE TABLE master_photos_backup AS SELECT * FROM master_photos');
            
            echo "Dropping master_photos table...\n";
            Schema::drop('master_photos');
        }

        if (Schema::hasTable('master_videos')) {
            echo "Creating backup of master_videos...\n";
            DB::statement('CREATE TABLE master_videos_backup AS SELECT * FROM master_videos');
            
            echo "Dropping master_videos table...\n";
            Schema::drop('master_videos');
        }

        echo "Media unification migration completed successfully!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Восстанавливаем таблицы master_photos и master_videos
        if (!Schema::hasTable('master_photos') && Schema::hasTable('master_photos_backup')) {
            echo "Restoring master_photos table...\n";
            DB::statement('CREATE TABLE master_photos AS SELECT * FROM master_photos_backup');
            Schema::drop('master_photos_backup');
        }

        if (!Schema::hasTable('master_videos') && Schema::hasTable('master_videos_backup')) {
            echo "Restoring master_videos table...\n";
            DB::statement('CREATE TABLE master_videos AS SELECT * FROM master_videos_backup');
            Schema::drop('master_videos_backup');
        }

        // 2. Удаляем мигрированные данные из таблицы media
        if (Schema::hasTable('media')) {
            echo "Removing migrated data from media table...\n";
            
            DB::table('media')
                ->where('mediable_type', 'App\\Domain\\Master\\Models\\MasterProfile')
                ->whereIn('collection_name', ['photos', 'videos'])
                ->delete();
        }

        echo "Media unification migration rollback completed!\n";
    }
};