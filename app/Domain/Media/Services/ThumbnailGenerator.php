<?php

namespace App\Domain\Media\Services;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

/**
 * Сервис для генерации миниатюр
 * Создаёт различные размеры изображений для оптимальной загрузки
 */
class ThumbnailGenerator
{
    private const SIZES = [
        'thumb' => [
            'width' => 300,
            'height' => 300,
            'quality' => 85,
            'suffix' => '_thumb'
        ],
        'medium' => [
            'width' => 800,
            'height' => 800,
            'quality' => 90,
            'suffix' => '_medium'
        ],
        'large' => [
            'width' => 1200,
            'height' => 1200,
            'quality' => 92,
            'suffix' => '_large'
        ]
    ];

    /**
     * Генерировать все размеры для изображения
     */
    public function generateSizes($image, $disk, string $masterFolder, int $photoNumber): array
    {
        $generatedFiles = [];

        foreach (self::SIZES as $sizeName => $config) {
            $filename = $this->generateFilename($photoNumber, $config['suffix']);
            $path = "{$masterFolder}/photos/{$filename}";
            
            $resized = $this->createResizedImage($image, $config);
            $disk->put($path, $resized->stream());
            
            $generatedFiles[$sizeName] = $filename;
        }

        return $generatedFiles;
    }

    /**
     * Генерировать миниатюру для конкретного размера
     */
    public function generateThumbnail(
        string $originalPath,
        string $thumbnailPath,
        int $width,
        int $height,
        string $mode = 'fit',
        int $quality = 85
    ): bool {
        try {
            $image = Image::make($originalPath);
            
            switch ($mode) {
                case 'fit':
                    $image->fit($width, $height);
                    break;
                case 'resize':
                    $image->resize($width, $height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    break;
                case 'crop':
                    $image->crop($width, $height);
                    break;
            }
            
            $image->encode('jpg', $quality);
            $image->save($thumbnailPath);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Создать изображение с измененным размером
     */
    private function createResizedImage($sourceImage, array $config)
    {
        $image = clone $sourceImage;
        
        // Изменяем размер только если изображение больше целевого
        if ($image->width() > $config['width'] || $image->height() > $config['height']) {
            $image->resize($config['width'], $config['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        
        // Оптимизируем
        $image->stripImage();
        $image->encode('jpg', $config['quality']);
        
        return $image;
    }

    /**
     * Генерировать имя файла
     */
    private function generateFilename(int $photoNumber, string $suffix): string
    {
        return "photo_{$photoNumber}{$suffix}.jpg";
    }

    /**
     * Создать квадратную миниатюру с умным кадрированием
     */
    public function createSmartSquareThumbnail($image, int $size): void
    {
        $width = $image->width();
        $height = $image->height();
        
        // Определяем наилучшую область для кадрирования
        if ($width > $height) {
            // Горизонтальное изображение - берём центр
            $x = ($width - $height) / 2;
            $y = 0;
            $cropSize = $height;
        } else {
            // Вертикальное изображение - берём верхнюю часть (обычно там лицо)
            $x = 0;
            $y = 0;
            $cropSize = $width;
        }
        
        $image->crop($cropSize, $cropSize, $x, $y);
        $image->resize($size, $size);
    }

    /**
     * Создать миниатюру с размытым фоном
     */
    public function createBlurredBackgroundThumbnail(
        string $originalPath,
        string $outputPath,
        int $width,
        int $height,
        int $quality = 85
    ): bool {
        try {
            // Создаём размытый фон
            $background = Image::make($originalPath);
            $background->fit($width, $height);
            $background->blur(50);
            
            // Накладываем оригинальное изображение
            $foreground = Image::make($originalPath);
            $foreground->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            // Центрируем изображение
            $x = ($width - $foreground->width()) / 2;
            $y = ($height - $foreground->height()) / 2;
            
            $background->insert($foreground, 'top-left', $x, $y);
            $background->encode('jpg', $quality);
            $background->save($outputPath);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получить оптимальный размер для веб
     */
    public function getOptimalWebSize(int $originalWidth, int $originalHeight): array
    {
        // Максимальные размеры для веб
        $maxWidth = 1920;
        $maxHeight = 1080;
        
        // Если изображение меньше максимального - возвращаем оригинальные размеры
        if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
            return ['width' => $originalWidth, 'height' => $originalHeight];
        }
        
        // Вычисляем пропорциональные размеры
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        
        return [
            'width' => round($originalWidth * $ratio),
            'height' => round($originalHeight * $ratio)
        ];
    }

    /**
     * Создать адаптивный набор изображений
     */
    public function createResponsiveSet(string $originalPath, string $outputDir): array
    {
        $sizes = [
            'small' => ['width' => 640, 'suffix' => '@1x'],
            'medium' => ['width' => 1280, 'suffix' => '@2x'],
            'large' => ['width' => 1920, 'suffix' => '@3x'],
        ];
        
        $responsiveSet = [];
        $image = Image::make($originalPath);
        
        foreach ($sizes as $sizeName => $config) {
            $filename = pathinfo($originalPath, PATHINFO_FILENAME) . $config['suffix'] . '.jpg';
            $outputPath = $outputDir . '/' . $filename;
            
            $resized = clone $image;
            $resized->resize($config['width'], null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            $resized->encode('jpg', 90);
            $resized->save($outputPath);
            
            $responsiveSet[$sizeName] = $filename;
        }
        
        return $responsiveSet;
    }
}