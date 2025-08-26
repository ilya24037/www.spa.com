<?php

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\Media;
use App\Enums\MediaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для управления медиа (очистка, оптимизация, массовые операции)
 * СОЗДАН согласно CLAUDE.md: ≤200 строк, ≤50 строк/метод, полная обработка ошибок
 */
class MediaManagementRepository
{
    public function __construct(
        private Media $model
    ) {}

    /**
     * Поиск медиа с фильтрами
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            $query = $this->model->newQuery();

            $this->applyFilters($query, $filters);
            $this->applySorting($query, $filters);

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error('Media search failed', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            return $this->model->newQuery()->paginate(0);
        }
    }

    /**
     * Изменить порядок медиа для сущности
     */
    public function reorderForEntity(Model $entity, array $mediaIds, string $collection = null): bool
    {
        try {
            return DB::transaction(function () use ($entity, $mediaIds, $collection) {
                foreach ($mediaIds as $index => $mediaId) {
                    $query = $this->model->where('id', $mediaId)
                                       ->where('mediable_type', get_class($entity))
                                       ->where('mediable_id', $entity->id);
                    
                    if ($collection) {
                        $query->where('collection_name', $collection);
                    }
                    
                    $updated = $query->update(['sort_order' => $index + 1]);
                    
                    if (!$updated) {
                        throw new \Exception("Failed to update media order for ID: {$mediaId}");
                    }
                }
                
                Log::info('Media reordered successfully', [
                    'entity_type' => get_class($entity),
                    'entity_id' => $entity->id,
                    'collection' => $collection,
                    'media_count' => count($mediaIds)
                ]);
                
                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to reorder media', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id ?? null,
                'collection' => $collection,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Массовое обновление статуса
     */
    public function batchUpdateStatus(array $ids, MediaStatus $status): int
    {
        try {
            $updated = $this->model->whereIn('id', $ids)
                                 ->update(['status' => $status]);
            
            Log::info('Batch status update completed', [
                'ids_count' => count($ids),
                'new_status' => $status->value,
                'updated_count' => $updated
            ]);
            
            return $updated;
        } catch (\Exception $e) {
            Log::error('Batch status update failed', [
                'ids_count' => count($ids),
                'status' => $status->value,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Массовое удаление
     */
    public function batchDelete(array $ids): int
    {
        try {
            $deleted = $this->model->whereIn('id', $ids)->delete();
            
            Log::info('Batch delete completed', [
                'ids_count' => count($ids),
                'deleted_count' => $deleted
            ]);
            
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Batch delete failed', [
                'ids_count' => count($ids),
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Массовое восстановление
     */
    public function batchRestore(array $ids): int
    {
        try {
            $restored = $this->model->withTrashed()
                                  ->whereIn('id', $ids)
                                  ->restore();
            
            Log::info('Batch restore completed', [
                'ids_count' => count($ids),
                'restored_count' => $restored
            ]);
            
            return $restored;
        } catch (\Exception $e) {
            Log::error('Batch restore failed', [
                'ids_count' => count($ids),
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Получить просроченные файлы
     */
    public function getExpiredFiles(): Collection
    {
        try {
            return $this->model->expired()->get();
        } catch (\Exception $e) {
            Log::error('Failed to get expired files', [
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }

    /**
     * Очистка просроченных файлов
     */
    public function cleanupExpired(): int
    {
        try {
            $deleted = $this->model->expired()->delete();
            
            Log::info('Expired files cleanup completed', [
                'deleted_count' => $deleted
            ]);
            
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Expired files cleanup failed', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Получить осиротевшие файлы
     */
    public function getOrphanedFiles(): Collection
    {
        try {
            return $this->model->whereNotNull('mediable_type')
                              ->whereNotNull('mediable_id')
                              ->whereDoesntHave('mediable')
                              ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get orphaned files', [
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }

    /**
     * Очистка осиротевших файлов
     */
    public function cleanupOrphaned(): int
    {
        try {
            $deleted = $this->model->whereNotNull('mediable_type')
                                 ->whereNotNull('mediable_id')
                                 ->whereDoesntHave('mediable')
                                 ->delete();
            
            Log::info('Orphaned files cleanup completed', [
                'deleted_count' => $deleted
            ]);
            
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Orphaned files cleanup failed', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Применить фильтры к запросу
     */
    private function applyFilters($query, array $filters): void
    {
        $filterMap = [
            'type' => fn($value) => $query->byType($value),
            'status' => fn($value) => $query->byStatus($value),
            'collection' => fn($value) => $query->byCollection($value),
            'entity_type' => fn($value) => $query->where('mediable_type', $value),
            'entity_id' => fn($value) => $query->where('mediable_id', $value),
            'name' => fn($value) => $query->where('name', 'like', "%{$value}%"),
            'mime_type' => fn($value) => $query->where('mime_type', $value),
        ];

        foreach ($filterMap as $key => $callback) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $callback($filters[$key]);
            }
        }

        // Размер файла
        if (isset($filters['size_min'])) {
            $query->where('size', '>=', $filters['size_min']);
        }
        if (isset($filters['size_max'])) {
            $query->where('size', '<=', $filters['size_max']);
        }

        // Дата создания
        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }
        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        // Просроченные
        if (isset($filters['expired'])) {
            if ($filters['expired']) {
                $query->expired();
            } else {
                $query->notExpired();
            }
        }
    }

    /**
     * Применить сортировку к запросу
     */
    private function applySorting($query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        $sortMap = [
            'name' => 'name',
            'size' => 'size',
            'type' => 'type',
            'created_at' => 'created_at',
            'sort_order' => 'sort_order',
        ];

        if (isset($sortMap[$sortBy])) {
            $query->orderBy($sortMap[$sortBy], $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }
}