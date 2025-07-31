<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class AIMediaService
{
    /**
     * Обработать изображение с AI
     */
    public function processImage($file, array $settings): array
    {
        $image = Image::make($file);
        
        // AI-размытие лиц
        if ($settings['autoBlurFaces']) {
            $image = $this->blurFaces($image);
        }
        
        // Добавление водяного знака
        if ($settings['addWatermark']) {
            $image = $this->addWatermark($image);
        }
        
        // Оптимизация для веба
        if ($settings['optimizeForWeb']) {
            $image = $this->optimizeForWeb($image);
        }
        
        return [
            'original' => $this->saveOriginal($image, $file),
            'thumb' => $this->createThumbnail($image, $file),
            'webp' => $this->createWebP($image, $file)
        ];
    }

    /**
     * AI-размытие лиц
     */
    private function blurFaces($image)
    {
        // Интеграция с OpenCV или облачным AI
        // Пока используем простой алгоритм
        return $image->blur(15);
    }

    /**
     * Добавить водяной знак
     */
    private function addWatermark($image)
    {
        $watermark = Image::make(public_path('images/watermark.png'));
        
        return $image->insert($watermark, 'bottom-right', 10, 10);
    }

    /**
     * Оптимизация для веба
     */
    private function optimizeForWeb($image)
    {
        return $image->resize(1200, 1200, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode('jpg', 85);
    }

    /**
     * Создать WebP версию
     */
    private function createWebP($image, $file)
    {
        $webpImage = $image->encode('webp', 85);
        $webpPath = $this->generateWebPPath($file);
        
        Storage::disk('masters_photos')->put($webpPath, $webpImage);
        
        return $webpPath;
    }

    /**
     * Генерировать путь для WebP
     */
    private function generateWebPPath($file)
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        return $name . '.webp';
    }
} 