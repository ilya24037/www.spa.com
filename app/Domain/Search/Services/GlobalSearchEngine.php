<?php

namespace App\Domain\Search\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use App\Domain\Service\Models\Service;
use App\Enums\SortBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Глобальный движок поиска
 */
class GlobalSearchEngine extends BaseSearchEngine
{
    public function __construct(
        protected \App\Domain\Search\Repositories\SearchRepository $repository,
        protected AdSearchEngine $adEngine,
        protected MasterSearchEngine $masterEngine,
        protected ServiceSearchEngine $serviceEngine
    ) {
        parent::__construct($repository);
    }

    public function search(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator {
        
        $results = collect();
        
        // Поиск по объявлениям
        $adResults = $this->adEngine->search($query, $filters, $sortBy, 1, 10, $location);
        foreach ($adResults->items() as $ad) {
            $results->push([
                'type' => 'ad',
                'data' => $ad,
                'relevance_score' => $ad->relevance_score ?? 0,
            ]);
        }

        // Поиск по мастерам
        $masterResults = $this->masterEngine->search($query, $filters, $sortBy, 1, 8, $location);
        foreach ($masterResults->items() as $master) {
            $results->push([
                'type' => 'master',
                'data' => $master,
                'relevance_score' => $master->relevance_score ?? 0,
            ]);
        }

        // Поиск по услугам
        $serviceResults = $this->serviceEngine->search($query, $filters, $sortBy, 1, 5, $location);
        foreach ($serviceResults->items() as $service) {
            $results->push([
                'type' => 'service',
                'data' => $service,
                'relevance_score' => $service->relevance_score ?? 0,
            ]);
        }

        // Сортируем по релевантности
        $sorted = $results->sortByDesc('relevance_score');
        
        $total = $adResults->total() + $masterResults->total() + $serviceResults->total();
        
        // Пагинация
        $offset = ($page - 1) * $perPage;
        $items = $sorted->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    protected function getBaseQuery(): Builder
    {
        // Не используется в глобальном поиске
        return Ad::query();
    }

    protected function applyTextSearch(Builder $builder, string $query): void
    {
        // Не используется в глобальном поиске
    }

    protected function applyFilters(Builder $builder, array $filters): void
    {
        // Не используется в глобальном поиске
    }

    protected function getIdField(): string
    {
        return 'id';
    }

    protected function formatQuickResult($item): array
    {
        return [
            'type' => $item['type'],
            'data' => $item['data'],
        ];
    }

    protected function formatSimilarResult($item): array
    {
        return $this->formatQuickResult($item);
    }

    protected function formatGeoResult($item): array
    {
        return $this->formatQuickResult($item);
    }
}