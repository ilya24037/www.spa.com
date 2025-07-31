<?php

namespace App\Domain\Master\Traits;

use Illuminate\Support\Str;

/**
 * Трейт для работы со slug
 * Перенесён из HasUniqueSlug для доменной структуры
 */
trait HasSlug
{
    /**
     * Автоматическая генерация slug при создании модели
     */
    protected static function bootHasSlug()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->slugField})) {
                $model->{$model->slugField} = $model->generateSlug();
            }
        });

        static::updating(function ($model) {
            // Если изменилось поле-источник slug и slug пустой
            $sourceField = $model->slugSource ?? 'name';
            if ($model->isDirty($sourceField) && empty($model->{$model->slugField})) {
                $model->{$model->slugField} = $model->generateSlug();
            }
        });
    }

    /**
     * Генерация уникального slug
     */
    public function generateSlug(): string
    {
        $sourceField = $this->slugSource ?? 'name';
        $slugField = $this->slugField ?? 'slug';
        
        $baseSlug = Str::slug($this->{$sourceField});
        
        if (empty($baseSlug)) {
            $baseSlug = 'master-' . Str::random(6);
        }

        $slug = $baseSlug;
        $counter = 1;

        // Проверяем уникальность
        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Проверка существования slug
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where($this->slugField ?? 'slug', $slug);
        
        // Исключаем текущую запись при обновлении
        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return $query->exists();
    }

    /**
     * Поиск по slug
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where($this->slugField ?? 'slug', $slug);
    }

    /**
     * Получить URL профиля
     */
    public function getUrlAttribute(): string
    {
        return route('masters.show', [
            'slug'   => $this->slug,
            'master' => $this->id,
        ]);
    }

    /**
     * Получить полный URL
     */
    public function getFullUrlAttribute(): string
    {
        return $this->url;
    }

    /**
     * Получить URL для шаринга
     */
    public function getShareUrlAttribute(): string
    {
        return preg_replace('#^https?://#', '', $this->full_url);
    }
}