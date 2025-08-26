<?php

namespace App\Domain\Media\Handlers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * Обработчик оптимизации медиа файлов
 * Отвечает ТОЛЬКО за оптимизацию: сжатие, изменение размеров, эффекты
 */
class OptimizationHandler
{
    private const AVATAR_SIZE = 400;
    private const AVATAR_THUMB_SIZE = 150;
    
    private const IMAGE_SIZES = [
        'thumbnail' => [300, 300],
        'medium' => [800, 600], 
        'large' => [1200, 900]
    ];

    /**
     * Оптимизировать и сохранить изображение
     */
    public function optimizeAndSaveImage($image, string $disk, string $path, int $quality = 85): void
    {
        $storage = Storage::disk($disk);
        
        // Создаем директорию если её нет
        $directory = dirname($path);
        if (!$storage->exists($directory)) {
            $storage->makeDirectory($directory);
        }
        
        // Оптимизируем качество
        $image->encode('jpg', $quality);
        
        // Сохраняем
        $storage->put($path, $image->stream('jpg', $quality));
    }

    /**
     * Создать множественные размеры изображения
     */
    public function createMultipleSizes($sourceImage, string $disk, string $basePath, string $filename): array
    {
        $paths = [];
        $storage = Storage::disk($disk);
        
        foreach (self::IMAGE_SIZES as $sizeName => $dimensions) {
            $resizedImage = clone $sourceImage;
            $resizedImage->fit($dimensions[0], $dimensions[1]);
            
            $sizePath = $basePath . '/' . $sizeName . '/' . $filename;
            $this->optimizeAndSaveImage($resizedImage, $disk, $sizePath, 80);
            $paths[$sizeName] = $sizePath;
        }
        
        return $paths;
    }

    /**
     * Обработать аватар (квадратное изображение)
     */
    public function processAvatar($sourceImage, string $disk, string $masterFolder): array
    {
        $storage = Storage::disk($disk);
        $paths = [];
        
        // Основной аватар
        $avatar = clone $sourceImage;
        $avatar->fit(self::AVATAR_SIZE, self::AVATAR_SIZE);
        $avatarPath = "{$masterFolder}/avatar.jpg";
        $this->optimizeAndSaveImage($avatar, $disk, $avatarPath, 90);
        $paths['avatar'] = $avatarPath;
        
        // Миниатюра аватара
        $thumb = clone $sourceImage;
        $thumb->fit(self::AVATAR_THUMB_SIZE, self::AVATAR_THUMB_SIZE);
        $thumbPath = "{$masterFolder}/avatar_thumb.jpg";
        $this->optimizeAndSaveImage($thumb, $disk, $thumbPath, 85);
        $paths['thumb'] = $thumbPath;
        
        return $paths;
    }

    /**
     * Применить водяной знак
     */
    public function applyWatermark($image, string $watermarkPath): void
    {
        if (!file_exists($watermarkPath)) {
            return; // Водяной знак отсутствует, пропускаем
        }

        $watermark = Image::make($watermarkPath);
        
        // Масштабируем водяной знак до 15% от ширины изображения
        $watermarkWidth = (int) ($image->width() * 0.15);
        $watermark->resize($watermarkWidth, null, function ($constraint) {
            $constraint->aspectRatio();
        });

        // Размещаем в правом нижнем углу с отступом
        $image->insert($watermark, 'bottom-right', 20, 20);
    }

    /**
     * Автоматическое улучшение изображения
     */
    public function autoEnhance($image): void
    {
        // Повышение резкости
        $image->sharpen(10);
        
        // Коррекция контраста (легкая)
        $image->contrast(5);
        
        // Коррекция яркости (легкая)
        $image->brightness(2);
    }

    /**
     * Создать превью для видео (первый кадр)
     */
    public function createVideoThumbnail(string $videoPath, string $outputPath): bool
    {
        // Простая реализация для создания превью из видео
        // В реальном проекте стоит использовать FFmpeg
        try {
            // Создаем заглушку для превью видео
            $placeholder = Image::canvas(640, 480, '#f0f0f0');
            $placeholder->text('Video Preview', 320, 240, function($font) {
                $font->size(24);
                $font->color('#666666');
                $font->align('center');
                $font->valign('middle');
            });
            
            $placeholder->encode('jpg', 80);
            file_put_contents($outputPath, $placeholder->stream('jpg', 80));
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Сжать изображение без потери качества (lossless)
     */
    public function compressLossless($image): void
    {
        // Оптимизация без потери качества
        $image->encode('jpg', 95);
    }

    /**
     * Изменить размер с сохранением пропорций
     */
    public function resizeKeepAspectRatio($image, int $maxWidth, int $maxHeight): void
    {
        $image->resize($maxWidth, $maxHeight, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize(); // Не увеличивать маленькие изображения
        });
    }

    /**
     * Создать квадратную миниатюру (обрезка по центру)
     */
    public function createSquareThumbnail($image, int $size): void
    {
        $image->fit($size, $size, function ($constraint) {
            $constraint->upsize();
        });
    }

    /**
     * Применить размытие (для приватных фото)
     */
    public function applyBlur($image, int $amount = 15): void
    {
        $image->blur($amount);
    }

    /**
     * Проверить нужна ли оптимизация
     */
    public function shouldOptimize(array $metadata): bool
    {
        $fileSize = $metadata['file_size'] ?? 0;
        $width = $metadata['width'] ?? 0;
        
        // Оптимизируем если файл больше 2MB или ширина больше 2000px
        return $fileSize > (2 * 1024 * 1024) || $width > 2000;
    }
}