<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Ad\Models\Ad;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupOldMedia extends Command
{
    protected $signature = 'media:cleanup {--days=365 : Удалить файлы старше N дней} {--drafts-only : Удалять только из черновиков}';
    protected $description = 'Очистка старых медиа файлов';

    public function handle()
    {
        $days = $this->option('days');
        $this->info("🧹 Очистка файлов старше {$days} дней...");
        
        $deletedPhotos = 0;
        $deletedVideos = 0;
        $freedSpace = 0;
        
        // Определяем какие объявления чистить
        $query = Ad::where('updated_at', '<', Carbon::now()->subDays($days));
        
        if ($this->option('drafts-only')) {
            $query->where('status', 'draft');
        } else {
            // Удаляем только из неактивных объявлений
            $query->whereIn('status', ['draft', 'expired', 'deleted', 'blocked']);
        }
        
        $oldAds = $query->get();
            
        foreach ($oldAds as $draft) {
            // Очищаем фото
            if ($draft->photos) {
                $photos = json_decode($draft->photos, true);
                foreach ($photos as $photo) {
                    $path = str_replace('/storage/', '', $photo);
                    if (Storage::disk('public')->exists($path)) {
                        $freedSpace += Storage::disk('public')->size($path);
                        Storage::disk('public')->delete($path);
                        $deletedPhotos++;
                    }
                }
                $draft->photos = json_encode([]);
            }
            
            // Очищаем видео
            if ($draft->video) {
                $videos = json_decode($draft->video, true);
                foreach ($videos as $video) {
                    $path = str_replace('/storage/', '', $video);
                    if (Storage::disk('public')->exists($path)) {
                        $freedSpace += Storage::disk('public')->size($path);
                        Storage::disk('public')->delete($path);
                        $deletedVideos++;
                    }
                }
                $draft->video = json_encode([]);
            }
            
            $draft->save();
        }
        
        // Удаляем неиспользуемые файлы
        $this->cleanupOrphanedFiles($deletedPhotos, $deletedVideos, $freedSpace);
        
        $freedSpaceMB = round($freedSpace / 1024 / 1024, 2);
        
        $this->info("✅ Очистка завершена!");
        $this->info("📊 Статистика:");
        $this->info("  - Удалено фото: {$deletedPhotos}");
        $this->info("  - Удалено видео: {$deletedVideos}");
        $this->info("  - Освобождено места: {$freedSpaceMB} MB");
    }
    
    private function cleanupOrphanedFiles(&$deletedPhotos, &$deletedVideos, &$freedSpace)
    {
        // Получаем все используемые файлы
        $usedFiles = [];
        
        Ad::whereNotNull('photos')->orWhereNotNull('video')->chunk(100, function($ads) use (&$usedFiles) {
            foreach ($ads as $ad) {
                if ($ad->photos) {
                    $photos = json_decode($ad->photos, true);
                    foreach ($photos as $photo) {
                        $usedFiles[] = basename($photo);
                    }
                }
                if ($ad->video) {
                    $videos = json_decode($ad->video, true);
                    foreach ($videos as $video) {
                        $usedFiles[] = basename($video);
                    }
                }
            }
        });
        
        // Проверяем папку с фото
        $photosPath = 'ads/photos';
        $photoFiles = Storage::disk('public')->files($photosPath);
        
        foreach ($photoFiles as $file) {
            $filename = basename($file);
            if (!in_array($filename, $usedFiles)) {
                $freedSpace += Storage::disk('public')->size($file);
                Storage::disk('public')->delete($file);
                $deletedPhotos++;
            }
        }
        
        // Проверяем папку с видео
        $videosPath = 'ads/videos';
        $videoFiles = Storage::disk('public')->files($videosPath);
        
        foreach ($videoFiles as $file) {
            $filename = basename($file);
            if (!in_array($filename, $usedFiles)) {
                $freedSpace += Storage::disk('public')->size($file);
                Storage::disk('public')->delete($file);
                $deletedVideos++;
            }
        }
    }
}