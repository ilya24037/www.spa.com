<?php

namespace App\Domain\Search\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Унифицированный сервис обработки результатов поиска
 * Консолидирует 4→1:
 * - SearchResultAggregator.php
 * - SearchResultFormatter.php  
 * - SearchResultExporter.php
 * - SearchResultStatistics.php
 * 
 * Принцип KISS: вся обработка результатов в одном месте
 */
class SearchResultService
{
    /**
     * Агрегировать результаты из нескольких источников
     */
    public function aggregateResults(array $resultSets, array $weights = []): Collection
    {
        $aggregated = collect();
        $defaultWeight = 1.0;
        
        foreach ($resultSets as $source => $results) {
            $weight = $weights[$source] ?? $defaultWeight;
            
            if ($results instanceof LengthAwarePaginator) {
                $items = $results->items();
            } elseif ($results instanceof Collection) {
                $items = $results->toArray();
            } else {
                $items = is_array($results) ? $results : [];
            }
            
            foreach ($items as $item) {
                // Преобразуем в массив если это объект
                $itemArray = is_object($item) ? $item->toArray() : $item;
                
                // Добавляем метаданные
                $itemArray['_source'] = $source;
                $itemArray['_weight'] = $weight;
                $itemArray['_relevance_score'] = $this->calculateRelevanceScore($itemArray, $weight);
                
                $aggregated->push($itemArray);
            }
        }
        
        // Сортируем по релевантности
        return $aggregated->sortByDesc('_relevance_score')->values();
    }

    /**
     * Форматировать результаты для API
     */
    public function formatForApi(LengthAwarePaginator $results, array $options = []): array
    {
        $formatted = [
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem()
            ],
            'links' => [
                'first' => $results->url(1),
                'last' => $results->url($results->lastPage()),
                'prev' => $results->previousPageUrl(),
                'next' => $results->nextPageUrl(),
                'self' => $results->url($results->currentPage())
            ]
        ];
        
        // Добавляем статистику если запрошена
        if ($options['include_stats'] ?? false) {
            $formatted['statistics'] = $this->generateStatistics($results);
        }
        
        // Добавляем фасеты если есть
        if (isset($options['facets'])) {
            $formatted['facets'] = $options['facets'];
        }
        
        return $formatted;
    }

    /**
     * Форматировать результаты для отображения в UI
     */
    public function formatForUI(LengthAwarePaginator $results, array $options = []): array
    {
        $items = collect($results->items())->map(function ($item) use ($options) {
            return $this->formatSingleItem($item, $options);
        });
        
        return [
            'items' => $items,
            'pagination' => [
                'current' => $results->currentPage(),
                'total_pages' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total_items' => $results->total(),
                'has_prev' => $results->currentPage() > 1,
                'has_next' => $results->hasMorePages()
            ],
            'summary' => $this->generateSummary($results),
            'sort_options' => $this->getSortOptions(),
            'filter_suggestions' => $options['suggest_filters'] ?? false ? $this->suggestFilters($results) : null
        ];
    }

    /**
     * Экспорт результатов в различные форматы
     */
    public function export(LengthAwarePaginator $results, string $format = 'csv', array $options = []): string
    {
        $items = $results->items();
        
        switch (strtolower($format)) {
            case 'csv':
                return $this->exportToCsv($items, $options);
                
            case 'json':
                return $this->exportToJson($items, $options);
                
            case 'xml':
                return $this->exportToXml($items, $options);
                
            case 'excel':
                return $this->exportToExcel($items, $options);
                
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Получить статистику результатов
     */
    public function getStatistics(LengthAwarePaginator $results): array
    {
        $items = collect($results->items());
        
        return [
            'total_results' => $results->total(),
            'results_per_page' => $results->perPage(),
            'total_pages' => $results->lastPage(),
            'current_page' => $results->currentPage(),
            'response_time' => $this->getResponseTime(),
            'item_statistics' => $this->calculateItemStatistics($items),
            'distribution' => $this->calculateDistribution($items),
            'quality_metrics' => $this->calculateQualityMetrics($items)
        ];
    }

    /**
     * Обогатить результаты дополнительными данными
     */
    public function enrichResults(LengthAwarePaginator $results, array $enrichments = []): LengthAwarePaginator
    {
        $enrichedItems = collect($results->items())->map(function ($item) use ($enrichments) {
            $enrichedItem = is_object($item) ? $item->toArray() : $item;
            
            foreach ($enrichments as $enrichment) {
                $enrichedItem = $this->applyEnrichment($enrichedItem, $enrichment);
            }
            
            return $enrichedItem;
        });
        
        // Создаем новый LengthAwarePaginator с обогащенными данными
        return new LengthAwarePaginator(
            $enrichedItems,
            $results->total(),
            $results->perPage(),
            $results->currentPage(),
            [
                'path' => $results->path(),
                'pageName' => $results->getPageName(),
            ]
        );
    }

    /**
     * Группировать результаты по критерию
     */
    public function groupResults(LengthAwarePaginator $results, string $groupBy): array
    {
        $items = collect($results->items());
        
        $grouped = $items->groupBy(function ($item) use ($groupBy) {
            $itemArray = is_object($item) ? $item->toArray() : $item;
            return $itemArray[$groupBy] ?? 'Не указано';
        });
        
        return $grouped->map(function ($group, $key) {
            return [
                'group_name' => $key,
                'count' => $group->count(),
                'items' => $group->values()
            ];
        })->toArray();
    }

    /**
     * Найти дубликаты в результатах
     */
    public function findDuplicates(LengthAwarePaginator $results, array $compareFields = ['id']): array
    {
        $items = collect($results->items());
        $duplicates = [];
        
        $groups = $items->groupBy(function ($item) use ($compareFields) {
            $itemArray = is_object($item) ? $item->toArray() : $item;
            $key = '';
            
            foreach ($compareFields as $field) {
                $key .= ($itemArray[$field] ?? '') . '|';
            }
            
            return $key;
        });
        
        foreach ($groups as $key => $group) {
            if ($group->count() > 1) {
                $duplicates[] = [
                    'duplicate_key' => $key,
                    'count' => $group->count(),
                    'items' => $group->values()
                ];
            }
        }
        
        return $duplicates;
    }

    /**
     * Получить топ результатов по критерию
     */
    public function getTopResults(LengthAwarePaginator $results, string $sortField, int $limit = 10): array
    {
        $items = collect($results->items());
        
        return $items->sortByDesc(function ($item) use ($sortField) {
            $itemArray = is_object($item) ? $item->toArray() : $item;
            return $itemArray[$sortField] ?? 0;
        })->take($limit)->values()->toArray();
    }

    // === ПРИВАТНЫЕ МЕТОДЫ ===

    /**
     * Вычислить оценку релевантности
     */
    private function calculateRelevanceScore(array $item, float $weight): float
    {
        $baseScore = $item['_score'] ?? $item['relevance'] ?? 1.0;
        
        // Учитываем дополнительные факторы
        $bonusFactors = 0;
        
        // Бонус за рейтинг
        if (isset($item['rating'])) {
            $bonusFactors += ($item['rating'] / 5) * 0.2;
        }
        
        // Бонус за количество отзывов
        if (isset($item['reviews_count'])) {
            $bonusFactors += min($item['reviews_count'] / 100, 0.3);
        }
        
        // Штраф за старые записи
        if (isset($item['updated_at'])) {
            $daysSinceUpdate = (time() - strtotime($item['updated_at'])) / 86400;
            $bonusFactors -= min($daysSinceUpdate / 365, 0.2);
        }
        
        return ($baseScore + $bonusFactors) * $weight;
    }

    /**
     * Форматировать отдельный элемент
     */
    private function formatSingleItem($item, array $options): array
    {
        $itemArray = is_object($item) ? $item->toArray() : $item;
        
        // Базовое форматирование
        $formatted = [
            'id' => $itemArray['id'] ?? null,
            'title' => $itemArray['title'] ?? $itemArray['name'] ?? 'Без названия',
            'description' => $this->truncateText($itemArray['description'] ?? '', $options['description_length'] ?? 200),
            'url' => $this->generateUrl($itemArray),
            'image' => $this->getMainImage($itemArray),
            'price' => $this->formatPrice($itemArray),
            'rating' => $this->formatRating($itemArray),
            'location' => $this->formatLocation($itemArray)
        ];
        
        // Добавляем дополнительные поля если запрошены
        if ($options['include_meta'] ?? false) {
            $formatted['meta'] = [
                'created_at' => $itemArray['created_at'] ?? null,
                'updated_at' => $itemArray['updated_at'] ?? null,
                'views_count' => $itemArray['views_count'] ?? 0,
                'source' => $itemArray['_source'] ?? 'database'
            ];
        }
        
        return $formatted;
    }

    /**
     * Экспорт в CSV
     */
    private function exportToCsv(array $items, array $options): string
    {
        $csv = '';
        $delimiter = $options['delimiter'] ?? ',';
        $enclosure = $options['enclosure'] ?? '"';
        
        if (empty($items)) {
            return $csv;
        }
        
        // Заголовки
        $firstItem = is_object($items[0]) ? $items[0]->toArray() : $items[0];
        $headers = array_keys($firstItem);
        $csv .= implode($delimiter, array_map(function($header) use ($enclosure) {
            return $enclosure . $header . $enclosure;
        }, $headers)) . "\n";
        
        // Данные
        foreach ($items as $item) {
            $itemArray = is_object($item) ? $item->toArray() : $item;
            $row = array_map(function($value) use ($enclosure) {
                return $enclosure . str_replace($enclosure, $enclosure . $enclosure, (string) $value) . $enclosure;
            }, $itemArray);
            $csv .= implode($delimiter, $row) . "\n";
        }
        
        return $csv;
    }

    /**
     * Экспорт в JSON
     */
    private function exportToJson(array $items, array $options): string
    {
        $data = [
            'export_date' => date('Y-m-d H:i:s'),
            'total_items' => count($items),
            'items' => $items
        ];
        
        $flags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
        if ($options['compact'] ?? false) {
            $flags = JSON_UNESCAPED_UNICODE;
        }
        
        return json_encode($data, $flags);
    }

    /**
     * Экспорт в XML
     */
    private function exportToXml(array $items, array $options): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><export></export>');
        $xml->addAttribute('date', date('Y-m-d H:i:s'));
        $xml->addAttribute('total', count($items));
        
        $itemsNode = $xml->addChild('items');
        
        foreach ($items as $item) {
            $itemArray = is_object($item) ? $item->toArray() : $item;
            $itemNode = $itemsNode->addChild('item');
            
            foreach ($itemArray as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                $itemNode->addChild($key, htmlspecialchars((string) $value));
            }
        }
        
        return $xml->asXML();
    }

    /**
     * Экспорт в Excel (заглушка)
     */
    private function exportToExcel(array $items, array $options): string
    {
        // Здесь нужна библиотека типа PhpSpreadsheet
        // Пока возвращаем CSV
        return $this->exportToCsv($items, $options);
    }

    /**
     * Генерировать статистику
     */
    private function generateStatistics(LengthAwarePaginator $results): array
    {
        $items = collect($results->items());
        
        return [
            'count' => $items->count(),
            'total' => $results->total(),
            'avg_rating' => $items->avg('rating'),
            'price_range' => [
                'min' => $items->min('price'),
                'max' => $items->max('price'),
                'avg' => $items->avg('price')
            ]
        ];
    }

    /**
     * Другие вспомогательные методы
     */
    private function generateSummary(LengthAwarePaginator $results): string
    {
        $total = $results->total();
        $page = $results->currentPage();
        $totalPages = $results->lastPage();
        
        return "Найдено {$total} результатов. Страница {$page} из {$totalPages}";
    }

    private function getSortOptions(): array
    {
        return [
            'relevance' => 'По relevanceпоходящности',
            'rating' => 'По рейтингу',
            'price_asc' => 'По цене (возрастание)',
            'price_desc' => 'По цене (убывание)',
            'newest' => 'Сначала новые',
            'popular' => 'По популярности'
        ];
    }

    private function suggestFilters(LengthAwarePaginator $results): array
    {
        // Анализируем результаты и предлагаем фильтры
        return [
            'city' => 'Уточнить город',
            'price' => 'Установить ценовой диапазон',
            'rating' => 'Выбрать минимальный рейтинг'
        ];
    }

    private function calculateItemStatistics(Collection $items): array
    {
        return [
            'count' => $items->count(),
            'avg_price' => $items->avg('price'),
            'avg_rating' => $items->avg('rating'),
            'unique_cities' => $items->pluck('city')->unique()->count()
        ];
    }

    private function calculateDistribution(Collection $items): array
    {
        return [
            'by_city' => $items->countBy('city'),
            'by_rating' => $items->groupBy(function($item) {
                return floor(($item['rating'] ?? 0));
            })->map->count(),
            'by_price_range' => $items->groupBy(function($item) {
                $price = $item['price'] ?? 0;
                if ($price < 1000) return '< 1000';
                if ($price < 3000) return '1000-3000';
                if ($price < 5000) return '3000-5000';
                return '> 5000';
            })->map->count()
        ];
    }

    private function calculateQualityMetrics(Collection $items): array
    {
        return [
            'completeness' => $this->calculateCompleteness($items),
            'freshness' => $this->calculateFreshness($items),
            'diversity' => $this->calculateDiversity($items)
        ];
    }

    private function calculateCompleteness(Collection $items): float
    {
        $requiredFields = ['title', 'description', 'price', 'rating'];
        $totalFields = count($requiredFields) * $items->count();
        $filledFields = 0;
        
        foreach ($items as $item) {
            foreach ($requiredFields as $field) {
                if (!empty($item[$field])) {
                    $filledFields++;
                }
            }
        }
        
        return $totalFields > 0 ? ($filledFields / $totalFields) * 100 : 0;
    }

    private function calculateFreshness(Collection $items): float
    {
        $now = time();
        $totalAge = 0;
        $itemsWithDate = 0;
        
        foreach ($items as $item) {
            if (isset($item['updated_at'])) {
                $totalAge += ($now - strtotime($item['updated_at'])) / 86400; // дни
                $itemsWithDate++;
            }
        }
        
        if ($itemsWithDate === 0) return 0;
        
        $avgAge = $totalAge / $itemsWithDate;
        return max(0, 100 - ($avgAge / 30 * 100)); // свежесть в % (30 дней = 0%)
    }

    private function calculateDiversity(Collection $items): float
    {
        $categories = $items->pluck('category')->unique()->count();
        $totalItems = $items->count();
        
        return $totalItems > 0 ? ($categories / $totalItems) * 100 : 0;
    }

    private function applyEnrichment(array $item, string $enrichment): array
    {
        switch ($enrichment) {
            case 'location_details':
                // Добавляем детали местоположения
                if (isset($item['city'])) {
                    $item['location_details'] = $this->getLocationDetails($item['city']);
                }
                break;
                
            case 'contact_info':
                // Добавляем контактную информацию
                if (isset($item['user_id'])) {
                    $item['contact_info'] = $this->getContactInfo($item['user_id']);
                }
                break;
        }
        
        return $item;
    }

    private function getResponseTime(): float
    {
        return microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
    }

    private function truncateText(string $text, int $length): string
    {
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
    }

    private function generateUrl(array $item): string
    {
        $type = $item['_source'] ?? 'ads';
        $id = $item['id'] ?? 0;
        
        return url("/{$type}/{$id}");
    }

    private function getMainImage(array $item): ?string
    {
        if (isset($item['photos']) && is_array($item['photos']) && !empty($item['photos'])) {
            return $item['photos'][0];
        }
        
        return $item['image'] ?? $item['avatar'] ?? null;
    }

    private function formatPrice(array $item): ?array
    {
        if (!isset($item['price']) && !isset($item['price_per_hour'])) {
            return null;
        }
        
        $price = $item['price'] ?? $item['price_per_hour'];
        
        return [
            'amount' => $price,
            'currency' => 'RUB',
            'formatted' => number_format($price, 0, '.', ' ') . ' ₽'
        ];
    }

    private function formatRating(array $item): ?array
    {
        if (!isset($item['rating'])) {
            return null;
        }
        
        return [
            'value' => (float) $item['rating'],
            'count' => $item['reviews_count'] ?? 0,
            'stars' => str_repeat('★', floor($item['rating'])) . str_repeat('☆', 5 - floor($item['rating']))
        ];
    }

    private function formatLocation(array $item): ?array
    {
        if (!isset($item['city']) && !isset($item['location'])) {
            return null;
        }
        
        return [
            'city' => $item['city'] ?? null,
            'address' => $item['address'] ?? null,
            'coordinates' => $item['geo'] ?? null
        ];
    }

    private function getLocationDetails(string $city): array
    {
        // Заглушка для получения деталей города
        return [
            'region' => 'Московская область',
            'timezone' => 'UTC+3',
            'population' => 12000000
        ];
    }

    private function getContactInfo(int $userId): array
    {
        // Заглушка для получения контактов
        return [
            'phone_verified' => true,
            'email_verified' => true,
            'response_rate' => 95
        ];
    }
}