<?php

namespace App\Domain\Master\Repositories;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use App\Enums\MasterStatus;
use App\Enums\MasterLevel;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий для работы с мастерами
 * 
 * @extends BaseRepository<MasterProfile>
 */
class MasterRepository extends BaseRepository
{
    public function __construct(MasterProfile $model)
    {
        parent::__construct($model);
    }

    /**
     * Найти профиль по ID с загрузкой связей
     * Переопределяем базовый метод find
     */
    public function find(int $id): ?MasterProfile
    {
        return $this->with([
            'user',
            'services',
            'photos',
            'videos',
            'reviews',
            'schedules'
        ])->find($id);
    }
    
    /**
     * Найти профиль по ID (алиас для обратной совместимости)
     */
    public function findById(int $id): ?MasterProfile
    {
        return $this->find($id);
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
     * Обновить профиль
     * Переопределяем базовый метод для совместимости
     */
    public function update(int $id, array $data): bool
    {
        $master = $this->findOrFail($id);
        return $master->update($data);
    }
    
    /**
     * Обновить профиль (старая сигнатура для обратной совместимости)
     */
    public function updateMaster(MasterProfile $master, array $data): bool
    {
        return $master->update($data);
    }

    /**
     * Удалить профиль
     * Переопределяем базовый метод для совместимости
     */
    public function delete(int $id): bool
    {
        $master = $this->findOrFail($id);
        return $master->delete();
    }
    
    /**
     * Удалить профиль (старая сигнатура для обратной совместимости)
     */
    public function deleteMaster(MasterProfile $master): bool
    {
        return $master->delete();
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
     * Получить мастеров в городе
     */
    public function getMastersByCity(string $city, array $filters = []): Collection
    {
        $query = $this->model->with(['user', 'services'])
            ->where('city', $city)
            ->where('status', MasterStatus::ACTIVE);

        return $this->applyFilters($query, $filters)->get();
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
     * Получить статистику мастера
     */
    public function getMasterStats(int $masterId): array
    {
        $master = $this->model->find($masterId);
        
        if (!$master) {
            return [];
        }

        return [
            'total_bookings' => $master->bookings()->count(),
            'completed_bookings' => $master->bookings()
                ->where('status', 'completed')->count(),
            'total_revenue' => $master->bookings()
                ->where('status', 'completed')->sum('total_price'),
            'average_rating' => $master->user ? $master->user->getAverageRating() : 0,
            'total_reviews' => $master->user ? $master->user->getReceivedReviewsCount() : 0,
            'profile_views' => $master->views_count,
            'repeat_clients' => $this->getRepeatClientsCount($masterId),
            'services_count' => $master->services()->count(),
            'photos_count' => $master->photos()->count(),
        ];
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews(MasterProfile $master): void
    {
        $master->increment('views_count');
    }

    /**
     * Обновить рейтинг мастера
     */
    public function updateRating(MasterProfile $master): void
    {
        $avgRating = $master->user ? $master->user->getAverageRating() : 0;
        $reviewsCount = $master->user ? $master->user->getReceivedReviewsCount() : 0;
        
        $master->update([
            'rating' => round($avgRating, 2),
            'reviews_count' => $reviewsCount,
        ]);
    }

    /**
     * Обновить уровень мастера
     */
    public function updateLevel(MasterProfile $master): void
    {
        $level = MasterLevel::determineLevel(
            $master->experience_years,
            $master->rating,
            $master->reviews_count
        );

        if ($master->level !== $level) {
            $master->update(['level' => $level]);
        }
    }

    /**
     * Применить фильтры к запросу
     */
    protected function applyFilters($query, array $filters)
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
    protected function applySorting($query, array $filters)
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
                      ->orderBy(DB::raw('MIN(master_service_prices.price)'), $sortOrder);
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

    /**
     * Получить количество постоянных клиентов
     */
    protected function getRepeatClientsCount(int $masterId): int
    {
        return DB::table('bookings')
            ->where('master_id', $masterId)
            ->where('status', 'completed')
            ->groupBy('client_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
    }

    /**
     * Массовое обновление статусов
     */
    public function batchUpdateStatus(array $ids, MasterStatus $status): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * Автоматическая деактивация неактивных мастеров
     */
    public function deactivateInactiveMasters(int $days = 90): int
    {
        return $this->model->where('status', MasterStatus::ACTIVE)
            ->whereDoesntHave('bookings', function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            })
            ->update(['status' => MasterStatus::INACTIVE]);
    }

    /**
     * Найти мастера с отношениями
     */
    public function findWithRelations(int $id, array $relations = [])
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    /**
     * Найти мастера по display_name
     */
    public function findByDisplayName(string $displayName)
    {
        return $this->model->where('display_name', $displayName)->first();
    }
}