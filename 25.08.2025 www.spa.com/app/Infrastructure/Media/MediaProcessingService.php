<?php

namespace App\Infrastructure\Media;

use App\Domain\Media\Services\MediaService;
use App\Domain\Media\Services\ImageProcessor;
use App\Domain\Media\Services\VideoProcessor;
use App\Domain\Media\Services\ThumbnailService;
use App\Domain\Media\Models\Photo;
use App\Domain\Media\Models\Video;
use App\Domain\Media\Models\Video as MasterVideo;
use App\Domain\Master\Models\MasterProfile;
use Illuminate\Http\UploadedFile;

/**
 * Legacy MediaProcessingService для обратной совместимости
 * Использует новую доменную структуру
 */
class MediaProcessingService
{
    private MediaService $mediaService;
    private ImageProcessor $imageProcessor;
    private VideoProcessor $videoProcessor;

    public function __construct()
    {
        $thumbnailGenerator = app(ThumbnailService::class);
        $this->imageProcessor = app(ImageProcessor::class);
        $this->videoProcessor = app(VideoProcessor::class);
        $photoRepository = app(\App\Domain\Media\Repositories\PhotoRepository::class);
        $videoRepository = app(\App\Domain\Media\Repositories\VideoRepository::class);
        $masterRepository = app(\App\Domain\Master\Repositories\MasterRepository::class);
        
        $this->mediaService = new MediaService(
            $this->imageProcessor,
            $this->videoProcessor,
            $thumbnailGenerator,
            $photoRepository,
            $videoRepository,
            $masterRepository
        );
    }

    /**
     * Загрузить и обработать фотографии мастера
     */
    public function uploadPhotos(MasterProfile $master, array $files): array
    {
        return $this->mediaService->uploadPhotos($master, $files);
    }

    /**
     * Загрузить и обработать видео мастера
     */
    public function uploadVideo(MasterProfile $master, UploadedFile $file): MasterVideo
    {
        return $this->mediaService->uploadVideo($master, $file);
    }

    /**
     * Загрузить и обработать аватар мастера
     */
    public function uploadAvatar(MasterProfile $master, UploadedFile $file): bool
    {
        $this->mediaService->uploadAvatar($master, $file);
        return true;
    }

    /**
     * Обработать и сохранить фото
     * @deprecated Используйте MediaService::uploadPhotos()
     */
    private function processPhoto(UploadedFile $file, MasterProfile $master, int $photoNumber): Photo
    {
        return $this->imageProcessor->processPhoto($file, $master, $photoNumber);
    }

    /**
     * Обработать и сохранить видео
     * @deprecated Используйте MediaService::uploadVideo()
     */
    private function processVideo(UploadedFile $file, MasterProfile $master): MasterVideo
    {
        return $this->videoProcessor->processVideo($file, $master);
    }

    /**
     * Удалить фотографию
     */
    public function deletePhoto(Photo $photo): void
    {
        $this->mediaService->deletePhoto($photo);
    }

    /**
     * Удалить видео
     */
    public function deleteVideo(MasterVideo $video): void
    {
        $this->mediaService->deleteVideo($video);
    }

    /**
     * Переупорядочить фотографии
     */
    public function reorderPhotos(MasterProfile $master, array $photoIds): void
    {
        $this->mediaService->reorderPhotos($master, $photoIds);
    }

    /**
     * Установить главное фото
     */
    public function setMainPhoto(MasterProfile $master, int $photoId): void
    {
        $this->mediaService->setMainPhoto($master, $photoId);
    }

    /**
     * Получить статистику медиа
     */
    public function getMediaStats(MasterProfile $master): array
    {
        return $this->mediaService->getMediaStats($master);
    }

    /**
     * Проверить лимиты медиа
     */
    public function checkMediaLimits(MasterProfile $master, string $type = 'photo'): bool
    {
        return $this->mediaService->checkMediaLimits($master, $type);
    }
} 
