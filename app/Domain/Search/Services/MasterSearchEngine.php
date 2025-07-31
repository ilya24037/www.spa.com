<?php

namespace App\Services\Search;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Движок поиска мастеров
 */
class MasterSearchEngine extends BaseSearchEngine
{
    /**
     * Получить базовый запрос
     */
    protected function getBaseQuery(): Builder
    {
        return User::query()
            ->select([
                'users.*',
                DB::raw('COUNT(DISTINCT ads.id) as ads_count'),
                DB::raw('COUNT(DISTINCT reviews.id) as total_reviews'),
                DB::raw('AVG(reviews.rating) as avg_rating')
            ])
            ->leftJoin('ads', function($join) {
                $join->on('users.id', '=', 'ads.user_id')
                     ->where('ads.status', 'active')
                     ->where('ads.is_published', true);
            })
            ->leftJoin('reviews', 'users.id', '=', 'reviews.master_id')
            ->with([
                'ads' => function($query) {
                    $query->where('status', 'active')
                          ->where('is_published', true)
                          ->orderBy('created_at', 'desc')
                          ->limit(5);
                },
                'media' => function($query) {
                    $query->where('type', 'avatar')
                          ->orWhere('type', 'portfolio');
                },
                'reviews' => function($query) {
                    $query->with('user:id,name,avatar')
                          ->orderBy('created_at', 'desc')
                          ->limit(5);
                }
            ])
            ->where('users.is_master', true)
            ->where('users.is_active', true)
            ->groupBy('users.id');
    }

    /**
     * Применить текстовый поиск
     */
    protected function applyTextSearch(Builder $builder, string $query): void
    {
        $relevanceScore = $this->getRelevanceScore($query, [
            'users.name' => 4.0,
            'users.specialty' => 3.5,
            'users.description' => 2.5,
            'users.city' => 2.0,
            'users.services_description' => 2.2,
        ]);
        
        $builder->addSelect(DB::raw($relevanceScore . ' as relevance_score'));
        
        $searchTerms = $this->parseSearchQuery($query);
        
        $builder->where(function($q) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $q->orWhere('users.name', 'LIKE', "%{$term}%")
                  ->orWhere('users.specialty', 'LIKE', "%{$term}%")
                  ->orWhere('users.description', 'LIKE', "%{$term}%")
                  ->orWhere('users.city', 'LIKE', "%{$term}%")
                  ->orWhere('users.services_description', 'LIKE', "%{$term}%");
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
            $builder->where('users.city', $filters['city']);
        }

        // Специализация
        if (!empty($filters['specialty'])) {
            $builder->where('users.specialty', $filters['specialty']);
        }

        // Минимальный рейтинг
        if (!empty($filters['rating'])) {
            $builder->having('avg_rating', '>=', (float)$filters['rating']);
        }

        // Опыт работы
        if (!empty($filters['experience'])) {
            $builder->where('users.experience_years', '>=', (int)$filters['experience']);
        }

        // Диапазон цен
        if (!empty($filters['price_range'])) {
            if (is_array($filters['price_range'])) {
                [$min, $max] = $filters['price_range'];
            } else {
                [$min, $max] = explode('-', $filters['price_range']);
            }
            $builder->whereBetween('users.min_price', [(int)$min, (int)$max]);
        }

        // Доступность
        if (!empty($filters['availability'])) {
            $builder->where('users.is_available', true);
        }

        // Проверенные мастера
        if (!empty($filters['verified'])) {
            $builder->where('users.is_verified', true);
        }

        // Минимальное количество отзывов
        if (!empty($filters['min_reviews'])) {
            $builder->having('total_reviews', '>=', (int)$filters['min_reviews']);
        }

        // Онлайн в данный момент
        if (!empty($filters['online'])) {
            $builder->where('users.last_activity_at', '>=', now()->subMinutes(15));
        }

        // Премиум мастера
        if (!empty($filters['premium'])) {
            $builder->where('users.is_premium', true);
        }

        // Регион
        if (!empty($filters['region'])) {
            $builder->where('users.region', $filters['region']);
        }

        // Пол мастера
        if (!empty($filters['gender'])) {
            $builder->where('users.gender', $filters['gender']);
        }

        // Возраст мастера
        if (!empty($filters['age_range'])) {
            if (is_array($filters['age_range'])) {
                [$minAge, $maxAge] = $filters['age_range'];
            } else {
                [$minAge, $maxAge] = explode('-', $filters['age_range']);
            }
            
            $minBirthYear = now()->year - (int)$maxAge;
            $maxBirthYear = now()->year - (int)$minAge;
            
            $builder->whereBetween(DB::raw('YEAR(users.birth_date)'), [$minBirthYear, $maxBirthYear]);
        }

        // Языки
        if (!empty($filters['languages'])) {
            $languages = is_array($filters['languages']) ? $filters['languages'] : [$filters['languages']];
            $builder->where(function($q) use ($languages) {
                foreach ($languages as $language) {
                    $q->orWhere('users.languages', 'LIKE', "%{$language}%");
                }
            });
        }

        // Сертификаты
        if (!empty($filters['has_certificates'])) {
            $builder->whereHas('certificates');
        }

        // Наличие портфолио
        if (!empty($filters['has_portfolio'])) {
            $builder->whereHas('media', function($q) {
                $q->where('type', 'portfolio');
            });
        }
    }

    /**
     * Получить поле ID
     */
    protected function getIdField(): string
    {
        return 'users.id';
    }

    /**
     * Форматировать результат быстрого поиска
     */
    protected function formatQuickResult($item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'specialty' => $item->specialty,
            'city' => $item->city,
            'rating' => $item->rating,
            'reviews_count' => $item->reviews_count,
            'min_price' => $item->min_price,
            'avatar' => $item->media->where('type', 'avatar')->first()?->url,
            'is_online' => $item->last_activity_at >= now()->subMinutes(15),
            'url' => route('masters.show', $item->id),
        ];
    }

    /**
     * Форматировать результат поиска похожих
     */
    protected function formatSimilarResult($item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'specialty' => $item->specialty,
            'city' => $item->city,
            'rating' => $item->rating,
            'reviews_count' => $item->reviews_count,
            'experience_years' => $item->experience_years,
            'min_price' => $item->min_price,
            'similarity_score' => $item->similarity_score ?? 0,
            'avatar' => $item->media->where('type', 'avatar')->first()?->url,
            'url' => route('masters.show', $item->id),
        ];
    }

    /**
     * Форматировать результат геопоиска
     */
    protected function formatGeoResult($item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'specialty' => $item->specialty,
            'city' => $item->city,
            'address' => $item->address,
            'latitude' => $item->latitude,
            'longitude' => $item->longitude,
            'distance' => round($item->distance ?? 0, 2),
            'rating' => $item->rating,
            'min_price' => $item->min_price,
            'url' => route('masters.show', $item->id),
        ];
    }

    /**
     * Применить фильтры для поиска похожих объектов
     */
    protected function applySimilarityFilters(Builder $builder, $master): void
    {
        // Похожие по специализации
        $builder->where('users.specialty', $master->specialty);
        
        // Похожий ценовой диапазон (±40%)
        if ($master->min_price) {
            $priceMin = $master->min_price * 0.6;
            $priceMax = $master->min_price * 1.4;
            $builder->whereBetween('users.min_price', [$priceMin, $priceMax]);
        }
        
        // Тот же город или регион
        $builder->where(function($q) use ($master) {
            $q->where('users.city', $master->city)
              ->orWhere('users.region', $master->region);
        });
        
        // Похожий опыт (±2 года)
        if ($master->experience_years) {
            $expMin = max(0, $master->experience_years - 2);
            $expMax = $master->experience_years + 2;
            $builder->whereBetween('users.experience_years', [$expMin, $expMax]);
        }
        
        // Добавляем счет похожести
        $builder->addSelect(DB::raw("
            (
                CASE WHEN users.specialty = '{$master->specialty}' THEN 4 ELSE 0 END +
                CASE WHEN users.city = '{$master->city}' THEN 3 ELSE 0 END +
                CASE WHEN users.region = '{$master->region}' THEN 2 ELSE 0 END +
                CASE WHEN users.gender = '{$master->gender}' THEN 1 ELSE 0 END +
                CASE WHEN ABS(users.experience_years - {$master->experience_years}) <= 2 THEN 2 ELSE 0 END
            ) as similarity_score
        "));
        
        $builder->orderBy('similarity_score', 'desc');
    }

    /**
     * Применить продвинутые фильтры
     */
    protected function applyAdvancedFilters(Builder $builder, array $criteria): void
    {
        // Исключить мастеров
        if (!empty($criteria['exclude_ids'])) {
            $builder->whereNotIn('users.id', $criteria['exclude_ids']);
        }

        // Диапазон дат регистрации
        if (!empty($criteria['registered_from'])) {
            $builder->where('users.created_at', '>=', $criteria['registered_from']);
        }
        
        if (!empty($criteria['registered_to'])) {
            $builder->where('users.created_at', '<=', $criteria['registered_to']);
        }

        // Комбинированные фильтры
        if (!empty($criteria['master_level'])) {
            $level = $criteria['master_level'];
            
            if ($level === 'top') {
                $builder->where('users.rating', '>=', 4.7)
                        ->having('total_reviews', '>=', 50)
                        ->where('users.experience_years', '>=', 3);
            } elseif ($level === 'experienced') {
                $builder->where('users.rating', '>=', 4.0)
                        ->having('total_reviews', '>=', 10)
                        ->where('users.experience_years', '>=', 1);
            } elseif ($level === 'newcomer') {
                $builder->where('users.created_at', '>=', now()->subMonths(6))
                        ->where('users.experience_years', '<=', 1);
            }
        }

        // Активность мастера
        if (!empty($criteria['activity_level'])) {
            $activity = $criteria['activity_level'];
            
            if ($activity === 'very_active') {
                $builder->where('users.last_activity_at', '>=', now()->subDays(1))
                        ->having('ads_count', '>=', 3);
            } elseif ($activity === 'active') {
                $builder->where('users.last_activity_at', '>=', now()->subDays(7))
                        ->having('ads_count', '>=', 1);
            } elseif ($activity === 'inactive') {
                $builder->where('users.last_activity_at', '<=', now()->subDays(30));
            }
        }

        // Специальные предложения
        if (!empty($criteria['has_special_offers'])) {
            $builder->whereHas('ads', function($q) {
                $q->where('has_discount', true)
                  ->orWhere('is_featured', true);
            });
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
            'experience_levels' => $this->calculateExperienceLevelsFacet($clonedBuilder),
            'price_ranges' => $this->calculatePriceRangesFacet($clonedBuilder),
            'ratings' => $this->calculateRatingsFacet($clonedBuilder),
            'genders' => $this->calculateGendersFacet($clonedBuilder),
            default => []
        };
    }

    /**
     * Вычислить фасет городов
     */
    protected function calculateCitiesFacet(Builder $builder): array
    {
        return $builder
            ->select('users.city', DB::raw('count(*) as count'))
            ->groupBy('users.city')
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
            ->select('users.specialty', DB::raw('count(*) as count'))
            ->groupBy('users.specialty')
            ->orderBy('count', 'desc')
            ->limit(15)
            ->pluck('count', 'specialty')
            ->toArray();
    }

    /**
     * Вычислить фасет уровней опыта
     */
    protected function calculateExperienceLevelsFacet(Builder $builder): array
    {
        $levels = [
            'Новичок (0-1 год)' => [0, 1],
            'Начинающий (1-3 года)' => [1, 3],
            'Опытный (3-7 лет)' => [3, 7],
            'Эксперт (7+ лет)' => [7, 100],
        ];
        
        $result = [];
        foreach ($levels as $label => $range) {
            $count = (clone $builder)
                ->whereBetween('users.experience_years', $range)
                ->count();
                
            if ($count > 0) {
                $result[$label] = $count;
            }
        }
        
        return $result;
    }

    /**
     * Вычислить фасет ценовых диапазонов
     */
    protected function calculatePriceRangesFacet(Builder $builder): array
    {
        $ranges = [
            'До 1500' => [0, 1500],
            '1500-2500' => [1500, 2500],
            '2500-4000' => [2500, 4000],
            '4000-6000' => [4000, 6000],
            'От 6000' => [6000, PHP_INT_MAX],
        ];
        
        $result = [];
        foreach ($ranges as $label => $range) {
            $count = (clone $builder)
                ->whereBetween('users.min_price', $range)
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
            '4.8+' => [4.8, 5.0],
            '4.5+' => [4.5, 5.0],
            '4.0+' => [4.0, 5.0],
            '3.5+' => [3.5, 5.0],
        ];
        
        $result = [];
        foreach ($ranges as $label => $range) {
            $count = (clone $builder)
                ->having('avg_rating', '>=', $range[0])
                ->count();
                
            if ($count > 0) {
                $result[$label] = $count;
            }
        }
        
        return $result;
    }

    /**
     * Вычислить фасет полов
     */
    protected function calculateGendersFacet(Builder $builder): array
    {
        return $builder
            ->select('users.gender', DB::raw('count(*) as count'))
            ->whereNotNull('users.gender')
            ->groupBy('users.gender')
            ->orderBy('count', 'desc')
            ->pluck('count', 'gender')
            ->toArray();
    }

    /**
     * Получить заголовки для CSV
     */
    protected function getCsvHeaders(): array
    {
        return [
            'ID',
            'Имя',
            'Специализация',
            'Город',
            'Рейтинг',
            'Количество отзывов',
            'Опыт (лет)',
            'Минимальная цена',
            'Проверен',
            'Дата регистрации',
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
            $item->name,
            $item->specialty,
            $item->city,
            $item->rating,
            $item->reviews_count,
            $item->experience_years,
            $item->min_price,
            $item->is_verified ? 'Да' : 'Нет',
            $item->created_at->format('d.m.Y'),
            route('masters.show', $item->id)
        ];
    }

    /**
     * Получить алиас таблицы
     */
    protected function getTableAlias(): string
    {
        return 'users';
    }
}