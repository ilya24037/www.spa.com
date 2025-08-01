<?php

namespace App\Services;

use App\Domain\Search\Services\SearchService as DomainSearchService;
use App\Enums\SearchType;
use App\Enums\SortBy;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * Legacy-адаптер для SearchService
 * @deprecated Используйте App\Domain\Search\Services\SearchService
 */
class SearchService
{
    private DomainSearchService $domainService;

    public function __construct(DomainSearchService $domainService)
    {
        $this->domainService = $domainService;
    }

    /**
     * @deprecated Используйте DomainSearchService::search()
     */
    public function search(
        string $query,
        SearchType $type = SearchType::ADS,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator {
        Log::info('Using legacy SearchService::search - consider migrating to Domain service');
        return $this->domainService->search($query, $type, $filters, $sortBy, $page, $perPage, $location);
    }

    /**
     * @deprecated Используйте DomainSearchService::quickSearch()
     */
    public function quickSearch(
        string $query,
        SearchType $type = SearchType::ADS,
        int $limit = 5
    ): array {
        Log::info('Using legacy SearchService::quickSearch - consider migrating to Domain service');
        return $this->domainService->quickSearch($query, $type, $limit);
    }

    /**
     * @deprecated Используйте DomainSearchService::getAutocomplete()
     */
    public function getAutocomplete(
        string $query,
        SearchType $type = SearchType::ADS,
        int $limit = 10
    ): array {
        Log::info('Using legacy SearchService::getAutocomplete - consider migrating to Domain service');
        return $this->domainService->getAutocomplete($query, $type, $limit);
    }

    /**
     * @deprecated Используйте DomainSearchService::getPopularQueries()
     */
    public function getPopularQueries(SearchType $type = SearchType::ADS, int $limit = 10): array
    {
        Log::info('Using legacy SearchService::getPopularQueries - consider migrating to Domain service');
        return $this->domainService->getPopularQueries($type, $limit);
    }

    /**
     * @deprecated Используйте DomainSearchService::getRelatedQueries()
     */
    public function getRelatedQueries(string $query, SearchType $type = SearchType::ADS): array
    {
        Log::info('Using legacy SearchService::getRelatedQueries - consider migrating to Domain service');
        return $this->domainService->getRelatedQueries($query, $type);
    }

    /**
     * @deprecated Используйте DomainSearchService::getSearchSuggestions()
     */
    public function getSearchSuggestions(
        string $query,
        SearchType $type = SearchType::ADS,
        array $context = []
    ): array {
        Log::info('Using legacy SearchService::getSearchSuggestions - consider migrating to Domain service');
        return $this->domainService->getSearchSuggestions($query, $type, $context);
    }

    /**
     * @deprecated Используйте DomainSearchService::findSimilar()
     */
    public function findSimilar(
        int $objectId,
        SearchType $type,
        int $limit = 10,
        array $excludeIds = []
    ): array {
        Log::info('Using legacy SearchService::findSimilar - consider migrating to Domain service');
        return $this->domainService->findSimilar($objectId, $type, $limit, $excludeIds);
    }

    /**
     * @deprecated Используйте DomainSearchService::advancedSearch()
     */
    public function advancedSearch(array $criteria): LengthAwarePaginator
    {
        Log::info('Using legacy SearchService::advancedSearch - consider migrating to Domain service');
        return $this->domainService->advancedSearch($criteria);
    }

    /**
     * @deprecated Используйте DomainSearchService::facetedSearch()
     */
    public function facetedSearch(
        string $query,
        SearchType $type,
        array $facets = []
    ): array {
        Log::info('Using legacy SearchService::facetedSearch - consider migrating to Domain service');
        return $this->domainService->facetedSearch($query, $type, $facets);
    }

    /**
     * @deprecated Используйте DomainSearchService::geoSearch()
     */
    public function geoSearch(
        array $location,
        float $radius,
        SearchType $type = SearchType::ADS,
        array $filters = [],
        int $limit = 20
    ): array {
        Log::info('Using legacy SearchService::geoSearch - consider migrating to Domain service');
        return $this->domainService->geoSearch($location, $radius, $type, $filters, $limit);
    }

    /**
     * @deprecated Используйте DomainSearchService::intelligentSearch()
     */
    public function intelligentSearch(
        string $query,
        SearchType $type,
        ?int $userId = null,
        array $context = []
    ): LengthAwarePaginator {
        Log::info('Using legacy SearchService::intelligentSearch - consider migrating to Domain service');
        return $this->domainService->intelligentSearch($query, $type, $userId, $context);
    }

    /**
     * @deprecated Используйте DomainSearchService::exportResults()
     */
    public function exportResults(
        string $query,
        SearchType $type,
        array $filters = [],
        string $format = 'csv',
        int $limit = 1000
    ): string {
        Log::info('Using legacy SearchService::exportResults - consider migrating to Domain service');
        return $this->domainService->exportResults($query, $type, $filters, $format, $limit);
    }

    /**
     * @deprecated Используйте DomainSearchService::getSearchStatistics()
     */
    public function getSearchStatistics(array $filters = []): array
    {
        Log::info('Using legacy SearchService::getSearchStatistics - consider migrating to Domain service');
        return $this->domainService->getSearchStatistics($filters);
    }

    /**
     * @deprecated Используйте DomainSearchService::clearSearchCache()
     */
    public function clearSearchCache(?SearchType $type = null): void
    {
        Log::info('Using legacy SearchService::clearSearchCache - consider migrating to Domain service');
        $this->domainService->clearSearchCache($type);
    }

    /**
     * @deprecated Используйте DomainSearchService::warmupCache()
     */
    public function warmupCache(): void
    {
        Log::info('Using legacy SearchService::warmupCache - consider migrating to Domain service');
        $this->domainService->warmupCache();
    }

    /**
     * Proxy для всех других методов
     */
    public function __call($method, $arguments)
    {
        Log::info("Using legacy SearchService::{$method} - consider migrating to Domain service");
        return $this->domainService->$method(...$arguments);
    }
}