<?php

namespace App\Domain\Media\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * Трейт для вычисляемых атрибутов медиафайлов
 */
trait MediaAttributesTrait
{
    public function getFullUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->file_name);
    }

    public function getFullPathAttribute(): string
    {
        return Storage::disk($this->disk)->path($this->file_name);
    }

    public function getSizeInKbAttribute(): float
    {
        return round($this->size / 1024, 2);
    }

    public function getSizeInMbAttribute(): float
    {
        return round($this->size / (1024 * 1024), 2);
    }

    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }
}