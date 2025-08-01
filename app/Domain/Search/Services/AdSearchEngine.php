<?php

namespace App\Domain\Search\Services;

use App\Models\Ad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Движок поиска объявлений
 */
class AdSearchEngine extends BaseSearchEngine
{
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
        // Город
        if (!empty($filters['city'])) {
            $builder->where('ads.city', $filters['city']);
        }

        // Диапазон цен
        if (!empty($filters['price_range'])) {
            if (is_array($filters['price_range'])) {
                [$min, $max] = $filters['price_range'];
            } else {
                [$min, $max] = explode('-', $filters['price_range']);
            }
            $builder->whereBetween('ads.price', [(int)$min, (int)$max]);
        }

        // Минимальный рейтинг мастера
        if (!empty($filters['rating'])) {
            $builder->where('users.rating', '>=', (float)$filters['rating']);
        }

        // Опыт мастера
        if (!empty($filters['experience'])) {
            $builder->where('users.experience_years', '>=', (int)$filters['experience']);
        }

        // Тип услуги
        if (!empty($filters['service_type'])) {
            $builder->where('ads.specialty', $filters['service_type']);
        }

        // Доступность
        if (!empty($filters['availability'])) {
            $builder->where('ads.is_available', true);
        }

        // Тип объявления
        if (!empty($filters['ad_type'])) {
            $builder->where('ads.ad_type', $filters['ad_type']);
        }

        // Формат работы
        if (!empty($filters['work_format'])) {
            $builder->where('ads.work_format', $filters['work_format']);
        }

        // Проверенные мастера
        if (!empty($filters['verified'])) {
            $builder->where('users.is_verified', true);
        }

        // Дата создания
        if (!empty($filters['created_since'])) {
            $builder->where('ads.created_at', '>=', $filters['created_since']);
        }

        // Наличие фото
        if (!empty($filters['has_photos'])) {
            $builder->whereHas('media', function($q) {
                $q->where('type', 'image');
            });
        }

        // Наличие отзывов
        if (!empty($filters['has_reviews'])) {
            $builder->where('users.reviews_count', '>', 0);
        }

        // Минимальное количество отзывов
        if (!empty($filters['min_reviews'])) {
            $builder->where('users.reviews_count', '>=', (int)$filters['min_reviews']);
        }

        // Премиум объявления
        if (!empty($filters['premium'])) {
            $builder->where('ads.is_premium', true);
        }

        // Регион/область
        if (!empty($filters['region'])) {
            $builder->where('ads.region', $filters['region']);
        }

        // Метро (если есть)
        if (!empty($filters['metro'])) {
            $builder->where('ads.metro_station', $filters['metro']);
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
        return [
            'id' => $item->id,
            'title' => $item->title,
            'price' => $item->price,
            'city' => $item->city,
            'master_name' => $item->user->name,
            'master_rating' => $item->user->rating,
            'image' => $item->media->first()?->url,
            'url' => route('ads.show', $item->id),
        ];
    }

    /**
     * Форматировать результат поиска похожих
     */
    protected function formatSimilarResult($item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'price' => $item->price,
            'city' => $item->city,
            'specialty' => $item->specialty,
            'master_name' => $item->user->name,
            'master_rating' => $item->user->rating,
            'similarity_score' => $item->similarity_score ?? 0,
            'image' => $item->media->first()?->url,
            'url' => route('ads.show', $item->id),
        ];
    }

    /**
     * Форматировать результат геопоиска
     */
    protected function formatGeoResult($item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'price' => $item->price,
            'city' => $item->city,
            'address' => $item->address,
            'latitude' => $item->latitude,
            'longitude' => $item->longitude,
            'distance' => round($item->distance ?? 0, 2),
            'master_name' => $item->user->name,
            'url' => route('ads.show', $item->id),
        ];
    }

    /**
     * Применить фильтры для поиска похожих объектов
     */
    protected function applySimilarityFilters(Builder $builder, $ad): void
    {
        // Похожие по специализации
        $builder->where('ads.specialty', $ad->specialty);
        
        // Похожий ценовой диапазон (±30%)
        $priceMin = $ad->price * 0.7;
        $priceMax = $ad->price * 1.3;
        $builder->whereBetween('ads.price', [$priceMin, $priceMax]);
        
        // Тот же город или регион
        $builder->where(function($q) use ($ad) {
            $q->where('ads.city', $ad->city)
              ->orWhere('ads.region', $ad->region);
        });
        
        // Добавляем счет похожести
        $builder->addSelect(DB::raw("
            (
                CASE WHEN ads.specialty = '{$ad->specialty}' THEN 3 ELSE 0 END +
                CASE WHEN ads.city = '{$ad->city}' THEN 2 ELSE 0 END +
                CASE WHEN ads.work_format = '{$ad->work_format}' THEN 1 ELSE 0 END +
                CASE WHEN ads.region = '{$ad->region}' THEN 1 ELSE 0 END
            ) as similarity_score
        "));
        
        $builder->orderBy('similarity_score', 'desc');
    }

    /**
     * Применить продвинутые фильтры
     */
    protected function applyAdvancedFilters(Builder $builder, array $criteria): void
    {
        // Исключить объявления
        if (!empty($criteria['exclude_ids'])) {
            $builder->whereNotIn('ads.id', $criteria['exclude_ids']);
        }

        // Конкретные мастера
        if (!empty($criteria['master_ids'])) {
            $builder->whereIn('ads.user_id', $criteria['master_ids']);
        }

        // Диапазон дат
        if (!empty($criteria['date_from'])) {
            $builder->where('ads.created_at', '>=', $criteria['date_from']);
        }
        
        if (!empty($criteria['date_to'])) {
            $builder->where('ads.created_at', '<=', $criteria['date_to']);
        }

        // Сложные фильтры по рейтингу и отзывам
        if (!empty($criteria['rating_reviews_combo'])) {
            $builder->where(function($q) use ($criteria) {
                $combo = $criteria['rating_reviews_combo'];
                if ($combo === 'high_rated') {
                    $q->where('users.rating', '>=', 4.5)
                      ->where('users.reviews_count', '>=', 10);
                } elseif ($combo === 'popular') {
                    $q->where('users.reviews_count', '>=', 20);
                } elseif ($combo === 'new_good') {
                    $q->where('users.rating', '>=', 4.0)
                      ->where('ads.created_at', '>=', now()->subDays(30));
                }
            });
        }

        // Радиус поиска
        if (!empty($criteria['location']) && !empty($criteria['radius'])) {
            $location = $criteria['location'];
            $radius = $criteria['radius'];
            
            $this->addDistanceCalculation($builder, $location['lat'], $location['lng']);
            $builder->having('distance', '<=', $radius);
        }
    }

    /**
     * Вычислить фасет
     */
    protected function calculateFacet(Builder $builder, string $facet): array
    {
        $clonedBuilder = clone $builder;
        
        return match($facet) {
            'cities' => $this->calculateCitiesFacet($clonedBuilder),
            'specialties' => $this->calculateSpecialtiesFacet($clonedBuilder),
            'price_ranges' => $this->calculatePriceRangesFacet($clonedBuilder),
            'ratings' => $this->calculateRatingsFacet($clonedBuilder),
            'work_formats' => $this->calculateWorkFormatsFacet($clonedBuilder),
            default => []
        };
    }

    /**
     * Вычислить фасет городов
     */
    protected function calculateCitiesFacet(Builder $builder): array
    {
        return $builder
            ->select('ads.city', DB::raw('count(*) as count'))
            ->groupBy('ads.city')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->pluck('count', 'city')
            ->toArray();
    }

    /**
     * Вычислить фасет специализаций
     */
    protected function calculateSpecialtiesFacet(Builder $builder): array
    {
        return $builder
            ->select('ads.specialty', DB::raw('count(*) as count'))
            ->groupBy('ads.specialty')
            ->orderBy('count', 'desc')
            ->limit(15)
            ->pluck('count', 'specialty')
            ->toArray();
    }

    /**
     * Вычислить фасет ценовых диапазонов
     */
    protected function calculatePriceRangesFacet(Builder $builder): array
    {
        $ranges = [
            '0-1000' => [0, 1000],
            '1000-2000' => [1000, 2000],
            '2000-3000' => [2000, 3000],
            '3000-5000' => [3000, 5000],
            '5000+' => [5000, PHP_INT_MAX],
        ];
        
        $result = [];
        foreach ($ranges as $label => $range) {
            $count = (clone $builder)
                ->whereBetween('ads.price', $range)
                ->count();
            
            if ($count > 0) {
                $result[$label] = $count;
            }
        }
        
        return $result;
    }

    /**
     * Вычислить фасет рейтингов
     */
    protected function calculateRatingsFacet(Builder $builder): array
    {
        $ranges = [
            '4.5+' => [4.5, 5.0],
            '4.0+' => [4.0, 5.0],
            '3.5+' => [3.5, 5.0],
            '3.0+' => [3.0, 5.0],
        ];
        
        $result = [];
        foreach ($ranges as $label => $range) {
            $count = (clone $builder)
                ->whereBetween('users.rating', $range)
                ->count();
                
            if ($count > 0) {
                $result[$label] = $count;
            }
        }
        
        return $result;
    }

    /**
     * Вычислить фасет форматов работы
     */
    protected function calculateWorkFormatsFacet(Builder $builder): array
    {
        return $builder
            ->select('ads.work_format', DB::raw('count(*) as count'))
            ->whereNotNull('ads.work_format')
            ->groupBy('ads.work_format')
            ->orderBy('count', 'desc')
            ->pluck('count', 'work_format')
            ->toArray();
    }

    /**
     * Получить заголовки для CSV
     */
    protected function getCsvHeaders(): array
    {
        return [
            'ID',
            'Заголовок',
            'Описание',
            'Цена',
            'Город',
            'Специализация',
            'Мастер',
            'Рейтинг мастера',
            'Количество отзывов',
            'Дата создания',
            'URL'
        ];
    }

    /**
     * Форматировать строку CSV
     */
    protected function formatCsvRow($item): array
    {
        return [
            $item->id,
            $item->title,
            strip_tags($item->description),
            $item->price,
            $item->city,
            $item->specialty,
            $item->user->name,
            $item->user->rating,
            $item->user->reviews_count,
            $item->created_at->format('d.m.Y H:i'),
            route('ads.show', $item->id)
        ];
    }

    /**
     * Получить алиас таблицы
     */
    protected function getTableAlias(): string
    {
        return 'ads';
    }
}