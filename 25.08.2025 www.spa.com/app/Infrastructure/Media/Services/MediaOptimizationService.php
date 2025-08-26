<?php

namespace App\Infrastructure\Media\Services;

use App\Domain\Media\Models\Media;

/**
 * Сервис оптимизации медиафайлов
 */
class MediaOptimizationService
{
    /**
     * Оптимизация изображения
     */
    public function optimizeImage(Media $media, $image): void
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
        // TODO: Обновление размера файла через MediaRepository
        // $this->mediaRepository->update($media->id, ['size' => $newSize]);
    }
}