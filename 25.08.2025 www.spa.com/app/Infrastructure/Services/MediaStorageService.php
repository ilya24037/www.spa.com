<?php

namespace App\Infrastructure\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;

class MediaStorageService
{
    /**
     * Размеры для изображений (как на Avito)
     */
    private const IMAGE_SIZES = [
        'thumb' => [200, 150],
        'small' => [400, 300],
        'medium' => [800, 600],
        'large' => [1200, 900],
        'xlarge' => [1920, 1440]
    ];

    /**
     * Максимальные размеры
     */
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100MB

    /**
     * Генерация структуры папок для пользователя
     */
    public function getUserStoragePath(User $user, ?Ad $ad = null): string
    {
        // Шардирование по ID пользователя (распределение по папкам)
        $shardId = floor($user->id / 1000); // Каждые 1000 пользователей в отдельной папке
        
        $basePath = "users/shard_{$shardId}/{$user->id}";
        
        if ($ad) {
            $basePath .= "/ads/{$ad->id}";
        }
        
        return $basePath;
    }

    /**
     * Загрузка и обработка изображения
     */
    public function uploadImage(UploadedFile $file, User $user, ?Ad $ad = null): array
    {
        // Валидация
        if ($file->getSize() > self::MAX_IMAGE_SIZE) {
            throw new \Exception('Файл слишком большой. Максимум 10MB');
        }

        // Генерация уникального имени
        $hash = sha1_file($file->getRealPath());
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $fileName = $hash . '.' . $extension;
        
        // Проверка на дубликаты (дедупликация)
        $existingFile = \DB::table('media_files')
            ->where('hash', $hash)
            ->where('user_id', $user->id)
            ->first();
            
        if ($existingFile) {
            return json_decode($existingFile->variants, true);
        }

        // Путь для сохранения
        $basePath = $this->getUserStoragePath($user, $ad);
        $photoPath = "{$basePath}/photos";
        
        // Сохранение оригинала
        $originalPath = "{$photoPath}/original/{$fileName}";
        Storage::disk('public')->put($originalPath, file_get_contents($file));
        
        // Создание разных размеров
        $variants = ['original' => $originalPath];
        
        foreach (self::IMAGE_SIZES as $sizeName => $dimensions) {
            $variantPath = "{$photoPath}/{$sizeName}/{$fileName}";
            
            // Используем Intervention Image для ресайза
            $image = Image::make($file);
            $image->fit($dimensions[0], $dimensions[1], function ($constraint) {
                $constraint->upsize(); // Не увеличивать маленькие изображения
            });
            
            // Оптимизация качества
            $image->encode('jpg', 85);
            
            // Добавление водяного знака (опционально)
            if ($sizeName !== 'thumb') {
                $this->addWatermark($image);
            }
            
            Storage::disk('public')->put($variantPath, $image->stream());
            $variants[$sizeName] = $variantPath;
        }

        // Сохранение в БД
        \DB::table('media_files')->insert([
            'user_id' => $user->id,
            'ad_id' => $ad?->id,
            'type' => 'photo',
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $fileName,
            'path' => $originalPath,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'variants' => json_encode($variants),
            'metadata' => json_encode($this->extractImageMetadata($file)),
            'hash' => $hash,
            'is_processed' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $variants;
    }

    /**
     * Загрузка видео
     */
    public function uploadVideo(UploadedFile $file, User $user, ?Ad $ad = null): string
    {
        if ($file->getSize() > self::MAX_VIDEO_SIZE) {
            throw new \Exception('Видео слишком большое. Максимум 100MB');
        }

        $hash = sha1_file($file->getRealPath());
        $fileName = $hash . '.' . $file->getClientOriginalExtension();
        
        $basePath = $this->getUserStoragePath($user, $ad);
        $videoPath = "{$basePath}/videos/original/{$fileName}";
        
        Storage::disk('public')->put($videoPath, file_get_contents($file));

        // Создание превью (первый кадр)
        $this->generateVideoThumbnail($videoPath, "{$basePath}/videos/preview/{$hash}.jpg");

        // Сохранение в БД
        \DB::table('media_files')->insert([
            'user_id' => $user->id,
            'ad_id' => $ad?->id,
            'type' => 'video',
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $fileName,
            'path' => $videoPath,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'hash' => $hash,
            'is_processed' => false, // Будет обработано в очереди
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Отправка в очередь для обработки
        \App\Jobs\ProcessVideoJob::dispatch($videoPath)->onQueue('media');

        return $videoPath;
    }

    /**
     * Получение URL для доступа к файлу
     */
    public function getPublicUrl(string $path, string $variant = 'medium'): string
    {
        // Для production используем CDN
        if (app()->environment('production')) {
            $cdnUrl = config('services.cdn.url');
            return "{$cdnUrl}/{$path}";
        }
        
        // Для локальной разработки
        return Storage::disk('public')->url($path);
    }

    /**
     * Очистка старых файлов
     */
    public function cleanupOldFiles(int $daysOld = 30): int
    {
        $deletedCount = 0;
        
        // Находим файлы без привязки к объявлениям
        $orphanedFiles = \DB::table('media_files')
            ->whereNull('ad_id')
            ->where('created_at', '<', now()->subDays($daysOld))
            ->get();
            
        foreach ($orphanedFiles as $file) {
            $variants = json_decode($file->variants, true) ?? [];
            
            // Удаляем все варианты файла
            foreach ($variants as $variantPath) {
                Storage::disk('public')->delete($variantPath);
            }
            
            // Удаляем запись из БД
            \DB::table('media_files')->where('id', $file->id)->delete();
            $deletedCount++;
        }
        
        return $deletedCount;
    }

    /**
     * Миграция существующих файлов на новую структуру
     */
    public function migrateExistingFiles(): void
    {
        $ads = Ad::whereNotNull('photos')->orWhereNotNull('video')->get();
        
        foreach ($ads as $ad) {
            $user = $ad->user;
            $newPhotoPaths = [];
            $newVideoPaths = [];
            
            // Миграция фото
            if ($ad->photos) {
                $photos = json_decode($ad->photos, true);
                foreach ($photos as $oldPath) {
                    $newPath = $this->migrateFile($oldPath, $user, $ad, 'photo');
                    if ($newPath) {
                        $newPhotoPaths[] = $newPath;
                    }
                }
            }
            
            // Миграция видео
            if ($ad->video) {
                $videos = json_decode($ad->video, true);
                foreach ($videos as $oldPath) {
                    $newPath = $this->migrateFile($oldPath, $user, $ad, 'video');
                    if ($newPath) {
                        $newVideoPaths[] = $newPath;
                    }
                }
            }
            
            // Обновление путей в БД
            $ad->update([
                'photos' => json_encode($newPhotoPaths),
                'video' => json_encode($newVideoPaths),
                'user_folder' => $this->getUserStoragePath($user),
                'media_paths' => json_encode([
                    'photos' => $newPhotoPaths,
                    'videos' => $newVideoPaths
                ])
            ]);
        }
    }

    /**
     * Вспомогательные методы
     */
    private function migrateFile(string $oldPath, User $user, Ad $ad, string $type): ?string
    {
        $oldFullPath = storage_path('app/public' . str_replace('/storage', '', $oldPath));
        
        if (!file_exists($oldFullPath)) {
            return null;
        }
        
        $fileName = basename($oldPath);
        $newBasePath = $this->getUserStoragePath($user, $ad);
        
        if ($type === 'photo') {
            $newPath = "{$newBasePath}/photos/original/{$fileName}";
        } else {
            $newPath = "{$newBasePath}/videos/original/{$fileName}";
        }
        
        // Создание директорий
        Storage::disk('public')->makeDirectory(dirname($newPath));
        
        // Перемещение файла
        Storage::disk('public')->move(
            str_replace('/storage/', '', $oldPath),
            $newPath
        );
        
        return "/storage/{$newPath}";
    }

    private function addWatermark($image): void
    {
        // Добавление водяного знака
        $watermark = Image::make(public_path('images/watermark.png'));
        $image->insert($watermark, 'bottom-right', 10, 10);
    }

    private function generateVideoThumbnail(string $videoPath, string $thumbnailPath): void
    {
        // Используем FFMpeg для генерации превью
        // Требует установки ffmpeg на сервере
        $fullVideoPath = storage_path('app/public/' . $videoPath);
        $fullThumbPath = storage_path('app/public/' . $thumbnailPath);
        
        $cmd = "ffmpeg -i {$fullVideoPath} -ss 00:00:01.000 -vframes 1 {$fullThumbPath} 2>&1";
        exec($cmd);
    }

    private function extractImageMetadata(UploadedFile $file): array
    {
        $metadata = [];
        
        // Получение EXIF данных
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($file->getRealPath());
            if ($exif) {
                $metadata['camera'] = $exif['Make'] ?? null;
                $metadata['model'] = $exif['Model'] ?? null;
                $metadata['datetime'] = $exif['DateTime'] ?? null;
            }
        }
        
        // Размеры изображения
        list($width, $height) = getimagesize($file->getRealPath());
        $metadata['width'] = $width;
        $metadata['height'] = $height;
        
        return $metadata;
    }
}