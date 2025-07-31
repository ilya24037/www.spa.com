<?php

namespace App\Domain\User\DTOs;

/**
 * DTO для обновления настроек пользователя
 */
class UpdateSettingsDTO
{
    public function __construct(
        public readonly ?array $notifications = null,
        public readonly ?array $privacy = null,
        public readonly ?array $preferences = null,
        public readonly ?string $theme = null,
        public readonly ?string $language = null,
        public readonly ?string $timezone = null,
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            notifications: $data['notifications'] ?? null,
            privacy: $data['privacy'] ?? null,
            preferences: $data['preferences'] ?? null,
            theme: $data['theme'] ?? null,
            language: $data['language'] ?? null,
            timezone: $data['timezone'] ?? null,
        );
    }

    /**
     * Создать DTO из запроса
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return self::fromArray($request->validated());
    }

    /**
     * Конвертировать в массив (только заполненные поля)
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->notifications !== null) $data['notifications'] = $this->notifications;
        if ($this->privacy !== null) $data['privacy'] = $this->privacy;
        if ($this->preferences !== null) $data['preferences'] = $this->preferences;
        if ($this->theme !== null) $data['theme'] = $this->theme;
        if ($this->language !== null) $data['language'] = $this->language;
        if ($this->timezone !== null) $data['timezone'] = $this->timezone;

        return $data;
    }

    /**
     * Валидация данных
     */
    public function validate(): array
    {
        $errors = [];

        // Валидация уведомлений
        if ($this->notifications !== null) {
            if (!is_array($this->notifications)) {
                $errors['notifications'] = 'Настройки уведомлений должны быть массивом';
            } else {
                $errors = array_merge($errors, $this->validateNotifications());
            }
        }

        // Валидация приватности
        if ($this->privacy !== null) {
            if (!is_array($this->privacy)) {
                $errors['privacy'] = 'Настройки приватности должны быть массивом';
            } else {
                $errors = array_merge($errors, $this->validatePrivacy());
            }
        }

        // Валидация предпочтений
        if ($this->preferences !== null) {
            if (!is_array($this->preferences)) {
                $errors['preferences'] = 'Настройки предпочтений должны быть массивом';
            } else {
                $errors = array_merge($errors, $this->validatePreferences());
            }
        }

        // Валидация темы
        if ($this->theme !== null && !in_array($this->theme, ['light', 'dark', 'auto'])) {
            $errors['theme'] = 'Некорректная тема оформления';
        }

        // Валидация языка
        if ($this->language !== null && !in_array($this->language, ['ru', 'en', 'uk'])) {
            $errors['language'] = 'Неподдерживаемый язык';
        }

        // Валидация часового пояса
        if ($this->timezone !== null && !in_array($this->timezone, timezone_identifiers_list())) {
            $errors['timezone'] = 'Некорректный часовой пояс';
        }

        return $errors;
    }

    /**
     * Валидация настроек уведомлений
     */
    private function validateNotifications(): array
    {
        $errors = [];
        $allowedTypes = ['email', 'sms', 'push', 'browser'];
        $allowedEvents = [
            'new_booking', 'booking_confirmed', 'booking_cancelled', 'new_review',
            'new_message', 'system_updates', 'marketing', 'booking_reminder', 'urgent_notifications'
        ];

        foreach ($this->notifications as $type => $events) {
            if (!in_array($type, $allowedTypes)) {
                $errors["notifications.{$type}"] = 'Неизвестный тип уведомлений';
                continue;
            }

            if (!is_array($events)) {
                $errors["notifications.{$type}"] = 'События должны быть массивом';
                continue;
            }

            foreach ($events as $event => $enabled) {
                if (!in_array($event, $allowedEvents)) {
                    $errors["notifications.{$type}.{$event}"] = 'Неизвестное событие';
                }

                if (!is_bool($enabled)) {
                    $errors["notifications.{$type}.{$event}"] = 'Значение должно быть boolean';
                }
            }
        }

        return $errors;
    }

    /**
     * Валидация настроек приватности
     */
    private function validatePrivacy(): array
    {
        $errors = [];
        $allowedCategories = ['profile', 'search', 'communication', 'data'];
        $allowedSettings = [
            'profile' => ['show_phone', 'show_email', 'show_last_activity', 'show_online_status'],
            'search' => ['appear_in_search', 'show_in_recommendations'],
            'communication' => ['allow_messages_from_anyone', 'allow_booking_requests'],
            'data' => ['analytics_tracking', 'personalized_ads', 'gdpr_consent'],
        ];

        foreach ($this->privacy as $category => $settings) {
            if (!in_array($category, $allowedCategories)) {
                $errors["privacy.{$category}"] = 'Неизвестная категория настроек';
                continue;
            }

            if (!is_array($settings)) {
                $errors["privacy.{$category}"] = 'Настройки должны быть массивом';
                continue;
            }

            foreach ($settings as $setting => $value) {
                if (!in_array($setting, $allowedSettings[$category])) {
                    $errors["privacy.{$category}.{$setting}"] = 'Неизвестная настройка';
                }

                if (!is_bool($value)) {
                    $errors["privacy.{$category}.{$setting}"] = 'Значение должно быть boolean';
                }
            }
        }

        return $errors;
    }

    /**
     * Валидация настроек предпочтений
     */
    private function validatePreferences(): array
    {
        $errors = [];
        $allowedCategories = ['interface', 'booking', 'search'];
        $allowedSettings = [
            'interface' => ['compact_view', 'show_tutorials', 'auto_save_drafts'],
            'booking' => ['auto_confirm_bookings', 'require_deposit', 'booking_window_hours'],
            'search' => ['default_radius', 'save_search_history', 'show_similar_ads'],
        ];

        foreach ($this->preferences as $category => $settings) {
            if (!in_array($category, $allowedCategories)) {
                $errors["preferences.{$category}"] = 'Неизвестная категория настроек';
                continue;
            }

            if (!is_array($settings)) {
                $errors["preferences.{$category}"] = 'Настройки должны быть массивом';
                continue;
            }

            foreach ($settings as $setting => $value) {
                if (!in_array($setting, $allowedSettings[$category])) {
                    $errors["preferences.{$category}.{$setting}"] = 'Неизвестная настройка';
                    continue;
                }

                // Специальная валидация для разных типов настроек
                switch ($setting) {
                    case 'booking_window_hours':
                        if (!is_int($value) || $value < 1 || $value > 168) {
                            $errors["preferences.{$category}.{$setting}"] = 'Должно быть числом от 1 до 168 часов';
                        }
                        break;
                    
                    case 'default_radius':
                        if (!is_int($value) || $value < 1 || $value > 100) {
                            $errors["preferences.{$category}.{$setting}"] = 'Должно быть числом от 1 до 100 км';
                        }
                        break;
                    
                    default:
                        if (!is_bool($value)) {
                            $errors["preferences.{$category}.{$setting}"] = 'Значение должно быть boolean';
                        }
                        break;
                }
            }
        }

        return $errors;
    }

    /**
     * Проверить валидность DTO
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Получить только измененные поля
     */
    public function getChangedFields(array $currentData): array
    {
        $changed = [];
        $newData = $this->toArray();

        foreach ($newData as $field => $value) {
            if (!array_key_exists($field, $currentData) || $currentData[$field] !== $value) {
                $changed[$field] = $value;
            }
        }

        return $changed;
    }

    /**
     * Проверить есть ли изменения
     */
    public function hasChanges(array $currentData): bool
    {
        return !empty($this->getChangedFields($currentData));
    }
}