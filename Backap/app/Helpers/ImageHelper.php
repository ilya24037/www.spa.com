<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageHelper
{
    /**
     * Создать миниатюру
     */
    public static function createThumbnail($file, $fileName, $width = 300, $height = 300)
    {
        try {
            $image = Image::make($file);
            
            // Изменяем размер с сохранением пропорций
            $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            // Оптимизируем качество
            $image->encode('jpg', 85);
            
            // Сохраняем миниатюру
            $thumbFileName = 'thumb_' . $width . 'x' . $height . '_' . $fileName;
            $thumbPath = $thumbFileName;
            
            Storage::disk('masters_thumbnails')->put($thumbPath, $image);
            
            return $thumbPath;
        } catch (\Exception $e) {
            // Если не удалось создать миниатюру, возвращаем null
            return null;
        }
    }

    /**
     * Оптимизировать изображение
     */
    public static function optimizeImage($path)
    {
        $image = Image::make($path);
        
        // Максимальные размеры
        $image->resize(1200, 1200, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        // Сохраняем с оптимизацией
        $image->save($path, 85);
        
        return $path;
    }

    /**
     * Получить URL изображения с проверкой существования
     */
    public static function getImageUrl(?string $path, string $default = '/images/no-photo.jpg'): string
    {
        if (!$path) {
            return $default;
        }

        // Если путь уже является полным URL
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Если путь начинается с /
        if (str_starts_with($path, '/')) {
            return file_exists(public_path($path)) ? $path : $default;
        }

        // Проверяем в storage
        if (Storage::disk('public')->exists($path)) {
            return Storage::url($path);
        }

        return $default;
    }

    /**
     * Получить URL аватара пользователя
     */
    public static function getUserAvatar(?string $avatar, ?string $name = null): string
    {
        if ($avatar) {
            return self::getImageUrl($avatar, '/images/no-avatar.jpg');
        }

        // Генерируем через UI Avatars API
        if ($name) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=6366f1&color=fff&size=200';
        }

        return '/images/no-avatar.jpg';
    }

    /**
     * Получить заглушку для категории
     */
    public static function getCategoryImage(?string $slug): string
    {
        $categoryImages = [
            'lechebnyi-massazh' => '/images/categories/medical.jpg',
            'rasslablyayushchii-massazh' => '/images/categories/relax.jpg',
            'sportivnyi-massazh' => '/images/categories/sport.jpg',
            'spa-procedury' => '/images/categories/spa.jpg',
            'detskii-massazh' => '/images/categories/kids.jpg',
            'massazh-dlya-beremennykh' => '/images/categories/pregnancy.jpg',
        ];

        return $categoryImages[$slug] ?? '/images/categories/default.jpg';
    }

    /**
     * Проверить и вернуть массив изображений
     */
    public static function validateImageArray(array $images, string $default = '/images/no-photo.jpg'): array
    {
        if (empty($images)) {
            return [$default];
        }

        return array_map(fn($img) => self::getImageUrl($img, $default), $images);
    }
}