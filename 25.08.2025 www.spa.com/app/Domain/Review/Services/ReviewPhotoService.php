<?php

namespace App\Domain\Review\Services;

use App\Domain\Review\Models\Review;
use Illuminate\Support\Facades\Storage;

/**
 * Сервис для работы с фотографиями отзывов
 */
class ReviewPhotoService
{
    /**
     * Получить URL фотографий отзыва
     */
    public function getPhotoUrls(Review $review): array
    {
        if (empty($review->photos)) {
            return [];
        }

        return array_map(function($photo) {
            if (is_string($photo)) {
                return $this->generatePhotoUrl($photo);
            }
            
            if (is_array($photo) && isset($photo['path'])) {
                return $this->generatePhotoUrl($photo['path']);
            }
            
            return null;
        }, $review->photos);
    }

    /**
     * Получить миниатюры фотографий
     */
    public function getThumbnailUrls(Review $review, string $size = 'medium'): array
    {
        if (empty($review->photos)) {
            return [];
        }

        return array_map(function($photo) use ($size) {
            $path = is_array($photo) ? $photo['path'] : $photo;
            return $this->generateThumbnailUrl($path, $size);
        }, $review->photos);
    }

    /**
     * Проверить есть ли фотографии в отзыве
     */
    public function hasPhotos(Review $review): bool
    {
        return !empty($review->photos) && count($review->photos) > 0;
    }

    /**
     * Получить количество фотографий
     */
    public function getPhotosCount(Review $review): int
    {
        return empty($review->photos) ? 0 : count($review->photos);
    }

    /**
     * Добавить фотографию к отзыву
     */
    public function addPhoto(Review $review, string $photoPath, array $metadata = []): void
    {
        $photos = $review->photos ?? [];
        
        $photos[] = [
            'path' => $photoPath,
            'uploaded_at' => now()->toISOString(),
            'metadata' => $metadata
        ];

        $review->update(['photos' => $photos]);
    }

    /**
     * Удалить фотографию из отзыва
     */
    public function removePhoto(Review $review, string $photoPath): void
    {
        if (empty($review->photos)) {
            return;
        }

        $photos = array_filter($review->photos, function($photo) use ($photoPath) {
            $path = is_array($photo) ? $photo['path'] : $photo;
            return $path !== $photoPath;
        });

        $review->update(['photos' => array_values($photos)]);
    }

    /**
     * Сгенерировать URL фотографии
     */
    private function generatePhotoUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Сгенерировать URL миниатюры
     */
    private function generateThumbnailUrl(string $path, string $size): string
    {
        // Логика генерации миниатюр может быть вынесена в отдельный сервис
        $pathInfo = pathinfo($path);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $size . '_' . $pathInfo['basename'];
        
        return $this->generatePhotoUrl($thumbnailPath);
    }
}