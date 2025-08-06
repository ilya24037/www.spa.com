<?php

namespace App\Domain\User\DTOs;

use App\Domain\User\ValueObjects\PersonalInfo;
use App\Domain\User\ValueObjects\ContactInfo;
use App\Domain\User\ValueObjects\PreferencesInfo;

/**
 * DTO для обновления профиля пользователя - композиция Value Objects
 */
final class UpdateProfileDTO
{
    public function __construct(
        public readonly PersonalInfo $personalInfo,
        public readonly ContactInfo $contactInfo,
        public readonly PreferencesInfo $preferencesInfo,
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            personalInfo: PersonalInfo::fromArray($data),
            contactInfo: ContactInfo::fromArray($data),
            preferencesInfo: PreferencesInfo::fromArray($data),
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
     * Конвертировать в массив
     */
    public function toArray(): array
    {
        return array_merge(
            $this->personalInfo->toArray(),
            $this->contactInfo->toArray(),
            $this->preferencesInfo->toArray()
        );
    }

    /**
     * Валидация данных
     */
    public function validate(): array
    {
        return array_merge(
            $this->personalInfo->validate(),
            $this->contactInfo->validate(),
            $this->preferencesInfo->validate()
        );
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
        $data = $this->toArray();
        return empty($data);
    }

    /**
     * Получить социальные ссылки
     */
    public function getSocialLinks(): array
    {
        return $this->contactInfo->getSocialLinks();
    }

    /**
     * Получить данные для логирования (без чувствительной информации)
     */
    public function getLoggableData(): array
    {
        $personalData = $this->personalInfo->toArray();
        $contactData = $this->contactInfo->toArray();
        $preferencesData = $this->preferencesInfo->toArray();
        
        return [
            'has_name' => isset($personalData['name']),
            'has_phone' => isset($personalData['phone']),
            'has_birth_date' => isset($personalData['birth_date']),
            'gender' => $personalData['gender'] ?? null,
            'has_city' => isset($personalData['city']),
            'has_about' => isset($preferencesData['about']),
            'has_website' => isset($contactData['website']),
            'social_platforms' => array_keys($this->getSocialLinks()),
            'language' => $preferencesData['language'] ?? null,
            'timezone' => $preferencesData['timezone'] ?? null,
        ];
    }
}