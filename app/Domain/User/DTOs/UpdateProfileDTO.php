<?php

namespace App\Domain\User\DTOs;

use Carbon\Carbon;

/**
 * DTO для обновления профиля пользователя (согласно плану)
 * 
 * КРИТИЧЕСКИ ВАЖНО:
 * - Immutable объект (final class)
 * - Строгая валидация всех полей
 * - Защита от XSS и SQL инъекций
 * - Соответствие стандартам RegisterUserDTO
 */
final class UpdateProfileDTO
{
    // Приватные поля для дополнительной безопасности
    private array $validatedSocialLinks = [];
    
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $phone = null,
        public readonly ?Carbon $birth_date = null,
        public readonly ?string $gender = null,
        public readonly ?string $city = null,
        public readonly ?string $about = null,
        public readonly ?string $website = null,
        ?array $social_links = null, // НЕ readonly для валидации
        public readonly ?string $language = null,
        public readonly ?string $timezone = null,
    ) {
        // Валидация social_links при создании
        if ($social_links !== null) {
            $this->validatedSocialLinks = $this->validateSocialLinks($social_links);
        }
    }

    /**
     * Создать DTO из массива данных с безопасной обработкой
     */
    public static function fromArray(array $data): self
    {
        // Безопасная обработка даты
        $birthDate = null;
        if (isset($data['birth_date']) && !empty($data['birth_date'])) {
            try {
                $birthDate = Carbon::parse($data['birth_date']);
                // Проверяем разумность даты
                if ($birthDate->isFuture() || $birthDate->age > 120) {
                    $birthDate = null;
                }
            } catch (\Exception $e) {
                $birthDate = null;
            }
        }
        
        return new self(
            name: isset($data['name']) ? self::sanitizeString($data['name']) : null,
            phone: isset($data['phone']) ? self::sanitizePhone($data['phone']) : null,
            birth_date: $birthDate,
            gender: $data['gender'] ?? null,
            city: isset($data['city']) ? self::sanitizeString($data['city']) : null,
            about: isset($data['about']) ? self::sanitizeText($data['about']) : null,
            website: isset($data['website']) ? self::sanitizeUrl($data['website']) : null,
            social_links: $data['social_links'] ?? null,
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
     * Конвертировать в массив БЕЗ ЧУВСТВИТЕЛЬНЫХ ДАННЫХ
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->phone !== null) $data['phone'] = $this->phone;
        if ($this->birth_date !== null) $data['birth_date'] = $this->birth_date->toDateString();
        if ($this->gender !== null) $data['gender'] = $this->gender;
        if ($this->city !== null) $data['city'] = $this->city;
        if ($this->about !== null) $data['about'] = $this->about;
        if ($this->website !== null) $data['website'] = $this->website;
        if (!empty($this->validatedSocialLinks)) $data['social_links'] = $this->validatedSocialLinks;
        if ($this->language !== null) $data['language'] = $this->language;
        if ($this->timezone !== null) $data['timezone'] = $this->timezone;

        return $data;
    }

    /**
     * Валидация данных (строгая, с защитой от XSS/SQL инъекций)
     */
    public function validate(): array
    {
        $errors = [];

        // Имя валидация (с защитой от XSS)
        if ($this->name !== null) {
            if (strlen($this->name) < 2) {
                $errors['name'] = 'Имя должно содержать минимум 2 символа';
            } elseif (strlen($this->name) > 100) {
                $errors['name'] = 'Имя не должно превышать 100 символов';
            } elseif (!preg_match('/^[\p{L}\s\'-]+$/u', $this->name)) {
                $errors['name'] = 'Имя содержит недопустимые символы';
            }
        }

        // Телефон валидация (международный формат E.164)
        if ($this->phone !== null && !empty($this->phone)) {
            $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $this->phone);
            if (!preg_match('/^\+?[1-9]\d{1,14}$/', $cleanPhone)) {
                $errors['phone'] = 'Некорректный формат телефона (используйте международный формат)';
            }
        }

        // Дата рождения валидация
        if ($this->birth_date !== null) {
            if ($this->birth_date->isFuture()) {
                $errors['birth_date'] = 'Дата рождения не может быть в будущем';
            } elseif ($this->birth_date->age < 16) {
                $errors['birth_date'] = 'Возраст должен быть не менее 16 лет';
            } elseif ($this->birth_date->age > 120) {
                $errors['birth_date'] = 'Некорректная дата рождения';
            }
        }

        // Пол валидация (enum значения)
        if ($this->gender !== null && !in_array($this->gender, ['male', 'female', 'other'], true)) {
            $errors['gender'] = 'Некорректное значение пола';
        }

        // Город валидация (защита от XSS)
        if ($this->city !== null) {
            if (strlen($this->city) > 100) {
                $errors['city'] = 'Название города не должно превышать 100 символов';
            } elseif (!preg_match('/^[\p{L}\s\-\.]+$/u', $this->city)) {
                $errors['city'] = 'Название города содержит недопустимые символы';
            }
        }

        // О себе валидация (защита от XSS/HTML)
        if ($this->about !== null) {
            if (strlen($this->about) > 1000) {
                $errors['about'] = 'Описание не должно превышать 1000 символов';
            } elseif ($this->about !== strip_tags($this->about)) {
                $errors['about'] = 'Описание не должно содержать HTML теги';
            }
        }

        // Веб-сайт валидация (безопасные протоколы)
        if ($this->website !== null) {
            if (!filter_var($this->website, FILTER_VALIDATE_URL)) {
                $errors['website'] = 'Некорректный URL веб-сайта';
            } elseif (!preg_match('/^https?:\/\//i', $this->website)) {
                $errors['website'] = 'URL должен начинаться с http:// или https://';
            } elseif (strlen($this->website) > 255) {
                $errors['website'] = 'URL слишком длинный';
            }
        }

        // Социальные ссылки валидация
        if (!empty($this->validatedSocialLinks)) {
            foreach ($this->validatedSocialLinks as $platform => $url) {
                if (!$this->isValidSocialUrl($platform, $url)) {
                    $errors['social_links'] = "Некорректная ссылка для $platform";
                    break;
                }
            }
        }

        // Язык валидация (поддерживаемые языки)
        if ($this->language !== null && !in_array($this->language, ['ru', 'en', 'uk'], true)) {
            $errors['language'] = 'Неподдерживаемый язык';
        }

        // Часовой пояс валидация
        if ($this->timezone !== null && !in_array($this->timezone, timezone_identifiers_list(), true)) {
            $errors['timezone'] = 'Некорректный часовой пояс';
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

    /**
     * Проверить, что DTO не пустой
     */
    public function isEmpty(): bool
    {
        return $this->name === null &&
               $this->phone === null &&
               $this->birth_date === null &&
               $this->gender === null &&
               $this->city === null &&
               $this->about === null &&
               $this->website === null &&
               empty($this->validatedSocialLinks) &&
               $this->language === null &&
               $this->timezone === null;
    }

    /**
     * Создать копию с измененными данными (для immutability)
     */
    public function withName(string $name): self
    {
        return new self(
            name: self::sanitizeString($name),
            phone: $this->phone,
            birth_date: $this->birth_date,
            gender: $this->gender,
            city: $this->city,
            about: $this->about,
            website: $this->website,
            social_links: $this->validatedSocialLinks,
            language: $this->language,
            timezone: $this->timezone,
        );
    }

    /**
     * Создать копию с измененным телефоном
     */
    public function withPhone(string $phone): self
    {
        return new self(
            name: $this->name,
            phone: self::sanitizePhone($phone),
            birth_date: $this->birth_date,
            gender: $this->gender,
            city: $this->city,
            about: $this->about,
            website: $this->website,
            social_links: $this->validatedSocialLinks,
            language: $this->language,
            timezone: $this->timezone,
        );
    }

    /**
     * Получить социальные ссылки
     */
    public function getSocialLinks(): array
    {
        return $this->validatedSocialLinks;
    }

    /**
     * Валидация социальных ссылок
     */
    private function validateSocialLinks(array $links): array
    {
        $validated = [];
        $allowedPlatforms = ['vk', 'telegram', 'instagram', 'whatsapp', 'youtube', 'tiktok'];
        
        foreach ($links as $platform => $url) {
            if (!in_array($platform, $allowedPlatforms, true)) {
                continue;
            }
            
            $sanitizedUrl = self::sanitizeUrl($url);
            if ($sanitizedUrl && $this->isValidSocialUrl($platform, $sanitizedUrl)) {
                $validated[$platform] = $sanitizedUrl;
            }
        }
        
        return $validated;
    }

    /**
     * Проверка URL социальной сети
     */
    private function isValidSocialUrl(string $platform, string $url): bool
    {
        $patterns = [
            'vk' => '/^https:\/\/(www\.)?vk\.com\/.+$/i',
            'telegram' => '/^https:\/\/t\.me\/.+$/i',
            'instagram' => '/^https:\/\/(www\.)?instagram\.com\/.+$/i',
            'whatsapp' => '/^https:\/\/(wa\.me|api\.whatsapp\.com)\/.+$/i',
            'youtube' => '/^https:\/\/(www\.)?(youtube\.com|youtu\.be)\/.+$/i',
            'tiktok' => '/^https:\/\/(www\.)?tiktok\.com\/.+$/i',
        ];
        
        return isset($patterns[$platform]) && preg_match($patterns[$platform], $url);
    }

    /**
     * Санитизация строки (защита от XSS)
     */
    private static function sanitizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        // Удаляем управляющие символы и лишние пробелы
        $value = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value));
        
        // Конвертируем HTML entities
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Санитизация текста (сохраняем переносы строк)
     */
    private static function sanitizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        // Удаляем HTML теги
        $value = strip_tags($value);
        
        // Удаляем управляющие символы кроме переносов
        $value = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        
        return trim($value);
    }

    /**
     * Санитизация телефона
     */
    private static function sanitizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        // Оставляем только цифры и +
        return preg_replace('/[^+\d]/', '', $value);
    }

    /**
     * Санитизация URL
     */
    private static function sanitizeUrl(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        
        $value = trim($value);
        
        // Проверяем базовую валидность URL
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        // Разрешаем только безопасные протоколы
        if (!preg_match('/^https?:\/\//i', $value)) {
            return null;
        }
        
        return $value;
    }

    /**
     * Получить данные для логирования (без чувствительной информации)
     */
    public function getLoggableData(): array
    {
        return [
            'has_name' => $this->name !== null,
            'has_phone' => $this->phone !== null,
            'has_birth_date' => $this->birth_date !== null,
            'gender' => $this->gender,
            'has_city' => $this->city !== null,
            'has_about' => $this->about !== null,
            'has_website' => $this->website !== null,
            'social_platforms' => array_keys($this->validatedSocialLinks),
            'language' => $this->language,
            'timezone' => $this->timezone,
        ];
    }
}