<?php

namespace App\Services;

use App\Models\MasterProfile;
use App\Models\MasterPhoto;
use App\Models\MasterVideo;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Загрузить фотографии
     */
    public function uploadPhotos(MasterProfile $master, array $files): array
    {
        $uploadedPhotos = [];

        foreach ($files as $file) {
            $photo = $this->processPhoto($file, $master);
            $uploadedPhotos[] = $photo;
        }

        return $uploadedPhotos;
    }

    /**
     * Загрузить видео
     */
    public function uploadVideo(MasterProfile $master, $file): array
    {
        return $this->processVideo($file, $master);
    }

    /**
     * Удалить фотографию
     */
    public function deletePhoto(MasterPhoto $photo): void
    {
        // Удаляем файлы
        $this->deletePhotoFiles($photo);
        
        // Удаляем запись из БД
        $photo->delete();
    }

    /**
     * Удалить видео
     */
    public function deleteVideo(MasterVideo $video): void
    {
        // Удаляем файлы
        $this->deleteVideoFiles($video);
        
        // Удаляем запись из БД
        $video->delete();
    }

    /**
     * Обработка фотографии
     */
    private function processPhoto($file, MasterProfile $master): array
    {
        // Генерируем уникальное имя
        $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();
        
        // Сохраняем оригинал
        $originalPath = $file->storeAs('', $fileName, 'masters_photos');
        
        // Создаем миниатюры
        $thumbPath = ImageHelper::createThumbnail($file, $fileName, 300, 300);
        $mediumPath = ImageHelper::createThumbnail($file, $fileName, 600, 600);
        
        // Создаем запись в БД
        $photo = MasterPhoto::create([
            'master_profile_id' => $master->id,
            'path' => $originalPath,
            'thumb_path' => $thumbPath,
            'medium_path' => $mediumPath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'is_main' => $master->photos()->count() === 0,
            'order' => $master->photos()->count() + 1,
        ]);

        return [
            'id' => $photo->id,
            'url' => $photo->url,
            'thumb_url' => $photo->thumb_url,
            'medium_url' => $photo->medium_url,
            'file_size' => $photo->formatted_size,
        ];
    }

    /**
     * Обработка видео
     */
    private function processVideo($file, MasterProfile $master): array
    {
        $fileName = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $videoPath = $file->storeAs('', $fileName, 'masters_videos');
        
        $video = MasterVideo::create([
            'master_profile_id' => $master->id,
            'path' => $videoPath,
            'thumb_path' => null, // Пока без превью
            'duration' => null,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'is_main' => $master->videos()->count() === 0,
            'order' => $master->videos()->count() + 1,
        ]);

        return [
            'id' => $video->id,
            'url' => $video->url,
            'thumb_url' => $video->thumb_url,
            'duration' => $video->formatted_duration,
            'file_size' => $video->formatted_size,
        ];
    }

    /**
     * Удалить файлы фотографии
     */
    private function deletePhotoFiles(MasterPhoto $photo): void
    {
        if (Storage::disk('masters_photos')->exists($photo->path)) {
            Storage::disk('masters_photos')->delete($photo->path);
        }
        
        if ($photo->thumb_path && Storage::disk('masters_thumbnails')->exists($photo->thumb_path)) {
            Storage::disk('masters_thumbnails')->delete($photo->thumb_path);
        }
        
        if ($photo->medium_path && Storage::disk('masters_thumbnails')->exists($photo->medium_path)) {
            Storage::disk('masters_thumbnails')->delete($photo->medium_path);
        }
    }

    /**
     * Удалить файлы видео
     */
    private function deleteVideoFiles(MasterVideo $video): void
    {
        if (Storage::disk('masters_videos')->exists($video->path)) {
            Storage::disk('masters_videos')->delete($video->path);
        }
        
        if ($video->thumb_path && Storage::disk('masters_thumbnails')->exists($video->thumb_path)) {
            Storage::disk('masters_thumbnails')->delete($video->thumb_path);
        }
    }
} 