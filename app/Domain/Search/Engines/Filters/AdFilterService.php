<?php

namespace App\Domain\Search\Engines\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Сервис фильтрации объявлений
 */
class AdFilterService
{
    /**
     * Применить базовые фильтры
     */
    public function applyBasicFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['category_id'])) {
            $builder->where('ads.category_id', $filters['category_id']);
        }

        if (!empty($filters['specialty'])) {
            $builder->where('ads.specialty', $filters['specialty']);
        }

        if (!empty($filters['city'])) {
            if (is_array($filters['city'])) {
                $builder->whereIn('ads.city', $filters['city']);
            } else {
                $builder->where('ads.city', $filters['city']);
            }
        }

        if (!empty($filters['district'])) {
            $builder->where('ads.district', $filters['district']);
        }

        if (!empty($filters['user_id'])) {
            $builder->where('ads.user_id', $filters['user_id']);
        }

        if (!empty($filters['is_premium'])) {
            $builder->where('ads.is_premium', true);
        }

        if (!empty($filters['has_reviews'])) {
            $builder->has('reviews');
        }

        if (!empty($filters['languages']) && is_array($filters['languages'])) {
            $builder->whereJsonContains('ads.languages', $filters['languages']);
        }

        if (!empty($filters['experience_years'])) {
            $builder->where('users.experience_years', '>=', $filters['experience_years']);
        }
    }

    /**
     * Применить ценовые фильтры
     */
    public function applyPriceFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['price_min'])) {
            $builder->where('ads.price', '>=', $filters['price_min']);
        }

        if (!empty($filters['price_max'])) {
            $builder->where('ads.price', '<=', $filters['price_max']);
        }

        if (!empty($filters['price_type'])) {
            $builder->where('ads.price_type', $filters['price_type']);
        }

        if (!empty($filters['negotiable_price'])) {
            $builder->where('ads.negotiable_price', true);
        }
    }

    /**
     * Применить фильтры рейтинга
     */
    public function applyRatingFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['rating_min'])) {
            $builder->where('users.rating', '>=', $filters['rating_min']);
        }

        if (!empty($filters['reviews_count_min'])) {
            $builder->where('users.reviews_count', '>=', $filters['reviews_count_min']);
        }
    }

    /**
     * Применить фильтры доступности
     */
    public function applyAvailabilityFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['work_format'])) {
            if (is_array($filters['work_format'])) {
                $builder->where(function($q) use ($filters) {
                    foreach ($filters['work_format'] as $format) {
                        $q->orWhereJsonContains('ads.work_format', $format);
                    }
                });
            } else {
                $builder->whereJsonContains('ads.work_format', $filters['work_format']);
            }
        }

        if (!empty($filters['available_now'])) {
            $builder->where('ads.available_now', true);
        }

        if (!empty($filters['online_booking'])) {
            $builder->where('ads.online_booking', true);
        }
    }

    /**
     * Применить временные фильтры
     */
    public function applyDateFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['created_after'])) {
            $builder->where('ads.created_at', '>=', $filters['created_after']);
        }

        if (!empty($filters['updated_after'])) {
            $builder->where('ads.updated_at', '>=', $filters['updated_after']);
        }

        if (!empty($filters['expires_before'])) {
            $builder->where('ads.expires_at', '<=', $filters['expires_before']);
        }
    }
}