<?php

namespace App\Domain\User\ValueObjects;

/**
 * Value Object для настроек и предпочтений пользователя
 */
final class PreferencesInfo
{
    public function __construct(
        public readonly ?string $about = null,
        public readonly ?string $language = null,
        public readonly ?string $timezone = null,
    ) {}

    /**
     * Создать из массива
     */
    public static function fromArray(array $data): self
    {
        return new self(
            about: isset($data['about']) ? self::sanitizeText($data['about']) : null,
            language: $data['language'] ?? null,
            timezone: $data['timezone'] ?? null,
        );
    }

    /**
     * Конвертировать в массив
     */
    public function toArray(): array
    {
        $data = [];
        
        if ($this->about !== null) $data['about'] = $this->about;
        if ($this->language !== null) $data['language'] = $this->language;
        if ($this->timezone !== null) $data['timezone'] = $this->timezone;

        return $data;
    }

    /**
     * Валидировать данные
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->about !== null) {
            if (strlen($this->about) > 1000) {
                $errors['about'] = 'Описание не должно превышать 1000 символов';
            } elseif ($this->about !== strip_tags($this->about)) {
                $errors['about'] = 'Описание не должно содержать HTML теги';
            }
        }

        if ($this->language !== null && !in_array($this->language, ['ru', 'en', 'uk'], true)) {
            $errors['language'] = 'Неподдерживаемый язык';
        }

        if ($this->timezone !== null && !in_array($this->timezone, timezone_identifiers_list(), true)) {
            $errors['timezone'] = 'Некорректный часовой пояс';
        }

        return $errors;
    }

    /**
     * Проверить валидность
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Санитизация текста
     */
    private static function sanitizeText(?string $value): ?string
    {
        if ($value === null) return null;
        
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        
        return trim($value);
    }
}