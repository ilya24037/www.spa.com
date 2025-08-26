<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Support\Helpers\ImageHelper;
use Illuminate\Support\Collection;

/**
 * Сервис для работы с галереей мастера
 * Отвечает за построение и форматирование галереи изображений
 */
class MasterGalleryService
{
    /**
     * Построение галереи мастера
     * 
     * @param MasterProfile $profile
     * @return array
     */
    public function buildGallery(MasterProfile $profile): array
    {
        $gallery = [];
        
        // Добавляем аватар как первое фото
        if ($profile->avatar) {
            $gallery[] = $this->createGalleryItem(
                0,
                $profile->avatar_url,
                'Фото ' . $profile->display_name,
                true
            );
        }
        
        // Добавляем остальные фото
        if ($profile->photos && $profile->photos->isNotEmpty()) {
            foreach ($profile->photos as $photo) {
                $gallery[] = $this->createGalleryItemFromPhoto($photo);
            }
        }
        
        // Если галерея пустая - добавляем заглушки
        if (empty($gallery)) {
            $gallery = $this->getPlaceholderGallery();
        }
        
        return $gallery;
    }
    
    /**
     * Создание элемента галереи
     * 
     * @param int $id
     * @param string $url
     * @param string $alt
     * @param bool $isMain
     * @return array
     */
    private function createGalleryItem(int $id, string $url, string $alt, bool $isMain = false): array
    {
        return [
            'id' => $id,
            'url' => $url,
            'thumb' => $url,
            'alt' => $alt,
            'is_main' => $isMain
        ];
    }
    
    /**
     * Создание элемента галереи из модели фото
     * 
     * @param mixed $photo
     * @return array
     */
    private function createGalleryItemFromPhoto($photo): array
    {
        return [
            'id' => $photo->id,
            'url' => ImageHelper::getImageUrl($photo->path),
            'thumb' => ImageHelper::getImageUrl($photo->path),
            'alt' => $photo->alt ?? 'Фото мастера',
            'is_main' => $photo->is_main ?? false
        ];
    }
    
    /**
     * Возвращает галерею с заглушками
     * 
     * @return array
     */
    private function getPlaceholderGallery(): array
    {
        return collect(range(1, 4))->map(fn($i) => [
            'id' => $i,
            'url' => asset("images/placeholders/master-{$i}.jpg"),
            'thumb' => asset("images/placeholders/master-{$i}-thumb.jpg"),
            'alt' => "Фото {$i}",
            'is_main' => $i === 1
        ])->toArray();
    }
    
    /**
     * Построение галереи для формы редактирования
     * 
     * @param MasterProfile $profile
     * @return Collection
     */
    public function buildEditGallery(MasterProfile $profile): Collection
    {
        if (!$profile->photos || $profile->photos->isEmpty()) {
            return collect();
        }
        
        return $profile->photos->map(function($photo) {
            return [
                'id' => $photo->id,
                'filename' => $photo->filename,
                'original_url' => $photo->original_url,
                'medium_url' => $photo->medium_url,
                'thumb_url' => $photo->thumb_url,
                'is_main' => $photo->is_main,
                'sort_order' => $photo->sort_order,
            ];
        });
    }
    
    /**
     * Извлечение главного фото
     * 
     * @param MasterProfile $profile
     * @return string|null
     */
    public function getMainPhoto(MasterProfile $profile): ?string
    {
        // Сначала пробуем аватар
        if ($profile->avatar) {
            return $profile->avatar_url;
        }
        
        // Затем ищем главное фото
        if ($profile->photos && $profile->photos->isNotEmpty()) {
            $mainPhoto = $profile->photos->firstWhere('is_main', true);
            if ($mainPhoto) {
                return ImageHelper::getImageUrl($mainPhoto->path);
            }
            
            // Если главного нет - берем первое
            $firstPhoto = $profile->photos->first();
            if ($firstPhoto) {
                return ImageHelper::getImageUrl($firstPhoto->path);
            }
        }
        
        return null;
    }
    
    /**
     * Подсчет количества фото
     * 
     * @param MasterProfile $profile
     * @return int
     */
    public function getPhotoCount(MasterProfile $profile): int
    {
        $count = 0;
        
        if ($profile->avatar) {
            $count++;
        }
        
        if ($profile->photos && $profile->photos->isNotEmpty()) {
            $count += $profile->photos->count();
        }
        
        return $count;
    }
    
    /**
     * Получение превью галереи (первые N фото)
     * 
     * @param MasterProfile $profile
     * @param int $limit
     * @return array
     */
    public function getGalleryPreview(MasterProfile $profile, int $limit = 4): array
    {
        $gallery = $this->buildGallery($profile);
        return array_slice($gallery, 0, $limit);
    }
}