<?php

namespace App\Domain\Ad\Models;

use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель для работы с медиа контентом объявления
 * Управляет фото, видео и их настройками
 */
class AdMedia extends Model
{
    use JsonFieldsTrait;
    protected $table = 'ad_media';

    protected $fillable = [
        'ad_id',
        'photos',
        'video',
        'show_photos_in_gallery',
        'allow_download_photos',
        'watermark_photos',
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'photos',
        'video',
    ];

    protected $casts = [
        // JSON поля обрабатываются через JsonFieldsTrait
        'show_photos_in_gallery' => 'boolean',
        'allow_download_photos' => 'boolean',
        'watermark_photos' => 'boolean',
    ];

    /**
     * Связь с объявлением
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Проверка наличия медиа
     */
    public function hasMedia(): bool
    {
        return !empty($this->photos) || !empty($this->video);
    }

    /**
     * Проверка наличия фото
     */
    public function hasPhotos(): bool
    {
        return !empty($this->photos) && is_array($this->photos) && count($this->photos) > 0;
    }

    /**
     * Проверка наличия видео
     */
    public function hasVideo(): bool
    {
        return !empty($this->video) && is_array($this->video) && count($this->video) > 0;
    }

    /**
     * Получить количество фото
     */
    public function getPhotosCountAttribute(): int
    {
        return $this->hasPhotos() ? count($this->photos) : 0;
    }

    /**
     * Получить количество видео
     */
    public function getVideosCountAttribute(): int
    {
        return $this->hasVideo() ? count($this->video) : 0;
    }

    /**
     * Получить общее количество медиа файлов
     */
    public function getTotalMediaCountAttribute(): int
    {
        return $this->photos_count + $this->videos_count;
    }

    /**
     * Получить главное фото
     */
    public function getMainPhotoAttribute(): ?string
    {
        if (!$this->hasPhotos()) {
            return null;
        }

        return $this->photos[0] ?? null;
    }

    /**
     * Получить превью для галереи
     */
    public function getGalleryPreviewAttribute(): array
    {
        if (!$this->hasPhotos()) {
            return [];
        }

        return array_slice($this->photos, 0, 4);
    }

    /**
     * Добавить фото
     */
    public function addPhoto(string $photoPath): self
    {
        $photos = $this->photos ?? [];
        $photos[] = $photoPath;
        
        $this->photos = $photos;
        $this->save();
        
        return $this;
    }

    /**
     * Удалить фото
     */
    public function removePhoto(string $photoPath): self
    {
        if (!$this->hasPhotos()) {
            return $this;
        }

        $photos = array_filter($this->photos, function($photo) use ($photoPath) {
            return $photo !== $photoPath;
        });
        
        $this->photos = array_values($photos);
        $this->save();
        
        return $this;
    }

    /**
     * Добавить видео
     */
    public function addVideo(string $videoPath): self
    {
        $videos = $this->video ?? [];
        $videos[] = $videoPath;
        
        $this->video = $videos;
        $this->save();
        
        return $this;
    }

    /**
     * Удалить видео
     */
    public function removeVideo(string $videoPath): self
    {
        if (!$this->hasVideo()) {
            return $this;
        }

        $videos = array_filter($this->video, function($video) use ($videoPath) {
            return $video !== $videoPath;
        });
        
        $this->video = array_values($videos);
        $this->save();
        
        return $this;
    }

    /**
     * Очистить все медиа
     */
    public function clearAllMedia(): self
    {
        $this->photos = [];
        $this->video = [];
        $this->save();
        
        return $this;
    }
}