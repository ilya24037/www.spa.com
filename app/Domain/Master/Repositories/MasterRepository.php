<?php

namespace App\Domain\Master\Repositories;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use App\Enums\MasterStatus;
use App\Domain\Common\Repositories\BaseRepository;
use App\Domain\Master\Contracts\MasterRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Упрощенный репозиторий для работы с мастерами
 * Делегирует работу специализированным репозиториям
 * 
 * @extends BaseRepository<MasterProfile>
 */
class MasterRepository extends BaseRepository implements MasterRepositoryInterface
{
    private MasterQueryRepository $queryRepository;
    private MasterLocationRepository $locationRepository;
    private MasterStatsRepository $statsRepository;
    private MasterBusinessRepository $businessRepository;

    /**
     * Получить класс модели
     */
    protected function getModelClass(): string
    {
        return MasterProfile::class;
    }

    public function __construct()
    {
        parent::__construct();
        
        $this->queryRepository = new MasterQueryRepository();
        $this->locationRepository = new MasterLocationRepository();
        $this->statsRepository = new MasterStatsRepository();
        $this->businessRepository = new MasterBusinessRepository();
    }

    // === ПОИСК И ЗАПРОСЫ ===

    /**
     * Найти профиль по ID с загрузкой связей
     */
    public function find(int $id): ?MasterProfile
    {
        return $this->queryRepository->find($id);
    }

    /**
     * Получить профиль мастера по ID пользователя
     */
    public function getUserMasterProfile(int $userId): ?MasterProfile
    {
        return MasterProfile::where('user_id', $userId)->first();
    }

    /**
     * Получить все профили мастера пользователя
     */
    public function getUserMasterProfiles(int $userId): Collection
    {
        return MasterProfile::where('user_id', $userId)->get();
    }

    /**
     * Получить основной профиль мастера
     */
    public function getMainMasterProfile(int $userId): ?MasterProfile
    {
        return MasterProfile::where('user_id', $userId)
            ->where('is_main', true)
            ->first() ?: $this->getUserMasterProfile($userId);
    }

    /**
     * Проверить есть ли активный профиль мастера
     */
    public function hasActiveMasterProfile(int $userId): bool
    {
        return MasterProfile::where('user_id', $userId)
            ->where('status', MasterStatus::ACTIVE)
            ->exists();
    }

    /**
     * Получить рейтинг мастера
     */
    public function getMasterRating(int $masterId): array
    {
        $master = $this->find($masterId);
        if (!$master) {
            return ['rating' => 0, 'reviews_count' => 0];
        }
        
        return [
            'rating' => $master->rating ?? 0,
            'reviews_count' => $master->reviews_count ?? 0
        ];
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
        return $this->queryRepository->findByUserId($userId);
    }

    /**
     * Найти профиль по slug
     */
    public function findBySlug(string $slug): ?MasterProfile
    {
        return $this->queryRepository->findBySlug($slug);
    }

    /**
     * Найти мастера по display_name
     */
    public function findByDisplayName(string $displayName): ?MasterProfile
    {
        return $this->queryRepository->findByDisplayName($displayName);
    }

    /**
     * Найти мастера с отношениями
     */
    public function findWithRelations(int $id, array $relations = []): ?MasterProfile
    {
        return $this->queryRepository->findWithRelations($id, $relations);
    }

    /**
     * Найти мастера с медиафайлами для редактирования
     */
    public function findWithMedia(int $id): ?MasterProfile
    {
        return MasterProfile::with([
            'photos' => function($query) {
                $query->orderBy('sort_order')->orderBy('created_at');
            },
            'videos'
        ])->find($id);
    }

    /**
     * Получить активных мастеров
     */
    public function getActive(array $filters = []): Collection
    {
        return $this->queryRepository->getActive($filters);
    }

    /**
     * Поиск мастеров с пагинацией
     */
    public function search(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->queryRepository->search($filters, $perPage);
    }

    /**
     * Получить топ мастеров
     */
    public function getTopMasters(int $limit = 10): Collection
    {
        return $this->queryRepository->getTopMasters($limit);
    }

    /**
     * Получить новых мастеров
     */
    public function getNewMasters(int $limit = 10): Collection
    {
        return $this->queryRepository->getNewMasters($limit);
    }

    /**
     * Получить мастеров по услуге
     */
    public function getMastersByService(int $serviceId, array $filters = []): Collection
    {
        return $this->queryRepository->getMastersByService($serviceId, $filters);
    }

    /**
     * Получить мастеров для модерации
     */
    public function getPendingModeration(): Collection
    {
        return $this->queryRepository->getPendingModeration();
    }

    // === ГЕОЛОКАЦИЯ ===

    /**
     * Получить мастеров в городе
     */
    public function getMastersByCity(string $city, array $filters = []): Collection
    {
        return $this->locationRepository->getMastersByCity($city, $filters);
    }

    /**
     * Получить ближайших мастеров
     */
    public function getNearbyMasters(float $lat, float $lng, int $radius = 10): Collection
    {
        return $this->locationRepository->getNearbyMasters($lat, $lng, $radius);
    }

    /**
     * Получить доступные районы
     */
    public function getAvailableDistricts(): Collection
    {
        return $this->locationRepository->getAvailableDistricts();
    }

    // === СТАТИСТИКА ===

    /**
     * Получить статистику мастера
     */
    public function getMasterStats(int $masterId): array
    {
        return $this->statsRepository->getMasterStats($masterId);
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews(MasterProfile $master): void
    {
        $this->statsRepository->incrementViews($master);
    }

    /**
     * Обновить рейтинг мастера
     */
    public function updateRating(MasterProfile $master): void
    {
        $this->statsRepository->updateRating($master);
    }

    /**
     * Обновить уровень мастера
     */
    public function updateLevel(MasterProfile $master): void
    {
        $this->statsRepository->updateLevel($master);
    }

    // === БИЗНЕС-ОПЕРАЦИИ ===

    /**
     * Массовое обновление статусов
     */
    public function batchUpdateStatus(array $ids, MasterStatus $status): int
    {
        return $this->businessRepository->batchUpdateStatus($ids, $status);
    }

    /**
     * Автоматическая деактивация неактивных мастеров
     */
    public function deactivateInactiveMasters(int $days = 90): int
    {
        return $this->businessRepository->deactivateInactiveMasters($days);
    }

    // === CRUD ОПЕРАЦИИ ===

    /**
     * Обновить профиль
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

    // === СПРАВОЧНЫЕ ДАННЫЕ ===

    /**
     * Получить активные категории
     */
    public function getActiveCategories(): Collection
    {
        return $this->model->select('category')
            ->where('status', MasterStatus::ACTIVE)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();
    }

    /**
     * Получить диапазон цен
     */
    public function getPriceRange(): array
    {
        $prices = $this->model->where('status', MasterStatus::ACTIVE)
            ->whereNotNull('price_from')
            ->whereNotNull('price_to');

        return [
            'min' => $prices->min('price_from'),
            'max' => $prices->max('price_to'),
        ];
    }

    // === АЛИАСЫ ДЛЯ СОВМЕСТИМОСТИ ===

    /**
     * Найти активных мастеров (алиас для getActive)
     */
    public function findActive(array $filters = []): Collection
    {
        return $this->getActive($filters);
    }

    /**
     * Найти мастеров по местоположению (алиас для getMastersByCity)
     */
    public function findByLocation(string $city, array $filters = []): Collection
    {
        return $this->getMastersByCity($city, $filters);
    }

    /**
     * Найти мастеров по типу услуги (алиас для getMastersByService)
     */
    public function findByService(int $serviceId, array $filters = []): Collection
    {
        return $this->getMastersByService($serviceId, $filters);
    }

    // === УСТАРЕВШИЕ МЕТОДЫ ДЛЯ СОВМЕСТИМОСТИ ===

    /**
     * @deprecated Логика перенесена в специализированные репозитории
     */
    protected function applyFilters($query, array $filters = [])
    {
        return $query;
    }

    /**
     * @deprecated Логика перенесена в специализированные репозитории
     */
    protected function applySorting($query, array $filters)
    {
        return $query;
    }

    /**
     * @deprecated Используйте MasterStatsRepository::getRepeatClientsCount()
     */
    protected function getRepeatClientsCount(int $masterId): int
    {
        return $this->statsRepository->getRepeatClientsCount($masterId);
    }
}