<?php

namespace App\Infrastructure\Media\Services;

use App\Domain\Media\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Сервис генерации миниатюр для медиафайлов
 */
class MediaThumbnailService
{
    /**
     * Генерация миниатюр для изображения
     */
    public function generateImageThumbnails(Media $media, $image): void
    {
        $thumbnailSizes = $media->type->getThumbnailSizes();
        
        foreach ($thumbnailSizes as $name => $size) {
            $this->generateImageThumbnail($media, $image, $name, $size);
        }
    }

    /**
     * Генерация миниатюр для видео
     */
    public function generateVideoThumbnails(Media $media): void
    {
        $thumbnailSizes = $media->type->getThumbnailSizes();
        
        foreach ($thumbnailSizes as $name => $size) {
            $this->generateVideoThumbnail($media, $name, $size);
        }
    }

    /**
     * Генерация миниатюры изображения
     */
    private function generateImageThumbnail(Media $media, $image, string $name, array $size): void
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

    /**
     * Генерация миниатюры видео
     */
    private function generateVideoThumbnail(Media $media, string $name, array $size): void
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

    /**
     * Получение пути для миниатюры
     */
    private function getThumbnailPath(Media $media, string $conversion, ?string $extension = null): string
    {
        $extension = $extension ?? pathinfo($media->file_name, PATHINFO_EXTENSION);
        $baseName = pathinfo($media->file_name, PATHINFO_FILENAME);
        $directory = dirname($media->file_name);
        
        return $directory . '/' . $baseName . '_' . $conversion . '.' . $extension;
    }
}