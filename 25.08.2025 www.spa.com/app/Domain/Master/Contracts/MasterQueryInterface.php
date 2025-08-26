<?php

namespace App\Domain\Master\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Интерфейс для запросов к мастерам
 * Для чтения данных без изменения состояния (CQRS pattern)
 */
interface MasterQueryInterface
{
    /**
     * Получить мастеров с пагинацией
     */
    public function getMastersPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Поиск мастеров по названию и услугам
     */
    public function searchMasters(string $query, array $filters = []): Collection;

    /**
     * Получить мастеров по рейтингу
     */
    public function getMastersByRating(float $minRating = 4.0, int $limit = 10): Collection;

    /**
     * Получить новых мастеров
     */
    public function getNewMasters(int $days = 30, int $limit = 10): Collection;

    /**
     * Получить популярных мастеров
     */
    public function getPopularMasters(int $period = 30, int $limit = 10): Collection;

    /**
     * Получить мастеров рядом с координатами
     */
    public function getMastersNearby(float $latitude, float $longitude, int $radius = 10): Collection;

    /**
     * Получить категории услуг с количеством мастеров
     */
    public function getServiceCategoriesWithCount(): array;

    /**
     * Получить статистику по городам
     */
    public function getCityStatistics(): array;

    /**
     * Получить отчет по активности мастеров
     */
    public function getMasterActivityReport(array $filters): array;

    /**
     * Получить мастеров для модерации
     */
    public function getMastersForModeration(): Collection;

    /**
     * Получить топ мастеров по отзывам
     */
    public function getTopMastersByReviews(int $limit = 10): Collection;

    /**
     * Получить неактивных мастеров
     */
    public function getInactiveMasters(int $days = 30): Collection;
}