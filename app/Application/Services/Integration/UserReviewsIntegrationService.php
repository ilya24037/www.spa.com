<?php

namespace App\Application\Services\Integration;

use App\Domain\Review\Events\ReviewCreated;
use App\Domain\Review\Events\ReviewUpdated;
use App\Domain\Review\Events\ReviewDeleted;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис интеграции User ↔ Reviews доменов
 * Заменяет прямые связи через трейт HasReviews
 */
class UserReviewsIntegrationService
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
     * Получить все отзывы написанные пользователем
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
     * Заменяет: $user->receivedReviews()->count()
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
     * Заменяет: $user->writtenReviews()->count()
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
     * Заменяет: $user->receivedReviews()->avg('rating')
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
     * Проверить может ли пользователь оставить отзыв
     */
    public function canUserWriteReview(int $reviewerId, int $targetUserId, ?int $bookingId = null): bool
    {
        // Нельзя оставлять отзыв самому себе
        if ($reviewerId === $targetUserId) {
            return false;
        }

        // Проверяем что пользователь еще не оставлял отзыв
        if ($this->userHasReviewedUser($reviewerId, $targetUserId)) {
            return false;
        }

        // Если указано бронирование, проверяем что оно завершено
        if ($bookingId) {
            $bookingCompleted = DB::table('bookings')
                ->where('id', $bookingId)
                ->whereIn('status', ['completed', 'finished'])
                ->where(function ($query) use ($reviewerId, $targetUserId) {
                    $query->where('client_id', $reviewerId)
                          ->orWhere('master_id', $reviewerId);
                })
                ->exists();

            if (!$bookingCompleted) {
                return false;
            }
        }

        // Проверяем лимит отзывов в день (например, максимум 5)
        $todayReviewsCount = DB::table('reviews')
            ->where('client_id', $reviewerId)
            ->whereDate('created_at', today())
            ->count();

        return $todayReviewsCount < 5;
    }

    /**
     * Проверить есть ли отзыв от пользователя о целевом пользователе
     * Заменяет: $user->writtenReviews()->where('reviewed_user_id', $userId)->exists()
     */
    public function userHasReviewedUser(int $reviewerId, int $targetUserId): bool
    {
        // Получаем master_profile_id для целевого пользователя
        $masterProfileId = DB::table('master_profiles')
            ->where('user_id', $targetUserId)
            ->value('id');
            
        if (!$masterProfileId) {
            return false;
        }
        
        return DB::table('reviews')
            ->where('client_id', $reviewerId)
            ->where('master_profile_id', $masterProfileId)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Создать новый отзыв через событие
     * ВАЖНО: Не создает напрямую, а отправляет событие!
     */
    public function createUserReview(int $reviewerId, int $targetUserId, array $reviewData): bool
    {
        try {
            // Проверяем права и лимиты
            if (!$this->canUserWriteReview($reviewerId, $targetUserId, $reviewData['booking_id'] ?? null)) {
                return false;
            }

            // Валидируем данные отзыва
            $validatedData = $this->validateReviewData($reviewData);
            if (!$validatedData) {
                return false;
            }

            // Создаем отзыв в БД
            $reviewId = DB::table('reviews')->insertGetId([
                'reviewer_user_id' => $reviewerId,
                'reviewed_user_id' => $targetUserId,
                'booking_id' => $reviewData['booking_id'] ?? null,
                'rating' => $validatedData['rating'],
                'comment' => $validatedData['comment'] ?? '',
                'is_anonymous' => $validatedData['is_anonymous'] ?? false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Отправляем событие
            Event::dispatch(new ReviewCreated($reviewId, $reviewerId, $targetUserId, $validatedData));

            Log::info('Review created via integration service', [
                'review_id' => $reviewId,
                'reviewer_id' => $reviewerId,
                'target_user_id' => $targetUserId,
                'rating' => $validatedData['rating']
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to create review', [
                'reviewer_id' => $reviewerId,
                'target_user_id' => $targetUserId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Обновить отзыв через событие
     * ВАЖНО: Не обновляет напрямую, а отправляет событие!
     */
    public function updateUserReview(int $userId, int $reviewId, array $reviewData): bool
    {
        try {
            // Проверяем права (может обновлять только автор отзыва)
            $review = DB::table('reviews')
                ->where('id', $reviewId)
                ->where('reviewer_user_id', $userId)
                ->where('is_active', true)
                ->first();

            if (!$review) {
                return false;
            }

            // Проверяем что отзыв можно редактировать (например, в течение 24 часов)
            $canEdit = now()->diffInHours($review->created_at) <= 24;
            if (!$canEdit) {
                return false;
            }

            // Валидируем данные
            $validatedData = $this->validateReviewData($reviewData);
            if (!$validatedData) {
                return false;
            }

            // Обновляем отзыв
            $updated = DB::table('reviews')
                ->where('id', $reviewId)
                ->update([
                    'rating' => $validatedData['rating'],
                    'comment' => $validatedData['comment'] ?? $review->comment,
                    'is_anonymous' => $validatedData['is_anonymous'] ?? $review->is_anonymous,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                // Отправляем событие
                Event::dispatch(new ReviewUpdated($reviewId, $userId, $review->reviewed_user_id, $validatedData));

                Log::info('Review updated via integration service', [
                    'review_id' => $reviewId,
                    'user_id' => $userId
                ]);
            }

            return $updated > 0;

        } catch (\Exception $e) {
            Log::error('Failed to update review', [
                'user_id' => $userId,
                'review_id' => $reviewId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Удалить отзыв (мягкое удаление)
     */
    public function deleteUserReview(int $userId, int $reviewId): bool
    {
        try {
            // Проверяем права (может удалять только автор отзыва или администратор)
            $review = DB::table('reviews')
                ->where('id', $reviewId)
                ->where('reviewer_user_id', $userId)
                ->where('is_active', true)
                ->first();

            if (!$review) {
                return false;
            }

            // Мягкое удаление
            $deleted = DB::table('reviews')
                ->where('id', $reviewId)
                ->update([
                    'is_active' => false,
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($deleted) {
                // Отправляем событие
                Event::dispatch(new ReviewDeleted($reviewId, $userId, $review->reviewed_user_id));

                Log::info('Review deleted via integration service', [
                    'review_id' => $reviewId,
                    'user_id' => $userId
                ]);
            }

            return $deleted > 0;

        } catch (\Exception $e) {
            Log::error('Failed to delete review', [
                'user_id' => $userId,
                'review_id' => $reviewId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Получить недавние отзывы о пользователе
     */
    public function getRecentUserReceivedReviews(int $userId, int $limit = 10): Collection
    {
        return DB::table('reviews')
            ->where('reviewed_user_id', $userId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить статистику отзывов пользователя
     */
    public function getUserReviewsStatistics(int $userId): array
    {
        $receivedStats = DB::table('reviews')
            ->where('reviewed_user_id', $userId)
            ->where('is_active', true)
            ->selectRaw('
                COUNT(*) as total_received,
                AVG(rating) as average_rating,
                MIN(created_at) as first_review,
                MAX(created_at) as latest_review
            ')
            ->first();

        $writtenStats = DB::table('reviews')
            ->where('reviewer_user_id', $userId)
            ->where('is_active', true)
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
     * Получить детальную статистику рейтинга (распределение по звездам)
     */
    public function getUserRatingBreakdown(int $userId): array
    {
        $breakdown = DB::table('reviews')
            ->where('reviewed_user_id', $userId)
            ->where('is_active', true)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->pluck('count', 'rating')
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
        return DB::table('reviews')
            ->where('reviewed_user_id', $userId)
            ->where('rating', '>=', $minRating)
            ->where('is_active', true)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Валидировать данные отзыва
     */
    private function validateReviewData(array $data): ?array
    {
        // Проверяем обязательные поля
        if (!isset($data['rating']) || !is_numeric($data['rating'])) {
            return null;
        }

        $rating = (int) $data['rating'];
        if ($rating < 1 || $rating > 5) {
            return null;
        }

        // Проверяем комментарий (опционально, но если есть - валидируем)
        $comment = $data['comment'] ?? '';
        if (strlen($comment) > 1000) {
            return null;
        }

        return [
            'rating' => $rating,
            'comment' => trim($comment),
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'booking_id' => isset($data['booking_id']) ? (int) $data['booking_id'] : null,
        ];
    }
}