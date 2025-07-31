<?php

namespace App\Models;

use App\Domain\Master\Models\MasterProfile as BaseMasterProfile;
use App\Domain\Master\Models\MasterMedia;
use App\Domain\Master\Models\MasterSchedule;
use App\Helpers\ImageHelper;

/**
 * Legacy MasterProfile model для обратной совместимости
 * Использует новую доменную структуру
 */
class MasterProfile extends BaseMasterProfile
{

    // Для совместимости используем сервисы

    protected $appends = [
        'url', 'full_salon_address', 'full_url',
        'share_url', 'avatar_url', 'all_photos', 'folder_name',
    ];

    private ?MasterMedia $mediaService = null;
    private ?MasterSchedule $scheduleService = null;

    /**
     * Получить сервис для работы с медиа
     */
    protected function getMediaService(): MasterMedia
    {
        if (!$this->mediaService) {
            $this->mediaService = new MasterMedia();
        }
        return $this->mediaService;
    }

    /**
     * Получить сервис для работы с расписанием
     */
    protected function getScheduleService(): MasterSchedule
    {
        if (!$this->scheduleService) {
            $this->scheduleService = new MasterSchedule();
        }
        return $this->scheduleService;
    }

    /**
     * Получить имя папки для файлов мастера
     */
    public function getFolderNameAttribute(): string
    {
        return $this->getMediaService()->getFolderName($this->display_name, $this->id);
    }

    /* --------------------------------------------------------------------- */
    /*  Дополнительные отношения для обратной совместимости                 */
    /* --------------------------------------------------------------------- */

    /**
     * Связь с расписанием (делегируем в MasterSchedule)
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Связь с исключениями расписания
     */
    public function scheduleExceptions()
    {
        return $this->hasMany(ScheduleException::class);
    }


    /**
     * Связь с фотографиями (делегируем в MasterMedia)
     */
    public function photos()
    {
        return $this->hasMany(MasterPhoto::class, 'master_profile_id');
    }

    /**
     * Связь с видео
     */
    public function videos()
    {
        return $this->hasMany(MasterVideo::class, 'master_profile_id');
    }

    /**
     * Главное фото
     */
    public function mainPhoto()
    {
        return $this->hasOne(MasterPhoto::class, 'master_profile_id')
                    ->where('is_main', true);
    }

    /**
     * Главное видео
     */
    public function mainVideo()
    {
        return $this->hasOne(MasterVideo::class, 'master_profile_id')
                    ->where('is_main', true);
    }

    /* --------------------------------------------------------------------- */
    /*  Дополнительная логика для обратной совместимости                    */
    /* --------------------------------------------------------------------- */

    /**
     * Проверка доступности мастера (делегируем в MasterSchedule)
     */
    public function isAvailableNow(): bool
    {
        return $this->getScheduleService()->isAvailableNow($this->id, $this->isActive());
    }


    /* --------------------------------------------------------------------- */
    /*  Accessors & Mutators                                                 */
    /* --------------------------------------------------------------------- */

    public function getFullSalonAddressAttribute(): string
    {
        $parts = array_filter([
            $this->city,
            $this->district,
            $this->metro_station ? "м. {$this->metro_station}" : null,
            $this->salon_address,
        ]);

        return implode(', ', $parts);
    }

    public function getFullUrlAttribute(): string
    {
        return $this->url;
    }

    public function getShareUrlAttribute(): string
    {
        return preg_replace('#^https?://#', '', $this->full_url);
    }

    /**
     * Получить URL аватара (делегируем в MasterMedia)
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->getMediaService()->getAvatarUrl($this->avatar);
    }

    /**
     * Получить все фото (делегируем в MasterMedia)
     */
    public function getAllPhotosAttribute(): array
    {
        return $this->getMediaService()->getAllPhotosFormatted($this->id);
    }

    /**
     * Получить все видео (делегируем в MasterMedia)
     */
    public function getAllVideosAttribute(): array
    {
        return $this->getMediaService()->getAllVideosFormatted($this->id);
    }

}
