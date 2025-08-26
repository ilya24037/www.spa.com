<?php

namespace App\Enums;

/**
 * Типы медиа файлов
 */
enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case AUDIO = 'audio';
    case DOCUMENT = 'document';
    case AVATAR = 'avatar';

    /**
     * Получить читаемое название типа
     */
    public function getLabel(): string
    {
        return match($this) {
            self::IMAGE => 'Изображение',
            self::VIDEO => 'Видео',
            self::AUDIO => 'Аудио',
            self::DOCUMENT => 'Документ',
            self::AVATAR => 'Аватар',
        };
    }

    /**
     * Получить разрешённые расширения файлов
     */
    public function getAllowedExtensions(): array
    {
        return match($this) {
            self::IMAGE => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'],
            self::VIDEO => ['mp4', 'webm', 'avi', 'mov', 'mkv', 'flv'],
            self::AUDIO => ['mp3', 'wav', 'ogg', 'aac', 'flac'],
            self::DOCUMENT => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
            self::AVATAR => ['jpg', 'jpeg', 'png', 'webp'],
        };
    }

    /**
     * Получить максимальный размер файла в байтах
     */
    public function getMaxFileSize(): int
    {
        return match($this) {
            self::IMAGE => 10 * 1024 * 1024,      // 10MB
            self::VIDEO => 100 * 1024 * 1024,     // 100MB
            self::AUDIO => 50 * 1024 * 1024,      // 50MB
            self::DOCUMENT => 20 * 1024 * 1024,   // 20MB
            self::AVATAR => 5 * 1024 * 1024,      // 5MB
        };
    }

    /**
     * Получить MIME типы
     */
    public function getMimeTypes(): array
    {
        return match($this) {
            self::IMAGE => [
                'image/jpeg', 'image/png', 'image/webp', 
                'image/gif', 'image/bmp'
            ],
            self::VIDEO => [
                'video/mp4', 'video/webm', 'video/avi', 
                'video/quicktime', 'video/x-msvideo'
            ],
            self::AUDIO => [
                'audio/mpeg', 'audio/wav', 'audio/ogg', 
                'audio/aac', 'audio/flac'
            ],
            self::DOCUMENT => [
                'application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain', 'application/rtf'
            ],
            self::AVATAR => [
                'image/jpeg', 'image/png', 'image/webp'
            ],
        ];
    }

    /**
     * Получить папку для хранения
     */
    public function getStorageDirectory(): string
    {
        return match($this) {
            self::IMAGE => 'images',
            self::VIDEO => 'videos',
            self::AUDIO => 'audio',
            self::DOCUMENT => 'documents',
            self::AVATAR => 'avatars',
        };
    }

    /**
     * Нужны ли превью/миниатюры
     */
    public function needsThumbnails(): bool
    {
        return match($this) {
            self::IMAGE, self::VIDEO, self::AVATAR => true,
            default => false,
        };
    }

    /**
     * Размеры превью для генерации
     */
    public function getThumbnailSizes(): array
    {
        return match($this) {
            self::IMAGE => [
                'thumb' => [200, 200],
                'medium' => [600, 600], 
                'large' => [1200, 1200],
            ],
            self::VIDEO => [
                'thumb' => [200, 150],
                'medium' => [640, 480],
            ],
            self::AVATAR => [
                'thumb' => [100, 100],
                'medium' => [300, 300],
            ],
            default => [],
        };
    }

    /**
     * Поддерживается ли оптимизация
     */
    public function supportsOptimization(): bool
    {
        return match($this) {
            self::IMAGE, self::AVATAR => true,
            default => false,
        };
    }

    /**
     * Поддерживается ли водяной знак
     */
    public function supportsWatermark(): bool
    {
        return match($this) {
            self::IMAGE, self::VIDEO => true,
            default => false,
        };
    }

    /**
     * Получить иконку для UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::IMAGE => '🖼️',
            self::VIDEO => '🎥',
            self::AUDIO => '🎵',
            self::DOCUMENT => '📄',
            self::AVATAR => '👤',
        };
    }

    /**
     * Определить тип медиа по расширению файла
     */
    public static function fromExtension(string $extension): ?self
    {
        $extension = strtolower($extension);
        
        foreach (self::cases() as $type) {
            if (in_array($extension, $type->getAllowedExtensions())) {
                return $type;
            }
        }
        
        return null;
    }

    /**
     * Определить тип медиа по MIME типу
     */
    public static function fromMimeType(string $mimeType): ?self
    {
        foreach (self::cases() as $type) {
            if (in_array($mimeType, $type->getMimeTypes())) {
                return $type;
            }
        }
        
        return null;
    }

    /**
     * Получить все типы для выборки
     */
    public static function options(): array
    {
        $types = [];
        foreach (self::cases() as $type) {
            $types[$type->value] = $type->getLabel();
        }
        return $types;
    }
}