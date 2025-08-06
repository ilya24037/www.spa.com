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

    public function processMediaAsync(Media $media): void
    {
        dispatch(function () use ($media) {
            $this->processMedia($media);
        });
    }

    public function processMedia(Media $media): Media
    {
        try {
            $media->markAsProcessing();

            $processorMethod = $this->processors[$media->type->value] ?? null;
            
            if ($processorMethod && method_exists($this, $processorMethod)) {
                $this->$processorMethod($media);
            }

            $media->markAsProcessed();
            
            Log::info("Media processed successfully", ['media_id' => $media->id]);
            
        } catch (\Exception $e) {
            $media->markAsFailed($e->getMessage());
            Log::error("Media processing failed", [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
        }

        return $media;
    }

    protected function processImage(Media $media): void
    {
        if (!$media->exists()) {
            throw new \Exception('Файл не найден');
        }

        $image = Image::make($media->full_path);
        
        $metadata = $media->getMetadata();
        $metadata['dimensions'] = [
            'width' => $image->width(),
            'height' => $image->height()
        ];
        
        $media->updateMetadata($metadata);

        $thumbnailSizes = $media->type->getThumbnailSizes();
        
        foreach ($thumbnailSizes as $name => $size) {
            $this->generateImageThumbnail($media, $image, $name, $size);
        }

        if ($media->type->supportsOptimization()) {
            $this->optimizeImage($media, $image);
        }
    }

    protected function processAvatar(Media $media): void
    {
        $this->processImage($media);
        
        $image = Image::make($media->full_path);
        
        if ($image->width() !== $image->height()) {
            $size = min($image->width(), $image->height());
            $image->crop($size, $size);
            $image->save($media->full_path);
        }
    }

    protected function processVideo(Media $media): void
    {
        if (!$media->exists()) {
            throw new \Exception('Файл не найден');
        }

        try {
            $videoInfo = $this->getVideoInfo($media->full_path);
            
            $metadata = $media->getMetadata();
            $metadata = array_merge($metadata, $videoInfo);
            $media->updateMetadata($metadata);

            $thumbnailSizes = $media->type->getThumbnailSizes();
            
            foreach ($thumbnailSizes as $name => $size) {
                $this->generateVideoThumbnail($media, $name, $size);
            }
            
        } catch (\Exception $e) {
            Log::warning("Video processing partially failed", [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function processAudio(Media $media): void
    {
        if (!$media->exists()) {
            throw new \Exception('Файл не найден');
        }

        try {
            $audioInfo = $this->getAudioInfo($media->full_path);
            
            $metadata = $media->getMetadata();
            $metadata = array_merge($metadata, $audioInfo);
            $media->updateMetadata($metadata);
            
        } catch (\Exception $e) {
            Log::warning("Audio processing failed", [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function processDocument(Media $media): void
    {
        if (!$media->exists()) {
            throw new \Exception('Файл не найден');
        }

        $metadata = $media->getMetadata();
        $metadata['file_size_readable'] = $media->human_readable_size;
        $media->updateMetadata($metadata);
    }

    protected function generateImageThumbnail(Media $media, $image, string $name, array $size): void
    {
        [$width, $height] = $size;
        
        $thumbnail = clone $image;
        $thumbnail->fit($width, $height, function ($constraint) {
            $constraint->upsize();
        });

        $thumbnailPath = $this->getThumbnailPath($media, $name);
        $thumbnail->save(Storage::disk($media->disk)->path($thumbnailPath));

        $media->addConversion($name, [
            'file_name' => $thumbnailPath,
            'width' => $width,
            'height' => $height,
            'size' => Storage::disk($media->disk)->size($thumbnailPath),
        ]);
    }

    protected function generateVideoThumbnail(Media $media, string $name, array $size): void
    {
        [$width, $height] = $size;
        
        $thumbnailPath = $this->getThumbnailPath($media, $name, 'jpg');
        $fullThumbnailPath = Storage::disk($media->disk)->path($thumbnailPath);
        
        $command = sprintf(
            'ffmpeg -i "%s" -ss 00:00:01.000 -vframes 1 -s %dx%d "%s" 2>/dev/null',
            $media->full_path,
            $width,
            $height,
            $fullThumbnailPath
        );

        if (exec($command) !== false && file_exists($fullThumbnailPath)) {
            $media->addConversion($name, [
                'file_name' => $thumbnailPath,
                'width' => $width,
                'height' => $height,
                'size' => Storage::disk($media->disk)->size($thumbnailPath),
            ]);
        }
    }

    protected function optimizeImage(Media $media, $image): void
    {
        $maxWidth = config('media.optimization.max_width', 1920);
        $maxHeight = config('media.optimization.max_height', 1080);
        $quality = config('media.optimization.quality', 85);

        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            $image->resize($maxWidth, $maxHeight, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $image->save($media->full_path, $quality);
        
        $newSize = filesize($media->full_path);
        $this->mediaRepository->update($media->id, ['size' => $newSize]);
    }

    protected function getVideoInfo(string $path): array
    {
        $command = sprintf('ffprobe -v quiet -print_format json -show_format -show_streams "%s"', $path);
        $output = shell_exec($command);
        
        if (!$output) {
            return [];
        }

        $info = json_decode($output, true);
        
        $videoStream = collect($info['streams'] ?? [])
            ->first(fn($stream) => $stream['codec_type'] === 'video');

        return [
            'duration' => $info['format']['duration'] ?? null,
            'bitrate' => $info['format']['bit_rate'] ?? null,
            'dimensions' => $videoStream ? [
                'width' => $videoStream['width'] ?? null,
                'height' => $videoStream['height'] ?? null,
            ] : null,
            'codec' => $videoStream['codec_name'] ?? null,
        ];
    }

    protected function getAudioInfo(string $path): array
    {
        $command = sprintf('ffprobe -v quiet -print_format json -show_format -show_streams "%s"', $path);
        $output = shell_exec($command);
        
        if (!$output) {
            return [];
        }

        $info = json_decode($output, true);
        
        $audioStream = collect($info['streams'] ?? [])
            ->first(fn($stream) => $stream['codec_type'] === 'audio');

        return [
            'duration' => $info['format']['duration'] ?? null,
            'bitrate' => $info['format']['bit_rate'] ?? null,
            'codec' => $audioStream['codec_name'] ?? null,
            'sample_rate' => $audioStream['sample_rate'] ?? null,
            'channels' => $audioStream['channels'] ?? null,
        ];
    }

    protected function getThumbnailPath(Media $media, string $conversion, ?string $extension = null): string
    {
        $extension = $extension ?? pathinfo($media->file_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/' . $baseName . '_' . $conversion . '.' . $extension;
    }

    public function delete(int $mediaId): bool
    {
        $media = $this->mediaRepository->find($mediaId);
        
        if (!$media) {
            return false;
        }

        return $media->delete();
    }

    public function forceDelete(int $mediaId): bool
    {
        return $this->mediaRepository->forceDelete($mediaId);
    }

    public function restore(int $mediaId): bool
    {
        return $this->mediaRepository->restore($mediaId);
    }

    public function attachToEntity(array $mediaIds, Model $entity, string $collection = 'default'): int
    {
        $updated = 0;
        
        foreach ($mediaIds as $mediaId) {
            $success = $this->mediaRepository->update($mediaId, [
                'mediable_type' => get_class($entity),
                'mediable_id' => $entity->id,
                'collection_name' => $collection,
            ]);
            
            if ($success) {
                $updated++;
            }
        }
        
        return $updated;
    }

    public function detachFromEntity(array $mediaIds): int
    {
        $updated = 0;
        
        foreach ($mediaIds as $mediaId) {
            $success = $this->mediaRepository->update($mediaId, [
                'mediable_type' => null,
                'mediable_id' => null,
                'collection_name' => 'unattached',
            ]);
            
            if ($success) {
                $updated++;
            }
        }
        
        return $updated;
    }

    public function reorder(Model $entity, array $mediaIds, ?string $collection = null): bool
    {
        return $this->mediaRepository->reorderForEntity($entity, $mediaIds, $collection);
    }

    public function cleanupExpired(): int
    {
        return $this->mediaRepository->cleanupExpired();
    }

    public function cleanupOrphaned(): int
    {
        return $this->mediaRepository->cleanupOrphaned();
    }

    public function getStatistics(): array
    {
        return $this->mediaRepository->getStatistics();
    }

    protected function determineMediaType(UploadedFile $file): ?MediaType
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        return MediaType::fromMimeType($mimeType) ?? MediaType::fromExtension($extension);
    }

    protected function validateFile(UploadedFile $file, MediaType $type): void
    {
        if ($file->getSize() > $type->getMaxFileSize()) {
            throw new \InvalidArgumentException('Файл слишком большой');
        }

        if (!in_array($file->getMimeType(), $type->getMimeTypes())) {
            throw new \InvalidArgumentException('Неподдерживаемый MIME-тип');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $type->getAllowedExtensions())) {
            throw new \InvalidArgumentException('Неподдерживаемое расширение файла');
        }
    }

    protected function generateFileName(UploadedFile $file, MediaType $type): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        
        return $baseName . '_' . uniqid() . '.' . $extension;
    }

    protected function getNextSortOrder(?Model $entity = null, string $collection = 'default'): int
    {
        if (!$entity) {
            return 1;
        }

        return $this->mediaRepository->countForEntity($entity, $collection) + 1;
    }
}