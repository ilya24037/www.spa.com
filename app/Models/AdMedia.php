<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Медиа контент объявления (фото, видео)
 */
class AdMedia extends Model
{
    use HasFactory;

    protected $table = 'ad_media';

    protected $fillable = [
        'ad_id',
        'photos',
        'video',
        'show_photos_in_gallery',
        'allow_download_photos',
        'watermark_photos',
    ];

    protected $casts = [
        'photos' => 'array',
        'video' => 'array',
        'show_photos_in_gallery' => 'boolean',
        'allow_download_photos' => 'boolean',
        'watermark_photos' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Связь с объявлением
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Получить количество фотографий
     */
    public function getPhotosCountAttribute(): int
    {
        return is_array($this->photos) ? count($this->photos) : 0;
    }

    /**
     * Получить первое фото (главное)
     */
    public function getMainPhotoAttribute(): ?string
    {
        if (empty($this->photos) || !is_array($this->photos)) {
            return null;
        }

        return $this->photos[0] ?? null;
    }

    /**
     * Получить все фото кроме главного
     */
    public function getAdditionalPhotosAttribute(): array
    {
        if (empty($this->photos) || !is_array($this->photos) || count($this->photos) <= 1) {
            return [];
        }

        return array_slice($this->photos, 1);
    }

    /**
     * Проверить есть ли видео
     */
    public function hasVideo(): bool
    {
        return !empty($this->video) && is_array($this->video) && !empty($this->video['id']);
    }

    /**
     * Получить URL видео
     */
    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->hasVideo()) {
            return null;
        }

        return $this->video['url'] ?? null;
    }

    /**
     * Получить ID видео
     */
    public function getVideoIdAttribute(): ?string
    {
        if (!$this->hasVideo()) {
            return null;
        }

        return $this->video['id'] ?? null;
    }

    /**
     * Проверить есть ли медиа контент
     */
    public function hasMedia(): bool
    {
        return $this->getPhotosCountAttribute() > 0 || $this->hasVideo();
    }

    /**
     * Получить общее количество медиа файлов
     */
    public function getMediaCountAttribute(): int
    {
        $count = $this->getPhotosCountAttribute();
        
        if ($this->hasVideo()) {
            $count++;
        }

        return $count;
    }

    /**
     * Проверить можно ли скачивать фото
     */
    public function canDownloadPhotos(): bool
    {
        return $this->allow_download_photos === true;
    }

    /**
     * Проверить нужен ли водяной знак
     */
    public function needsWatermark(): bool
    {
        return $this->watermark_photos === true;
    }

    /**
     * Проверить показывать ли в галерее
     */
    public function shouldShowInGallery(): bool
    {
        return $this->show_photos_in_gallery === true;
    }

    /**
     * Добавить фото к существующим
     */
    public function addPhoto(string $photoUrl): void
    {
        $photos = $this->photos ?? [];
        $photos[] = $photoUrl;
        $this->photos = $photos;
        $this->save();
    }

    /**
     * Удалить фото по индексу
     */
    public function removePhoto(int $index): bool
    {
        $photos = $this->photos ?? [];
        
        if (!isset($photos[$index])) {
            return false;
        }

        unset($photos[$index]);
        $this->photos = array_values($photos); // Переиндексация массива
        $this->save();
        
        return true;
    }

    /**
     * Заменить все фото
     */
    public function replacePhotos(array $photos): void
    {
        $this->photos = $photos;
        $this->save();
    }
}