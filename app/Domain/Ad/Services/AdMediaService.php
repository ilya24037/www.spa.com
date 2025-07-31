<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AdMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * Сервис для работы с медиа объявлений
 */
class AdMediaService
{
    private const MAX_PHOTOS = 20;
    private const MAX_PHOTO_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100MB
    
    private const ALLOWED_PHOTO_TYPES = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_VIDEO_TYPES = ['mp4', 'webm', 'avi', 'mov'];

    private const PHOTO_SIZES = [
        'thumbnail' => [200, 200],
        'medium' => [600, 600],
        'large' => [1200, 1200],
    ];

    /**
     * Загрузить фото для объявления
     */
    public function uploadPhoto(Ad $ad, UploadedFile $file): array
    {
        try {
            // Валидация файла
            $this->validatePhotoFile($file);

            // Проверка лимита фотографий
            $this->checkPhotoLimit($ad);

            // Генерация уникального имени
            $filename = $this->generateFilename($file);

            // Сохранение оригинала и создание разных размеров
            $paths = $this->processAndSavePhoto($file, $filename);

            // Обновление медиа объявления
            $this->addPhotoToAd($ad, $paths);

            Log::info('Photo uploaded successfully', [
                'ad_id' => $ad->id,
                'filename' => $filename,
                'size' => $file->getSize()
            ]);

            return [
                'success' => true,
                'paths' => $paths,
                'message' => 'Фото успешно загружено'
            ];

        } catch (\Exception $e) {
            Log::error('Photo upload failed', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Загрузить видео для объявления
     */
    public function uploadVideo(Ad $ad, UploadedFile $file): array
    {
        try {
            // Валидация файла
            $this->validateVideoFile($file);

            // Генерация уникального имени
            $filename = $this->generateFilename($file);

            // Сохранение видео
            $path = $this->saveVideo($file, $filename);

            // Обновление медиа объявления
            $this->setVideoForAd($ad, [
                'id' => Str::random(10),
                'filename' => $filename,
                'path' => $path,
                'url' => Storage::url($path),
                'size' => $file->getSize(),
                'type' => $file->getMimeType(),
                'name' => $file->getClientOriginalName()
            ]);

            Log::info('Video uploaded successfully', [
                'ad_id' => $ad->id,
                'filename' => $filename,
                'size' => $file->getSize()
            ]);

            return [
                'success' => true,
                'video' => [
                    'filename' => $filename,
                    'url' => Storage::url($path)
                ],
                'message' => 'Видео успешно загружено'
            ];

        } catch (\Exception $e) {
            Log::error('Video upload failed', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Удалить фото объявления
     */
    public function deletePhoto(Ad $ad, int $photoIndex): bool
    {
        try {
            $media = $ad->media;
            
            if (!$media || empty($media->photos)) {
                return false;
            }

            $photos = $media->photos;
            
            if (!isset($photos[$photoIndex])) {
                return false;
            }

            // Удаляем файлы с диска
            $photoPath = $photos[$photoIndex];
            $this->deletePhotoFiles($photoPath);

            // Удаляем из массива
            $media->removePhoto($photoIndex);

            Log::info('Photo deleted successfully', [
                'ad_id' => $ad->id,
                'photo_index' => $photoIndex
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Photo deletion failed', [
                'ad_id' => $ad->id,
                'photo_index' => $photoIndex,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Удалить видео объявления
     */
    public function deleteVideo(Ad $ad): bool
    {
        try {
            $media = $ad->media;
            
            if (!$media || !$media->hasVideo()) {
                return false;
            }

            // Удаляем файл с диска
            $video = $media->video;
            if (isset($video['path'])) {
                Storage::delete($video['path']);
            }

            // Очищаем в базе
            $media->video = null;
            $media->save();

            Log::info('Video deleted successfully', [
                'ad_id' => $ad->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Video deletion failed', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Обновить настройки медиа
     */
    public function updateMediaSettings(Ad $ad, array $settings): bool
    {
        try {
            $media = $ad->media ?? new AdMedia(['ad_id' => $ad->id]);

            if (isset($settings['show_photos_in_gallery'])) {
                $media->show_photos_in_gallery = (bool) $settings['show_photos_in_gallery'];
            }

            if (isset($settings['allow_download_photos'])) {
                $media->allow_download_photos = (bool) $settings['allow_download_photos'];
            }

            if (isset($settings['watermark_photos'])) {
                $media->watermark_photos = (bool) $settings['watermark_photos'];
            }

            $media->save();

            return true;

        } catch (\Exception $e) {
            Log::error('Media settings update failed', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Валидация фото файла
     */
    private function validatePhotoFile(UploadedFile $file): void
    {
        // Проверка размера
        if ($file->getSize() > self::MAX_PHOTO_SIZE) {
            throw new \InvalidArgumentException('Размер фото не должен превышать 10MB');
        }

        // Проверка типа
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_PHOTO_TYPES)) {
            throw new \InvalidArgumentException('Разрешены только файлы: ' . implode(', ', self::ALLOWED_PHOTO_TYPES));
        }

        // Проверка что это действительно изображение
        if (!$file->isValid() || !getimagesize($file->getPathname())) {
            throw new \InvalidArgumentException('Файл не является корректным изображением');
        }
    }

    /**
     * Валидация видео файла
     */
    private function validateVideoFile(UploadedFile $file): void
    {
        // Проверка размера
        if ($file->getSize() > self::MAX_VIDEO_SIZE) {
            throw new \InvalidArgumentException('Размер видео не должен превышать 100MB');
        }

        // Проверка типа
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_VIDEO_TYPES)) {
            throw new \InvalidArgumentException('Разрешены только видео файлы: ' . implode(', ', self::ALLOWED_VIDEO_TYPES));
        }

        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Файл не является корректным видео');
        }
    }

    /**
     * Проверка лимита фотографий
     */
    private function checkPhotoLimit(Ad $ad): void
    {
        $media = $ad->media;
        
        if ($media && $media->getPhotosCountAttribute() >= self::MAX_PHOTOS) {
            throw new \InvalidArgumentException('Максимальное количество фото: ' . self::MAX_PHOTOS);
        }
    }

    /**
     * Генерация уникального имени файла
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return 'ad_' . time() . '_' . Str::random(10) . '.' . $extension;
    }

    /**
     * Обработка и сохранение фото в разных размерах
     */
    private function processAndSavePhoto(UploadedFile $file, string $filename): array
    {
        $paths = [];
        $basePath = 'public/ads/photos';

        // Создание разных размеров
        foreach (self::PHOTO_SIZES as $sizeName => [$width, $height]) {
            $image = Image::make($file);
            
            // Изменение размера с сохранением пропорций
            $image->fit($width, $height, function ($constraint) {
                $constraint->upsize();
            });

            // Оптимизация качества
            $quality = $sizeName === 'thumbnail' ? 80 : 90;
            $image->encode('jpg', $quality);

            // Путь для сохранения
            $sizePath = $basePath . '/' . $sizeName . '/' . $filename;
            
            // Сохранение
            Storage::put($sizePath, $image->stream());
            
            $paths[$sizeName] = $sizePath;
        }

        return $paths;
    }

    /**
     * Сохранение видео
     */
    private function saveVideo(UploadedFile $file, string $filename): string
    {
        $path = 'public/ads/videos/' . $filename;
        
        Storage::putFileAs('public/ads/videos', $file, $filename);
        
        return $path;
    }

    /**
     * Добавление фото к объявлению
     */
    private function addPhotoToAd(Ad $ad, array $paths): void
    {
        $media = $ad->media ?? new AdMedia(['ad_id' => $ad->id]);

        // Используем URL среднего размера для основного массива
        $photoUrl = Storage::url($paths['medium']);
        $media->addPhoto($photoUrl);
    }

    /**
     * Установка видео для объявления
     */
    private function setVideoForAd(Ad $ad, array $videoData): void
    {
        $media = $ad->media ?? new AdMedia(['ad_id' => $ad->id]);
        
        $media->video = $videoData;
        $media->save();
    }

    /**
     * Удаление файлов фото всех размеров
     */
    private function deletePhotoFiles(string $photoUrl): void
    {
        // Получаем базовое имя файла из URL
        $filename = basename($photoUrl);
        
        // Удаляем все размеры
        foreach (array_keys(self::PHOTO_SIZES) as $sizeName) {
            $path = 'public/ads/photos/' . $sizeName . '/' . $filename;
            Storage::delete($path);
        }
    }

    /**
     * Получить статистику по медиа
     */
    public function getMediaStats(): array
    {
        return [
            'total_photos' => AdMedia::whereNotNull('photos')
                ->where('photos', '!=', '[]')
                ->count(),
            'total_videos' => AdMedia::whereNotNull('video')
                ->where('video', '!=', '[]')
                ->count(),
            'disk_usage' => [
                'photos' => $this->calculateDirectorySize('public/ads/photos'),
                'videos' => $this->calculateDirectorySize('public/ads/videos'),
            ]
        ];
    }

    /**
     * Рассчитать размер директории
     */
    private function calculateDirectorySize(string $directory): int
    {
        $size = 0;
        $files = Storage::allFiles($directory);
        
        foreach ($files as $file) {
            $size += Storage::size($file);
        }
        
        return $size;
    }
}