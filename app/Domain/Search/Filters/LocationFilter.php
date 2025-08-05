<?php

namespace App\Domain\Search\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Фильтр по локации (город, район, метро)
 * Соответствует DDD архитектуре - размещен в Domain\Search\Filters
 */
class LocationFilter implements FilterInterface
{
    protected ?string $city = null;
    protected ?string $district = null;
    protected ?string $metroStation = null;
    protected ?float $latitude = null;
    protected ?float $longitude = null;
    protected ?float $radius = null; // В километрах

    public function __construct(array $params = [])
    {
        $this->city = $params['city'] ?? null;
        $this->district = $params['district'] ?? null;
        $this->metroStation = $params['metro_station'] ?? null;
        $this->latitude = isset($params['latitude']) ? (float) $params['latitude'] : null;
        $this->longitude = isset($params['longitude']) ? (float) $params['longitude'] : null;
        $this->radius = isset($params['radius']) ? (float) $params['radius'] : null;
    }

    /**
     * Применить фильтр к запросу
     */
    public function apply(Builder $query): Builder
    {
        // Фильтр по городу
        if ($this->city) {
            $query->where(function($q) {
                $q->where('city', $this->city)
                  ->orWhere('location->city', $this->city)
                  ->orWhereHas('location', function($q) {
                      $q->where('city', $this->city);
                  });
            });
        }

        // Фильтр по району
        if ($this->district) {
            $query->where(function($q) {
                $q->where('district', $this->district)
                  ->orWhere('location->district', $this->district)
                  ->orWhereHas('location', function($q) {
                      $q->where('district', $this->district);
                  });
            });
        }

        // Фильтр по метро
        if ($this->metroStation) {
            $query->where(function($q) {
                $q->where('metro_station', $this->metroStation)
                  ->orWhere('location->metro_station', $this->metroStation)
                  ->orWhereHas('location', function($q) {
                      $q->where('metro_station', $this->metroStation);
                  });
            });
        }

        // Геофильтр по координатам и радиусу
        if ($this->hasGeoFilter()) {
            $this->applyGeoFilter($query);
        }

        return $query;
    }

    /**
     * Применить геофильтр
     */
    protected function applyGeoFilter(Builder $query): void
    {
        // Haversine формула для расчета расстояния
        $haversine = "(6371 * acos(cos(radians(?)) 
                       * cos(radians(latitude)) 
                       * cos(radians(longitude) - radians(?)) 
                       + sin(radians(?)) 
                       * sin(radians(latitude))))";

        $query->whereRaw("$haversine < ?", [
            $this->latitude,
            $this->longitude,
            $this->latitude,
            $this->radius
        ]);

        // Сортировка по расстоянию
        $query->orderByRaw($haversine, [
            $this->latitude,
            $this->longitude,
            $this->latitude
        ]);
    }

    /**
     * Проверить наличие гео-фильтра
     */
    public function hasGeoFilter(): bool
    {
        return $this->latitude !== null && 
               $this->longitude !== null && 
               $this->radius !== null;
    }

    /**
     * Получить активные параметры фильтра
     */
    public function getActiveParams(): array
    {
        $params = [];

        if ($this->city) {
            $params['city'] = $this->city;
        }

        if ($this->district) {
            $params['district'] = $this->district;
        }

        if ($this->metroStation) {
            $params['metro_station'] = $this->metroStation;
        }

        if ($this->hasGeoFilter()) {
            $params['geo'] = [
                'lat' => $this->latitude,
                'lng' => $this->longitude,
                'radius' => $this->radius
            ];
        }

        return $params;
    }

    /**
     * Проверить, активен ли фильтр
     */
    public function isActive(): bool
    {
        return !empty($this->getActiveParams());
    }

    /**
     * Получить описание фильтра
     */
    public function getDescription(): string
    {
        $parts = [];

        if ($this->city) {
            $parts[] = "Город: {$this->city}";
        }

        if ($this->district) {
            $parts[] = "Район: {$this->district}";
        }

        if ($this->metroStation) {
            $parts[] = "Метро: {$this->metroStation}";
        }

        if ($this->hasGeoFilter()) {
            $parts[] = "В радиусе {$this->radius} км";
        }

        return implode(', ', $parts);
    }

    /**
     * Клонировать фильтр
     */
    public function clone(): self
    {
        return new self($this->getActiveParams());
    }

    /**
     * Сбросить фильтр
     */
    public function reset(): self
    {
        $this->city = null;
        $this->district = null;
        $this->metroStation = null;
        $this->latitude = null;
        $this->longitude = null;
        $this->radius = null;

        return $this;
    }

    /**
     * Установить город
     */
    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Установить район
     */
    public function setDistrict(?string $district): self
    {
        $this->district = $district;
        return $this;
    }

    /**
     * Установить метро
     */
    public function setMetroStation(?string $metroStation): self
    {
        $this->metroStation = $metroStation;
        return $this;
    }

    /**
     * Установить геокоординаты
     */
    public function setGeoLocation(float $latitude, float $longitude, float $radius): self
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->radius = $radius;
        return $this;
    }
}