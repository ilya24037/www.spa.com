<?php

namespace App\Domain\User\Traits;

use App\Domain\Master\Models\MasterProfile;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Трейт для связи с профилем мастера
 */
trait HasMasterProfile
{
    /**
     * Профиль мастера
     */
    public function masterProfile(): HasOne
    {
        return $this->hasOne(MasterProfile::class);
    }

    /**
     * Профили мастера (множественное число для Dashboard)
     * Некоторые мастера могут иметь несколько профилей/анкет
     */
    public function masterProfiles(): HasMany
    {
        return $this->hasMany(MasterProfile::class);
    }

    /**
     * Проверка, есть ли у мастера активный профиль
     */
    public function hasActiveMasterProfile(): bool
    {
        return $this->isMaster() && 
               $this->masterProfile && 
               $this->masterProfile->status === 'active';
    }

    /**
     * Получить основной профиль мастера
     */
    public function getMainMasterProfile(): ?MasterProfile
    {
        return $this->masterProfiles()
            ->where('is_main', true)
            ->first() ?? $this->masterProfile;
    }

    /**
     * Создать профиль мастера
     */
    public function createMasterProfile(array $data): MasterProfile
    {
        return $this->masterProfile()->create($data);
    }
}