<?php

namespace App\Domain\Media\Traits;

use App\Enums\MediaType;
use App\Enums\MediaStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * Трейт для скоупов медиафайлов
 */
trait MediaScopesTrait
{
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