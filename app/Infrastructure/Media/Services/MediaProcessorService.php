<?php

namespace App\Infrastructure\Media\Services;

use App\Domain\Media\Models\Media;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

/**
 * Сервис обработки медиафайлов
 */
class MediaProcessorService
{
    private array $processors = [
        'image' => 'processImage',
        'video' => 'processVideo',
        'audio' => 'processAudio',
        'document' => 'processDocument',
        'avatar' => 'processAvatar',
    ];

    public function __construct(
        private MediaMetadataService $metadataService,
        private MediaThumbnailService $thumbnailService,
        private MediaOptimizationService $optimizationService
    ) {}

    /**
     * Асинхронная обработка медиафайла
     */
    public function processAsync(Media $media): void
    {
        dispatch(function () use ($media) {
            $this->process($media);
        });
    }

    /**
     * Обработка медиафайла
     */
    public function process(Media $media): Media
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

    /**
     * Обработка изображения
     */
    private function processImage(Media $media): void
    {
        if (!$media->exists()) {
            throw new \Exception('Файл не найден');
        }

        $image = Image::make($media->full_path);
        
        $this->metadataService->extractImageMetadata($media, $image);
        $this->thumbnailService->generateImageThumbnails($media, $image);

        if ($media->type->supportsOptimization()) {
            $this->optimizationService->optimizeImage($media, $image);
        }
    }

    /**
     * Обработка аватара
     */
    private function processAvatar(Media $media): void
    {
        $this->processImage($media);
        
        $image = Image::make($media->full_path);
        
        if ($image->width() !== $image->height()) {
            $size = min($image->width(), $image->height());
            $image->crop($size, $size);
            $image->save($media->full_path);
        }
    }

    /**
     * Обработка видео
     */
    private function processVideo(Media $media): void
    {
        if (!$media->exists()) {
            throw new \Exception('Файл не найден');
        }

        try {
            $this->metadataService->extractVideoMetadata($media);
            $this->thumbnailService->generateVideoThumbnails($media);
            
        } catch (\Exception $e) {
            Log::warning("Video processing partially failed", [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Обработка аудио
     */
    private function processAudio(Media $media): void
    {
        if (!$media->exists()) {
            throw new \Exception('Файл не найден');
        }

        try {
            $this->metadataService->extractAudioMetadata($media);
            
        } catch (\Exception $e) {
            Log::warning("Audio processing failed", [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Обработка документа
     */
    private function processDocument(Media $media): void
    {
        if (!$media->exists()) {
            throw new \Exception('Файл не найден');
        }

        $this->metadataService->extractDocumentMetadata($media);
    }
}