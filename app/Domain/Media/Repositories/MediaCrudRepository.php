<?php

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\Media;
use App\Enums\{MediaType, MediaStatus};
use App\Domain\Common\Contracts\RepositoryInterface;
use App\Domain\Common\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\{Model, Collection};
use Illuminate\Support\Facades\{Log, DB};

/**
 * CRUD репозиторий для медиа - соответствует CLAUDE.md ≤200 строк
 */
class MediaCrudRepository_DISABLED extends BaseRepository
{
    /**
     * Получить класс модели
     */
    protected function getModelClass(): string
    {
        return Media::class;
    }

    public function __construct() 
    { 
        parent::__construct(); 
    }

    private function safe($op, $msg, $ctx = [], $def = null) {
        try { return $op(); } catch (\Exception $e) {
            Log::error($msg, array_merge($ctx, ['error' => $e->getMessage()]));
            return $def;
        }
    }

    public function findByFileName(string $fileName): ?Media {
        return $this->safe(fn() => $this->model->where('file_name', $fileName)->first(), 'Find by filename failed');
    }

    public function findForEntity(Model $entity, string $collection = null): Collection {
        return $this->safe(fn() => $this->model->forEntity($entity, $collection)->ordered()->get(), 'Find for entity failed', [], new Collection());
    }

    public function getFirstForEntity(Model $entity, string $collection = null): ?Media {
        return $this->safe(fn() => $this->model->forEntity($entity, $collection)->ordered()->first(), 'Get first failed');
    }

    public function countForEntity(Model $entity, string $collection = null): int {
        return $this->safe(fn() => $this->model->forEntity($entity, $collection)->count(), 'Count failed', [], 0);
    }

    public function findByType(MediaType $type, int $limit = null): Collection {
        return $this->safe(function() use ($type, $limit) {
            $q = $this->model->byType($type)->processed()->ordered();
            return $limit ? $q->limit($limit)->get() : $q->get();
        }, 'Find by type failed', [], new Collection());
    }

    public function findByStatus(MediaStatus $status, int $limit = null): Collection {
        return $this->safe(function() use ($status, $limit) {
            $q = $this->model->byStatus($status)->ordered();
            return $limit ? $q->limit($limit)->get() : $q->get();
        }, 'Find by status failed', [], new Collection());
    }

    public function softDelete(int $id): bool {
        return $this->safe(function() use ($id) {
            $media = $this->find($id);
            $result = $media ? $media->delete() : false;
            if ($result) Log::info('Media soft deleted', ['id' => $id]);
            return $result;
        }, 'Soft delete failed', ['id' => $id], false);
    }

    public function forceDelete(int $id): bool {
        return $this->safe(function() use ($id) {
            $media = $this->model->withTrashed()->find($id);
            $result = $media ? $media->forceDelete() : false;
            if ($result) Log::info('Media force deleted', ['id' => $id]);
            return $result;
        }, 'Force delete failed', ['id' => $id], false);
    }

    public function restore(int $id): bool {
        return $this->safe(function() use ($id) {
            $media = $this->model->withTrashed()->find($id);
            $result = $media ? $media->restore() : false;
            if ($result) Log::info('Media restored', ['id' => $id]);
            return $result;
        }, 'Restore failed', ['id' => $id], false);
    }

    public function getRecentlyAdded(int $days = 7, int $limit = 20): Collection {
        return $this->safe(fn() => $this->model->where('created_at', '>=', now()->subDays($days))->orderBy('created_at', 'desc')->limit($limit)->get(), 'Get recent failed', [], new Collection());
    }

    public function getProcessingQueue(int $limit = 100): Collection {
        return $this->safe(fn() => $this->model->where('status', MediaStatus::PENDING)->orderBy('created_at')->limit($limit)->get(), 'Get queue failed', [], new Collection());
    }

    public function markAsProcessing(int $id): bool {
        return $this->safe(function() use ($id) {
            $updated = $this->model->where('id', $id)->where('status', MediaStatus::PENDING)->update(['status' => MediaStatus::PROCESSING]);
            if ($updated) Log::info('Marked as processing', ['id' => $id]);
            return (bool) $updated;
        }, 'Mark processing failed', ['id' => $id], false);
    }

    public function reorderForEntity(Model $entity, array $mediaIds, string $collection = null): bool {
        return $this->safe(function() use ($entity, $mediaIds, $collection) {
            return DB::transaction(function () use ($entity, $mediaIds, $collection) {
                foreach ($mediaIds as $index => $mediaId) {
                    $q = $this->model->where('id', $mediaId)->where('mediable_type', get_class($entity))->where('mediable_id', $entity->id);
                    if ($collection) $q->where('collection_name', $collection);
                    if (!$q->update(['sort_order' => $index + 1])) throw new \Exception("Failed to update order for ID: {$mediaId}");
                }
                Log::info('Media reordered', ['entity' => get_class($entity), 'count' => count($mediaIds)]);
                return true;
            });
        }, 'Reorder failed', [], false);
    }

    public function batchUpdateStatus(array $ids, MediaStatus $status): int {
        return $this->safe(function() use ($ids, $status) {
            $updated = $this->model->whereIn('id', $ids)->update(['status' => $status]);
            Log::info('Batch status update', ['updated' => $updated, 'status' => $status->value]);
            return $updated;
        }, 'Batch update failed', [], 0);
    }

    public function batchDelete(array $ids): int {
        return $this->safe(function() use ($ids) {
            $deleted = $this->model->whereIn('id', $ids)->delete();
            Log::info('Batch delete', ['deleted' => $deleted]);
            return $deleted;
        }, 'Batch delete failed', [], 0);
    }

    public function batchRestore(array $ids): int {
        return $this->safe(function() use ($ids) {
            $restored = $this->model->withTrashed()->whereIn('id', $ids)->restore();
            Log::info('Batch restore', ['restored' => $restored]);
            return $restored;
        }, 'Batch restore failed', [], 0);
    }
}