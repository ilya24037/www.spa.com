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
 * MediaService - координатор медиа операций (согласно плану рефакторинга)
 * Координирует работу ImageProcessor, VideoProcessor, ThumbnailService
 */
class MediaService
{
    private ImageProcessor $imageProcessor;
    private VideoProcessor $videoProcessor;
    private ThumbnailService $thumbnailGenerator;
    private \App\Domain\Media\Repositories\PhotoRepository $photoRepository;
    private \App\Domain\Media\Repositories\VideoRepository $videoRepository;
    private \App\Domain\Master\Repositories\MasterRepository $masterRepository;

    public function __construct(
        ImageProcessor $imageProcessor,
        VideoProcessor $videoProcessor,
        ThumbnailService $thumbnailGenerator,
        \App\Domain\Media\Repositories\PhotoRepository $photoRepository,
        \App\Domain\Media\Repositories\VideoRepository $videoRepository,
        \App\Domain\Master\Repositories\MasterRepository $masterRepository
    ) {
        $this->imageProcessor = $imageProcessor;
        $this->videoProcessor = $videoProcessor;
        $this->thumbnailGenerator = $thumbnailGenerator;
        $this->photoRepository = $photoRepository;
        $this->videoRepository = $videoRepository;
        $this->masterRepository = $masterRepository;
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
        
        // Обновляем путь к аватару в профиле через репозиторий
        $this->masterRepository->update($master, ['avatar' => $avatarPath]);
        
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
        $photoOrders = [];
        foreach ($photoIds as $order => $photoId) {
            $photoOrders[$photoId] = $order + 1;
        }
        
        $this->photoRepository->updateMultipleSortOrders($photoOrders);
    }

    /**
     * Установить главное фото
     */
    public function setMainPhoto(MasterProfile $master, int $photoId): void
    {
        $this->photoRepository->setAsMain($photoId, $master->id);
    }

    /**
     * Получить статистику медиа мастера
     */
    public function getMediaStats(MasterProfile $master): array
    {
        $photosCount = $this->photoRepository->countByMasterProfileId($master->id);
        $videosCount = $this->videoRepository->countByMasterProfileId($master->id);
        
        return [
            'photos_count' => $photosCount,
            'videos_count' => $videosCount,
            'total_size' => $this->calculateTotalSize($master),
            'approved_photos' => $this->photoRepository->findByMasterProfileId($master->id)
                ->where('is_approved', true)->count(),
            'approved_videos' => $this->videoRepository->findByMasterProfileId($master->id)
                ->where('is_approved', true)->count(),
        ];
    }

    /**
     * Вычислить общий размер медиа файлов
     */
    private function calculateTotalSize(MasterProfile $master): int
    {
        $photosSize = $this->photoRepository->findByMasterProfileId($master->id)->sum('file_size');
        $videosSize = $this->videoRepository->getTotalSizeByMasterProfileId($master->id);
        
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