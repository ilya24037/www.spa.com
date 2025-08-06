<?php

namespace App\Domain\Search\Services\Handlers;

use App\Domain\Search\Services\SearchResult;

/**
 * Форматировщик сообщений и SEO данных для результатов поиска
 */
class SearchResultFormatter
{
    private SearchResult $searchResult;

    public function __construct(SearchResult $searchResult)
    {
        $this->searchResult = $searchResult;
    }

    /**
     * Создать сообщение о результатах
     */
    public function getResultsMessage(): string
    {
        $total = $this->searchResult->getTotal();
        
        if ($total === 0) {
            return $this->getNoResultsMessage();
        }
        
        $typeLabel = $this->searchResult->getSearchType()->getLabel();
        $queryText = !empty($this->searchResult->getQuery()) ? " по запросу \"{$this->searchResult->getQuery()}\"" : '';
        
        if ($total === 1) {
            return "Найден 1 результат в разделе \"{$typeLabel}\"{$queryText}";
        }
        
        $pluralForm = $this->getPluralForm($total, ['результат', 'результата', 'результатов']);
        return "Найдено {$total} {$pluralForm} в разделе \"{$typeLabel}\"{$queryText}";
    }

    /**
     * Получить сообщение об отсутствии результатов
     */
    public function getNoResultsMessage(): string
    {
        $queryText = !empty($this->searchResult->getQuery()) ? " по запросу \"{$this->searchResult->getQuery()}\"" : '';
        return "К сожалению, ничего не найдено{$queryText}. Попробуйте изменить параметры поиска.";
    }

    /**
     * Получить рекомендации при отсутствии результатов
     */
    public function getNoResultsRecommendations(): array
    {
        return [
            'Проверьте правильность написания',
            'Попробуйте использовать более общие термы',
            'Уберите некоторые фильтры',
            'Расширьте географию поиска',
        ];
    }

    /**
     * Получить альтернативные запросы
     */
    public function getAlternativeQueries(): array
    {
        $suggestions = $this->searchResult->getSuggestions();
        
        if (empty($suggestions['corrections'])) {
            return [];
        }
        
        return $suggestions['corrections'];
    }

    /**
     * Получить SEO заголовок
     */
    public function getSeoTitle(): string
    {
        $typeLabel = $this->searchResult->getSearchType()->getLabel();
        $queryText = !empty($this->searchResult->getQuery()) ? " \"{$this->searchResult->getQuery()}\" - " : ' - ';
        $page = $this->searchResult->getCurrentPage() > 1 ? " - страница {$this->searchResult->getCurrentPage()}" : '';
        
        return "{$typeLabel}{$queryText}поиск и заказ услуг{$page}";
    }

    /**
     * Получить SEO описание
     */
    public function getSeoDescription(): string
    {
        $total = $this->searchResult->getTotal();
        $typeLabel = mb_strtolower($this->searchResult->getSearchType()->getLabel());
        $queryText = !empty($this->searchResult->getQuery()) ? " по запросу \"{$this->searchResult->getQuery()}\"" : '';
        
        if ($total === 0) {
            return "Поиск {$typeLabel}{$queryText}. Не найдено подходящих результатов.";
        }
        
        return "Найдено {$total} {$typeLabel}{$queryText}. Сравните цены, почитайте отзывы и выберите лучшее предложение.";
    }

    /**
     * Получить множественную форму слова
     */
    public function getPluralForm(int $number, array $forms): string
    {
        $cases = [2, 0, 1, 1, 1, 2];
        return $forms[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }

    /**
     * Форматировать краткую информацию о результатах
     */
    public function getShortSummary(): string
    {
        $total = $this->searchResult->getTotal();
        
        if ($total === 0) {
            return 'Ничего не найдено';
        }
        
        $currentPage = $this->searchResult->getCurrentPage();
        $totalPages = $this->searchResult->getLastPage();
        
        if ($totalPages === 1) {
            return "Найдено {$total}";
        }
        
        return "Найдено {$total} (стр. {$currentPage} из {$totalPages})";
    }

    /**
     * Получить расширенное описание результатов
     */
    public function getDetailedSummary(): array
    {
        return [
            'message' => $this->getResultsMessage(),
            'short_summary' => $this->getShortSummary(),
            'total' => $this->searchResult->getTotal(),
            'has_results' => $this->searchResult->hasResults(),
            'execution_time_formatted' => $this->formatExecutionTime(),
            'filters_summary' => $this->getFiltersSummary(),
        ];
    }

    /**
     * Форматировать время выполнения
     */
    public function formatExecutionTime(): string
    {
        $time = $this->searchResult->getExecutionTime();
        
        if ($time < 0.001) {
            return 'менее 1 мс';
        }
        
        if ($time < 1) {
            return round($time * 1000) . ' мс';
        }
        
        return round($time, 2) . ' сек';
    }

    /**
     * Получить сводку по фильтрам
     */
    public function getFiltersSummary(): string
    {
        $filters = $this->searchResult->getFilters();
        
        if (!$filters->hasActiveFilters()) {
            return 'Фильтры не применены';
        }
        
        $count = $filters->getActiveFiltersCount();
        $plural = $this->getPluralForm($count, ['фильтр', 'фильтра', 'фильтров']);
        
        return "Применено {$count} {$plural}";
    }

    /**
     * Получить хлебные крошки для поиска
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            ['title' => 'Главная', 'url' => '/'],
            ['title' => 'Поиск', 'url' => '/search'],
        ];

        $typeLabel = $this->searchResult->getSearchType()->getLabel();
        $breadcrumbs[] = ['title' => $typeLabel, 'url' => null];

        if (!empty($this->searchResult->getQuery())) {
            $breadcrumbs[] = [
                'title' => '"' . $this->searchResult->getQuery() . '"',
                'url' => null
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Получить мета-теги для страницы
     */
    public function getMetaTags(): array
    {
        return [
            'title' => $this->getSeoTitle(),
            'description' => $this->getSeoDescription(),
            'keywords' => $this->generateKeywords(),
            'og:title' => $this->getSeoTitle(),
            'og:description' => $this->getSeoDescription(),
            'og:type' => 'website',
        ];
    }

    /**
     * Сгенерировать ключевые слова
     */
    private function generateKeywords(): string
    {
        $keywords = [];
        
        if (!empty($this->searchResult->getQuery())) {
            $keywords[] = $this->searchResult->getQuery();
        }
        
        $keywords[] = $this->searchResult->getSearchType()->getLabel();
        $keywords[] = 'поиск услуг';
        $keywords[] = 'заказать';
        
        return implode(', ', $keywords);
    }
}