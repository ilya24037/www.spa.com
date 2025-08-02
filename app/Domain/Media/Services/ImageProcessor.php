<?php

namespace App\Domain\Media\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Models\Photo as MasterPhoto;
use App\Infrastructure\Media\ImageProcessor as InfrastructureImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Адаптер для обратной совместимости
 * Обертка над Infrastructure ImageProcessor для работы с MasterProfile
 */
class ImageProcessor
{
    private InfrastructureImageProcessor $infrastructureProcessor;
    private ThumbnailGenerator $thumbnailGenerator;

    public function __construct(InfrastructureImageProcessor $infrastructureProcessor, ThumbnailGenerator $thumbnailGenerator)
    {
        $this->infrastructureProcessor = $infrastructureProcessor;
        $this->thumbnailGenerator = $thumbnailGenerator;
    }

    /**
     * Обработать и сохранить фотографию (адаптированный метод)
     */
    public function processPhoto(UploadedFile $file, MasterProfile $master, int $photoNumber): MasterPhoto
    {
        // Временная заглушка - нужна доработка для полной совместимости
        throw new \Exception('Method processPhoto needs migration to Infrastructure layer');
    }

    /**
     * Обработать аватар (адаптированный метод)
     */
    public function processAvatar(UploadedFile $file, MasterProfile $master): string
    {
        // Временная заглушка - нужна доработка для полной совместимости
        throw new \Exception('Method processAvatar needs migration to Infrastructure layer');
    }

    /**
     * Удалить фотографию (адаптированный метод)
     */
    public function deletePhoto(MasterPhoto $photo): void
    {
        // Временная заглушка - нужна доработка для полной совместимости
        throw new \Exception('Method deletePhoto needs migration to Infrastructure layer');
    }

    /**
     * Валидировать файл изображения (адаптированный метод)
     */
    public function validatePhotoFilePublic(UploadedFile $file): void
    {
        // Временная заглушка - нужна доработка для полной совместимости
        throw new \Exception('Method validatePhotoFilePublic needs migration to Infrastructure layer');
    }

    /**
     * Обработать и сохранить изображение в нескольких размерах (адаптированный метод)
     */
    public function processAndSaveMultipleSizes(UploadedFile $file, string $filename, string $context = 'ad'): array
    {
        // Временная заглушка - нужна доработка для полной совместимости
        throw new \Exception('Method processAndSaveMultipleSizes needs migration to Infrastructure layer');
    }
}