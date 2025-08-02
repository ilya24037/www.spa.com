<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Media\Services\MasterMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Адаптер для работы с медиа объявлений
 * Использует универсальный MediaService для обработки файлов
 * Сохраняет данные в JSON поля таблицы ads
 */
class AdMediaService
{
    private MasterMediaService $mediaService;
    
    // Лимиты для объявлений
    private const MAX_PHOTOS = 10;
    private const MAX_VIDEOS = 1;

    public function __construct(MasterMediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Обработать и добавить фото к объявлению
     * Фото сохраняются в JSON поле photos таблицы ads
     */
    public function addPhoto(Ad $ad, UploadedFile $file): array
    {
        try {
            // Проверяем лимит
            $currentPhotos = $ad->photos ?? [];
            if (count($currentPhotos) >= self::MAX_PHOTOS) {
                throw new \InvalidArgumentException('Максимальное количество фото: ' . self::MAX_PHOTOS);
            }

            // Обрабатываем фото через MediaService
            $photoData = $this->mediaService->processPhotoForStorage($file, 'ad');
            
            // Добавляем к существующим фото
            $photos = $currentPhotos;
            $photos[] = $photoData;
            
            // Сохраняем в базу
            $ad->update(['photos' => $photos]);
            
            Log::info('Photo added to ad', [
                'ad_id' => $ad->id,
                'photo_id' => $photoData['id'],
                'total_photos' => count($photos)
            ]);

            return [
                'success' => true,
                'photo' => $photoData,
                'message' => 'Фото успешно добавлено'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to add photo to ad', [
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
     * Добавить несколько фото к объявлению
     */
    public function addMultiplePhotos(Ad $ad, array $files): array
    {
        $results = [];
        $successCount = 0;
        $allPhotos = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $result = $this->addPhoto($ad, $file);
                if ($result['success']) {
                    $successCount++;
                    $allPhotos[] = $result['photo'];
                }
                $results[] = $result;
            }
        }
        
        // Перезагружаем объявление для получения всех фото
        $ad->refresh();
        
        return [
            'success' => $successCount > 0,
            'added' => $successCount,
            'total' => count($files),
            'results' => $results,
            'photos' => $ad->photos ?? [] // Возвращаем все фото объявления
        ];
    }

    /**
     * Удалить фото из объявления
     */
    public function removePhoto(Ad $ad, string $photoId): bool
    {
        try {
            $photos = $ad->photos ?? [];
            $photoToDelete = null;
            $newPhotos = [];
            
            // Находим и удаляем фото
            foreach ($photos as $photo) {
                if (isset($photo['id']) && $photo['id'] === $photoId) {
                    $photoToDelete = $photo;
                } else {
                    $newPhotos[] = $photo;
                }
            }
            
            if (!$photoToDelete) {
                return false;
            }
            
            // Удаляем файлы из хранилища
            $this->mediaService->deletePhotoFromStorage($photoToDelete);
            
            // Обновляем в базе
            $ad->update(['photos' => $newPhotos]);
            
            Log::info('Photo removed from ad', [
                'ad_id' => $ad->id,
                'photo_id' => $photoId,
                'remaining_photos' => count($newPhotos)
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to remove photo from ad', [
                'ad_id' => $ad->id,
                'photo_id' => $photoId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Изменить порядок фото
     */
    public function reorderPhotos(Ad $ad, array $photoIds): bool
    {
        try {
            $photos = $ad->photos ?? [];
            $reorderedPhotos = [];
            
            // Переупорядочиваем согласно новому порядку
            foreach ($photoIds as $photoId) {
                foreach ($photos as $photo) {
                    if (isset($photo['id']) && $photo['id'] === $photoId) {
                        $reorderedPhotos[] = $photo;
                        break;
                    }
                }
            }
            
            // Добавляем фото, которых не было в списке (на всякий случай)
            foreach ($photos as $photo) {
                if (isset($photo['id']) && !in_array($photo['id'], $photoIds)) {
                    $reorderedPhotos[] = $photo;
                }
            }
            
            // Сохраняем
            $ad->update(['photos' => $reorderedPhotos]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to reorder photos', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Обработать и установить видео для объявления
     */
    public function setVideo(Ad $ad, UploadedFile $file): array
    {
        try {
            // Для видео используем аналогичный подход (пока заглушка)
            // TODO: Реализовать обработку видео через VideoProcessor
            
            $videoData = [
                'id' => Str::uuid()->toString(),
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_at' => now()->toIso8601String()
            ];
            
            $ad->update(['video' => $videoData]);
            
            return [
                'success' => true,
                'video' => $videoData,
                'message' => 'Видео успешно добавлено'
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to set video for ad', [
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
     * Удалить видео из объявления
     */
    public function removeVideo(Ad $ad): bool
    {
        try {
            // TODO: Удалить файлы видео из хранилища
            
            $ad->update(['video' => null]);
            
            Log::info('Video removed from ad', ['ad_id' => $ad->id]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to remove video from ad', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Получить статистику медиа для объявления
     */
    public function getMediaStats(Ad $ad): array
    {
        $photos = $ad->photos ?? [];
        $video = $ad->video;
        
        return [
            'photos_count' => count($photos),
            'has_video' => !empty($video),
            'can_add_photos' => count($photos) < self::MAX_PHOTOS,
            'photos_limit' => self::MAX_PHOTOS,
            'videos_limit' => self::MAX_VIDEOS
        ];
    }

    /**
     * Синхронизировать фото из фронтенда
     * Используется при сохранении формы с уже загруженными фото
     */
    public function syncPhotos(Ad $ad, array $photosData): void
    {
        // Фильтруем только валидные данные о фото
        $validPhotos = array_filter($photosData, function ($photo) {
            return is_array($photo) && isset($photo['id']);
        });
        
        // Сохраняем
        $ad->update(['photos' => array_values($validPhotos)]);
        
        Log::info('Photos synced for ad', [
            'ad_id' => $ad->id,
            'photos_count' => count($validPhotos)
        ]);
    }
}