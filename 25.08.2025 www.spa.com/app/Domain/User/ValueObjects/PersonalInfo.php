<?php

namespace App\Domain\User\ValueObjects;

use Carbon\Carbon;

/**
 * Value Object для личной информации пользователя
 */
final class PersonalInfo
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $phone = null,
        public readonly ?Carbon $birth_date = null,
        public readonly ?string $gender = null,
        public readonly ?string $city = null,
    ) {}

    /**
     * Создать из массива с валидацией
     */
    public static function fromArray(array $data): self
    {
        $birthDate = null;
        if (isset($data['birth_date']) && !empty($data['birth_date'])) {
            try {
                $birthDate = Carbon::parse($data['birth_date']);
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
        );
    }

    /**
     * Конвертировать в массив
     */
    public function toArray(): array
    {
        $data = [];
        
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->phone !== null) $data['phone'] = $this->phone;
        if ($this->birth_date !== null) $data['birth_date'] = $this->birth_date->toDateString();
        if ($this->gender !== null) $data['gender'] = $this->gender;
        if ($this->city !== null) $data['city'] = $this->city;

        return $data;
    }

    /**
     * Валидировать данные
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->name !== null) {
            if (strlen($this->name) < 2) {
                $errors['name'] = 'Имя должно содержать минимум 2 символа';
            } elseif (strlen($this->name) > 100) {
                $errors['name'] = 'Имя не должно превышать 100 символов';
            } elseif (!preg_match('/^[\p{L}\s\'-]+$/u', $this->name)) {
                $errors['name'] = 'Имя содержит недопустимые символы';
            }
        }

        if ($this->phone !== null && !empty($this->phone)) {
            $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $this->phone);
            if (!preg_match('/^\+?[1-9]\d{1,14}$/', $cleanPhone)) {
                $errors['phone'] = 'Некорректный формат телефона (используйте международный формат)';
            }
        }

        if ($this->birth_date !== null) {
            if ($this->birth_date->isFuture()) {
                $errors['birth_date'] = 'Дата рождения не может быть в будущем';
            } elseif ($this->birth_date->age < 16) {
                $errors['birth_date'] = 'Возраст должен быть не менее 16 лет';
            } elseif ($this->birth_date->age > 120) {
                $errors['birth_date'] = 'Некорректная дата рождения';
            }
        }

        if ($this->gender !== null && !in_array($this->gender, ['male', 'female', 'other'], true)) {
            $errors['gender'] = 'Некорректное значение пола';
        }

        if ($this->city !== null) {
            if (strlen($this->city) > 100) {
                $errors['city'] = 'Название города не должно превышать 100 символов';
            } elseif (!preg_match('/^[\p{L}\s\-\.]+$/u', $this->city)) {
                $errors['city'] = 'Название города содержит недопустимые символы';
            }
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
     * Санитизация строки
     */
    private static function sanitizeString(?string $value): ?string
    {
        if ($value === null) return null;
        
        $value = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value));
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Санитизация телефона
     */
    private static function sanitizePhone(?string $value): ?string
    {
        if ($value === null) return null;
        return preg_replace('/[^+\d]/', '', $value);
    }
}