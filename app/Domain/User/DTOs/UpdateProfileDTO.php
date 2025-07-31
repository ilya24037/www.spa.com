<?php

namespace App\Domain\User\DTOs;

use Carbon\Carbon;

/**
 * DTO для обновления профиля пользователя
 */
class UpdateProfileDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $phone = null,
        public readonly ?Carbon $birth_date = null,
        public readonly ?string $gender = null,
        public readonly ?string $city = null,
        public readonly ?string $about = null,
        public readonly ?string $website = null,
        public readonly ?array $social_links = null,
        public readonly ?string $language = null,
        public readonly ?string $timezone = null,
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            phone: $data['phone'] ?? null,
            birth_date: isset($data['birth_date']) ? Carbon::parse($data['birth_date']) : null,
            gender: $data['gender'] ?? null,
            city: $data['city'] ?? null,
            about: $data['about'] ?? null,
            website: $data['website'] ?? null,
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
     * Конвертировать в массив (только заполненные поля)
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->phone !== null) $data['phone'] = $this->phone;
        if ($this->birth_date !== null) $data['birth_date'] = $this->birth_date;
        if ($this->gender !== null) $data['gender'] = $this->gender;
        if ($this->city !== null) $data['city'] = $this->city;
        if ($this->about !== null) $data['about'] = $this->about;
        if ($this->website !== null) $data['website'] = $this->website;
        if ($this->social_links !== null) $data['social_links'] = $this->social_links;
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

        if ($this->name !== null && strlen($this->name) < 2) {
            $errors['name'] = 'Имя должно содержать минимум 2 символа';
        }

        if ($this->name !== null && strlen($this->name) > 100) {
            $errors['name'] = 'Имя не должно превышать 100 символов';
        }

        if ($this->phone !== null && !preg_match('/^[+]?[0-9\s\-\(\)]{10,20}$/', $this->phone)) {
            $errors['phone'] = 'Некорректный формат номера телефона';
        }

        if ($this->birth_date !== null) {
            if ($this->birth_date->isFuture()) {
                $errors['birth_date'] = 'Дата рождения не может быть в будущем';
            }

            if ($this->birth_date->age < 16) {
                $errors['birth_date'] = 'Возраст должен быть не менее 16 лет';
            }

            if ($this->birth_date->age > 120) {
                $errors['birth_date'] = 'Некорректная дата рождения';
            }
        }

        if ($this->gender !== null && !in_array($this->gender, ['male', 'female', 'other'])) {
            $errors['gender'] = 'Некорректное значение пола';
        }

        if ($this->city !== null && strlen($this->city) > 100) {
            $errors['city'] = 'Название города не должно превышать 100 символов';
        }

        if ($this->about !== null && strlen($this->about) > 1000) {
            $errors['about'] = 'Описание не должно превышать 1000 символов';
        }

        if ($this->website !== null && !filter_var($this->website, FILTER_VALIDATE_URL)) {
            $errors['website'] = 'Некорректный URL веб-сайта';
        }

        if ($this->social_links !== null && !is_array($this->social_links)) {
            $errors['social_links'] = 'Социальные ссылки должны быть массивом';
        }

        if ($this->language !== null && !in_array($this->language, ['ru', 'en', 'uk'])) {
            $errors['language'] = 'Неподдерживаемый язык';
        }

        if ($this->timezone !== null && !in_array($this->timezone, timezone_identifiers_list())) {
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
}