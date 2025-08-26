<?php

namespace App\Domain\Search\Transformers;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Трансформер результатов поиска
 */
class SearchResultsTransformer
{
    /**
     * Трансформировать hit из Elasticsearch
     */
    public function transformHit(array $hit): array
    {
        $source = $hit['_source'] ?? [];
        
        return array_merge($source, [
            '_id' => $hit['_id'] ?? null,
            '_index' => $hit['_index'] ?? null,
            '_score' => $hit['_score'] ?? null,
        ]);
    }
    
    /**
     * Обогатить результаты дополнительными данными
     */
    public function enrichResults(LengthAwarePaginator $results): LengthAwarePaginator
    {
        // Здесь можно добавить дополнительные данные к результатам
        // Например, информацию о категориях, изображения и т.д.
        return $results;
    }
    
    /**
     * Экспортировать результаты
     */
    public function exportResults(LengthAwarePaginator $results, string $format = 'csv'): string
    {
        switch ($format) {
            case 'json':
                return json_encode($results->items());
            case 'csv':
            default:
                return $this->exportToCsv($results);
        }
    }
    
    /**
     * Экспорт в CSV
     */
    private function exportToCsv(LengthAwarePaginator $results): string
    {
        $output = '';
        $items = $results->items();
        
        if (empty($items)) {
            return $output;
        }
        
        // Заголовки
        $firstItem = $items[0];
        if (is_array($firstItem)) {
            $headers = array_keys($firstItem);
            $output .= implode(',', $headers) . "\n";
            
            // Данные
            foreach ($items as $item) {
                $values = array_map(function($value) {
                    return is_string($value) ? '"' . str_replace('"', '""', $value) . '"' : $value;
                }, array_values($item));
                $output .= implode(',', $values) . "\n";
            }
        }
        
        return $output;
    }
}