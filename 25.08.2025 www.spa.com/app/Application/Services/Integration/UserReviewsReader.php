<?php

namespace App\Application\Services\Integration;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Сервис чтения отзывов пользователей
 * Следует принципу Single Responsibility - только чтение данных
 */
class UserReviewsReader
{
    /**
     * Получить все отзывы о пользователе (для мастеров)
     * Заменяет: $user->receivedReviews()
     */
    public function getUserReceivedReviews(int $userId): Collection
    {
        // Получаем master_profile_id для пользователя
        $masterProfileId = DB::table('master_profiles')
            ->where('user_id', $userId)
            ->value('id');
            
        if (!$masterProfileId) {
            return new Collection();
        }

        // Используем реальные колонки таблицы reviews
        return new Collection(DB::table('reviews')
            ->where('master_profile_id', $masterProfileId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get());
    }

    /**
     * Получить отзывы, написанные пользователем
     * Заменяет: $user->writtenReviews()
     */
    public function getUserWrittenReviews(int $userId): Collection
    {
        // В таблице reviews client_id - это ID пользователя, который написал отзыв
        return new Collection(DB::table('reviews')
            ->where('client_id', $userId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get());
    }

    /**
     * Получить количество полученных отзывов
     */
    public function getUserReceivedReviewsCount(int $userId): int
    {
        $masterProfileId = DB::table('master_profiles')
            ->where('user_id', $userId)
            ->value('id');
            
        if (!$masterProfileId) {
            return 0;
        }
        
        return DB::table('reviews')
            ->where('master_profile_id', $masterProfileId)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Получить количество написанных отзывов
     */
    public function getUserWrittenReviewsCount(int $userId): int
    {
        return DB::table('reviews')
            ->where('client_id', $userId)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Получить средний рейтинг пользователя
     */
    public function getUserAverageRating(int $userId): float
    {
        $masterProfileId = DB::table('master_profiles')
            ->where('user_id', $userId)
            ->value('id');
            
        if (!$masterProfileId) {
            return 0.0;
        }
        
        $average = DB::table('reviews')
            ->where('master_profile_id', $masterProfileId)
            ->whereNull('deleted_at')
            ->avg('rating_overall');

        return (float) ($average ?? 0);
    }

    /**
     * Получить недавние отзывы о пользователе
     */
    public function getRecentUserReceivedReviews(int $userId, int $limit = 10): Collection
    {
        $masterProfileId = DB::table('master_profiles')
            ->where('user_id', $userId)
            ->value('id');
            
        if (!$masterProfileId) {
            return new Collection();
        }
        
        return new Collection(DB::table('reviews')
            ->where('master_profile_id', $masterProfileId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get());
    }

    /**
     * Получить статистику отзывов пользователя
     */
    public function getUserReviewsStatistics(int $userId): array
    {
        $masterProfileId = DB::table('master_profiles')
            ->where('user_id', $userId)
            ->value('id');
            
        if (!$masterProfileId) {
            return [
                'received_reviews' => 0,
                'written_reviews' => $this->getUserWrittenReviewsCount($userId),
                'average_rating' => 0.0,
                'first_review' => null,
                'latest_review' => null,
            ];
        }
        
        $receivedStats = DB::table('reviews')
            ->where('master_profile_id', $masterProfileId)
            ->whereNull('deleted_at')
            ->selectRaw('
                COUNT(*) as total_received,
                AVG(rating_overall) as average_rating,
                MIN(created_at) as first_review,
                MAX(created_at) as latest_review
            ')
            ->first();

        $writtenStats = DB::table('reviews')
            ->where('client_id', $userId)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total_written')
            ->first();

        return [
            'received_reviews' => $receivedStats->total_received ?? 0,
            'written_reviews' => $writtenStats->total_written ?? 0,
            'average_rating' => round($receivedStats->average_rating ?? 0, 2),
            'first_review' => $receivedStats->first_review,
            'latest_review' => $receivedStats->latest_review,
        ];
    }

    /**
     * Получить разбивку рейтинга пользователя по звездам
     */
    public function getUserRatingBreakdown(int $userId): array
    {
        $masterProfileId = DB::table('master_profiles')
            ->where('user_id', $userId)
            ->value('id');
            
        if (!$masterProfileId) {
            // Заполняем нулями если нет профиля мастера
            $result = [];
            for ($i = 5; $i >= 1; $i--) {
                $result[$i] = 0;
            }
            return $result;
        }
        
        $breakdown = DB::table('reviews')
            ->where('master_profile_id', $masterProfileId)
            ->whereNull('deleted_at')
            ->selectRaw('rating_overall, COUNT(*) as count')
            ->groupBy('rating_overall')
            ->orderBy('rating_overall', 'desc')
            ->pluck('count', 'rating_overall')
            ->toArray();

        // Заполняем отсутствующие рейтинги нулями
        $result = [];
        for ($i = 5; $i >= 1; $i--) {
            $result[$i] = $breakdown[$i] ?? 0;
        }

        return $result;
    }

    /**
     * Получить отзывы с высоким рейтингом
     */
    public function getUserHighRatedReviews(int $userId, int $minRating = 4, int $limit = 5): Collection
    {
        $masterProfileId = DB::table('master_profiles')
            ->where('user_id', $userId)
            ->value('id');
            
        if (!$masterProfileId) {
            return new Collection();
        }
        
        return new Collection(DB::table('reviews')
            ->where('master_profile_id', $masterProfileId)
            ->where('rating_overall', '>=', $minRating)
            ->whereNull('deleted_at')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderBy('rating_overall', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get());
    }
}