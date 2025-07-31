<?php

namespace App\Domain\Media\Services;

use App\Models\MasterProfile;
use App\Models\MasterPhoto;
use App\Models\MasterVideo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Основной сервис для работы с медиа
 * Координирует работу процессоров изображений и видео
 */
class MediaService
{
    private ImageProcessor $imageProcessor;
    private VideoProcessor $videoProcessor;
    private ThumbnailGenerator $thumbnailGenerator;

    public function __construct(
        ImageProcessor $imageProcessor,
        VideoProcessor $videoProcessor,
        ThumbnailGenerator $thumbnailGenerator
    ) {
        $this->imageProcessor = $imageProcessor;
        $this->videoProcessor = $videoProcessor;
        $this->thumbnailGenerator = $thumbnailGenerator;
    }

    /**
     * Загрузить фотографии мастера
     */
    public function uploadPhotos(MasterProfile $master, array $files): array
    {
        $uploadedPhotos = [];
        
        DB::beginTransaction();
        try {
            foreach ($files as $index => $file) {
                $photoNumber = $index + 1;
                $uploadedPhotos[] = $this->imageProcessor->processPhoto($file, $master, $photoNumber);
            }
            
            DB::commit();
            return $uploadedPhotos;
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Удаляем загруженные файлы при ошибке
            foreach ($uploadedPhotos as $photo) {
                $this->deletePhoto($photo);
            }
            
            throw $e;
        }
    }

    /**
     * Загрузить видео мастера
     */
    public function uploadVideo(MasterProfile $master, UploadedFile $file): MasterVideo
    {
        return $this->videoProcessor->processVideo($file, $master);
    }

    /**
     * Загрузить аватар мастера
     */
    public function uploadAvatar(MasterProfile $master, UploadedFile $file): string
    {
        $avatarPath = $this->imageProcessor->processAvatar($file, $master);
        
        // Обновляем путь к аватару в профиле
        $master->update(['avatar' => $avatarPath]);
        
        return $avatarPath;
    }

    /**
     * Удалить фотографию
     */
    public function deletePhoto(MasterPhoto $photo): void
    {
        $this->imageProcessor->deletePhoto($photo);
    }

    /**
     * Удалить видео
     */
    public function deleteVideo(MasterVideo $video): void
    {
        $this->videoProcessor->deleteVideo($video);
    }

    /**
     * Переупорядочить фотографии
     */
    public function reorderPhotos(MasterProfile $master, array $photoIds): void
    {
        foreach ($photoIds as $order => $photoId) {
            MasterPhoto::where('id', $photoId)
                ->where('master_profile_id', $master->id)
                ->update(['sort_order' => $order + 1]);
        }
    }

    /**
     * Установить главное фото
     */
    public function setMainPhoto(MasterProfile $master, int $photoId): void
    {
        // Снимаем флаг главного со всех фото
        MasterPhoto::where('master_profile_id', $master->id)
            ->update(['is_main' => false]);
        
        // Устанавливаем новое главное фото
        MasterPhoto::where('id', $photoId)
            ->where('master_profile_id', $master->id)
            ->update(['is_main' => true]);
    }

    /**
     * Получить статистику медиа мастера
     */
    public function getMediaStats(MasterProfile $master): array
    {
        return [
            'photos_count' => $master->photos()->count(),
            'videos_count' => $master->videos()->count(),
            'total_size' => $this->calculateTotalSize($master),
            'approved_photos' => $master->photos()->where('is_approved', true)->count(),
            'approved_videos' => $master->videos()->where('is_approved', true)->count(),
        ];
    }

    /**
     * Вычислить общий размер медиа файлов
     */
    private function calculateTotalSize(MasterProfile $master): int
    {
        $photosSize = $master->photos()->sum('file_size');
        $videosSize = $master->videos()->sum('file_size');
        
        return $photosSize + $videosSize;
    }

    /**
     * Проверить лимиты медиа
     */
    public function checkMediaLimits(MasterProfile $master, string $type = 'photo'): bool
    {
        $limits = [
            'photo' => 10,
            'video' => 3,
        ];
        
        $count = $type === 'photo' 
            ? $master->photos()->count()
            : $master->videos()->count();
            
        return $count < ($limits[$type] ?? 0);
    }
}