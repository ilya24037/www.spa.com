<?php

namespace App\Domain\Review\Services;

use App\Domain\Review\Models\Review;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для расчета рейтингов на основе отзывов
 */
class RatingCalculator
{
    /**
     * Пересчитать рейтинг мастера
     */
    public function recalculateMasterRating(int $masterProfileId): array
    {
        try {
            DB::beginTransaction();

            $masterProfile = MasterProfile::findOrFail($masterProfileId);

            // Получаем статистику по отзывам
            $stats = $this->getMasterReviewStats($masterProfile);

            // Обновляем профиль мастера
            $masterProfile->update([
                'rating' => $stats['average_rating'],
                'reviews_count' => $stats['total_reviews'],
            ]);

            DB::commit();

            Log::info('Master rating recalculated', [
                'master_profile_id' => $masterProfileId,
                'old_rating' => $masterProfile->getOriginal('rating'),
                'new_rating' => $stats['average_rating'],
                'old_reviews_count' => $masterProfile->getOriginal('reviews_count'),
                'new_reviews_count' => $stats['total_reviews'],
            ]);

            return $stats;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to recalculate master rating', [
                'master_profile_id' => $masterProfileId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Пересчитать рейтинг пользователя (как получателя услуг)
     */
    public function recalculateUserRating(int $userId): array
    {
        try {
            $user = User::findOrFail($userId);

            // Получаем отзывы о пользователе как о клиенте
            $stats = $this->getUserReviewStats($user);

            // Обновляем данные пользователя (если есть поля для рейтинга)
            if ($user->hasClientRating()) {
                $user->update([
                    'client_rating' => $stats['average_rating'],
                    'client_reviews_count' => $stats['total_reviews'],
                ]);
            }

            Log::info('User rating recalculated', [
                'user_id' => $userId,
                'average_rating' => $stats['average_rating'],
                'total_reviews' => $stats['total_reviews'],
            ]);

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to recalculate user rating', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Массовый пересчет рейтингов всех мастеров
     */
    public function recalculateAllMasterRatings(): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'updated' => 0,
        ];

        MasterProfile::chunk(100, function ($masters) use (&$results) {
            foreach ($masters as $master) {
                try {
                    $oldRating = $master->rating;
                    $stats = $this->recalculateMasterRating($master->id);
                    
                    $results['processed']++;
                    
                    if ($oldRating != $stats['average_rating']) {
                        $results['updated']++;
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    
                    Log::error('Failed to recalculate rating in batch', [
                        'master_profile_id' => $master->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        Log::info('Batch rating recalculation completed', $results);

        return $results;
    }

    /**
     * Получить детальную статистику отзывов мастера
     */
    public function getMasterReviewStats(MasterProfile $masterProfile): array
    {
        $reviews = Review::where('reviewable_type', MasterProfile::class)
            ->where('reviewable_id', $masterProfile->id)
            ->where('status', ReviewStatus::APPROVED)
            ->where('type', ReviewType::SERVICE) // Только сервисные отзывы
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0.00,
                'rating_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'recent_reviews' => 0,
                'verified_reviews' => 0,
                'recommended_count' => 0,
                'with_photos_count' => 0,
            ];
        }

        $ratingValues = $reviews->pluck('rating')->map(fn($rating) => $rating->value ?? $rating);
        $averageRating = round($ratingValues->average(), 2);

        // Распределение по рейтингам
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $reviews->where('rating.value', $i)->count();
        }

        return [
            'total_reviews' => $reviews->count(),
            'average_rating' => $averageRating,
            'rating_distribution' => $distribution,
            'recent_reviews' => $reviews->where('created_at', '>=', now()->subDays(30))->count(),
            'verified_reviews' => $reviews->where('is_verified', true)->count(),
            'recommended_count' => $reviews->where('is_recommended', true)->count(),
            'with_photos_count' => $reviews->where('photos', '!=', null)
                ->where('photos', '!=', '[]')->count(),
        ];
    }

    /**
     * Получить статистику отзывов пользователя
     */
    public function getUserReviewStats(User $user): array
    {
        // Отзывы о пользователе как о клиенте (если такие есть)
        $reviews = Review::where('reviewable_type', User::class)
            ->where('reviewable_id', $user->id)
            ->where('status', ReviewStatus::APPROVED)
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0.00,
                'rating_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            ];
        }

        $ratingValues = $reviews->pluck('rating')->map(fn($rating) => $rating->value ?? $rating);
        $averageRating = round($ratingValues->average(), 2);

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $reviews->where('rating.value', $i)->count();
        }

        return [
            'total_reviews' => $reviews->count(),
            'average_rating' => $averageRating,
            'rating_distribution' => $distribution,
        ];
    }

    /**
     * Рассчитать взвешенный рейтинг с учетом времени
     */
    public function calculateWeightedRating(MasterProfile $masterProfile, int $daysWeight = 90): float
    {
        $reviews = Review::where('reviewable_type', MasterProfile::class)
            ->where('reviewable_id', $masterProfile->id)
            ->where('status', ReviewStatus::APPROVED)
            ->where('type', ReviewType::SERVICE)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($reviews->isEmpty()) {
            return 0.00;
        }

        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($reviews as $review) {
            // Чем новее отзыв, тем больше его вес
            $daysAgo = $review->created_at->diffInDays(now());
            $weight = max(0.1, 1 - ($daysAgo / $daysWeight));
            
            // Дополнительный вес для верифицированных отзывов
            if ($review->is_verified) {
                $weight *= 1.2;
            }

            // Дополнительный вес для отзывов с фото
            if (!empty($review->photos)) {
                $weight *= 1.1;
            }

            $ratingValue = $review->rating->value ?? $review->rating;
            $weightedSum += $ratingValue * $weight;
            $totalWeight += $weight;
        }

        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Получить трендовую статистику рейтинга
     */
    public function getRatingTrend(MasterProfile $masterProfile, int $days = 30): array
    {
        $endDate = now();
        $startDate = now()->subDays($days);

        $currentPeriodReviews = Review::where('reviewable_type', MasterProfile::class)
            ->where('reviewable_id', $masterProfile->id)
            ->where('status', ReviewStatus::APPROVED)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->get();

        $previousPeriodReviews = Review::where('reviewable_type', MasterProfile::class)
            ->where('reviewable_id', $masterProfile->id)
            ->where('status', ReviewStatus::APPROVED)
            ->where('created_at', '>=', $startDate->copy()->subDays($days))
            ->where('created_at', '<', $startDate)
            ->get();

        $currentAvg = $currentPeriodReviews->isEmpty() ? 0 : 
            $currentPeriodReviews->avg(fn($r) => $r->rating->value ?? $r->rating);
        
        $previousAvg = $previousPeriodReviews->isEmpty() ? 0 : 
            $previousPeriodReviews->avg(fn($r) => $r->rating->value ?? $r->rating);

        $change = $currentAvg - $previousAvg;

        return [
            'current_period_avg' => round($currentAvg, 2),
            'previous_period_avg' => round($previousAvg, 2),
            'change' => round($change, 2),
            'trend' => $change > 0.1 ? 'up' : ($change < -0.1 ? 'down' : 'stable'),
            'current_period_count' => $currentPeriodReviews->count(),
            'previous_period_count' => $previousPeriodReviews->count(),
        ];
    }

    /**
     * Получить рекомендации для улучшения рейтинга
     */
    public function getRatingImprovementSuggestions(MasterProfile $masterProfile): array
    {
        $stats = $this->getMasterReviewStats($masterProfile);
        $suggestions = [];

        if ($stats['average_rating'] < 4.0) {
            $suggestions[] = [
                'type' => 'quality',
                'message' => 'Работайте над качеством услуг - ваш средний рейтинг ниже 4.0',
                'priority' => 'high',
            ];
        }

        if ($stats['total_reviews'] < 10) {
            $suggestions[] = [
                'type' => 'reviews_count',
                'message' => 'Просите клиентов оставлять отзывы - у вас мало отзывов',
                'priority' => 'medium',
            ];
        }

        if ($stats['verified_reviews'] / max(1, $stats['total_reviews']) < 0.3) {
            $suggestions[] = [
                'type' => 'verification',
                'message' => 'Поощряйте клиентов оставлять отзывы через подтвержденные бронирования',
                'priority' => 'medium',
            ];
        }

        if ($stats['with_photos_count'] / max(1, $stats['total_reviews']) < 0.2) {
            $suggestions[] = [
                'type' => 'photos',
                'message' => 'Просите клиентов добавлять фото к отзывам - это повышает доверие',
                'priority' => 'low',
            ];
        }

        return $suggestions;
    }
}