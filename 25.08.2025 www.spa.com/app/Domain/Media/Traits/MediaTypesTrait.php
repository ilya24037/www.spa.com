<?php

namespace App\Domain\Media\Traits;

use App\Enums\MediaType;

/**
 * Трейт для проверки типов медиафайлов
 */
trait MediaTypesTrait
{
    /**
     * Проверить, является ли файл изображением
     */
    public function isImage(): bool
    {
        return $this->type === MediaType::IMAGE || $this->type === MediaType::AVATAR;
    }

    /**
     * Проверить, является ли файл видео
     */
    public function isVideo(): bool  
    {
        return $this->type === MediaType::VIDEO;
    }

    /**
     * Проверить, является ли файл аудио
     */
    public function isAudio(): bool
    {
        return $this->type === MediaType::AUDIO;
    }

    /**
     * Проверить, является ли файл документом
     */
    public function isDocument(): bool
    {
        return $this->type === MediaType::DOCUMENT;
    }

    /**
     * Проверить, является ли файл аватаром
     */
    public function isAvatar(): bool
    {
        return $this->type === MediaType::AVATAR;
    }
}