<?php

namespace App\Domain\Media\Services;

use App\Domain\Media\Models\Media;
use App\Domain\Master\Models\MasterProfile;
// use App\Domain\Media\Repositories\MediaRepository; // ВРЕМЕННО ОТКЛЮЧЕНО
use App\Domain\Common\Services\BaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Сервис для работы с медиа мастеров
 * Согласно карте рефакторинга - выделен из MediaService
 */
class MasterMediaService extends BaseService
{
    public function __construct(
        // private MediaRepository $mediaRepository, // ВРЕМЕННО ОТКЛЮЧЕНО
        private MediaService $mediaService,
        private ImageProcessor $imageProcessor
    ) {}

    /**
     * Загрузить фото мастера
     */
    public function uploadPhoto(MasterProfile $master, UploadedFile $file, string $collection = 'photos'): Media
    {
        // Валидация файла
        $this->validatePhoto($file);

        // Обработка изображения
        $processedImage = $this->imageProcessor->process($file, [
            'resize' => [800, 600],
            'quality' => 85,
            'format' => 'jpg'
        ]);

        // Сохранение
        $media = $this->mediaService->store($processedImage, [
            'entity_type' => MasterProfile::class,
            'entity_id' => $master->id,
            'collection' => $collection,
            'alt_text' => "Фото мастера {$master->display_name}",
            'title' => $master->display_name
        ]);

        $this->log("Master photo uploaded", [
            'master_id' => $master->id,
            'media_id' => $media->id,
            'collection' => $collection
        ]);

        return $media;
    }

    /**
     * Загрузить видео мастера
     */
    public function uploadVideo(MasterProfile $master, UploadedFile $file, string $collection = 'videos'): Media
    {
        // Валидация видео
        $this->validateVideo($file);

        // Сохранение
        $media = $this->mediaService->store($file, [
            'entity_type' => MasterProfile::class,
            'entity_id' => $master->id,
            'collection' => $collection,
            'alt_text' => "Видео мастера {$master->display_name}",
            'title' => $master->display_name
        ]);

        $this->log("Master video uploaded", [
            'master_id' => $master->id,
            'media_id' => $media->id,
            'collection' => $collection
        ]);

        return $media;
    }

    /**
     * Получить все медиа мастера
     */
    public function getMasterMedia(MasterProfile $master, string $collection = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->mediaRepository->findForEntity($master, $collection);
    }

    /**
     * Удалить медиа мастера
     */
    public function deleteMedia(Media $media): bool
    {
        $deleted = $this->mediaService->delete($media);

        if ($deleted) {
            $this->log("Master media deleted", [
                'media_id' => $media->id,
                'entity_id' => $media->entity_id
            ]);
        }

        return $deleted;
    }

    /**
     * Обновить порядок медиа
     */
    public function updateOrder(MasterProfile $master, array $mediaIds, string $collection = 'photos'): bool
    {
        $media = $this->getMasterMedia($master, $collection);
        
        foreach ($mediaIds as $index => $mediaId) {
            $mediaItem = $media->firstWhere('id', $mediaId);
            if ($mediaItem) {
                $mediaItem->update(['sort_order' => $index + 1]);
            }
        }

        $this->log("Master media order updated", [
            'master_id' => $master->id,
            'collection' => $collection,
            'count' => count($mediaIds)
        ]);

        return true;
    }

    /**
     * Валидация фото
     */
    private function validatePhoto(UploadedFile $file): void
    {
        $this->validate([
            'file' => $file
        ], [
            'file' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240' // 10MB
        ]);
    }

    /**
     * Валидация видео
     */
    private function validateVideo(UploadedFile $file): void
    {
        $this->validate([
            'file' => $file
        ], [
            'file' => 'required|mimetypes:video/mp4,video/mpeg,video/quicktime|max:51200' // 50MB
        ]);
    }

    /**
     * Получить правила валидации
     */
    protected function getValidationRules(): array
    {
        return [
            'file' => 'required|file|max:51200',
            'collection' => 'string|max:255',
            'alt_text' => 'string|max:255',
            'title' => 'string|max:255'
        ];
    }
}