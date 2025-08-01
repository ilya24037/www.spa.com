<?php

namespace App\Support\Traits;

use App\Models\UserProfile;
use App\Models\UserSettings;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Trait для работы с профилем пользователя
 */
trait HasUserProfile
{
    /**
     * Связь с профилем пользователя
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Связь с настройками пользователя
     */
    public function settings(): HasOne
    {
        return $this->hasOne(UserSettings::class);
    }

    /**
     * Получить или создать профиль
     */
    public function getProfile(): UserProfile
    {
        if (!$this->profile) {
            $this->profile()->create([
                'user_id' => $this->id,
                'name' => $this->name ?? 'Пользователь',
            ]);
            $this->refresh();
        }
        
        return $this->profile;
    }

    /**
     * Получить или создать настройки
     */
    public function getSettings(): UserSettings
    {
        if (!$this->settings) {
            $this->settings()->create([
                'user_id' => $this->id,
            ]);
            $this->refresh();
        }
        
        return $this->settings;
    }

    /**
     * Получить URL аватара
     */
    public function getAvatarUrlAttribute(): string
    {
        $profile = $this->getProfile();
        return $profile->avatar_url;
    }

    /**
     * Получить имя для отображения
     */
    public function getDisplayNameAttribute(): string
    {
        $profile = $this->getProfile();
        return $profile->display_name;
    }

    /**
     * Проверить заполненность профиля
     */
    public function hasCompleteProfile(): bool
    {
        return $this->profile?->isComplete() ?? false;
    }

    /**
     * Получить процент заполненности профиля
     */
    public function getProfileCompletionAttribute(): int
    {
        return $this->profile?->completion_percentage ?? 0;
    }
}