<?php

namespace App\Application\Http\Controllers;

use App\Domain\Search\Services\SearchService;
use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;
use App\Application\Http\Requests\Search\GeoSearchRequest;
use App\Application\Http\Requests\Search\AdvancedSearchRequest;
use App\Application\Http\Resources\Search\SearchResultResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService
    ) {}

    /**
     * Универсальная страница поиска
     */
    public function index(Request $request): Response
    {
        $query = $request->get('q', '');
        $type = SearchType::tryFrom($request->get('type', 'ads')) ?? SearchType::ADS;
        $sortBy = SortBy::tryFrom($request->get('sort', 'relevance')) ?? SortBy::RELEVANCE;
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);
        
        // Получаем фильтры из запроса
        $filters = $this->extractFilters($request, $type);
        
        // Получаем местоположение пользователя
        $location = $this->getUserLocation($request);
        
        // Выполняем поиск
        $results = $this->searchService->search(
            $query,
            $type,
            $filters,
            $sortBy,
            $page,
            $perPage,
            $location
        );

        return Inertia::render('Search/Index', [
            'results' => $results,
            'query' => $query,
            'type' => $type->value,
            'filters' => $filters,
            'sortBy' => $sortBy->value,
            'availableFilters' => $type->getAvailableFilters(),
            'availableSortOptions' => $type->getAvailableSortOptions(),
            'searchTypes' => $this->getSearchTypes(),
            'popularQueries' => $this->searchService->getPopularQueries($type, 5),
        ]);
    }

    /**
     * Поиск объявлений
     */
    public function ads(Request $request): Response
    {
        $request->merge(['type' => SearchType::ADS->value]);
        return $this->index($request);
    }

    /**
     * Поиск мастеров
     */
    public function masters(Request $request): Response
    {
        $request->merge(['type' => SearchType::MASTERS->value]);
        return $this->index($request);
    }

    /**
     * Поиск услуг
     */
    public function services(Request $request): Response
    {
        $request->merge(['type' => SearchType::SERVICES->value]);
        return $this->index($request);
    }

    /**
     * Глобальный поиск
     */
    public function global(Request $request): Response
    {
        $request->merge(['type' => SearchType::GLOBAL->value]);
        return $this->index($request);
    }

    /**
     * API для автодополнения
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $type = SearchType::tryFrom($request->get('type', 'ads')) ?? SearchType::ADS;
        $limit = min((int) $request->get('limit', 10), 20);

        if (mb_strlen($query) < $type->getMinQueryLength()) {
            return response()->json([]);
        }

        $suggestions = $this->searchService->getAutocomplete($query, $type, $limit);
        
        return response()->json($suggestions);
    }

    /**
     * API для поисковых подсказок
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $type = SearchType::tryFrom($request->get('type', 'ads')) ?? SearchType::ADS;
        $context = $request->get('context', []);

        $suggestions = $this->searchService->getSearchSuggestions($query, $type, $context);

        return response()->json($suggestions);
    }

    /**
     * API для быстрого поиска
     */
    public function quick(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $type = SearchType::tryFrom($request->get('type', 'ads')) ?? SearchType::ADS;
        $limit = min((int) $request->get('limit', 5), 10);

        $results = $this->searchService->quickSearch($query, $type, $limit);

        return response()->json($results);
    }

    /**
     * Получить похожие объекты
     */
    public function similar(Request $request, int $id): JsonResponse
    {
        $type = SearchType::tryFrom($request->get('type', 'ads')) ?? SearchType::ADS;
        $limit = min((int) $request->get('limit', 10), 20);
        $excludeIds = $request->get('exclude', []);

        $similar = $this->searchService->findSimilar($id, $type, $limit, $excludeIds);

        return response()->json($similar);
    }

    /**
     * Продвинутый поиск
     */
    public function advanced(AdvancedSearchRequest $request): Response
    {
        $criteria = $request->validated();

        $results = $this->searchService->advancedSearch($criteria);

        return Inertia::render('Search/Advanced', [
            'results' => $results,
            'criteria' => $criteria,
        ]);
    }

    /**
     * Геопоиск
     */
    public function geo(GeoSearchRequest $request): JsonResponse
    {
        $location = [
            'lat' => $request->get('lat'),
            'lng' => $request->get('lng'),
        ];
        $radius = $request->get('radius');
        $type = SearchType::tryFrom($request->get('type', 'ads')) ?? SearchType::ADS;
        $filters = $this->extractFilters($request, $type);
        $limit = min((int) $request->get('limit', 20), 100);

        $results = $this->searchService->geoSearch($location, $radius, $type, $filters, $limit);

        return new SearchResultResource($results);
    }

    /**
     * Экспорт результатов поиска
     */
    public function export(Request $request)
    {
        $query = $request->get('q', '');
        $type = SearchType::tryFrom($request->get('type', 'ads')) ?? SearchType::ADS;
        $format = $request->get('format', 'csv');
        $filters = $this->extractFilters($request, $type);
        $limit = min((int) $request->get('limit', 1000), 5000);

        if (!$type->canExport()) {
            abort(403, 'Экспорт недоступен для данного типа поиска');
        }

        $exportData = $this->searchService->exportResults($query, $type, $filters, $format, $limit);

        return response($exportData)
            ->header('Content-Type', $this->getExportContentType($format))
            ->header('Content-Disposition', "attachment; filename=\"search-results.{$format}\"");
    }

    /**
     * Статистика поиска (для админов)
     */
    public function statistics(Request $request): Response
    {
        $this->authorize('viewSearchStatistics');

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'type' => $request->get('type'),
        ];

        $statistics = $this->searchService->getSearchStatistics($filters);

        return Inertia::render('Search/Statistics', [
            'statistics' => $statistics,
            'filters' => $filters,
        ]);
    }

    /**
     * Извлечь фильтры из запроса
     */
    private function extractFilters(Request $request, SearchType $type): array
    {
        $availableFilters = $type->getAvailableFilters();
        $filters = [];

        foreach ($availableFilters as $filter) {
            if ($request->has($filter)) {
                $filters[$filter] = $request->get($filter);
            }
        }

        return $filters;
    }

    /**
     * Получить местоположение пользователя
     */
    private function getUserLocation(Request $request): ?array
    {
        if ($request->has('lat') && $request->has('lng')) {
            return [
                'lat' => (float) $request->get('lat'),
                'lng' => (float) $request->get('lng'),
            ];
        }

        // Можно добавить логику определения местоположения по IP
        return null;
    }

    /**
     * Получить доступные типы поиска
     */
    private function getSearchTypes(): array
    {
        return collect(SearchType::cases())->map(fn($type) => [
            'value' => $type->value,
            'label' => $type->getLabel(),
            'icon' => $type->getIcon(),
            'url' => $type->getSearchUrl(),
        ])->toArray();
    }

    /**
     * Получить content-type для экспорта
     */
    private function getExportContentType(string $format): string
    {
        return match($format) {
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xml' => 'application/xml',
            default => 'text/plain',
        };
    }
}