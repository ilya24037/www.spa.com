<?php

namespace App\Domain\Media\Models;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Traits\MediaTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory, MediaTrait;

    protected $table = 'master_photos'; // Для совместимости с существующей БД

    protected $fillable = [
        'master_profile_id',
        'filename',           // photo_1.jpg
        'mime_type',
        'file_size',
        'width',
        'height',
        'is_main',
        'sort_order',
        'is_approved'
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'is_approved' => 'boolean',
        'sort_order' => 'integer',
        'file_size' => 'integer',
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
     * Получить URL оригинального изображения (legacy метод - используйте getUrl())
     * @deprecated Используйте getUrl() из MediaTrait
     */
    public function getOriginalUrlAttribute(): string
    {
        return $this->getUrl();
    }

    /**
     * Получить URL среднего размера (legacy метод)
     * @deprecated Используйте getMediumUrl() из MediaTrait
     */
    public function getMediumUrlAttribute(): string
    {
        if (method_exists($this, 'masterProfile') && $this->masterProfile) {
            $folderName = $this->masterProfile->folder_name;
            $mediumFilename = $this->getMediumFilename();
            return route('master.photo', ['master' => $folderName, 'filename' => $mediumFilename]);
        }
        return '';
    }

    /**
     * Получить URL миниатюры (legacy метод - используйте getThumbUrl())
     * @deprecated Используйте getThumbUrl() из MediaTrait
     */
    public function getThumbUrlAttribute(): string
    {
        return $this->getThumbUrl() ?? '';
    }

    /**
     * Получить имя файла миниатюры
     */
    public function getThumbFilename(): string
    {
        $info = pathinfo($this->filename);
        return $info['filename'] . '_thumb.' . $info['extension'];
    }

    /**
     * Получить имя файла среднего размера
     */
    public function getMediumFilename(): string
    {
        $info = pathinfo($this->filename);
        return $info['filename'] . '_medium.' . $info['extension'];
    }

    /**
     * Получить физический путь к файлу
     */
    public function getPhysicalPath(string $size = 'original'): string
    {
        $filename = match($size) {
            'thumb' => $this->getThumbFilename(),
            'medium' => $this->getMediumFilename(),
            default => $this->filename
        };

        $folderName = $this->masterProfile->folder_name;
        return "{$folderName}/photos/{$filename}";
    }

    /**
     * Получить размер файла в человекочитаемом формате (legacy метод)
     * @deprecated Используйте getFormattedFileSize() из MediaTrait
     */
    public function getFormattedFileSizeAttribute(): string
    {
        return $this->getFormattedFileSize();
    }

    /**
     * Скоп для одобренных фото
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Скоп для сортировки
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    // ===============================
    // УНИФИЦИРОВАННЫЕ МЕТОДЫ ДОСТУПА К ДАННЫМ
    // ===============================

    /**
     * Получить ширину изображения (унифицированный доступ)
     */
    public function getWidth(): ?int
    {
        return $this->getMetadataValue('width', $this->attributes['width'] ?? null);
    }

    /**
     * Получить высоту изображения (унифицированный доступ)
     */
    public function getHeight(): ?int
    {
        return $this->getMetadataValue('height', $this->attributes['height'] ?? null);
    }

    /**
     * Получить MIME тип (унифицированный доступ)
     */
    public function getMimeType(): ?string
    {
        return $this->getMetadataValue('mime_type', $this->attributes['mime_type'] ?? null);
    }

    /**
     * Проверить, является ли главным фото (унифицированный доступ)
     */
    public function isMain(): bool
    {
        return (bool) $this->getMetadataValue('is_main', $this->attributes['is_main'] ?? false);
    }

    /**
     * Проверить, одобрено ли фото (унифицированный доступ)
     */
    public function isApproved(): bool
    {
        return (bool) $this->getMetadataValue('is_approved', $this->attributes['is_approved'] ?? false);
    }

    /**
     * Установить как главное фото (унифицированный метод)
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

}