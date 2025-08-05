<?php

namespace App\Domain\User\Traits;

use App\Domain\User\Models\UserProfile;
use App\Domain\User\Models\UserSettings;

/**
 * Трейт для работы с профилем и настройками пользователя
 */
trait HasProfile
{
    /**
     * Безопасно получить профиль пользователя (без создания)
     */
    public function getProfile(): ?UserProfile
    {
        return $this->profile;
    }

    /**
     * Безопасно получить настройки пользователя (без создания)
     */
    public function getSettings(): ?UserSettings
    {
        return $this->settings;
    }

    /**
     * Обеспечить наличие профиля пользователя (создать если не существует)
     * Явно указывает на создание записи
     */
    public function ensureProfile(): UserProfile
    {
        if ($this->profile) {
            return $this->profile;
        }

        // Создаем профиль с дефолтными значениями
        $profile = $this->profile()->create($this->getDefaultProfileData());
        
        // Обновляем кеш отношения
        $this->setRelation('profile', $profile);
        
        return $profile;
    }

    /**
     * Обеспечить наличие настроек пользователя (создать если не существует)
     * Явно указывает на создание записи
     */
    public function ensureSettings(): UserSettings
    {
        if ($this->settings) {
            return $this->settings;
        }

        // Создаем настройки с дефолтными значениями
        $settings = $this->settings()->create($this->getDefaultSettingsData());
        
        // Обновляем кеш отношения
        $this->setRelation('settings', $settings);
        
        return $settings;
    }

    /**
     * Получить дефолтные данные для профиля
     */
    private function getDefaultProfileData(): array
    {
        return [
            'name' => 'Пользователь',
            'notifications_enabled' => true,
            'newsletter_enabled' => true,
        ];
    }

    /**
     * Получить дефолтные данные для настроек
     */
    private function getDefaultSettingsData(): array
    {
        return [
            'locale' => 'ru',
            'timezone' => 'Europe/Moscow',
            'currency' => 'RUB',
            'email_notifications' => true,
            'sms_notifications' => true,
            'push_notifications' => true,
            'marketing_emails' => false,
            'two_factor_enabled' => false,
            'privacy_settings' => [],
        ];
    }

    /**
     * Обновить профиль с валидацией данных
     */
    public function updateProfile(array $data): bool
    {
        $validatedData = $this->validateProfileData($data);
        
        $profile = $this->ensureProfile();
        return $profile->update($validatedData);
    }

    /**
     * Обновить настройки с валидацией данных
     */
    public function updateSettings(array $data): bool
    {
        $validatedData = $this->validateSettingsData($data);
        
        $settings = $this->ensureSettings();
        return $settings->update($validatedData);
    }

    /**
     * Валидация данных профиля
     */
    private function validateProfileData(array $data): array
    {
        $allowedFields = ['name', 'phone', 'city', 'about', 'avatar', 'birth_date', 
                         'notifications_enabled', 'newsletter_enabled'];
        
        $validated = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $validated[$field] = $this->sanitizeProfileField($field, $data[$field]);
            }
        }
        
        return $validated;
    }

    /**
     * Валидация данных настроек
     */
    private function validateSettingsData(array $data): array
    {
        $allowedFields = ['locale', 'timezone', 'currency', 'email_notifications', 
                         'sms_notifications', 'push_notifications', 'marketing_emails',
                         'two_factor_enabled', 'privacy_settings'];
        
        $validated = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $validated[$field] = $this->sanitizeSettingsField($field, $data[$field]);
            }
        }
        
        return $validated;
    }

    /**
     * Санитизация полей профиля
     */
    private function sanitizeProfileField(string $field, $value)
    {
        return match($field) {
            'name', 'city', 'about' => is_string($value) ? trim(strip_tags($value)) : null,
            'phone' => is_string($value) ? preg_replace('/[^+\d]/', '', $value) : null,
            'avatar' => is_string($value) ? trim($value) : null,
            'birth_date' => $value,
            'notifications_enabled', 'newsletter_enabled' => (bool) $value,
            default => $value
        };
    }

    /**
     * Санитизация полей настроек
     */
    private function sanitizeSettingsField(string $field, $value)
    {
        return match($field) {
            'locale' => in_array($value, ['ru', 'en']) ? $value : 'ru',
            'timezone' => is_string($value) ? $value : 'Europe/Moscow',
            'currency' => in_array($value, ['RUB', 'USD', 'EUR']) ? $value : 'RUB',
            'email_notifications', 'sms_notifications', 'push_notifications', 
            'marketing_emails', 'two_factor_enabled' => (bool) $value,
            'privacy_settings' => is_array($value) ? $value : [],
            default => $value
        };
    }

    /**
     * Проверить заполненность профиля
     */
    public function hasCompleteProfile(): bool
    {
        $profile = $this->profile;
        
        if (!$profile) {
            return false;
        }

        return !empty($profile->name) && 
               !empty($profile->phone) && 
               !empty($profile->city);
    }

    /**
     * Получить полное имя пользователя
     */
    public function getFullName(): string
    {
        $profile = $this->getProfile();
        return $profile?->getFullNameAttribute() ?? 'Пользователь';
    }

    /**
     * Получить URL аватара
     */
    public function getAvatarUrl(): string
    {
        $profile = $this->getProfile();
        return $profile?->getAvatarUrlAttribute() ?? asset('images/default-avatar.png');
    }

    /**
     * Проверить есть ли аватар
     */
    public function hasAvatar(): bool
    {
        $profile = $this->getProfile();
        return $profile && !empty($profile->avatar);
    }

    /**
     * Получить настройку уведомлений
     */
    public function getNotificationSetting(string $type): bool
    {
        $settings = $this->getSettings();
        
        return match($type) {
            'email' => $settings?->email_notifications ?? true,
            'sms' => $settings?->sms_notifications ?? true,
            'push' => $settings?->push_notifications ?? true,
            'marketing' => $settings?->marketing_emails ?? false,
            default => false
        };
    }

    /**
     * Проверить включена ли двухфакторная аутентификация
     */
    public function hasTwoFactorEnabled(): bool
    {
        $settings = $this->getSettings();
        return $settings?->two_factor_enabled ?? false;
    }
}