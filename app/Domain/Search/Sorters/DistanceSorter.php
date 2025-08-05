<?php

namespace App\Domain\Search\Sorters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Сортировщик по расстоянию от точки
 * Соответствует DDD архитектуре - размещен в Domain\Search\Sorters
 */
class DistanceSorter implements SorterInterface
{
    protected ?float $latitude = null;
    protected ?float $longitude = null;
    protected string $direction = 'asc';
    protected string $latitudeField = 'latitude';
    protected string $longitudeField = 'longitude';
    protected bool $onlyWithCoordinates = false;

    public function __construct(array $params = [])
    {
        $this->latitude = isset($params['latitude']) ? (float) $params['latitude'] : null;
        $this->longitude = isset($params['longitude']) ? (float) $params['longitude'] : null;
        $this->direction = $this->validateDirection($params['direction'] ?? 'asc');
        $this->latitudeField = $params['latitude_field'] ?? 'latitude';
        $this->longitudeField = $params['longitude_field'] ?? 'longitude';
        $this->onlyWithCoordinates = (bool) ($params['only_with_coordinates'] ?? false);
    }

    /**
     * Применить сортировку к запросу
     */
    public function apply(Builder $query): Builder
    {
        if (!$this->hasCoordinates()) {
            return $query;
        }

        // Фильтр только с координатами
        if ($this->onlyWithCoordinates) {
            $query->whereNotNull($this->latitudeField)
                  ->whereNotNull($this->longitudeField);
        }

        // Haversine формула для расчета расстояния
        $haversine = $this->getHaversineFormula();

        return $query->orderByRaw("{$haversine} {$this->direction}");
    }

    /**
     * Получить формулу Haversine для расчета расстояния
     */
    protected function getHaversineFormula(): string
    {
        return "(6371 * acos(cos(radians({$this->latitude})) 
                * cos(radians({$this->latitudeField})) 
                * cos(radians({$this->longitudeField}) - radians({$this->longitude})) 
                + sin(radians({$this->latitude})) 
                * sin(radians({$this->latitudeField}))))";
    }

    /**
     * Получить параметры сортировки
     */
    public function getParams(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'direction' => $this->direction,
            'latitude_field' => $this->latitudeField,
            'longitude_field' => $this->longitudeField,
            'only_with_coordinates' => $this->onlyWithCoordinates,
        ];
    }

    /**
     * Получить описание сортировки
     */
    public function getDescription(): string
    {
        if (!$this->hasCoordinates()) {
            return 'По расстоянию (координаты не заданы)';
        }

        return $this->direction === 'asc' 
            ? 'По расстоянию (сначала ближе)' 
            : 'По расстоянию (сначала дальше)';
    }

    /**
     * Установить координаты
     */
    public function setCoordinates(float $latitude, float $longitude): self
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * Установить направление сортировки
     */
    public function setDirection(string $direction): self
    {
        $this->direction = $this->validateDirection($direction);
        return $this;
    }

    /**
     * Сначала ближе
     */
    public function nearestFirst(): self
    {
        $this->direction = 'asc';
        return $this;
    }

    /**
     * Сначала дальше
     */
    public function farthestFirst(): self
    {
        $this->direction = 'desc';
        return $this;
    }

    /**
     * Только с координатами
     */
    public function withCoordinatesOnly(bool $only = true): self
    {
        $this->onlyWithCoordinates = $only;
        return $this;
    }

    /**
     * Клонировать сортировщик
     */
    public function clone(): self
    {
        return new self($this->getParams());
    }

    /**
     * Инвертировать направление
     */
    public function invert(): self
    {
        $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        return $this;
    }

    /**
     * Проверить наличие координат
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Проверить направление сортировки
     */
    protected function validateDirection(string $direction): string
    {
        $direction = strtolower($direction);
        return in_array($direction, ['asc', 'desc']) ? $direction : 'asc';
    }

    /**
     * Добавить расстояние к выборке
     */
    public function withDistance(Builder $query, string $alias = 'distance_km'): Builder
    {
        if (!$this->hasCoordinates()) {
            return $query;
        }

        $haversine = $this->getHaversineFormula();
        return $query->selectRaw("*, {$haversine} as {$alias}");
    }
}