<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Support\Helpers\Transliterator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class RenameFoldersToUserNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'folders:rename-to-names 
                            {--dry-run : Показать что будет сделано, без изменений}
                            {--user=* : ID пользователей для обработки}
                            {--limit=50 : Лимит пользователей для обработки}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Переименовать папки пользователей из формата "1" в формат "anna-1"';

    private int $renamedCount = 0;
    private int $errorCount = 0;
    private int $skippedCount = 0;
    private array $errors = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $userIds = $this->option('user');
        $limit = (int) $this->option('limit');

        $this->info('🔄 Переименование папок пользователей');
        $this->info('=====================================');
        
        if ($isDryRun) {
            $this->warn('⚠️  Режим DRY-RUN: изменения НЕ будут сохранены');
        }
        $this->newLine();

        // Получаем пользователей
        $query = User::query();
        
        if (!empty($userIds)) {
            $query->whereIn('id', $userIds);
        }
        
        $users = $query->limit($limit)->get();

        $this->info("📊 Найдено пользователей: {$users->count()}");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            $this->processUser($user, $isDryRun);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Выводим результаты
        $this->showResults();

        return Command::SUCCESS;
    }

    /**
     * Обработка одного пользователя
     */
    private function processUser(User $user, bool $isDryRun): void
    {
        try {
            // Генерируем новое имя папки
            $newFolderName = Transliterator::generateUserFolderName($user->name, $user->id);
            
            // Старый путь (числовой)
            $oldPath = "users/{$user->id}";
            
            // Новый путь (с именем)
            $newPath = "users/{$newFolderName}";
            
            // Проверяем существование старой папки
            if (!Storage::disk('public')->exists($oldPath)) {
                $this->skippedCount++;
                return; // Папки нет, пропускаем
            }
            
            // Если папка уже переименована
            if ($oldPath === $newPath) {
                $this->skippedCount++;
                return;
            }
            
            // Проверяем, что новой папки еще нет
            if (Storage::disk('public')->exists($newPath)) {
                $this->errors[] = "Папка {$newPath} уже существует для пользователя {$user->id}";
                $this->errorCount++;
                return;
            }
            
            if (!$isDryRun) {
                // Физическое переименование папки
                $oldFullPath = storage_path("app/public/{$oldPath}");
                $newFullPath = storage_path("app/public/{$newPath}");
                
                if (!File::move($oldFullPath, $newFullPath)) {
                    throw new \Exception("Не удалось переименовать папку {$oldPath} в {$newPath}");
                }
                
                // Обновляем поле folder_name у пользователя
                $user->update(['folder_name' => $newFolderName]);
                
                // Обновляем пути в объявлениях
                $this->updateAdsPaths($user->id, $user->id, $newFolderName, $isDryRun);
            }
            
            $this->renamedCount++;
            
        } catch (\Exception $e) {
            $this->errors[] = "Ошибка для пользователя {$user->id}: " . $e->getMessage();
            $this->errorCount++;
        }
    }

    /**
     * Обновление путей в объявлениях
     */
    private function updateAdsPaths(int $userId, int $oldUserId, string $newFolderName, bool $isDryRun): void
    {
        $ads = Ad::where('user_id', $userId)->get();
        
        foreach ($ads as $ad) {
            $updated = false;
            
            // Обновляем пути к фото
            if ($ad->photos) {
                $photos = json_decode($ad->photos, true);
                if (is_array($photos)) {
                    $newPhotos = array_map(function($path) use ($oldUserId, $newFolderName) {
                        // Заменяем /users/1/ на /users/anna-1/
                        return str_replace(
                            "/users/{$oldUserId}/", 
                            "/users/{$newFolderName}/", 
                            $path
                        );
                    }, $photos);
                    
                    if (!$isDryRun && $photos !== $newPhotos) {
                        $ad->photos = json_encode($newPhotos);
                        $updated = true;
                    }
                }
            }
            
            // Обновляем пути к видео
            if ($ad->video) {
                $videos = json_decode($ad->video, true);
                if (is_array($videos)) {
                    $newVideos = array_map(function($path) use ($oldUserId, $newFolderName) {
                        return str_replace(
                            "/users/{$oldUserId}/", 
                            "/users/{$newFolderName}/", 
                            $path
                        );
                    }, $videos);
                    
                    if (!$isDryRun && $videos !== $newVideos) {
                        $ad->video = json_encode($newVideos);
                        $updated = true;
                    }
                }
            }
            
            // Обновляем user_folder
            if (!$isDryRun && $ad->user_folder !== $newFolderName) {
                $ad->user_folder = $newFolderName;
                $updated = true;
            }
            
            // Обновляем media_paths если есть
            if ($ad->media_paths) {
                $mediaPaths = json_decode($ad->media_paths, true);
                if (is_array($mediaPaths)) {
                    // Обновляем пути в photos
                    if (isset($mediaPaths['photos']) && is_array($mediaPaths['photos'])) {
                        foreach ($mediaPaths['photos'] as &$photoData) {
                            foreach ($photoData as $variant => &$path) {
                                $path = str_replace(
                                    "/users/{$oldUserId}/", 
                                    "/users/{$newFolderName}/", 
                                    $path
                                );
                            }
                        }
                    }
                    
                    // Обновляем пути в videos
                    if (isset($mediaPaths['videos']) && is_array($mediaPaths['videos'])) {
                        $mediaPaths['videos'] = array_map(function($path) use ($oldUserId, $newFolderName) {
                            return str_replace(
                                "/users/{$oldUserId}/", 
                                "/users/{$newFolderName}/", 
                                $path
                            );
                        }, $mediaPaths['videos']);
                    }
                    
                    if (!$isDryRun) {
                        $ad->media_paths = json_encode($mediaPaths);
                        $updated = true;
                    }
                }
            }
            
            if (!$isDryRun && $updated) {
                $ad->save();
            }
        }
    }

    /**
     * Показать результаты выполнения
     */
    private function showResults(): void
    {
        $this->info('📊 Результаты:');
        $this->info('==============');
        
        if ($this->renamedCount > 0) {
            $this->info("✅ Переименовано папок: {$this->renamedCount}");
        }
        
        if ($this->skippedCount > 0) {
            $this->info("⏭️  Пропущено: {$this->skippedCount}");
        }
        
        if ($this->errorCount > 0) {
            $this->error("❌ Ошибок: {$this->errorCount}");
            
            if (!empty($this->errors)) {
                $this->newLine();
                $this->error('Список ошибок:');
                foreach ($this->errors as $error) {
                    $this->error("  - {$error}");
                }
            }
        }
        
        if ($this->renamedCount > 0 && !$this->option('dry-run')) {
            $this->newLine();
            $this->info('✅ Папки успешно переименованы!');
            $this->info('   Новый формат: users/anna-1/, users/ivan-2/, и т.д.');
        }
    }
}