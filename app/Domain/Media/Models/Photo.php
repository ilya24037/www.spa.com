<?php

namespace App\Domain\Media\Models;

use App\Domain\Master\Models\MasterProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

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
     * Получить URL оригинального изображения
     */
    public function getOriginalUrlAttribute(): string
    {
        $folderName = $this->masterProfile->folder_name;
        return route('master.photo', ['master' => $folderName, 'filename' => $this->filename]);
    }

    /**
     * Получить URL среднего размера
     */
    public function getMediumUrlAttribute(): string
    {
        $folderName = $this->masterProfile->folder_name;
        $mediumFilename = $this->getMediumFilename();
        return route('master.photo', ['master' => $folderName, 'filename' => $mediumFilename]);
    }

    /**
     * Получить URL миниатюры
     */
    public function getThumbUrlAttribute(): string
    {
        $folderName = $this->masterProfile->folder_name;
        $thumbFilename = $this->getThumbFilename();
        return route('master.photo', ['master' => $folderName, 'filename' => $thumbFilename]);
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
     * Получить размер файла в человекочитаемом формате
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
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

    /**
     * Удалить файлы при удалении записи
     */
    protected static function booted(): void
    {
        static::deleting(function (Photo $photo) {
            $disk = Storage::disk('masters_private');
            $folderName = $photo->masterProfile->folder_name;
            
            // Удаляем все размеры
            $disk->delete("{$folderName}/photos/{$photo->filename}");
            $disk->delete("{$folderName}/photos/{$photo->getMediumFilename()}");
            $disk->delete("{$folderName}/photos/{$photo->getThumbFilename()}");
        });
    }
}