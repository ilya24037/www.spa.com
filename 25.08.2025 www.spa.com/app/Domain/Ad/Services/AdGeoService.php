<?php

namespace App\Domain\Ad\Services;

/**
 * Сервис для работы с геолокацией объявлений
 * Извлекает координаты из разных форматов JSON
 */
class AdGeoService
{
    /**
     * Извлечение координат из разных форматов geo
     * 
     * @param mixed $geo JSON строка или массив с координатами
     * @return array ['lat' => float|null, 'lng' => float|null, 'district' => string|null]
     */
    public function extractCoordinates($geo): array
    {
        if (!$geo) {
            return $this->getEmptyCoordinates();
        }
        
        $geoData = $this->parseGeoData($geo);
        
        if (!is_array($geoData)) {
            return $this->getEmptyCoordinates();
        }
        
        // Формат 1: {"lat": 58.0, "lng": 56.0}
        if (isset($geoData['lat']) && isset($geoData['lng'])) {
            return [
                'lat' => $this->validateCoordinate($geoData['lat']),
                'lng' => $this->validateCoordinate($geoData['lng']),
                'district' => $geoData['district'] ?? null
            ];
        }
        
        // Формат 2: {"coordinates": {"lat": 58.0, "lng": 56.0}}
        if (isset($geoData['coordinates']['lat']) && isset($geoData['coordinates']['lng'])) {
            return [
                'lat' => $this->validateCoordinate($geoData['coordinates']['lat']),
                'lng' => $this->validateCoordinate($geoData['coordinates']['lng']),
                'district' => $geoData['district'] ?? null
            ];
        }
        
        return $this->getEmptyCoordinates();
    }
    
    /**
     * Проверка валидности объявления для отображения на карте
     * 
     * @param mixed $geo
     * @return bool
     */
    public function hasValidCoordinates($geo): bool
    {
        $coordinates = $this->extractCoordinates($geo);
        return $coordinates['lat'] !== null && $coordinates['lng'] !== null;
    }
    
    /**
     * Парсинг geo данных из строки или массива
     * 
     * @param mixed $geo
     * @return mixed
     */
    private function parseGeoData($geo)
    {
        if (is_string($geo)) {
            try {
                return json_decode($geo, true);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        return $geo;
    }
    
    /**
     * Валидация координаты
     * 
     * @param mixed $coordinate
     * @return float|null
     */
    private function validateCoordinate($coordinate): ?float
    {
        if ($coordinate === null || $coordinate === '') {
            return null;
        }
        
        $value = (float) $coordinate;
        
        // Базовая проверка диапазона координат
        if ($value < -180 || $value > 180) {
            return null;
        }
        
        return $value;
    }
    
    /**
     * Возвращает пустые координаты
     * 
     * @return array
     */
    private function getEmptyCoordinates(): array
    {
        return [
            'lat' => null,
            'lng' => null,
            'district' => null
        ];
    }
}