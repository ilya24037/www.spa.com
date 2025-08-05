<?php

namespace App\Domain\Media\Models;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Traits\MediaTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory, MediaTrait;

    protected $table = 'master_videos'; // Для совместимости с существующей БД

    protected $fillable = [
        'master_profile_id',
        'filename',           // intro.mp4
        'poster_filename',    // intro_poster.jpg
        'mime_type',
        'file_size',
        'duration',
        'width',
        'height',
        'is_main',
        'sort_order',
        'is_approved',
        'processing_status'
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'is_approved' => 'boolean',
        'sort_order' => 'integer',
        'file_size' => 'integer',
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer'
    ];

    /**
     * Связь с профилем мастера
     */
    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    /**
     * Получить URL видео (legacy метод - используйте getUrl())
     * @deprecated Используйте getUrl() из MediaTrait
     */
    public function getVideoUrlAttribute(): string
    {
        return $this->getUrl();
    }

    /**
     * Получить URL постера (legacy метод - используйте getThumbUrl())
     * @deprecated Используйте getThumbUrl() из MediaTrait
     */
    public function getPosterUrlAttribute(): string
    {
        return $this->getThumbUrl() ?? '';
    }

    /**
     * Получить физический путь к видео
     */
    public function getVideoPath(): string
    {
        $folderName = $this->masterProfile->folder_name;
        return "{$folderName}/video/{$this->filename}";
    }

    /**
     * Получить физический путь к постеру
     */
    public function getPosterPath(): string
    {
        $folderName = $this->masterProfile->folder_name;
        return "{$folderName}/video/{$this->poster_filename}";
    }

    /**
     * Получить длительность в человекочитаемом формате
     */
    public function getFormattedDurationAttribute(): string
    {
        $duration = $this->getDuration();
        if (!$duration) return '00:00';
        
        $minutes = floor($duration / 60);
        $seconds = $duration % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    // ===============================
    // УНИФИЦИРОВАННЫЕ МЕТОДЫ ДОСТУПА К ДАННЫМ
    // ===============================

    /**
     * Получить ширину видео (унифицированный доступ)
     */
    public function getWidth(): ?int
    {
        return $this->getMetadataValue('width', $this->attributes['width'] ?? null);
    }

    /**
     * Получить высоту видео (унифицированный доступ)
     */
    public function getHeight(): ?int
    {
        return $this->getMetadataValue('height', $this->attributes['height'] ?? null);
    }

    /**
     * Получить длительность видео (унифицированный доступ)
     */
    public function getDuration(): ?int
    {
        return $this->getMetadataValue('duration', $this->attributes['duration'] ?? null);
    }

    /**
     * Получить MIME тип (унифицированный доступ)
     */
    public function getMimeType(): ?string
    {
        return $this->getMetadataValue('mime_type', $this->attributes['mime_type'] ?? null);
    }

    /**
     * Получить имя файла постера (унифицированный доступ)
     */
    public function getPosterFilename(): ?string
    {
        return $this->getMetadataValue('poster_filename', $this->attributes['poster_filename'] ?? null);
    }

    /**
     * Проверить, является ли главным видео (унифицированный доступ)
     */
    public function isMain(): bool
    {
        return (bool) $this->getMetadataValue('is_main', $this->attributes['is_main'] ?? false);
    }

    /**
     * Проверить, одобрено ли видео (унифицированный доступ)
     */
    public function isApproved(): bool
    {
        return (bool) $this->getMetadataValue('is_approved', $this->attributes['is_approved'] ?? true);
    }

    /**
     * Получить статус обработки (унифицированный доступ)
     */
    public function getProcessingStatus(): string
    {
        return $this->getMetadataValue('processing_status', $this->attributes['processing_status'] ?? 'completed');
    }

    /**
     * Установить как главное видео (унифицированный метод)
     */
    public function setAsMain(bool $isMain = true): void
    {
        $this->setMetadataValue('is_main', $isMain);
        if (isset($this->attributes['is_main'])) {
            $this->attributes['is_main'] = $isMain;
        }
        $this->save();
    }

    /**
     * Установить статус одобрения (унифицированный метод)
     */
    public function setApproved(bool $approved = true): void
    {
        $this->setMetadataValue('is_approved', $approved);
        if (isset($this->attributes['is_approved'])) {
            $this->attributes['is_approved'] = $approved;
        }
        $this->save();
    }

    /**
     * Установить статус обработки (унифицированный метод)
     */
    public function setProcessingStatus(string $status): void
    {
        $this->setMetadataValue('processing_status', $status);
        if (isset($this->attributes['processing_status'])) {
            $this->attributes['processing_status'] = $status;
        }
        $this->save();
    }

}