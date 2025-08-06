<?php

namespace App\Domain\Master\Repositories;

use App\Domain\Master\Models\MasterProfile;
use App\Enums\MasterStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Репозиторий для поиска и фильтрации мастеров
 */
class MasterQueryRepository
{
    private MasterProfile $model;

    public function __construct()
    {
        $this->model = new MasterProfile();
    }

    /**
     * Найти профиль по ID с загрузкой связей
     */
    public function find(int $id): ?MasterProfile
    {
        return $this->model->with([
            'user',
            'services',
            'photos',
            'videos',
            'reviews',
            'schedules'
        ])->find($id);
    }

    /**
     * Найти профиль по user_id
     */
    public function findByUserId(int $userId): ?MasterProfile
    {
        return $this->model->with(['user'])->where('user_id', $userId)->first();
    }

    /**
     * Найти профиль по slug
     */
    public function findBySlug(string $slug): ?MasterProfile
    {
        return $this->model->with([
            'user',
            'services',
            'photos',
            'reviews'
        ])->where('slug', $slug)->first();
    }

    /**
     * Найти мастера по display_name
     */
    public function findByDisplayName(string $displayName): ?MasterProfile
    {
        return $this->model->where('display_name', $displayName)->first();
    }

    /**
     * Найти мастера с отношениями
     */
    public function findWithRelations(int $id, array $relations = []): ?MasterProfile
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Получить активных мастеров
     */
    public function getActive(array $filters = []): Collection
    {
        $query = $this->model->with(['user', 'services'])
            ->where('status', MasterStatus::ACTIVE);

        return $this->applyFilters($query, $filters)->get();
    }

    /**
     * Поиск мастеров с пагинацией
     */
    public function search(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'services', 'photos']);

        // Применяем фильтры
        $query = $this->applyFilters($query, $filters);

        // Сортировка
        $query = $this->applySorting($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Получить топ мастеров
     */
    public function getTopMasters(int $limit = 10): Collection
    {
        return $this->model->with(['user', 'photos'])
            ->where('status', MasterStatus::ACTIVE)
            ->orderByDesc('rating')
            ->orderByDesc('reviews_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить новых мастеров
     */
    public function getNewMasters(int $limit = 10): Collection
    {
        return $this->model->with(['user', 'photos'])
            ->where('status', MasterStatus::ACTIVE)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить мастеров по услуге
     */
    public function getMastersByService(int $serviceId, array $filters = []): Collection
    {
        $query = $this->model->with(['user', 'services'])
            ->whereHas('services', function ($q) use ($serviceId) {
                $q->where('services.id', $serviceId);
            })
            ->where('status', MasterStatus::ACTIVE);

        return $this->applyFilters($query, $filters)->get();
    }

    /**
     * Получить мастеров для модерации
     */
    public function getPendingModeration(): Collection
    {
        return $this->model->with(['user'])
            ->where('status', MasterStatus::PENDING)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Применить фильтры к запросу
     */
    private function applyFilters($query, array $filters = [])
    {
        // Статус
        if (isset($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Уровень
        if (isset($filters['level'])) {
            if (is_array($filters['level'])) {
                $query->whereIn('level', $filters['level']);
            } else {
                $query->where('level', $filters['level']);
            }
        }

        // Город
        if (isset($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        // Район
        if (isset($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        // Метро
        if (isset($filters['metro_station'])) {
            $query->where('metro_station', $filters['metro_station']);
        }

        // Тип услуг
        if (isset($filters['service_type'])) {
            if ($filters['service_type'] === 'home') {
                $query->where('home_service', true);
            } elseif ($filters['service_type'] === 'salon') {
                $query->where('salon_service', true);
            }
        }

        // Рейтинг
        if (isset($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        // Опыт
        if (isset($filters['min_experience'])) {
            $query->where('experience_years', '>=', $filters['min_experience']);
        }

        // Верификация
        if (isset($filters['is_verified']) && $filters['is_verified']) {
            $query->where('is_verified', true);
        }

        // Премиум
        if (isset($filters['is_premium']) && $filters['is_premium']) {
            $query->where('is_premium', true)
                  ->where('premium_until', '>', now());
        }

        // Поиск по тексту
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'LIKE', "%{$search}%")
                  ->orWhere('bio', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($subQ) use ($search) {
                      $subQ->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('services', function ($subQ) use ($search) {
                      $subQ->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Услуги
        if (isset($filters['services']) && is_array($filters['services'])) {
            $query->whereHas('services', function ($q) use ($filters) {
                $q->whereIn('services.id', $filters['services']);
            });
        }

        // Возраст
        if (isset($filters['age_min'])) {
            $query->where('age', '>=', $filters['age_min']);
        }
        if (isset($filters['age_max'])) {
            $query->where('age', '<=', $filters['age_max']);
        }

        return $query;
    }

    /**
     * Применить сортировку
     */
    private function applySorting($query, array $filters)
    {
        $sortBy = $filters['sort_by'] ?? 'rating';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'rating':
                $query->orderBy('rating', $sortOrder)
                      ->orderBy('reviews_count', 'desc');
                break;
            case 'experience':
                $query->orderBy('experience_years', $sortOrder);
                break;
            case 'price':
                // Сортировка по минимальной цене услуг
                $query->leftJoin('master_service_prices', 'master_profiles.id', '=', 'master_service_prices.master_profile_id')
                      ->groupBy('master_profiles.id')
                      ->orderBy(\Illuminate\Support\Facades\DB::raw('MIN(master_service_prices.price)'), $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'popularity':
                $query->orderBy('views_count', $sortOrder)
                      ->orderBy('completed_bookings', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        return $query;
    }
}