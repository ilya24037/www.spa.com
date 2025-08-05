<?php

namespace App\Support\Contracts;

use App\Domain\Media\Models\Media;
use App\Enums\MediaType;
use App\Enums\MediaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * Интерфейс для медиа репозитория
 * Определяет специфичные методы для работы с медиа файлами
 * СОЗДАН согласно CLAUDE.md принципам
 */
interface MediaRepositoryInterface extends RepositoryInterface
{
    /**
     * Найти медиа по имени файла
     */
    public function findByFileName(string $fileName): ?Media;

    /**
     * Получить медиа для конкретной сущности
     */
    public function findForEntity(Model $entity, string $collection = null): Collection;

    /**
     * Получить первое медиа для сущности
     */
    public function getFirstForEntity(Model $entity, string $collection = null): ?Media;

    /**
     * Подсчитать количество медиа для сущности
     */
    public function countForEntity(Model $entity, string $collection = null): int;

    /**
     * Найти медиа по типу
     */
    public function findByType(MediaType $type, int $limit = null): Collection;

    /**
     * Найти медиа по статусу
     */
    public function findByStatus(MediaStatus $status, int $limit = null): Collection;

    /**
     * Мягкое удаление
     */
    public function softDelete(int $id): bool;

    /**
     * Полное удаление
     */
    public function forceDelete(int $id): bool;

    /**
     * Восстановление удаленного
     */
    public function restore(int $id): bool;

    /**
     * Изменить порядок медиа для сущности
     */
    public function reorderForEntity(Model $entity, array $mediaIds, string $collection = null): bool;

    /**
     * Массовое обновление статуса
     */
    public function batchUpdateStatus(array $ids, MediaStatus $status): int;

    /**
     * Массовое удаление
     */
    public function batchDelete(array $ids): int;

    /**
     * Массовое восстановление
     */
    public function batchRestore(array $ids): int;

    /**
     * Получить недавно добавленные файлы
     */
    public function getRecentlyAdded(int $days = 7, int $limit = 20): Collection;

    /**
     * Получить очередь обработки
     */
    public function getProcessingQueue(int $limit = 100): Collection;

    /**
     * Отметить как "в обработке"
     */
    public function markAsProcessing(int $id): bool;
}