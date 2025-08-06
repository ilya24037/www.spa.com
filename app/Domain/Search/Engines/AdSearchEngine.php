<?php

namespace App\Domain\Search\Engines;

use App\Domain\Ad\Models\Ad;
use App\Domain\Search\Services\BaseSearchEngine;
use App\Domain\Search\Engines\Filters\AdFilterService;
use App\Domain\Search\Engines\Filters\AdSimilarityFilter;
use App\Domain\Search\Engines\Facets\AdFacetCalculator;
use App\Domain\Search\Engines\Formatters\AdResultFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Движок поиска объявлений - координатор
 */
class AdSearchEngine extends BaseSearchEngine
{
    protected AdFilterService $filterService;
    protected AdSimilarityFilter $similarityFilter;
    protected AdFacetCalculator $facetCalculator;
    protected AdResultFormatter $resultFormatter;

    public function __construct()
    {
        parent::__construct();
        $this->filterService = new AdFilterService();
        $this->similarityFilter = new AdSimilarityFilter();
        $this->facetCalculator = new AdFacetCalculator();
        $this->resultFormatter = new AdResultFormatter();
    }
    /**
     * Получить базовый запрос
     */
    protected function getBaseQuery(): Builder
    {
        return Ad::query()
            ->select([
                'ads.*',
                'users.name as master_name',
                'users.rating as master_rating',
                'users.reviews_count as master_reviews_count'
            ])
            ->join('users', 'ads.user_id', '=', 'users.id')
            ->with([
                'user:id,name,avatar,rating,reviews_count,experience_years,city',
                'media' => function($query) {
                    $query->where('type', 'image')->orderBy('order')->limit(5);
                },
                'reviews' => function($query) {
                    $query->with('user:id,name,avatar')->latest()->limit(3);
                }
            ])
            ->where('ads.status', 'active')
            ->where('ads.is_published', true)
            ->where('users.is_active', true);
    }

    /**
     * Применить текстовый поиск
     */
    protected function applyTextSearch(Builder $builder, string $query): void
    {
        $relevanceScore = $this->getRelevanceScore($query, [
            'ads.title' => 4.0,
            'ads.description' => 2.5,
            'ads.specialty' => 3.0,
            'ads.additional_features' => 2.0,
            'ads.city' => 1.5,
            'users.name' => 1.8,
            'users.specialty' => 2.2,
        ]);
        
        $builder->addSelect(DB::raw($relevanceScore . ' as relevance_score'));
        
        $searchTerms = $this->parseSearchQuery($query);
        
        $builder->where(function($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->orWhere('ads.title', 'LIKE', "%{$term}%")
                  ->orWhere('ads.description', 'LIKE', "%{$term}%")
                  ->orWhere('ads.specialty', 'LIKE', "%{$term}%")
                  ->orWhere('ads.additional_features', 'LIKE', "%{$term}%")
                  ->orWhere('ads.city', 'LIKE', "%{$term}%")
                  ->orWhere('users.name', 'LIKE', "%{$term}%")
                  ->orWhere('users.specialty', 'LIKE', "%{$term}%");
            }
        });
    }

    /**
     * Применить фильтры
     */
    protected function applyFilters(Builder $builder, array $filters): void
    {
        $this->filterService->applyBasicFilters($builder, $filters);
        $this->filterService->applyPriceFilters($builder, $filters);
        $this->filterService->applyRatingFilters($builder, $filters);
        $this->filterService->applyAvailabilityFilters($builder, $filters);
        $this->filterService->applyDateFilters($builder, $filters);
        
        // Особые фильтры
        if (!empty($filters['price_range'])) {
            if (is_array($filters['price_range'])) {
                [$min, $max] = $filters['price_range'];
            } else {
                [$min, $max] = explode('-', $filters['price_range']);
            }
            $builder->whereBetween('ads.price', [(int)$min, (int)$max]);
        }

        if (!empty($filters['rating'])) {
            $builder->where('users.rating', '>=', (float)$filters['rating']);
        }

        if (!empty($filters['experience'])) {
            $builder->where('users.experience_years', '>=', (int)$filters['experience']);
        }
    }

    /**
     * Получить поле ID
     */
    protected function getIdField(): string
    {
        return 'ads.id';
    }

    /**
     * Форматировать результат быстрого поиска
     */
    protected function formatQuickResult($item): array
    {
        return $this->resultFormatter->formatQuickResult($item);
    }

    /**
     * Форматировать результат поиска похожих
     */
    protected function formatSimilarResult($item): array
    {
        return $this->resultFormatter->formatSimilarResult($item);
    }

    /**
     * Форматировать результат геопоиска
     */
    protected function formatGeoResult($item): array
    {
        return $this->resultFormatter->formatGeoResult($item);
    }

    /**
     * Применить фильтры для поиска похожих объектов
     */
    protected function applySimilarityFilters(Builder $builder, $ad): void
    {
        $this->similarityFilter->applySimilarityFilters($builder, $ad);
    }

    /**
     * Применить продвинутые фильтры
     */
    protected function applyAdvancedFilters(Builder $builder, array $criteria): void
    {
        $this->similarityFilter->applyAdvancedFilters($builder, $criteria);
    }

    /**
     * Вычислить фасет
     */
    protected function calculateFacet(Builder $builder, string $facet): array
    {
        return $this->facetCalculator->calculateFacet($builder, $facet);
    }

    /**
     * Получить заголовки для CSV
     */
    protected function getCsvHeaders(): array
    {
        return $this->resultFormatter->getCsvHeaders();
    }

    /**
     * Форматировать строку CSV
     */
    protected function formatCsvRow($item): array
    {
        return $this->resultFormatter->formatCsvRow($item);
    }

    /**
     * Получить алиас таблицы
     */
    protected function getTableAlias(): string
    {
        return 'ads';
    }
}