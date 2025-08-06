<?php

namespace App\Domain\Search\Engines\Handlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Обработчик фильтров для поиска мастеров
 */
class MasterFilterHandler
{
    /**
     * Применить основные фильтры
     */
    public function applyFilters(Builder $builder, array $filters): void
    {
        $this->applyLocationFilters($builder, $filters);
        $this->applySpecialtyFilters($builder, $filters);
        $this->applyRatingFilters($builder, $filters);
        $this->applyPriceFilters($builder, $filters);
        $this->applyExperienceFilters($builder, $filters);
        $this->applyAvailabilityFilters($builder, $filters);
        $this->applyQualityFilters($builder, $filters);
        $this->applyPersonalFilters($builder, $filters);
        $this->applyLanguageFilters($builder, $filters);
        $this->applyPortfolioFilters($builder, $filters);
    }

    /**
     * Применить продвинутые фильтры
     */
    public function applyAdvancedFilters(Builder $builder, array $criteria): void
    {
        $this->applyExclusionFilters($builder, $criteria);
        $this->applyRegistrationFilters($builder, $criteria);
        $this->applyMasterLevelFilters($builder, $criteria);
        $this->applyActivityFilters($builder, $criteria);
        $this->applySpecialOfferFilters($builder, $criteria);
    }

    /**
     * Применить фильтры по местоположению
     */
    private function applyLocationFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['city'])) {
            $builder->where('users.city', $filters['city']);
        }

        if (!empty($filters['region'])) {
            $builder->where('users.region', $filters['region']);
        }
    }

    /**
     * Применить фильтры по специализации
     */
    private function applySpecialtyFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['specialty'])) {
            $builder->where('users.specialty', $filters['specialty']);
        }
    }

    /**
     * Применить фильтры по рейтингу
     */
    private function applyRatingFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['rating'])) {
            $builder->having('avg_rating', '>=', (float)$filters['rating']);
        }

        if (!empty($filters['min_reviews'])) {
            $builder->having('total_reviews', '>=', (int)$filters['min_reviews']);
        }
    }

    /**
     * Применить фильтры по цене
     */
    private function applyPriceFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['price_range'])) {
            if (is_array($filters['price_range'])) {
                [$min, $max] = $filters['price_range'];
            } else {
                [$min, $max] = explode('-', $filters['price_range']);
            }
            $builder->whereBetween('users.min_price', [(int)$min, (int)$max]);
        }
    }

    /**
     * Применить фильтры по опыту
     */
    private function applyExperienceFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['experience'])) {
            $builder->where('users.experience_years', '>=', (int)$filters['experience']);
        }
    }

    /**
     * Применить фильтры по доступности
     */
    private function applyAvailabilityFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['availability'])) {
            $builder->where('users.is_available', true);
        }

        if (!empty($filters['online'])) {
            $builder->where('users.last_activity_at', '>=', now()->subMinutes(15));
        }
    }

    /**
     * Применить фильтры качества
     */
    private function applyQualityFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['verified'])) {
            $builder->where('users.is_verified', true);
        }

        if (!empty($filters['premium'])) {
            $builder->where('users.is_premium', true);
        }

        if (!empty($filters['has_certificates'])) {
            $builder->whereHas('certificates');
        }
    }

    /**
     * Применить персональные фильтры
     */
    private function applyPersonalFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['gender'])) {
            $builder->where('users.gender', $filters['gender']);
        }

        if (!empty($filters['age_range'])) {
            $this->applyAgeRangeFilter($builder, $filters['age_range']);
        }
    }

    /**
     * Применить фильтр по возрасту
     */
    private function applyAgeRangeFilter(Builder $builder, $ageRange): void
    {
        if (is_array($ageRange)) {
            [$minAge, $maxAge] = $ageRange;
        } else {
            [$minAge, $maxAge] = explode('-', $ageRange);
        }
        
        $minBirthYear = now()->year - (int)$maxAge;
        $maxBirthYear = now()->year - (int)$minAge;
        
        $builder->whereBetween(DB::raw('YEAR(users.birth_date)'), [$minBirthYear, $maxBirthYear]);
    }

    /**
     * Применить языковые фильтры
     */
    private function applyLanguageFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['languages'])) {
            $languages = is_array($filters['languages']) ? $filters['languages'] : [$filters['languages']];
            $builder->where(function($q) use ($languages) {
                foreach ($languages as $language) {
                    $q->orWhere('users.languages', 'LIKE', "%{$language}%");
                }
            });
        }
    }

    /**
     * Применить фильтры портфолио
     */
    private function applyPortfolioFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['has_portfolio'])) {
            $builder->whereHas('media', function($q) {
                $q->where('type', 'portfolio');
            });
        }
    }

    /**
     * Применить фильтры исключения
     */
    private function applyExclusionFilters(Builder $builder, array $criteria): void
    {
        if (!empty($criteria['exclude_ids'])) {
            $builder->whereNotIn('users.id', $criteria['exclude_ids']);
        }
    }

    /**
     * Применить фильтры по дате регистрации
     */
    private function applyRegistrationFilters(Builder $builder, array $criteria): void
    {
        if (!empty($criteria['registered_from'])) {
            $builder->where('users.created_at', '>=', $criteria['registered_from']);
        }
        
        if (!empty($criteria['registered_to'])) {
            $builder->where('users.created_at', '<=', $criteria['registered_to']);
        }
    }

    /**
     * Применить фильтры уровня мастера
     */
    private function applyMasterLevelFilters(Builder $builder, array $criteria): void
    {
        if (empty($criteria['master_level'])) {
            return;
        }

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

    /**
     * Применить фильтры по активности
     */
    private function applyActivityFilters(Builder $builder, array $criteria): void
    {
        if (empty($criteria['activity_level'])) {
            return;
        }

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

    /**
     * Применить фильтры специальных предложений
     */
    private function applySpecialOfferFilters(Builder $builder, array $criteria): void
    {
        if (!empty($criteria['has_special_offers'])) {
            $builder->whereHas('ads', function($q) {
                $q->where('has_discount', true)
                  ->orWhere('is_featured', true);
            });
        }
    }
}