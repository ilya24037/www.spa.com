<?php

namespace App\Domain\Media\Models;

use App\Enums\MediaType;
use App\Enums\MediaStatus;
use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory, SoftDeletes, JsonFieldsTrait;

    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'collection_name',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'size',
        'type',
        'status',
        'alt_text',
        'caption',
        'metadata',
        'conversions',
        'sort_order',
        'expires_at',
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'metadata',
        'conversions',
    ];

    protected $casts = [
        'type' => MediaType::class,
        'status' => MediaStatus::class,
        // JSON поля обрабатываются через JsonFieldsTrait
        'size' => 'integer',
        'sort_order' => 'integer',
        'expires_at' => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'expires_at',
    ];

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

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

    public function getConversionUrl(string $conversionName): ?string
    {
        if (!isset($this->conversions[$conversionName])) {
            return null;
        }

        $conversion = $this->conversions[$conversionName];
        return Storage::disk($this->disk)->url($conversion['file_name']);
    }

    public function hasConversion(string $conversionName): bool
    {
        return isset($this->conversions[$conversionName]);
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->getConversionUrl('thumb');
    }

    public function getMediumUrl(): ?string
    {
        return $this->getConversionUrl('medium');
    }

    public function getLargeUrl(): ?string
    {
        return $this->getConversionUrl('large');
    }

    public function isImage(): bool
    {
        return $this->type === MediaType::IMAGE || $this->type === MediaType::AVATAR;
    }

    public function isVideo(): bool  
    {
        return $this->type === MediaType::VIDEO;
    }

    public function isAudio(): bool
    {
        return $this->type === MediaType::AUDIO;
    }

    public function isDocument(): bool
    {
        return $this->type === MediaType::DOCUMENT;
    }

    public function isAvatar(): bool
    {
        return $this->type === MediaType::AVATAR;
    }

    public function canBeDeleted(): bool
    {
        return $this->status->canBeDeleted();
    }

    public function canBeRestored(): bool
    {
        return $this->status->canBeRestored();
    }

    public function isProcessed(): bool
    {
        return $this->status === MediaStatus::PROCESSED;
    }

    public function isPending(): bool
    {
        return $this->status === MediaStatus::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status->isProcessing();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    public function markAsProcessing(): self
    {
        $this->status = MediaStatus::PROCESSING;
        $this->save();
        return $this;
    }

    public function markAsProcessed(): self
    {
        $this->status = MediaStatus::PROCESSED;
        $this->save();
        return $this;
    }

    public function markAsFailed(string $errorMessage = null): self
    {
        $this->status = MediaStatus::FAILED;
        
        if ($errorMessage) {
            $metadata = $this->metadata ?? [];
            $metadata['error'] = $errorMessage;
            $this->metadata = $metadata;
        }
        
        $this->save();
        return $this;
    }

    public function addConversion(string $name, array $conversionData): self
    {
        $conversions = $this->conversions ?? [];
        $conversions[$name] = $conversionData;
        $this->conversions = $conversions;
        $this->save();
        return $this;
    }

    public function removeConversion(string $name): self
    {
        $conversions = $this->conversions ?? [];
        
        if (isset($conversions[$name])) {
            $conversionPath = $conversions[$name]['file_name'] ?? null;
            if ($conversionPath && Storage::disk($this->disk)->exists($conversionPath)) {
                Storage::disk($this->disk)->delete($conversionPath);
            }
            
            unset($conversions[$name]);
            $this->conversions = $conversions;
            $this->save();
        }
        
        return $this;
    }

    public function updateMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata ?? [], $metadata);
        $this->save();
        return $this;
    }

    public function getMetadata(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->metadata ?? [];
        }
        
        return data_get($this->metadata, $key, $default);
    }

    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->file_name);
    }

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

    public function move(string $newPath): self
    {
        $oldPath = $this->file_name;
        
        Storage::disk($this->disk)->move($oldPath, $newPath);
        
        $this->file_name = $newPath;
        $this->save();
        
        return $this;
    }

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

    public function scopeByType($query, MediaType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, MediaStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCollection($query, string $collection)
    {
        return $query->where('collection_name', $collection);
    }

    public function scopeForEntity($query, Model $entity, string $collection = null)
    {
        $query->where('mediable_type', get_class($entity))
              ->where('mediable_id', $entity->id);
              
        if ($collection) {
            $query->where('collection_name', $collection);
        }
        
        return $query;
    }

    public function scopeImages($query)
    {
        return $query->whereIn('type', [MediaType::IMAGE, MediaType::AVATAR]);
    }

    public function scopeVideos($query)
    {
        return $query->where('type', MediaType::VIDEO);
    }

    public function scopeDocuments($query)
    {
        return $query->where('type', MediaType::DOCUMENT);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', MediaStatus::PROCESSED);
    }

    public function scopePending($query)
    {
        return $query->where('status', MediaStatus::PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', MediaStatus::FAILED);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        });
    }

}