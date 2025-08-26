<?php

namespace App\Infrastructure\Media\Services;

use App\Domain\Media\Models\Media;
use App\Enums\MediaType;
use App\Enums\MediaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Сервис для загрузки медиафайлов
 */
class MediaUploadService
{
    public function __construct(
        private MediaValidationService $validator
    ) {}

    /**
     * Загрузка файла
     */
    public function upload(
        UploadedFile $file, 
        ?Model $entity = null, 
        string $collection = 'default',
        array $metadata = []
    ): array {
        $mediaType = $this->determineMediaType($file);
        
        if (!$mediaType) {
            throw new \InvalidArgumentException('Неподдерживаемый тип файла');
        }

        $this->validator->validate($file, $mediaType);

        $fileName = $this->generateFileName($file, $mediaType);
        $disk = config('media.disk', 'public');

        $path = $file->storeAs(
            $mediaType->getStorageDirectory(),
            $fileName,
            $disk
        );

        $fileHash = hash_file('md5', $file->getRealPath());

        return [
            'mediable_type' => $entity ? get_class($entity) : null,
            'mediable_id' => $entity?->id,
            'collection_name' => $collection,
            'name' => $file->getClientOriginalName(),
            'file_name' => $path,
            'mime_type' => $file->getMimeType(),
            'disk' => $disk,
            'size' => $file->getSize(),
            'type' => $mediaType,
            'status' => MediaStatus::PENDING,
            'metadata' => array_merge($metadata, [
                'file_hash' => $fileHash,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now()->toISOString(),
            ]),
            'sort_order' => $this->getNextSortOrder($entity, $collection),
        ];
    }

    /**
     * Определение типа медиафайла
     */
    private function determineMediaType(UploadedFile $file): ?MediaType
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        return MediaType::fromMimeType($mimeType) ?? MediaType::fromExtension($extension);
    }

    /**
     * Генерация имени файла
     */
    private function generateFileName(UploadedFile $file, MediaType $type): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        
        return $baseName . '_' . uniqid() . '.' . $extension;
    }

    /**
     * Получение следующего порядкового номера
     */
    private function getNextSortOrder(?Model $entity = null, string $collection = 'default'): int
    {
        if (!$entity) {
            return 1;
        }

        // TODO: Получение через MediaRepository
        // return $this->mediaRepository->countForEntity($entity, $collection) + 1;
        return 1; // Временная заглушка
    }
}