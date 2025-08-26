<?php

namespace App\Domain\Media\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * Трейт для работы с конверсиями медиафайлов
 */
trait MediaConversionsTrait
{
    /**
     * Получить URL конверсии
     */
    public function getConversionUrl(string $conversionName): ?string
    {
        if (!isset($this->conversions[$conversionName])) {
            return null;
        }

        $conversion = $this->conversions[$conversionName];
        return Storage::disk($this->disk)->url($conversion['file_name']);
    }

    /**
     * Проверить наличие конверсии
     */
    public function hasConversion(string $conversionName): bool
    {
        return isset($this->conversions[$conversionName]);
    }

    /**
     * Получить URL миниатюры
     */
    public function getThumbnailUrl(): ?string
    {
        return $this->getConversionUrl('thumb');
    }

    /**
     * Получить URL средней версии
     */
    public function getMediumUrl(): ?string
    {
        return $this->getConversionUrl('medium');
    }

    /**
     * Получить URL большой версии
     */
    public function getLargeUrl(): ?string
    {
        return $this->getConversionUrl('large');
    }

    /**
     * Добавить конверсию
     */
    public function addConversion(string $name, array $conversionData): self
    {
        $conversions = $this->conversions ?? [];
        $conversions[$name] = $conversionData;
        $this->conversions = $conversions;
        $this->save();
        return $this;
    }

    /**
     * Удалить конверсию
     */
    public function removeConversion(string $name): self
    {
        $conversions = $this->conversions ?? [];
        
        if (isset($conversions[$name])) {
            $conversionPath = $conversions[$name]['file_name'] ?? null;
            if ($conversionPath && Storage::disk($this->disk)->exists($conversionPath)) {
                Storage::disk($this->disk)->delete($conversionPath);
            }
            
            unset($conversions[$name]);
            $this->conversions = $conversions;
            $this->save();
        }
        
        return $this;
    }
}