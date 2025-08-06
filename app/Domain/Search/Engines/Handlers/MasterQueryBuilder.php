<?php

namespace App\Domain\Search\Engines\Handlers;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Построитель запросов для поиска мастеров
 */
class MasterQueryBuilder
{
    /**
     * Получить базовый запрос для поиска мастеров
     */
    public function getBaseQuery(): Builder
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
     * Применить текстовый поиск с релевантностью
     */
    public function applyTextSearch(Builder $builder, string $query): void
    {
        $relevanceScore = $this->buildRelevanceScore($query, [
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
     * Применить фильтры для поиска похожих мастеров
     */
    public function applySimilarityFilters(Builder $builder, $master): void
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
     * Построить SQL для подсчета релевантности
     */
    private function buildRelevanceScore(string $query, array $fieldWeights): string
    {
        $terms = $this->parseSearchQuery($query);
        $scoreParts = [];
        
        foreach ($fieldWeights as $field => $weight) {
            foreach ($terms as $term) {
                $scoreParts[] = "CASE WHEN {$field} LIKE '%{$term}%' THEN {$weight} ELSE 0 END";
            }
        }
        
        return '(' . implode(' + ', $scoreParts) . ')';
    }

    /**
     * Парсить поисковый запрос
     */
    private function parseSearchQuery(string $query): array
    {
        // Удаляем лишние символы и разбиваем по словам
        $terms = preg_split('/\s+/', trim($query));
        
        // Фильтруем слова короче 2 символов
        return array_filter($terms, fn($term) => mb_strlen($term) >= 2);
    }

    /**
     * Получить поле ID для таблицы
     */
    public function getIdField(): string
    {
        return 'users.id';
    }

    /**
     * Получить алиас таблицы
     */
    public function getTableAlias(): string
    {
        return 'users';
    }
}