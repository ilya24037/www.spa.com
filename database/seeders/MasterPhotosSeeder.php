<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Domain\Master\Models\MasterProfile;

class MasterPhotosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Добавляем тестовые фотографии для мастеров...');
        
        // Получаем всех мастеров
        $masters = MasterProfile::all();
        
        if ($masters->isEmpty()) {
            $this->command->error('Мастера не найдены! Сначала запустите MasterSeeder.');
            return;
        }
        
        foreach ($masters as $master) {
            $this->command->info("Обрабатываем мастера: {$master->display_name}");
            
            // Удаляем существующие фотографии
            DB::table('master_photos')->where('master_profile_id', $master->id)->delete();
            
            // Тестовые фотографии для каждого мастера
            $photos = [
                [
                    'filename' => 'main_photo.jpg',
                    'mime_type' => 'image/jpeg',
                    'file_size' => 1024000,
                    'width' => 400,
                    'height' => 600,
                    'is_main' => true,
                    'sort_order' => 1,
                    'is_approved' => true
                ],
                [
                    'filename' => 'work_photo.jpg',
                    'mime_type' => 'image/jpeg',
                    'file_size' => 800000,
                    'width' => 400,
                    'height' => 600,
                    'is_main' => false,
                    'sort_order' => 2,
                    'is_approved' => true
                ],
                [
                    'filename' => 'certificate_photo.jpg',
                    'mime_type' => 'image/jpeg',
                    'file_size' => 600000,
                    'width' => 400,
                    'height' => 600,
                    'is_main' => false,
                    'sort_order' => 3,
                    'is_approved' => true
                ]
            ];
            
            foreach ($photos as $photo) {
                DB::table('master_photos')->insert([
                    'master_profile_id' => $master->id,
                    'filename' => $photo['filename'],
                    'mime_type' => $photo['mime_type'],
                    'file_size' => $photo['file_size'],
                    'width' => $photo['width'],
                    'height' => $photo['height'],
                    'is_main' => $photo['is_main'],
                    'sort_order' => $photo['sort_order'],
                    'is_approved' => $photo['is_approved'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->command->info("  ✅ Добавлена фотография: {$photo['filename']}");
            }
            
            // Добавляем тестовое видео
            DB::table('master_videos')->where('master_profile_id', $master->id)->delete();
            
            DB::table('master_videos')->insert([
                'master_profile_id' => $master->id,
                'filename' => 'intro_video.mp4',
                'mime_type' => 'video/mp4',
                'file_size' => 5000000,
                'width' => 1280,
                'height' => 720,
                'duration_seconds' => 30,
                'thumbnail_filename' => 'intro_video_thumb.jpg',
                'is_main' => false,
                'sort_order' => 1,
                'is_approved' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info("  ✅ Добавлено видео: intro_video.mp4");
        }
        
        $this->command->info('✅ Все фотографии и видео успешно добавлены!');
    }
} 