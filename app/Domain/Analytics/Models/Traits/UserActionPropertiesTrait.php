<?php

namespace App\Domain\Analytics\Models\Traits;

/**
 * Трейт для работы со свойствами действий пользователей
 */
trait UserActionPropertiesTrait
{
    /**
     * Добавить свойство к действию
     */
    public function addProperty(string $key, $value): void
    {
        $properties = $this->properties ?? [];
        $properties[$key] = $value;
        $this->update(['properties' => $properties]);
    }

    /**
     * Получить свойство действия
     */
    public function getProperty(string $key, $default = null)
    {
        return ($this->properties ?? [])[$key] ?? $default;
    }

    /**
     * Проверить наличие свойства
     */
    public function hasProperty(string $key): bool
    {
        return isset(($this->properties ?? [])[$key]);
    }

    /**
     * Отметить как конверсию
     */
    public function markAsConversion(float $value = 0): void
    {
        $this->update([
            'is_conversion' => true,
            'conversion_value' => $value,
        ]);
    }

    /**
     * Добавить множественные свойства
     */
    public function addProperties(array $properties): void
    {
        $existingProperties = $this->properties ?? [];
        $mergedProperties = array_merge($existingProperties, $properties);
        $this->update(['properties' => $mergedProperties]);
    }

    /**
     * Удалить свойство
     */
    public function removeProperty(string $key): void
    {
        $properties = $this->properties ?? [];
        if (isset($properties[$key])) {
            unset($properties[$key]);
            $this->update(['properties' => $properties]);
        }
    }

    /**
     * Получить все свойства
     */
    public function getAllProperties(): array
    {
        return $this->properties ?? [];
    }

    /**
     * Очистить все свойства
     */
    public function clearProperties(): void
    {
        $this->update(['properties' => []]);
    }
}