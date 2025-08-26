<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SortBy;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Интерфейс для движков поиска
 */
interface SearchEngineInterface
{
    /**
     * Основной метод поиска
     */
    public function search(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator;

    /**
     * Поиск объявлений
     */
    public function searchAds(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator;

    /**
     * Поиск мастеров
     */
    public function searchMasters(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator;

    /**
     * Поиск услуг
     */
    public function searchServices(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20
    ): LengthAwarePaginator;
}
