<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\Media\PathGenerator;
use App\Domain\Ad\Models\Ad;
use Illuminate\Support\Facades\Storage;

class MigrateMediaStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:migrate 
                            {--dry-run : Показать что будет сделано без выполнения}
                            {--user=* : Мигрировать только для указанных user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Мигрировать существующие медиа файлы на новую структуру папок';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Начинаем миграцию медиа файлов...');
        
        $isDryRun = $this->option('dry-run');
        $userIds = $this->option('user');
        
        if ($isDryRun) {
            $this->warn('⚠️  Режим DRY RUN - изменения не будут сохранены');
        }

        // Получаем объявления для миграции
        $query = Ad::whereNotNull('photos')->orWhereNotNull('video');
        
        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }
        
        $ads = $query->get();
        $totalAds = $ads->count();
        
        $this->info("📊 Найдено объявлений для миграции: {$totalAds}");
        
        $bar = $this->output->createProgressBar($totalAds);
        $bar->start();
        
        $stats = [
            'photos_migrated' => 0,
            'videos_migrated' => 0,
            'errors' => 0,
            'skipped' => 0
        ];
        
        foreach ($ads as $ad) {
            try {
                $this->migrateAdMedia($ad, $isDryRun, $stats);
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->error("\n❌ Ошибка для объявления {$ad->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        // Вывод статистики
        $this->newLine(2);
        $this->info('📈 Статистика миграции:');
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Фото мигрировано', $stats['photos_migrated']],
                ['Видео мигрировано', $stats['videos_migrated']],
                ['Пропущено', $stats['skipped']],
                ['Ошибок', $stats['errors']]
            ]
        );
        
        if (!$isDryRun) {
            $this->info('✅ Миграция завершена!');
            
            // Предложение очистить старые файлы
            if ($this->confirm('Удалить старые файлы из исходных папок?')) {
                $this->cleanupOldFiles();
            }
        }
    }
    
    /**
     * Миграция медиа для одного объявления
     */
    private function migrateAdMedia(Ad $ad, bool $isDryRun, array &$stats): void
    {
        $user = $ad->user;
        if (!$user) {
            $stats['skipped']++;
            return;
        }
        
        $newPhotoPaths = [];
        $newVideoPaths = [];
        
        // Определяем базовый путь пользователя
        $userBasePath = PathGenerator::getUserBasePath($user->id);
        $adBasePath = PathGenerator::getAdBasePath($user->id, $ad->id);
        
        // Миграция фотографий
        if ($ad->photos) {
            $photos = json_decode($ad->photos, true);
            
            foreach ($photos as $oldPath) {
                if (is_array($oldPath)) continue; // Пропускаем если уже массив
                
                // Проверяем, не мигрирован ли уже файл (должен содержать /users/)
                if (str_contains($oldPath, '/users/') && PathGenerator::isAdMediaPath($oldPath)) {
                    $this->info("✓ Уже мигрирован: {$oldPath}");
                    $newPhotoPaths[] = $oldPath;
                    continue;
                }
                
                $oldFullPath = $this->getFullPath($oldPath);
                
                if (!file_exists($oldFullPath)) {
                    $this->warn("\n⚠️  Файл не найден: {$oldPath}");
                    continue;
                }
                
                // Генерируем новый путь
                $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
                $newPath = PathGenerator::adPhotoPath($user->id, $ad->id, $extension, 'original');
                
                if (!$isDryRun) {
                    // Создаем директории
                    Storage::disk('public')->makeDirectory(dirname($newPath));
                    
                    // Копируем файл (не перемещаем, чтобы не потерять при ошибке)
                    Storage::disk('public')->copy(
                        $this->getStoragePath($oldPath),
                        $newPath
                    );
                }
                
                $newPhotoPaths[] = "/storage/{$newPath}";
                $stats['photos_migrated']++;
                
                $this->info("→ Фото: {$oldPath} → {$newPath}");
            }
        }
        
        // Миграция видео
        if ($ad->video) {
            $videos = json_decode($ad->video, true);
            
            foreach ($videos as $oldPath) {
                if (is_array($oldPath)) continue;
                
                // Проверяем, не мигрирован ли уже файл (должен содержать /users/)
                if (str_contains($oldPath, '/users/') && PathGenerator::isAdMediaPath($oldPath)) {
                    $this->info("✓ Уже мигрировано: {$oldPath}");
                    $newVideoPaths[] = $oldPath;
                    continue;
                }
                
                $oldFullPath = $this->getFullPath($oldPath);
                
                if (!file_exists($oldFullPath)) {
                    $this->warn("\n⚠️  Видео не найдено: {$oldPath}");
                    continue;
                }
                
                // Генерируем новый путь
                $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
                $newPath = PathGenerator::adVideoPath($user->id, $ad->id, $extension);
                
                if (!$isDryRun) {
                    Storage::disk('public')->makeDirectory(dirname($newPath));
                    Storage::disk('public')->copy(
                        $this->getStoragePath($oldPath),
                        $newPath
                    );
                }
                
                $newVideoPaths[] = "/storage/{$newPath}";
                $stats['videos_migrated']++;
                
                $this->info("→ Видео: {$oldPath} → {$newPath}");
            }
        }
        
        // Обновляем пути в БД
        if (!$isDryRun && (count($newPhotoPaths) > 0 || count($newVideoPaths) > 0)) {
            $ad->update([
                'photos' => count($newPhotoPaths) > 0 ? json_encode($newPhotoPaths) : $ad->photos,
                'video' => count($newVideoPaths) > 0 ? json_encode($newVideoPaths) : $ad->video,
                'user_folder' => $userBasePath,
                'media_paths' => json_encode([
                    'photos' => $newPhotoPaths,
                    'videos' => $newVideoPaths,
                    'migrated_at' => now()
                ])
            ]);
        }
    }
    
    
    /**
     * Очистка старых файлов
     */
    private function cleanupOldFiles(): void
    {
        $this->info('🗑️  Очистка старых файлов...');
        
        // Получаем все мигрированные объявления
        $ads = Ad::whereNotNull('media_paths')->get();
        
        foreach ($ads as $ad) {
            $mediaPaths = json_decode($ad->media_paths, true);
            
            if (!isset($mediaPaths['migrated_at'])) {
                continue;
            }
            
            // Удаляем старые файлы
            $oldPhotos = json_decode($ad->photos, true);
            $oldVideos = json_decode($ad->video, true);
            
            // Здесь логика удаления старых файлов
            // ...
        }
        
        $this->info('✅ Очистка завершена');
    }
    
    /**
     * Вспомогательные методы
     */
    private function getFullPath(string $path): string
    {
        return storage_path('app/public' . str_replace('/storage', '', $path));
    }
    
    private function getStoragePath(string $path): string
    {
        return str_replace('/storage/', '', $path);
    }
}