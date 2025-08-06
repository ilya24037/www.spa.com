<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SearchType;
use Illuminate\Http\Request;

/**
 * Фабрика для создания объектов SearchFilter
 */
class SearchFilterFactory
{
    public function __construct(
        private SearchFilterValidator $validator,
        private SearchFilterPersonalizationService $personalizationService
    ) {}

    /**
     * Создать из массива
     */
    public function fromArray(SearchType $searchType, array $filters = []): SearchFilter
    {
        return new SearchFilter($searchType, $filters);
    }

    /**
     * Создать из HTTP запроса
     */
    public function fromRequest(SearchType $searchType, ?Request $request = null): SearchFilter
    {
        $request = $request ?? request();
        
        $filters = [];
        $availableFilters = $searchType->getAvailableFilters();
        
        foreach (array_keys($availableFilters) as $filterKey) {
            if ($request->has($filterKey)) {
                $filters[$filterKey] = $request->input($filterKey);
            }
        }
        
        return new SearchFilter($searchType, $filters);
    }

    /**
     * Создать из JSON
     */
    public function fromJson(string $json): SearchFilter
    {
        $data = json_decode($json, true);
        
        if (!$data || !isset($data['search_type'])) {
            throw new \InvalidArgumentException('Invalid JSON format');
        }
        
        $searchType = SearchType::from($data['search_type']);
        return new SearchFilter($searchType, $data['filters'] ?? []);
    }

    /**
     * Создать с настройками по умолчанию
     */
    public function createWithDefaults(SearchType $searchType): SearchFilter
    {
        $filter = new SearchFilter($searchType, []);
        return $filter->applyDefaults();
    }

    /**
     * Создать персонализированный фильтр
     */
    public function createPersonalized(SearchType $searchType, ?int $userId = null): SearchFilter
    {
        $filter = $this->createWithDefaults($searchType);
        return $filter->applyPersonalization($userId);
    }

    /**
     * Создать для быстрого поиска
     */
    public function createQuickSearch(string $query, ?string $city = null): SearchFilter
    {
        $filters = ['query' => $query];
        
        if ($city) {
            $filters['city'] = $city;
        }
        
        return new SearchFilter(SearchType::ADS, $filters);
    }

    /**
     * Создать для популярных результатов
     */
    public function createPopular(SearchType $searchType, ?string $city = null): SearchFilter
    {
        $filters = [
            'rating' => 4.0,
            'sort_by' => 'rating',
            'verified' => true,
        ];
        
        if ($city) {
            $filters['city'] = $city;
        }
        
        return new SearchFilter($searchType, $filters);
    }

    /**
     * Создать для новых пользователей
     */
    public function createForNewUsers(SearchType $searchType): SearchFilter
    {
        $filters = [
            'rating' => 4.5,
            'verified' => true,
            'sort_by' => 'rating',
        ];
        
        return new SearchFilter($searchType, $filters);
    }

    /**
     * Получить настройки по умолчанию для типа поиска
     */
    public function getDefaultFilters(SearchType $searchType): array
    {
        return match($searchType) {
            SearchType::ADS => [
                'availability' => true,
                'sort_by' => 'rating',
            ],
            SearchType::MASTERS => [
                'rating' => 3.0,
                'verified' => false,
                'sort_by' => 'rating',
            ],
            SearchType::SERVICES => [
                'availability' => true,
                'sort_by' => 'price',
            ],
            default => [],
        };
    }
}