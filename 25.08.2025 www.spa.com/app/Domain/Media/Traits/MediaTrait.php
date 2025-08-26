<?php

namespace App\Domain\Media\Traits;

use App\Enums\MediaType;
use Illuminate\Support\Facades\Storage;

/**
 * Трейт для унификации работы с медиафайлами
 * Содержит общую логику для Photo, Video и Media моделей
 */
trait MediaTrait
{
    /**
     * Получить URL файла (унифицированный метод)
     */
    public function getUrl(): string
    {
        // Для новой архитектуры (Media модель)
        if (isset($this->attributes['disk']) && isset($this->attributes['file_name'])) {
            return Storage::disk($this->disk)->url($this->file_name);
        }
        
        // Для legacy архитектуры (Photo/Video модели)
        if (method_exists($this, 'masterProfile') && $this->masterProfile) {
            $folderName = $this->masterProfile->folder_name;
            
            if (isset($this->attributes['filename'])) {
                $type = $this->getMediaType();
                $subFolder = $type === MediaType::VIDEO ? 'video' : 'photos';
                return route('master.' . strtolower($type->value), [
                    'master' => $folderName, 
                    'filename' => $this->filename
                ]);
            }
        }
        
        return '';
    }

    /**
     * Получить URL миниатюры (унифицированный метод)
     */
    public function getThumbUrl(): ?string
    {
        // Для новой архитектуры (Media модель)
        if (method_exists($this, 'getConversionUrl')) {
            return $this->getConversionUrl('thumb');
        }
        
        // Для legacy архитектуры (Photo модель)
        if (method_exists($this, 'masterProfile') && $this->masterProfile && isset($this->attributes['filename'])) {
            $folderName = $this->masterProfile->folder_name;
            $thumbFilename = $this->getThumbFilename();
            return route('master.photo', ['master' => $folderName, 'filename' => $thumbFilename]);
        }
        
        return null;
    }

    /**
     * Получить размер файла в человекочитаемом формате (унифицированный метод)
     */
    public function getFormattedFileSize(): string
    {
        // Определяем поле с размером файла
        $size = $this->attributes['size'] ?? $this->attributes['file_size'] ?? 0;
        
        if ($size >= 1073741824) {
            return number_format($size / 1073741824, 2) . ' GB';
        } elseif ($size >= 1048576) {
            return number_format($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) {
            return number_format($size / 1024, 2) . ' KB';
        } else {
            return $size . ' bytes';
        }
    }

    /**
     * Проверить существование файла (унифицированный метод)
     */
    public function fileExists(): bool
    {
        // Для новой архитектуры (Media модель)
        if (isset($this->attributes['disk']) && isset($this->attributes['file_name'])) {
            return Storage::disk($this->disk)->exists($this->file_name);
        }
        
        // Для legacy архитектуры (Photo/Video модели)
        if (method_exists($this, 'masterProfile') && $this->masterProfile && isset($this->attributes['filename'])) {
            $disk = Storage::disk('masters_private');
            $folderName = $this->masterProfile->folder_name;
            $type = $this->getMediaType();
            $subFolder = $type === MediaType::VIDEO ? 'video' : 'photos';
            
            return $disk->exists("{$folderName}/{$subFolder}/{$this->filename}");
        }
        
        return false;
    }

    /**
     * Получить тип медиафайла (унифицированный метод)
     */
    public function getMediaType(): MediaType
    {
        // Для новой архитектуры (Media модель)
        if (isset($this->attributes['type']) && $this->attributes['type'] instanceof MediaType) {
            return $this->attributes['type'];
        }
        
        // Для legacy архитектуры - определяем по классу модели
        $className = class_basename($this);
        return match($className) {
            'Photo' => MediaType::IMAGE,
            'Video' => MediaType::VIDEO,
            default => MediaType::IMAGE
        };
    }

    /**
     * Проверить, является ли файл изображением (унифицированный метод)
     */
    public function isImage(): bool
    {
        $type = $this->getMediaType();
        return in_array($type, [MediaType::IMAGE, MediaType::AVATAR]);
    }

    /**
     * Проверить, является ли файл видео (унифицированный метод)
     */
    public function isVideo(): bool
    {
        return $this->getMediaType() === MediaType::VIDEO;
    }

    /**
     * Получить расширение файла (унифицированный метод)
     */
    public function getExtension(): string
    {
        // Для новой архитектуры
        if (isset($this->attributes['file_name'])) {
            return pathinfo($this->attributes['file_name'], PATHINFO_EXTENSION);
        }
        
        // Для legacy архитектуры
        if (isset($this->attributes['filename'])) {
            return pathinfo($this->attributes['filename'], PATHINFO_EXTENSION);
        }
        
        return '';
    }

    /**
     * Получить имя файла миниатюры для legacy архитектуры
     */
    protected function getThumbFilename(): string
    {
        if (!isset($this->attributes['filename'])) {
            return '';
        }
        
        $info = pathinfo($this->attributes['filename']);
        return $info['filename'] . '_thumb.' . $info['extension'];
    }

    /**
     * Получить имя файла среднего размера для legacy архитектуры
     */
    protected function getMediumFilename(): string
    {
        if (!isset($this->attributes['filename'])) {
            return '';
        }
        
        $info = pathinfo($this->attributes['filename']);
        return $info['filename'] . '_medium.' . $info['extension'];
    }

    /**
     * Получить метаданные (унифицированный метод)
     */
    public function getMetadataValue(string $key, $default = null)
    {
        // Для новой архитектуры (Media модель)
        if (isset($this->attributes['metadata']) && is_array($this->attributes['metadata'])) {
            return data_get($this->attributes['metadata'], $key, $default);
        }
        
        // Для legacy архитектуры - используем отдельные поля
        return match($key) {
            'width' => $this->attributes['width'] ?? $default,
            'height' => $this->attributes['height'] ?? $default,
            'duration' => $this->attributes['duration'] ?? $default,
            'mime_type' => $this->attributes['mime_type'] ?? $default,
            default => $default
        };
    }

    /**
     * Установить метаданные (унифицированный метод)
     */
    public function setMetadataValue(string $key, $value): void
    {
        // Для новой архитектуры (Media модель)
        if (isset($this->attributes['metadata'])) {
            $metadata = $this->attributes['metadata'] ?? [];
            $metadata[$key] = $value;
            $this->attributes['metadata'] = $metadata;
            return;
        }
        
        // Для legacy архитектуры - устанавливаем в отдельные поля
        $fieldMapping = [
            'width' => 'width',
            'height' => 'height', 
            'duration' => 'duration',
            'mime_type' => 'mime_type'
        ];
        
        if (isset($fieldMapping[$key])) {
            $this->attributes[$fieldMapping[$key]] = $value;
        }
    }

    /**
     * Получить путь к файлу для удаления (унифицированный метод)
     */
    public function getFilePath(): string
    {
        // Для новой архитектуры (Media модель)
        if (isset($this->attributes['file_name'])) {
            return $this->attributes['file_name'];
        }
        
        // Для legacy архитектуры (Photo/Video модели)
        if (method_exists($this, 'masterProfile') && $this->masterProfile && isset($this->attributes['filename'])) {
            $folderName = $this->masterProfile->folder_name;
            $type = $this->getMediaType();
            $subFolder = $type === MediaType::VIDEO ? 'video' : 'photos';
            
            return "{$folderName}/{$subFolder}/{$this->filename}";
        }
        
        return '';
    }

    /**
     * Получить все связанные файлы для удаления (унифицированный метод)
     */
    public function getAllFilePaths(): array
    {
        $paths = [];
        $mainPath = $this->getFilePath();
        
        if ($mainPath) {
            $paths[] = $mainPath;
            
            // Для изображений добавляем миниатюры
            if ($this->isImage()) {
                $info = pathinfo($mainPath);
                $directory = $info['dirname'];
                $filename = $info['filename'];
                $extension = $info['extension'];
                
                $paths[] = "{$directory}/{$filename}_thumb.{$extension}";
                $paths[] = "{$directory}/{$filename}_medium.{$extension}";
            }
            
            // Для видео добавляем постер
            if ($this->isVideo() && isset($this->attributes['poster_filename'])) {
                $videoDir = dirname($mainPath);
                $paths[] = "{$videoDir}/{$this->attributes['poster_filename']}";
            }
        }
        
        return array_filter($paths);
    }

    /**
     * Получить CDN URL (если включен CDN)
     */
    public function getCDNUrl(): string
    {
        if (app()->bound(\App\Infrastructure\CDN\CDNIntegration::class)) {
            $cdnIntegration = app(\App\Infrastructure\CDN\CDNIntegration::class);
            return $cdnIntegration->getCDNUrl($this);
        }
        
        return $this->getUrl();
    }

    /**
     * Получить оптимизированный URL изображения через CDN
     */
    public function getOptimizedUrl(array $transformations = []): string
    {
        if (!$this->isImage()) {
            return $this->getCDNUrl();
        }

        if (app()->bound(\App\Infrastructure\CDN\CDNIntegration::class)) {
            $cdnIntegration = app(\App\Infrastructure\CDN\CDNIntegration::class);
            return $cdnIntegration->getOptimizedImageUrl($this, $transformations);
        }
        
        return $this->getUrl();
    }
}