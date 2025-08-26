<?php

namespace App\Infrastructure\Media;

use App\Domain\Media\Models\Media;
use App\Infrastructure\Media\Services\MediaUploadService;
use App\Infrastructure\Media\Services\MediaProcessorService;
use App\Infrastructure\Media\Services\MediaManagementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Главный медиа-сервис - координатор операций
 */
class MediaService
{
    public function __construct(
        private MediaUploadService $uploadService,
        private MediaProcessorService $processorService,
        private MediaManagementService $managementService
        // private MediaRepository $mediaRepository // ВРЕМЕННО ОТКЛЮЧЕНО
    ) {}

    /**
     * Загрузка медиафайла
     */
    public function upload(
        UploadedFile $file, 
        ?Model $entity = null, 
        string $collection = 'default',
        array $metadata = []
    ): Media {
        $mediaData = $this->uploadService->upload($file, $entity, $collection, $metadata);

        // TODO: Создание через MediaRepository
        // $media = $this->mediaRepository->create($mediaData);
        $media = new Media($mediaData); // Временная заглушка

        $this->processMediaAsync($media);

        return $media;
    }

    /**
     * Асинхронная обработка медиафайла
     */
    public function processMediaAsync(Media $media): void
    {
        $this->processorService->processAsync($media);
    }

    /**
     * Синхронная обработка медиафайла
     */
    public function processMedia(Media $media): Media
    {
        return $this->processorService->process($media);
    }

    // === МЕТОДЫ УПРАВЛЕНИЯ МЕДИАФАЙЛАМИ ===

    /**
     * Удаление медиафайла
     */
    public function delete(int $mediaId): bool
    {
        return $this->managementService->delete($mediaId);
    }

    /**
     * Принудительное удаление медиафайла
     */
    public function forceDelete(int $mediaId): bool
    {
        return $this->managementService->forceDelete($mediaId);
    }

    /**
     * Восстановление медиафайла
     */
    public function restore(int $mediaId): bool
    {
        return $this->managementService->restore($mediaId);
    }

    /**
     * Привязка медиафайлов к сущности
     */
    public function attachToEntity(array $mediaIds, Model $entity, string $collection = 'default'): int
    {
        return $this->managementService->attachToEntity($mediaIds, $entity, $collection);
    }

    /**
     * Отвязка медиафайлов от сущности
     */
    public function detachFromEntity(array $mediaIds): int
    {
        return $this->managementService->detachFromEntity($mediaIds);
    }

    /**
     * Переупорядочивание медиафайлов
     */
    public function reorder(Model $entity, array $mediaIds, ?string $collection = null): bool
    {
        return $this->managementService->reorder($entity, $mediaIds, $collection);
    }

    /**
     * Очистка истёкших медиафайлов
     */
    public function cleanupExpired(): int
    {
        return $this->managementService->cleanupExpired();
    }

    /**
     * Очистка неприкреплённых медиафайлов
     */
    public function cleanupOrphaned(): int
    {
        return $this->managementService->cleanupOrphaned();
    }

    /**
     * Получение статистики медиафайлов
     */
    public function getStatistics(): array
    {
        return $this->managementService->getStatistics();
    }

}