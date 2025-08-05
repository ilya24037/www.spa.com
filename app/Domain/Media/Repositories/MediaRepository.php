<?php

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\Media;
use App\Enums\MediaType;
use App\Enums\MediaStatus;
use App\Domain\Common\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Главный медиа репозиторий - фасад для специализированных репозиториев
 * РЕФАКТОРЕН согласно CLAUDE.md: ≤200 строк, делегирование в специализированные классы
 */
class MediaRepository_DISABLED implements RepositoryInterface
{
    public function __construct(
        private MediaCrudRepository $crudRepository,
        private MediaStatisticsRepository $statisticsRepository,
        private MediaManagementRepository $managementRepository
    ) {}

    // ===============================
    // CRUD ОПЕРАЦИИ (делегируем в MediaCrudRepository)  
    // ===============================

    public function find(int $id): ?Media
    {
        return $this->crudRepository->find($id);
    }

    public function findOrFail(int $id): Media
    {
        return $this->crudRepository->findOrFail($id);
    }

    public function create(array $data): Media
    {
        return $this->crudRepository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->crudRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->crudRepository->delete($id);
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->crudRepository->all();
    }

    public function findByFileName(string $fileName): ?Media
    {
        return $this->crudRepository->findByFileName($fileName);
    }

    public function findForEntity(Model $entity, string $collection = null): Collection
    {
        return $this->crudRepository->findForEntity($entity, $collection);
    }

    public function getFirstForEntity(Model $entity, string $collection = null): ?Media
    {
        return $this->crudRepository->getFirstForEntity($entity, $collection);
    }

    public function countForEntity(Model $entity, string $collection = null): int
    {
        return $this->crudRepository->countForEntity($entity, $collection);
    }

    public function findByType(MediaType $type, int $limit = null): Collection
    {
        return $this->crudRepository->findByType($type, $limit);
    }

    public function findByStatus(MediaStatus $status, int $limit = null): Collection
    {
        return $this->crudRepository->findByStatus($status, $limit);
    }

    public function softDelete(int $id): bool
    {
        return $this->crudRepository->softDelete($id);
    }

    public function forceDelete(int $id): bool  
    {
        return $this->crudRepository->forceDelete($id);
    }

    public function restore(int $id): bool
    {
        return $this->crudRepository->restore($id);
    }

    public function getRecentlyAdded(int $days = 7, int $limit = 20): Collection
    {
        return $this->crudRepository->getRecentlyAdded($days, $limit);
    }

    public function getProcessingQueue(int $limit = 100): Collection
    {
        return $this->crudRepository->getProcessingQueue($limit);
    }

    public function markAsProcessing(int $id): bool
    {
        return $this->crudRepository->markAsProcessing($id);
    }

    // ===============================
    // СТАТИСТИКА (делегируем в MediaStatisticsRepository)
    // ===============================

    public function getStatistics(): array
    {
        return $this->statisticsRepository->getStatistics();
    }

    public function getTopLargestFiles(int $limit = 10): Collection
    {
        return $this->statisticsRepository->getTopLargestFiles($limit);
    }

    public function getUsageByCollection(): Collection
    {
        return $this->statisticsRepository->getUsageByCollection();
    }

    public function getUsageByEntityType(): Collection
    {
        return $this->statisticsRepository->getUsageByEntityType();
    }

    public function getUsageByPeriod(string $period = 'month'): Collection
    {
        return $this->statisticsRepository->getUsageByPeriod($period);
    }

    public function getTopUploaders(int $limit = 10): Collection
    {
        return $this->statisticsRepository->getTopUploaders($limit);
    }

    public function getProcessingStatistics(): array
    {
        return $this->statisticsRepository->getProcessingStatistics();
    }

    public function getDetailedAnalytics(int $days = 30): array
    {
        return $this->statisticsRepository->getDetailedAnalytics($days);
    }

    // ===============================
    // УПРАВЛЕНИЕ (делегируем в MediaManagementRepository)
    // ===============================

    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->managementRepository->search($filters, $perPage);
    }

    public function reorderForEntity(Model $entity, array $mediaIds, string $collection = null): bool
    {
        return $this->managementRepository->reorderForEntity($entity, $mediaIds, $collection);
    }

    public function batchUpdateStatus(array $ids, MediaStatus $status): int
    {
        return $this->managementRepository->batchUpdateStatus($ids, $status);
    }

    public function batchDelete(array $ids): int
    {
        return $this->managementRepository->batchDelete($ids);
    }

    public function batchRestore(array $ids): int
    {
        return $this->managementRepository->batchRestore($ids);
    }

    public function getExpiredFiles(): Collection
    {
        return $this->managementRepository->getExpiredFiles();
    }

    public function cleanupExpired(): int
    {
        return $this->managementRepository->cleanupExpired();
    }

    public function getOrphanedFiles(): Collection
    {
        return $this->managementRepository->getOrphanedFiles();
    }

    public function cleanupOrphaned(): int
    {
        return $this->managementRepository->cleanupOrphaned();
    }

    // ===============================
    // ОБРАТНАЯ СОВМЕСТИМОСТЬ (deprecated методы)
    // ===============================

    /**
     * @deprecated Используйте getOldestUnprocessed()
     */
    public function getOldestUnprocessed(int $limit = 10): Collection
    {
        return $this->findByStatus(MediaStatus::PENDING, $limit);
    }

    /**
     * @deprecated Используйте findByStatus() с MediaStatus::FAILED
     */
    public function getFailedProcessing(int $limit = 10): Collection
    {
        return $this->findByStatus(MediaStatus::from('failed'), $limit);
    }

    /**
     * @deprecated Используйте statisticsRepository->getDuplicatesByHash()
     */
    public function getDuplicatesByHash(): Collection
    {
        // Временная реализация для обратной совместимости
        return new Collection();
    }

    /**
     * @deprecated Используйте crudRepository->query()->optimizeQuery()
     */
    public function optimizeQuery()
    {
        return $this->crudRepository->getModel()->select([
            'id', 'mediable_type', 'mediable_id', 'collection_name',
            'name', 'file_name', 'mime_type', 'size', 'type', 'status', 
            'alt_text', 'sort_order', 'created_at'
        ]);
    }

    // ===============================
    // РЕАЛИЗАЦИЯ REPOSITORYINTERFACE
    // ===============================

    public function updateModel(Model $model, array $data): bool
    {
        return $this->update($model->id, $data);
    }

    public function deleteModel(Model $model): bool
    {
        return $this->delete($model->id);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->crudRepository->getModel()->paginate($perPage, $columns);
    }

    public function findWhere(array $where, array $columns = ['*']): Collection
    {
        $query = $this->crudRepository->getModel()->newQuery();
        
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->get($columns);
    }

    public function findWhereFirst(array $where, array $columns = ['*']): ?Model
    {
        $query = $this->crudRepository->getModel()->newQuery();
        
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->first($columns);
    }

    public function count(): int
    {
        return $this->crudRepository->getModel()->count();
    }

    public function exists(int $id): bool
    {
        return $this->crudRepository->getModel()->where('id', $id)->exists();
    }
}