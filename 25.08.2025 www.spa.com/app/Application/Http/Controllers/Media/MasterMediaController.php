<?php

namespace App\Application\Http\Controllers\Media;

use App\Application\Http\Controllers\Controller;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Media\Services\MediaService;

/**
 * Контроллер для работы с медиафайлами мастеров
 */
class MasterMediaController extends Controller
{
    private MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Показать фото мастера
     */
    public function photo(MasterProfile $master, string $filename)
    {
        return $this->mediaService->getMasterPhoto($master, $filename);
    }
    
    /**
     * Показать видео мастера
     */
    public function video(MasterProfile $master, string $filename)
    {
        return $this->mediaService->getMasterVideo($master, $filename);
    }
    
    /**
     * Показать постер видео
     */
    public function videoPoster(MasterProfile $master, string $filename)
    {
        return $this->mediaService->getVideoPoster($master, $filename);
    }
    
    /**
     * Показать аватар мастера
     */
    public function avatar(MasterProfile $master)
    {
        return $this->mediaService->getMasterAvatar($master);
    }
    
    /**
     * Показать миниатюру аватара
     */
    public function avatarThumb(MasterProfile $master)
    {
        return $this->mediaService->getMasterAvatarThumb($master);
    }
}