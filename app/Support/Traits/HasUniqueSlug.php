<?php

namespace App\Support\Traits;

use Illuminate\Support\Str;

/**
 * Trait для автоматической генерации уникальных slug
 * 
 * Использование:
 * 1. Подключите trait в модель: use HasUniqueSlug;
 * 2. Определите поле для slug: protected $slugField = 'slug';
 * 3. Определите поле-источник: protected $slugSource = 'display_name';
 * 
 * @package App\Traits
 */
trait HasUniqueSlug
{
    /**
     * Автоматически генерировать slug при создании модели
     */
    protected static function bootHasUniqueSlug()
    {
        // При создании новой записи
        static::creating(function ($model) {
            // Если slug не задан вручную - генерируем автоматически
            if (empty($model->{$model->getSlugField()})) {
                $model->{$model->getSlugField()} = $model->generateUniqueSlug();
            }
        });

        // При обновлении записи
        static::updating(function ($model) {
            // Если изменилось поле-источник и slug пустой
            $slugField = $model->getSlugField();
            $sourceField = $model->getSlugSourceField();
            
            if ($model->isDirty($sourceField) && empty($model->$slugField)) {
                $model->$slugField = $model->generateUniqueSlug();
            }
        });
    }

    /**
     * Генерировать уникальный slug
     * 
     * @param string|null $value Значение для генерации (если не указано - берём из поля-источника)
     * @return string
     */
    public function generateUniqueSlug(?string $value = null): string
    {
        // Берём значение из поля-источника, если не передано
        if (empty($value)) {
            $sourceField = $this->getSlugSourceField();
            $value = $this->$sourceField;
        }

        // Если значение всё ещё пустое - используем случайную строку
        if (empty($value)) {
            $value = 'profile-' . Str::random(6);
        }

        // Генерируем базовый slug
        $slug = Str::slug($value);
        
        // Проверяем уникальность
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $counter++;
            $slug = $originalSlug . '-' . $counter;
        }
        
        return $slug;
    }

    /**
     * Проверить, существует ли уже такой slug
     * 
     * @param string $slug
     * @return bool
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where($this->getSlugField(), $slug);
        
        // Если обновляем существующую запись - исключаем её из проверки
        if ($this->exists && $this->getKey()) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }
        
        return $query->exists();
    }

    /**
     * Получить имя поля для slug
     * 
     * @return string
     */
    protected function getSlugField(): string
    {
        // Можно переопределить в модели через свойство $slugField
        return property_exists($this, 'slugField') ? $this->slugField : 'slug';
    }

    /**
     * Получить имя поля-источника для генерации slug
     * 
     * @return string
     */
    protected function getSlugSourceField(): string
    {
        // Можно переопределить в модели через свойство $slugSource
        return property_exists($this, 'slugSource') ? $this->slugSource : 'name';
    }

    /**
     * Регенерировать slug (полезно для консольных команд)
     * 
     * @param bool $save Сохранить ли изменения в БД
     * @return string Новый slug
     */
    public function regenerateSlug(bool $save = false): string
    {
        $newSlug = $this->generateUniqueSlug();
        $this->{$this->getSlugField()} = $newSlug;
        
        if ($save) {
            $this->save();
        }
        
        return $newSlug;
    }

    /**
     * Получить URL на основе slug
     * 
     * @param string $prefix Префикс URL (например, '/master/')
     * @return string
     */
    public function getSlugUrl(string $prefix = ''): string
    {
        $slug = $this->{$this->getSlugField()};
        return url($prefix . $slug);
    }
}