<?php

namespace App\Domain\Media\Repositories;

use App\Models\Media;
use App\Enums\MediaType;
use App\Enums\MediaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MediaRepository
{
    public function __construct(
        private Media $model
    ) {}

    public function find(int $id): ?Media
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Media
    {
        return $this->model->findOrFail($id);
    }

    public function findByFileName(string $fileName): ?Media
    {
        return $this->model->where('file_name', $fileName)->first();
    }

    public function create(array $data): Media
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        $media = $this->find($id);
        return $media ? $media->delete() : false;
    }

    public function forceDelete(int $id): bool
    {
        $media = $this->model->withTrashed()->find($id);
        return $media ? $media->forceDelete() : false;
    }

    public function restore(int $id): bool
    {
        $media = $this->model->withTrashed()->find($id);
        return $media ? $media->restore() : false;
    }

    public function findForEntity(Model $entity, string $collection = null): Collection
    {
        $query = $this->model->forEntity($entity, $collection);
        return $query->ordered()->get();
    }

    public function getFirstForEntity(Model $entity, string $collection = null): ?Media
    {
        return $this->model->forEntity($entity, $collection)
                          ->ordered()
                          ->first();
    }

    public function countForEntity(Model $entity, string $collection = null): int
    {
        return $this->model->forEntity($entity, $collection)->count();
    }

    public function findByType(MediaType $type, int $limit = null): Collection
    {
        $query = $this->model->byType($type)->processed()->ordered();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    public function findByStatus(MediaStatus $status, int $limit = null): Collection
    {
        $query = $this->model->byStatus($status)->ordered();
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['type'])) {
            $query->byType(MediaType::from($filters['type']));
        }

        if (isset($filters['status'])) {
            $query->byStatus(MediaStatus::from($filters['status']));
        }

        if (isset($filters['collection'])) {
            $query->byCollection($filters['collection']);
        }

        if (isset($filters['entity_type'])) {
            $query->where('mediable_type', $filters['entity_type']);
        }

        if (isset($filters['entity_id'])) {
            $query->where('mediable_id', $filters['entity_id']);
        }

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['mime_type'])) {
            $query->where('mime_type', $filters['mime_type']);
        }

        if (isset($filters['size_min'])) {
            $query->where('size', '>=', $filters['size_min']);
        }

        if (isset($filters['size_max'])) {
            $query->where('size', '<=', $filters['size_max']);
        }

        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        if (isset($filters['expired']) && $filters['expired']) {
            $query->expired();
        } elseif (isset($filters['expired']) && !$filters['expired']) {
            $query->notExpired();
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        if ($sortBy === 'name') {
            $query->orderBy('name', $sortOrder);
        } elseif ($sortBy === 'size') {
            $query->orderBy('size', $sortOrder);
        } elseif ($sortBy === 'type') {
            $query->orderBy('type', $sortOrder);
        } elseif ($sortBy === 'sort_order') {
            $query->ordered();
        } else {
            $query->orderBy('created_at', $sortOrder);
        }

        return $query->paginate($perPage);
    }

    public function getStatistics(): array
    {
        $stats = DB::table('media')
            ->select(
                DB::raw('COUNT(*) as total_files'),
                DB::raw('SUM(size) as total_size'),
                DB::raw('AVG(size) as average_size')
            )
            ->whereNull('deleted_at')
            ->first();

        $byType = DB::table('media')
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(size) as size'))
            ->whereNull('deleted_at')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $byStatus = DB::table('media')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return [
            'total_files' => $stats->total_files ?? 0,
            'total_size' => $stats->total_size ?? 0,
            'average_size' => $stats->average_size ?? 0,
            'total_size_mb' => round(($stats->total_size ?? 0) / (1024 * 1024), 2),
            'by_type' => $byType->toArray(),
            'by_status' => $byStatus->toArray(),
        ];
    }

    public function getTopLargestFiles(int $limit = 10): Collection
    {
        return $this->model->orderBy('size', 'desc')
                          ->limit($limit)
                          ->get();
    }

    public function getOldestUnprocessed(int $limit = 10): Collection
    {
        return $this->model->where('status', MediaStatus::PENDING)
                          ->orderBy('created_at')
                          ->limit($limit)
                          ->get();
    }

    public function getFailedProcessing(int $limit = 10): Collection
    {
        return $this->model->failed()
                          ->orderBy('updated_at', 'desc')
                          ->limit($limit)
                          ->get();
    }

    public function getExpiredFiles(): Collection
    {
        return $this->model->expired()->get();
    }

    public function cleanupExpired(): int
    {
        return $this->model->expired()->delete();
    }

    public function getOrphanedFiles(): Collection
    {
        return $this->model->whereNotNull('mediable_type')
                          ->whereNotNull('mediable_id')
                          ->whereDoesntHave('mediable')
                          ->get();
    }

    public function cleanupOrphaned(): int
    {
        return $this->model->whereNotNull('mediable_type')
                          ->whereNotNull('mediable_id')
                          ->whereDoesntHave('mediable')
                          ->delete();
    }

    public function getDuplicatesByHash(): Collection
    {
        $duplicates = DB::table('media')
            ->select('metadata->file_hash as hash', DB::raw('COUNT(*) as count'))
            ->whereNotNull('metadata->file_hash')
            ->whereNull('deleted_at')
            ->groupBy('metadata->file_hash')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            return new Collection();
        }

        $hashes = $duplicates->pluck('hash');
        
        return $this->model->whereIn(DB::raw("JSON_EXTRACT(metadata, '$.file_hash')"), $hashes)
                          ->orderBy('created_at')
                          ->get();
    }

    public function getUsageByCollection(): Collection
    {
        return DB::table('media')
            ->select(
                'collection_name',
                DB::raw('COUNT(*) as files_count'),
                DB::raw('SUM(size) as total_size')
            )
            ->whereNull('deleted_at')
            ->groupBy('collection_name')
            ->orderBy('files_count', 'desc')
            ->get();
    }

    public function getUsageByEntityType(): Collection
    {
        return DB::table('media')
            ->select(
                'mediable_type',
                DB::raw('COUNT(*) as files_count'),
                DB::raw('SUM(size) as total_size')
            )
            ->whereNull('deleted_at')
            ->whereNotNull('mediable_type')
            ->groupBy('mediable_type')
            ->orderBy('files_count', 'desc')
            ->get();
    }

    public function batchUpdateStatus(array $ids, MediaStatus $status): int
    {
        return $this->model->whereIn('id', $ids)->update(['status' => $status]);
    }

    public function batchDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function batchRestore(array $ids): int
    {
        return $this->model->withTrashed()->whereIn('id', $ids)->restore();
    }

    public function reorderForEntity(Model $entity, array $mediaIds, string $collection = null): bool
    {
        DB::transaction(function () use ($entity, $mediaIds, $collection) {
            foreach ($mediaIds as $index => $mediaId) {
                $query = $this->model->where('id', $mediaId)
                                   ->where('mediable_type', get_class($entity))
                                   ->where('mediable_id', $entity->id);
                
                if ($collection) {
                    $query->where('collection_name', $collection);
                }
                
                $query->update(['sort_order' => $index + 1]);
            }
        });

        return true;
    }

    public function getRecentlyAdded(int $days = 7, int $limit = 20): Collection
    {
        return $this->model->where('created_at', '>=', now()->subDays($days))
                          ->orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get();
    }

    public function getProcessingQueue(int $limit = 100): Collection
    {
        return $this->model->where('status', MediaStatus::PENDING)
                          ->orderBy('created_at')
                          ->limit($limit)
                          ->get();
    }

    public function markAsProcessing(int $id): bool
    {
        return $this->model->where('id', $id)
                          ->where('status', MediaStatus::PENDING)
                          ->update(['status' => MediaStatus::PROCESSING]);
    }

    public function optimizeQuery()
    {
        return $this->model->select([
            'id', 'mediable_type', 'mediable_id', 'collection_name',
            'name', 'file_name', 'mime_type', 'size', 'type', 'status',
            'alt_text', 'sort_order', 'created_at'
        ]);
    }
}