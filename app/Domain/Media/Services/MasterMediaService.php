<?php

namespace App\Domain\Media\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Models\Photo as MasterPhoto;
use App\Domain\Media\Models\Video as MasterVideo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Основной сервис для работы с медиа
 * Координирует работу процессоров изображений и видео
 */
class MasterMediaService
{
    private ImageProcessor $imageProcessor;
    private VideoProcessor $videoProcessor;
    private ThumbnailGenerator $thumbnailGenerator;

    public function __construct(
        ImageProcessor $imageProcessor,
        VideoProcessor $videoProcessor,
        ThumbnailGenerator $thumbnailGenerator
    ) {
        $this->imageProcessor = $imageProcessor;
        $this->videoProcessor = $videoProcessor;
        $this->thumbnailGenerator = $thumbnailGenerator;
    }

    /**
     * Загрузить фотографии мастера
     */
    public function uploadPhotos(MasterProfile $master, array $files): array
    {
        $uploadedPhotos = [];
        
        DB::beginTransaction();
        try {
            foreach ($files as $index => $file) {
                $photoNumber = $index + 1;
                $uploadedPhotos[] = $this->imageProcessor->processPhoto($file, $master, $photoNumber);
            }
            
            DB::commit();
            return $uploadedPhotos;
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Удаляем загруженные файлы при ошибке
            foreach ($uploadedPhotos as $photo) {
                $this->deletePhoto($photo);
            }
            
            throw $e;
        }
    }

    /**
     * Загрузить видео мастера
     */
    public function uploadVideo(MasterProfile $master, UploadedFile $file): MasterVideo
    {
        return $this->videoProcessor->processVideo($file, $master);
    }

    /**
     * Загрузить аватар мастера
     */
    public function uploadAvatar(MasterProfile $master, UploadedFile $file): string
    {
        $avatarPath = $this->imageProcessor->processAvatar($file, $master);
        
        // Обновляем путь к аватару в профиле
        $master->update(['avatar' => $avatarPath]);
        
        return $avatarPath;
    }

    /**
     * Удалить фотографию
     */
    public function deletePhoto(MasterPhoto $photo): void
    {
        $this->imageProcessor->deletePhoto($photo);
    }

    /**
     * Удалить видео
     */
    public function deleteVideo(MasterVideo $video): void
    {
        $this->videoProcessor->deleteVideo($video);
    }

    /**
     * Переупорядочить фотографии
     */
    public function reorderPhotos(MasterProfile $master, array $photoIds): void
    {
        foreach ($photoIds as $order => $photoId) {
            MasterPhoto::where('id', $photoId)
                ->where('master_profile_id', $master->id)
                ->update(['sort_order' => $order + 1]);
        }
    }

    /**
     * Установить главное фото
     */
    public function setMainPhoto(MasterProfile $master, int $photoId): void
    {
        // Снимаем флаг главного со всех фото
        MasterPhoto::where('master_profile_id', $master->id)
            ->update(['is_main' => false]);
        
        // Устанавливаем новое главное фото
        MasterPhoto::where('id', $photoId)
            ->where('master_profile_id', $master->id)
            ->update(['is_main' => true]);
    }

    /**
     * Получить статистику медиа мастера
     */
    public function getMediaStats(MasterProfile $master): array
    {
        return [
            'photos_count' => $master->photos()->count(),
            'videos_count' => $master->videos()->count(),
            'total_size' => $this->calculateTotalSize($master),
            'approved_photos' => $master->photos()->where('is_approved', true)->count(),
            'approved_videos' => $master->videos()->where('is_approved', true)->count(),
        ];
    }

    /**
     * Вычислить общий размер медиа файлов
     */
    private function calculateTotalSize(MasterProfile $master): int
    {
        $photosSize = $master->photos()->sum('file_size');
        $videosSize = $master->videos()->sum('file_size');
        
        return $photosSize + $videosSize;
    }

    /**
     * Универсальный метод для обработки фото (для объявлений)
     * Возвращает массив с данными о фото для сохранения в JSON
     */
    public function processPhotoForStorage(UploadedFile $file, string $context = 'ad'): array
    {
        // Валидация
        $this->imageProcessor->validatePhotoFilePublic($file);
        
        // Генерация уникального имени
        $filename = $this->generateUniqueFilename($file);
        
        // Обработка и сохранение разных размеров
        $paths = $this->imageProcessor->processAndSaveMultipleSizes($file, $filename, $context);
        
        // Возвращаем данные для сохранения в JSON поле
        return [
            'id' => Str::uuid()->toString(),
            'filename' => $filename,
            'paths' => $paths,
            'preview' => Storage::url($paths['medium'] ?? $paths['original']),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'uploaded_at' => now()->toIso8601String()
        ];
    }

    /**
     * Обработать несколько фото для хранения в JSON
     */
    public function processMultiplePhotosForStorage(array $files, string $context = 'ad'): array
    {
        $processedPhotos = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $processedPhotos[] = $this->processPhotoForStorage($file, $context);
            }
        }
        
        return $processedPhotos;
    }

    /**
     * Удалить фото из хранилища по данным из JSON
     */
    public function deletePhotoFromStorage(array $photoData): void
    {
        if (isset($photoData['paths']) && is_array($photoData['paths'])) {
            foreach ($photoData['paths'] as $path) {
                Storage::delete($path);
            }
        }
    }

    /**
     * Генерация уникального имени файла
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '_' . time() . '.' . $extension;
    }

    /**
     * Проверить лимиты медиа
     */
    public function checkMediaLimits(MasterProfile $master, string $type = 'photo'): bool
    {
        $limits = [
            'photo' => 10,
            'video' => 3,
        ];
        
        $count = $type === 'photo' 
            ? $master->photos()->count()
            : $master->videos()->count();
            
        return $count < ($limits[$type] ?? 0);
    }
}