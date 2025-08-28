<?php

namespace App\Support\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Трейт для унификации работы с JSON полями в моделях
 * 
 * Обеспечивает:
 * - Автоматическую инициализацию casts для JSON полей
 * - Удобные методы для работы с JSON данными
 * - Валидацию и обработку ошибок
 * - Единообразие работы с JSON во всех моделях
 */
trait JsonFieldsTrait
{
    /**
     * Инициализация JSON полей при загрузке модели
     */
    protected function initializeJsonFieldsTrait(): void
    {
        // Автоматически добавляем JSON поля в casts
        if (property_exists($this, 'jsonFields')) {
            foreach ($this->jsonFields as $field) {
                $this->casts[$field] = 'array';
            }
        }
    }

    /**
     * КРИТИЧЕСКИ ВАЖНО: Исправление поврежденных JSON полей
     * Проверяет и исправляет JSON поля, которые содержат неправильные типы данных
     */
    public function fixCorruptedJsonFields(): bool
    {
        // ЗАЩИТА ОТ БЕСКОНЕЧНОГО ЦИКЛА: Проверяем флаг
        if ($this->isFixingJsonFields ?? false) {
            return false;
        }
        
        $this->isFixingJsonFields = true;
        $fixed = false;
        $corruptedFields = [];
        
        if (property_exists($this, 'jsonFields')) {
            foreach ($this->jsonFields as $field) {
                try {
                    $value = $this->getOriginal($field);
                    
                    // Если поле содержит не-строку и не null, это проблема
                    if ($value !== null && !is_string($value)) {
                        $corruptedFields[] = $field;
                        
                        // Исправляем поле напрямую в БД, избегая Eloquent события
                        if (is_array($value) || is_object($value)) {
                            $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE);
                        } else {
                            // Для других типов ставим дефолт
                            $jsonValue = in_array($field, ['services', 'prices', 'geo', 'faq']) ? '{}' : '[]';
                        }
                        
                        // Обновляем напрямую в БД без вызова событий Eloquent
                        \DB::table($this->getTable())
                            ->where('id', $this->id)
                            ->update([$field => $jsonValue]);
                        
                        $fixed = true;
                    }
                } catch (\Exception $e) {
                    Log::warning("Error processing JSON field {$field}: " . $e->getMessage());
                    continue;
                }
            }
        }
        
        if ($fixed) {
            Log::info("Fixed corrupted JSON fields for model " . get_class($this), [
                'id' => $this->id,
                'corrupted_fields' => $corruptedFields
            ]);
            
            // Перезагружаем модель из БД
            $this->refresh();
        }
        
        $this->isFixingJsonFields = false;
        return $fixed;
    }

    /**
     * Получить значение JSON поля с проверкой
     * 
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public function getJsonField(string $field, $default = [])
    {
        $value = $this->getAttribute($field);
        
        if (is_null($value) || $value === '') {
            return $default;
        }

        // Если значение уже массив (после cast)
        if (is_array($value)) {
            return $value;
        }

        // КРИТИЧЕСКИ ВАЖНО: Проверяем что значение является строкой перед json_decode
        if (!is_string($value)) {
            Log::warning("Field {$field} is not a string, cannot decode JSON for model " . get_class($this), [
                'id' => $this->id,
                'value' => $value,
                'type' => gettype($value)
            ]);
            return $default;
        }

        // Пытаемся декодировать JSON
        try {
            $decoded = json_decode($value, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Invalid JSON in field {$field} for model " . get_class($this), [
                    'id' => $this->id,
                    'value' => $value,
                    'error' => json_last_error_msg()
                ]);
                return $default;
            }

            return $decoded ?: $default;
        } catch (\Exception $e) {
            Log::error("Error decoding JSON field {$field}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Установить значение JSON поля с валидацией
     * 
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function setJsonField(string $field, $value): self
    {
        // Если передан массив или объект, сохраняем как есть (Laravel сам закодирует)
        if (is_array($value) || is_object($value)) {
            $this->setAttribute($field, $value);
            return $this;
        }

        // Если передана строка, проверяем что это валидный JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->setAttribute($field, $decoded);
            } else {
                throw new \InvalidArgumentException(
                    "Invalid JSON string for field {$field}: " . json_last_error_msg()
                );
            }
            
            return $this;
        }

        // Для других типов сохраняем как есть
        $this->setAttribute($field, $value);
        return $this;
    }

    /**
     * Добавить элемент в JSON массив
     * 
     * @param string $field
     * @param mixed $value
     * @param string|null $key
     * @return $this
     */
    public function appendToJsonField(string $field, $value, ?string $key = null): self
    {
        $current = $this->getJsonField($field, []);
        
        if (!is_array($current)) {
            $current = [];
        }

        if ($key !== null) {
            $current[$key] = $value;
        } else {
            $current[] = $value;
        }

        $this->setJsonField($field, $current);
        return $this;
    }

    /**
     * Удалить элемент из JSON массива
     * 
     * @param string $field
     * @param mixed $value Значение для удаления или ключ (если $byKey = true)
     * @param bool $byKey Удалять по ключу или по значению
     * @return $this
     */
    public function removeFromJsonField(string $field, $value, bool $byKey = false): self
    {
        $current = $this->getJsonField($field, []);
        
        if (!is_array($current)) {
            return $this;
        }

        if ($byKey) {
            unset($current[$value]);
        } else {
            $current = array_values(array_diff($current, [$value]));
        }

        $this->setJsonField($field, $current);
        return $this;
    }

    /**
     * Проверить наличие значения в JSON поле
     * 
     * @param string $field
     * @param mixed $value
     * @param bool $strict
     * @return bool
     */
    public function hasInJsonField(string $field, $value, bool $strict = false): bool
    {
        $current = $this->getJsonField($field, []);
        
        if (!is_array($current)) {
            return false;
        }

        return in_array($value, $current, $strict);
    }

    /**
     * Получить значение по ключу из JSON объекта
     * 
     * @param string $field
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getJsonFieldKey(string $field, string $key, $default = null)
    {
        $data = $this->getJsonField($field, []);
        
        if (!is_array($data)) {
            return $default;
        }

        return $data[$key] ?? $default;
    }

    /**
     * Установить значение по ключу в JSON объекте
     * 
     * @param string $field
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setJsonFieldKey(string $field, string $key, $value): self
    {
        $data = $this->getJsonField($field, []);
        
        if (!is_array($data)) {
            $data = [];
        }

        $data[$key] = $value;
        $this->setJsonField($field, $data);
        
        return $this;
    }

    /**
     * Объединить данные с существующим JSON полем
     * 
     * @param string $field
     * @param array $data
     * @param bool $recursive
     * @return $this
     */
    public function mergeJsonField(string $field, array $data, bool $recursive = false): self
    {
        $current = $this->getJsonField($field, []);
        
        if (!is_array($current)) {
            $current = [];
        }

        if ($recursive) {
            $merged = array_merge_recursive($current, $data);
        } else {
            $merged = array_merge($current, $data);
        }

        $this->setJsonField($field, $merged);
        return $this;
    }

    /**
     * Очистить JSON поле
     * 
     * @param string $field
     * @return $this
     */
    public function clearJsonField(string $field): self
    {
        $this->setAttribute($field, []);
        return $this;
    }

    /**
     * Валидировать структуру JSON поля
     * 
     * @param string $field
     * @param array $requiredKeys
     * @return bool
     */
    public function validateJsonStructure(string $field, array $requiredKeys): bool
    {
        $data = $this->getJsonField($field);
        
        if (!is_array($data)) {
            return false;
        }

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получить JSON поля модели
     * 
     * @return array
     */
    public function getJsonFields(): array
    {
        return property_exists($this, 'jsonFields') ? $this->jsonFields : [];
    }
}