<?php

namespace App\Domain\Search\Services;

use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Движок поиска услуг
 */
class ServiceSearchEngine extends BaseSearchEngine
{
    protected function getBaseQuery(): Builder
    {
        return Service::query()
            ->with(['category', 'media'])
            ->where('status', 'active');
    }

    protected function applyTextSearch(Builder $builder, string $query): void
    {
        $relevanceScore = $this->getRelevanceScore($query, [
            'services.name' => 4.0,
            'services.description' => 2.5,
        ]);
        
        $builder->addSelect(DB::raw($relevanceScore . ' as relevance_score'));
        
        $searchTerms = $this->parseSearchQuery($query);
        
        $builder->where(function($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->orWhere('name', 'LIKE', "%{$term}%")
                  ->orWhere('description', 'LIKE', "%{$term}%");
            }
        });
    }

    protected function applyFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['category'])) {
            $builder->where('category', $filters['category']);
        }

        if (!empty($filters['price_range'])) {
            [$min, $max] = is_array($filters['price_range']) 
                ? $filters['price_range'] 
                : explode('-', $filters['price_range']);
            $builder->whereBetween('price', [(int)$min, (int)$max]);
        }

        if (!empty($filters['duration'])) {
            $builder->where('duration_minutes', '<=', (int)$filters['duration']);
        }
    }

    protected function getIdField(): string
    {
        return 'services.id';
    }

    protected function formatQuickResult($item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'price' => $item->price,
            'duration' => $item->duration_minutes,
            'url' => route('services.show', $item->id),
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