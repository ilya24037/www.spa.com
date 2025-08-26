<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;

class SimpleImageOptimizer
{
    /**
     * Оптимизация изображения при загрузке
     * Простое решение без дополнительных библиотек
     */
    public static function optimize(UploadedFile $file, int $maxWidth = 1600): UploadedFile
    {
        // Проверяем размер файла
        if ($file->getSize() > 2 * 1024 * 1024) { // Больше 2MB
            
            $image = imagecreatefromstring(file_get_contents($file->getRealPath()));
            $width = imagesx($image);
            $height = imagesy($image);
            
            // Уменьшаем только если больше максимальной ширины
            if ($width > $maxWidth) {
                $ratio = $maxWidth / $width;
                $newWidth = $maxWidth;
                $newHeight = $height * $ratio;
                
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, 
                                   $newWidth, $newHeight, $width, $height);
                
                // Сохраняем с качеством 85%
                $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.jpg';
                imagejpeg($resized, $tempPath, 85);
                
                imagedestroy($image);
                imagedestroy($resized);
                
                // Создаем новый UploadedFile
                return new UploadedFile(
                    $tempPath,
                    $file->getClientOriginalName(),
                    'image/jpeg',
                    null,
                    true
                );
            }
        }
        
        return $file;
    }
    
    /**
     * Конвертация в WebP (если поддерживается)
     */
    public static function convertToWebP(string $imagePath): ?string
    {
        if (!function_exists('imagewebp')) {
            return null;
        }
        
        $info = getimagesize($imagePath);
        $type = $info[2];
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($imagePath);
                break;
            default:
                return null;
        }
        
        $webpPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $imagePath);
        imagewebp($image, $webpPath, 85);
        imagedestroy($image);
        
        // Удаляем оригинал если WebP меньше
        if (filesize($webpPath) < filesize($imagePath)) {
            unlink($imagePath);
            return $webpPath;
        }
        
        unlink($webpPath);
        return $imagePath;
    }
}