<?php

namespace App\Domain\Master\Repositories;

use App\Domain\Master\Models\MasterProfile;
use App\Enums\MasterStatus;
use Illuminate\Database\Eloquent\Collection;

/**
 * Репозиторий для работы с геолокацией мастеров
 */
class MasterLocationRepository
{
    private MasterProfile $model;

    public function __construct()
    {
        $this->model = new MasterProfile();
    }

    /**
     * Получить мастеров в городе
     */
    public function getMastersByCity(string $city, array $filters = []): Collection
    {
        $query = $this->model->with(['user', 'services'])
            ->where('city', $city)
            ->where('status', MasterStatus::ACTIVE);

        return $this->applyLocationFilters($query, $filters)->get();
    }

    /**
     * Получить ближайших мастеров
     */
    public function getNearbyMasters(float $lat, float $lng, int $radius = 10): Collection
    {
        return $this->model->with(['user', 'services'])
            ->where('status', MasterStatus::ACTIVE)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("
                (6371 * acos(cos(radians(?)) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians(?)) 
                + sin(radians(?)) 
                * sin(radians(latitude)))) <= ?
            ", [$lat, $lng, $lat, $radius])
            ->orderByRaw("
                (6371 * acos(cos(radians(?)) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians(?)) 
                + sin(radians(?)) 
                * sin(radians(latitude))))
            ", [$lat, $lng, $lat])
            ->get();
    }

    /**
     * Получить доступные районы
     */
    public function getAvailableDistricts(string $city = null): Collection
    {
        $query = $this->model->select('district')
            ->where('status', MasterStatus::ACTIVE)
            ->whereNotNull('district');

        if ($city) {
            $query->where('city', $city);
        }

        return $query->distinct()
            ->pluck('district')
            ->filter()
            ->values();
    }

    /**
     * Получить доступные станции метро
     */
    public function getAvailableMetroStations(string $city = null): Collection
    {
        $query = $this->model->select('metro_station')
            ->where('status', MasterStatus::ACTIVE)
            ->whereNotNull('metro_station');

        if ($city) {
            $query->where('city', $city);
        }

        return $query->distinct()
            ->pluck('metro_station')
            ->filter()
            ->values();
    }

    /**
     * Получить доступные города
     */
    public function getAvailableCities(): Collection
    {
        return $this->model->select('city')
            ->where('status', MasterStatus::ACTIVE)
            ->whereNotNull('city')
            ->distinct()
            ->pluck('city')
            ->filter()
            ->values();
    }

    /**
     * Получить мастеров в радиусе с возможностью домашнего обслуживания
     */
    public function getHomeServiceMastersNearby(float $lat, float $lng, int $radius = 20): Collection
    {
        return $this->model->with(['user', 'services'])
            ->where('status', MasterStatus::ACTIVE)
            ->where('home_service', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("
                (6371 * acos(cos(radians(?)) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians(?)) 
                + sin(radians(?)) 
                * sin(radians(latitude)))) <= ?
            ", [$lat, $lng, $lat, $radius])
            ->orderByRaw("
                (6371 * acos(cos(radians(?)) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians(?)) 
                + sin(radians(?)) 
                * sin(radians(latitude))))
            ", [$lat, $lng, $lat])
            ->get();
    }

    /**
     * Получить мастеров с салонным обслуживанием в городе
     */
    public function getSalonMastersInCity(string $city): Collection
    {
        return $this->model->with(['user', 'services'])
            ->where('city', $city)
            ->where('status', MasterStatus::ACTIVE)
            ->where('salon_service', true)
            ->whereNotNull('address')
            ->orderBy('rating', 'desc')
            ->get();
    }

    /**
     * Рассчитать расстояние между двумя точками
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    /**
     * Получить статистику покрытия по районам
     */
    public function getDistrictCoverage(string $city): array
    {
        $districts = $this->model->where('city', $city)
            ->where('status', MasterStatus::ACTIVE)
            ->whereNotNull('district')
            ->selectRaw('district, COUNT(*) as masters_count')
            ->groupBy('district')
            ->orderBy('masters_count', 'desc')
            ->get();

        return $districts->pluck('masters_count', 'district')->toArray();
    }

    /**
     * Применить фильтры для локации
     */
    private function applyLocationFilters($query, array $filters = [])
    {
        if (isset($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        if (isset($filters['metro_station'])) {
            $query->where('metro_station', $filters['metro_station']);
        }

        if (isset($filters['has_home_service']) && $filters['has_home_service']) {
            $query->where('home_service', true);
        }

        if (isset($filters['has_salon_service']) && $filters['has_salon_service']) {
            $query->where('salon_service', true);
        }

        if (isset($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        return $query;
    }
}