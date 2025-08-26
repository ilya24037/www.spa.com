<?php

namespace App\Domain\Media\Traits;

use App\Enums\MediaStatus;
use Illuminate\Support\Facades\Storage;

/**
 * Трейт для операций с медиафайлами
 */
trait MediaOperationsTrait
{
    /**
     * Обновить метаданные
     */
    public function updateMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata ?? [], $metadata);
        $this->save();
        return $this;
    }

    /**
     * Получить метаданные
     */
    public function getMetadata(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->metadata ?? [];
        }
        
        return data_get($this->metadata, $key, $default);
    }

    /**
     * Проверить существование файла
     */
    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->file_name);
    }

    /**
     * Скопировать файл
     */
    public function copy(string $newFileName = null): self
    {
        $newFileName = $newFileName ?? $this->generateUniqueFileName();
        
        Storage::disk($this->disk)->copy($this->file_name, $newFileName);
        
        return self::create([
            'mediable_type' => $this->mediable_type,
            'mediable_id' => $this->mediable_id,
            'collection_name' => $this->collection_name,
            'name' => $this->name,
            'file_name' => $newFileName,
            'mime_type' => $this->mime_type,
            'disk' => $this->disk,
            'size' => $this->size,
            'type' => $this->type,
            'status' => MediaStatus::PENDING,
            'alt_text' => $this->alt_text,
            'caption' => $this->caption,
            'metadata' => $this->metadata,
            'sort_order' => $this->sort_order,
        ]);
    }

    /**
     * Переместить файл
     */
    public function move(string $newPath): self
    {
        $oldPath = $this->file_name;
        
        Storage::disk($this->disk)->move($oldPath, $newPath);
        
        $this->file_name = $newPath;
        $this->save();
        
        return $this;
    }

    /**
     * Сгенерировать уникальное имя файла
     */
    protected function generateUniqueFileName(): string
    {
        $extension = $this->extension;
        $baseName = pathinfo($this->name, PATHINFO_FILENAME);
        $directory = $this->type->getStorageDirectory();
        
        do {
            $fileName = $directory . '/' . $baseName . '_' . uniqid() . '.' . $extension;
        } while (Storage::disk($this->disk)->exists($fileName));
        
        return $fileName;
    }
}