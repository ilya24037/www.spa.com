<?php

namespace App\Domain\Media\Services;

/**
 * Сервис для создания различных типов медиафайлов
 */
class MediaFactoryService
{
    /**
     * Создать Photo через центральную Media модель
     */
    public static function createPhoto(array $data): \App\Domain\Media\Models\Photo
    {
        $photo = new \App\Domain\Media\Models\Photo($data);
        $photo->save();
        return $photo;
    }

    /**
     * Создать Video через центральную Media модель  
     */
    public static function createVideo(array $data): \App\Domain\Media\Models\Video
    {
        $video = new \App\Domain\Media\Models\Video($data);
        $video->save();
        return $video;
    }

    /**
     * Получить все медиафайлы для сущности через базовую модель
     */
    public static function getMediaForEntity(string $entityType, int $entityId): \Illuminate\Support\Collection
    {
        $collection = collect();
        
        // Из новой таблицы media
        $mediaFiles = \App\Domain\Media\Models\Media::where('mediable_type', $entityType)
            ->where('mediable_id', $entityId)
            ->get();
        $collection = $collection->merge($mediaFiles);

        // Из legacy таблиц (если нужно)
        if ($entityType === 'App\\Domain\\Master\\Models\\MasterProfile') {
            $photos = \App\Domain\Media\Models\Photo::where('master_profile_id', $entityId)->get();
            $videos = \App\Domain\Media\Models\Video::where('master_profile_id', $entityId)->get();
            $collection = $collection->merge($photos)->merge($videos);
        }

        return $collection->sortBy('sort_order');
    }
}