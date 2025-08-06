<?php

namespace App\Domain\Notification\Repositories;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Repositories\Services\NotificationCrudService;
use App\Domain\Notification\Repositories\Services\NotificationSearchService;
use App\Domain\Notification\Repositories\Statistics\NotificationStatisticsService;
use App\Domain\Notification\Repositories\Operations\NotificationBatchOperations;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Репозиторий для работы с уведомлениями - координатор
 */
class NotificationRepository
{
    protected NotificationCrudService $crudService;
    protected NotificationSearchService $searchService;
    protected NotificationStatisticsService $statisticsService;
    protected NotificationBatchOperations $batchOperations;

    public function __construct(Notification $model)
    {
        $this->crudService = new NotificationCrudService($model);
        $this->searchService = new NotificationSearchService($model);
        $this->statisticsService = new NotificationStatisticsService($model);
        $this->batchOperations = new NotificationBatchOperations($model);
    }

    // CRUD операции
    public function find(int $id): ?Notification
    {
        return $this->crudService->find($id);
    }

    public function findOrFail(int $id): Notification
    {
        return $this->crudService->findOrFail($id);
    }

    public function create(array $data): Notification
    {
        return $this->crudService->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->crudService->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->crudService->delete($id);
    }

    // Поисковые операции
    public function getForUser(
        int $userId, 
        int $limit = 20, 
        int $offset = 0,
        bool $unreadOnly = false
    ): Collection {
        return $this->searchService->getForUser($userId, $limit, $offset, $unreadOnly);
    }

    public function getPaginatedForUser(
        int $userId, 
        int $perPage = 20,
        array $filters = []
    ): LengthAwarePaginator {
        return $this->searchService->getPaginatedForUser($userId, $perPage, $filters);
    }

    public function getUnreadForUser(int $userId, int $limit = 50): Collection
    {
        return $this->searchService->getUnreadForUser($userId, $limit);
    }

    public function getUnreadCountForUser(int $userId): int
    {
        return $this->searchService->getUnreadCountForUser($userId);
    }

    public function getReadyToSend(int $limit = 100): Collection
    {
        return $this->searchService->getReadyToSend($limit);
    }

    public function getScheduled(): Collection
    {
        return $this->searchService->getScheduled();
    }

    public function getFailedForRetry(int $limit = 50): Collection
    {
        return $this->searchService->getFailedForRetry($limit);
    }

    public function getByGroup(string $groupKey): Collection
    {
        return $this->searchService->getByGroup($groupKey);
    }

    public function getRecentByType(
        NotificationType $type, 
        int $days = 7, 
        int $limit = 100
    ): Collection {
        return $this->searchService->getRecentByType($type, $days, $limit);
    }

    public function search(array $criteria): Collection
    {
        return $this->searchService->search($criteria);
    }

    // Операции пометки
    public function markAsRead(int $id): bool
    {
        return $this->crudService->markAsRead($id);
    }

    public function markAllAsReadForUser(int $userId): int
    {
        return $this->batchOperations->markAllAsReadForUser($userId);
    }

    public function markTypeAsReadForUser(int $userId, NotificationType $type): int
    {
        return $this->batchOperations->markTypeAsReadForUser($userId, $type);
    }

    // Операции удаления
    public function deleteOld(int $days = 30): int
    {
        return $this->batchOperations->deleteOld($days);
    }

    public function deleteExpired(): int
    {
        return $this->batchOperations->deleteExpired();
    }

    // Статистика
    public function getStats(int $days = 7): array
    {
        return $this->statisticsService->getStats($days);
    }

    public function getUserStats(int $userId, int $days = 30): array
    {
        return $this->statisticsService->getUserStats($userId, $days);
    }

    public function getTopTypes(int $limit = 10, int $days = 30): array
    {
        return $this->statisticsService->getTopTypes($limit, $days);
    }

    public function getActiveUsers(int $days = 7): Collection
    {
        return $this->statisticsService->getActiveUsers($days);
    }

    // Batch операции
    public function batchCreate(array $notifications): Collection
    {
        return $this->batchOperations->batchCreate($notifications);
    }

    public function batchMarkAsRead(array $ids): int
    {
        return $this->batchOperations->batchMarkAsRead($ids);
    }

    public function batchDelete(array $ids): int
    {
        return $this->batchOperations->batchDelete($ids);
    }
}