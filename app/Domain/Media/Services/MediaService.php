<?php

namespace App\Domain\Media\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Support\Services\BaseService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as ResponseFacade;
use App\Domain\Media\Repositories\PhotoRepository;
use App\Domain\Media\Repositories\VideoRepository;

/**
 * Сервис для работы с медиафайлами
 */
class MediaService extends BaseService
{
    private PhotoRepository $photoRepository;
    private VideoRepository $videoRepository;
    
    public function __construct(
        PhotoRepository $photoRepository,
        VideoRepository $videoRepository
    ) {
        $this->photoRepository = $photoRepository;
        $this->videoRepository = $videoRepository;
    }
    /**
     * Получить файл фото мастера
     */
    public function getMasterPhoto(MasterProfile $master, string $filename): Response
    {
        $photo = $this->photoRepository->findByFilename($filename, $master->id);
        
        if (!$photo) {
            abort(404);
        }
        
        $path = storage_path('app/public/masters/' . $master->id . '/photos/' . $filename);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        return ResponseFacade::file($path);
    }

    /**
     * Получить файл видео мастера
     */
    public function getMasterVideo(MasterProfile $master, string $filename): Response
    {
        $video = $this->videoRepository->findByFilename($filename, $master->id);
        
        if (!$video) {
            abort(404);
        }
        
        $path = storage_path('app/public/masters/' . $master->id . '/videos/' . $filename);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        return ResponseFacade::file($path);
    }

    /**
     * Получить постер видео
     */
    public function getVideoPoster(MasterProfile $master, string $filename): Response
    {
        $posterFilename = str_replace('.mp4', '.jpg', $filename);
        $path = storage_path('app/public/masters/' . $master->id . '/videos/posters/' . $posterFilename);
        
        if (!file_exists($path)) {
            // Возвращаем дефолтный постер
            $defaultPath = public_path('images/video-placeholder.jpg');
            if (file_exists($defaultPath)) {
                return ResponseFacade::file($defaultPath);
            }
            abort(404);
        }
        
        return ResponseFacade::file($path);
    }

    /**
     * Получить аватар мастера
     */
    public function getMasterAvatar(MasterProfile $master): Response
    {
        if (!$master->avatar) {
            // Возвращаем дефолтный аватар
            $defaultPath = public_path('images/default-avatar.jpg');
            if (file_exists($defaultPath)) {
                return ResponseFacade::file($defaultPath);
            }
            abort(404);
        }
        
        $path = storage_path('app/public/' . $master->avatar);
        
        if (!file_exists($path)) {
            abort(404);
        }
        
        return ResponseFacade::file($path);
    }

    /**
     * Получить миниатюру аватара мастера
     */
    public function getMasterAvatarThumb(MasterProfile $master): Response
    {
        if (!$master->avatar) {
            $defaultPath = public_path('images/default-avatar-thumb.jpg');
            if (file_exists($defaultPath)) {
                return ResponseFacade::file($defaultPath);
            }
            abort(404);
        }
        
        // Генерируем путь к миниатюре
        $thumbPath = str_replace('.jpg', '_thumb.jpg', $master->avatar);
        $path = storage_path('app/public/' . $thumbPath);
        
        if (!file_exists($path)) {
            // Возвращаем оригинал если миниатюры нет
            $originalPath = storage_path('app/public/' . $master->avatar);
            if (file_exists($originalPath)) {
                return ResponseFacade::file($originalPath);
            }
            abort(404);
        }
        
        return ResponseFacade::file($path);
    }

    /**
     * Очистить фотографии мастера с удалением файлов
     */
    public function clearMasterPhotos($master): void
    {
        $photos = $this->photoRepository->findByMasterProfileId($master->id);
        foreach ($photos as $photo) {
            // Используем метод deletePhoto который удаляет файлы
            $this->photoRepository->deletePhoto($photo->id, $master->id);
        }
    }

    /**
     * Удалить фотографию с файлами
     */
    public function deletePhotoFiles(int $photoId, MasterProfile $master): bool
    {
        return $this->photoRepository->deletePhoto($photoId, $master->id);
    }

    /**
     * Удалить видео с файлами
     */
    public function deleteVideo(int $videoId, MasterProfile $master): bool
    {
        return $this->videoRepository->deleteVideo($videoId, $master->id);
    }

    /**
     * Очистить все видео мастера с удалением файлов
     */
    public function clearMasterVideos(MasterProfile $master): void
    {
        $videos = $this->videoRepository->findByMasterProfileId($master->id);
        foreach ($videos as $video) {
            $this->videoRepository->deleteVideo($video->id, $master->id);
        }
    }

    /**
     * Добавить тестовые фотографии
     */
    public function addTestPhotos($master, array $photos): array
    {
        $addedPhotos = [];
        
        foreach ($photos as $photo) {
            $createdPhoto = $this->photoRepository->createPhoto([
                'master_profile_id' => $master->id,
                'filename' => basename($photo['path']),
                'mime_type' => 'image/jpeg',
                'file_size' => 0,
                'width' => 800,
                'height' => 600,
                'is_main' => $photo['is_main'],
                'sort_order' => $photo['order'],
                'is_approved' => true
            ]);
            
            $addedPhotos[] = $createdPhoto;
        }
        
        return $addedPhotos;
    }

    /**
     * Добавить локальные фотографии
     */
    public function addLocalPhotos($master, array $localPhotos): array
    {
        $addedPhotos = [];
        
        foreach ($localPhotos as $index => $photoPath) {
            if (file_exists(public_path($photoPath))) {
                $photo = $this->photoRepository->createPhoto([
                    'master_profile_id' => $master->id,
                    'filename' => basename($photoPath),
                    'mime_type' => 'image/jpeg',
                    'file_size' => filesize(public_path($photoPath)),
                    'width' => 800,
                    'height' => 600,
                    'is_main' => $index === 0,
                    'sort_order' => $index + 1,
                    'is_approved' => true
                ]);
                
                $addedPhotos[] = [
                    'id' => $photo->id,
                    'url' => asset($photoPath),
                    'path' => $photoPath,
                ];
            } else {
                $photo = $this->photoRepository->createPhoto([
                    'master_profile_id' => $master->id,
                    'filename' => 'no-photo.jpg',
                    'mime_type' => 'image/jpeg',
                    'file_size' => 0,
                    'width' => 800,
                    'height' => 600,
                    'is_main' => $index === 0,
                    'sort_order' => $index + 1,
                    'is_approved' => true
                ]);
                
                $addedPhotos[] = [
                    'id' => $photo->id,
                    'url' => asset('images/no-photo.jpg'),
                    'path' => 'images/no-photo.jpg',
                    'note' => "Файл $photoPath не найден, использован placeholder"
                ];
            }
        }
        
        return $addedPhotos;
    }
}