<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Настройки пользователя (приватность, уведомления и т.д.)
 */
class UserSettings extends Model
{
    use HasFactory;

    protected $table = 'user_settings';

    protected $fillable = [
        'user_id',
        'notifications',
        'privacy',
        'preferences',
        'theme',
        'language',
        'timezone',
    ];

    protected $casts = [
        'notifications' => 'array',
        'privacy' => 'array',
        'preferences' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Настройки уведомлений по умолчанию
     */
    public static function getDefaultNotifications(): array
    {
        return [
            'email' => [
                'new_booking' => true,
                'booking_confirmed' => true,
                'booking_cancelled' => true,
                'new_review' => true,
                'new_message' => true,
                'system_updates' => false,
                'marketing' => false,
            ],
            'sms' => [
                'booking_reminder' => true,
                'urgent_notifications' => true,
            ],
            'push' => [
                'new_booking' => true,
                'new_message' => true,
                'booking_reminder' => true,
            ],
            'browser' => [
                'new_booking' => true,
                'new_message' => true,
            ],
        ];
    }

    /**
     * Настройки приватности по умолчанию
     */
    public static function getDefaultPrivacy(): array
    {
        return [
            'profile' => [
                'show_phone' => false,
                'show_email' => false,
                'show_last_activity' => true,
                'show_online_status' => true,
            ],
            'search' => [
                'appear_in_search' => true,
                'show_in_recommendations' => true,
            ],
            'communication' => [
                'allow_messages_from_anyone' => true,
                'allow_booking_requests' => true,
            ],
            'data' => [
                'analytics_tracking' => true,
                'personalized_ads' => false,
            ],
        ];
    }

    /**
     * Настройки предпочтений по умолчанию
     */
    public static function getDefaultPreferences(): array
    {
        return [
            'interface' => [
                'compact_view' => false,
                'show_tutorials' => true,
                'auto_save_drafts' => true,
            ],
            'booking' => [
                'auto_confirm_bookings' => false,
                'require_deposit' => false,
                'booking_window_hours' => 24,
            ],
            'search' => [
                'default_radius' => 10,
                'save_search_history' => true,
                'show_similar_ads' => true,
            ],
        ];
    }

    /**
     * Проверить включено ли уведомление
     */
    public function isNotificationEnabled(string $type, string $event): bool
    {
        return $this->notifications[$type][$event] ?? false;
    }

    /**
     * Включить/выключить уведомление
     */
    public function setNotification(string $type, string $event, bool $enabled): void
    {
        $notifications = $this->notifications ?? self::getDefaultNotifications();
        $notifications[$type][$event] = $enabled;
        
        $this->notifications = $notifications;
        $this->save();
    }

    /**
     * Проверить настройку приватности
     */
    public function getPrivacySetting(string $category, string $setting): bool
    {
        return $this->privacy[$category][$setting] ?? false;
    }

    /**
     * Установить настройку приватности
     */
    public function setPrivacySetting(string $category, string $setting, bool $value): void
    {
        $privacy = $this->privacy ?? self::getDefaultPrivacy();
        $privacy[$category][$setting] = $value;
        
        $this->privacy = $privacy;
        $this->save();
    }

    /**
     * Получить настройку предпочтений
     */
    public function getPreference(string $category, string $setting): mixed
    {
        return $this->preferences[$category][$setting] ?? null;
    }

    /**
     * Установить настройку предпочтений
     */
    public function setPreference(string $category, string $setting, mixed $value): void
    {
        $preferences = $this->preferences ?? self::getDefaultPreferences();
        $preferences[$category][$setting] = $value;
        
        $this->preferences = $preferences;
        $this->save();
    }

    /**
     * Получить тему оформления
     */
    public function getTheme(): string
    {
        return $this->theme ?? 'light';
    }

    /**
     * Установить тему оформления
     */
    public function setTheme(string $theme): void
    {
        $allowedThemes = ['light', 'dark', 'auto'];
        
        if (in_array($theme, $allowedThemes)) {
            $this->theme = $theme;
            $this->save();
        }
    }

    /**
     * Получить язык интерфейса
     */
    public function getLanguage(): string
    {
        return $this->language ?? 'ru';
    }

    /**
     * Установить язык интерфейса
     */
    public function setLanguage(string $language): void
    {
        $allowedLanguages = ['ru', 'en', 'uk'];
        
        if (in_array($language, $allowedLanguages)) {
            $this->language = $language;
            $this->save();
        }
    }

    /**
     * Получить часовой пояс
     */
    public function getTimezone(): string
    {
        return $this->timezone ?? 'Europe/Moscow';
    }

    /**
     * Установить часовой пояс
     */
    public function setTimezone(string $timezone): void
    {
        if (in_array($timezone, timezone_identifiers_list())) {
            $this->timezone = $timezone;
            $this->save();
        }
    }

    /**
     * Сбросить настройки к значениям по умолчанию
     */
    public function resetToDefaults(): void
    {
        $this->notifications = self::getDefaultNotifications();
        $this->privacy = self::getDefaultPrivacy();
        $this->preferences = self::getDefaultPreferences();
        $this->theme = 'light';
        $this->language = 'ru';
        $this->timezone = 'Europe/Moscow';
        
        $this->save();
    }

    /**
     * Экспорт настроек для бэкапа
     */
    public function exportSettings(): array
    {
        return [
            'notifications' => $this->notifications,
            'privacy' => $this->privacy,
            'preferences' => $this->preferences,
            'theme' => $this->theme,
            'language' => $this->language,
            'timezone' => $this->timezone,
        ];
    }

    /**
     * Импорт настроек из бэкапа
     */
    public function importSettings(array $settings): void
    {
        $allowedFields = ['notifications', 'privacy', 'preferences', 'theme', 'language', 'timezone'];
        
        foreach ($allowedFields as $field) {
            if (isset($settings[$field])) {
                $this->$field = $settings[$field];
            }
        }
        
        $this->save();
    }

    /**
     * Проверить согласие на обработку персональных данных
     */
    public function hasGdprConsent(): bool
    {
        return $this->getPrivacySetting('data', 'gdpr_consent') ?? false;
    }

    /**
     * Установить согласие на обработку персональных данных
     */
    public function setGdprConsent(bool $consent): void
    {
        $this->setPrivacySetting('data', 'gdpr_consent', $consent);
        $this->setPrivacySetting('data', 'gdpr_consent_date', now()->toISOString());
    }

    /**
     * Boot метод для установки значений по умолчанию
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($settings) {
            if (empty($settings->notifications)) {
                $settings->notifications = self::getDefaultNotifications();
            }
            
            if (empty($settings->privacy)) {
                $settings->privacy = self::getDefaultPrivacy();
            }
            
            if (empty($settings->preferences)) {
                $settings->preferences = self::getDefaultPreferences();
            }
        });
    }
}