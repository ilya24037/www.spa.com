<?php

namespace App\Application\Services\Integration;

use App\Domain\Review\Events\ReviewCreated;
use App\Domain\Review\Events\ReviewUpdated;
use App\Domain\Review\Events\ReviewDeleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис записи отзывов пользователей
 * Следует принципу Single Responsibility - только создание/изменение/удаление
 */
class UserReviewsWriter
{
    private ReviewValidator $validator;

    public function __construct(ReviewValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Создать новый отзыв через событие
     * ВАЖНО: Не создает напрямую, а отправляет событие!
     */
    public function createUserReview(int $reviewerId, int $targetUserId, array $reviewData): bool
    {
        try {
            // Проверяем права и лимиты
            if (!$this->validator->canUserWriteReview($reviewerId, $targetUserId, $reviewData['booking_id'] ?? null)) {
                return false;
            }

            // Валидируем данные отзыва
            $validatedData = $this->validator->validateReviewData($reviewData);
            if (!$validatedData) {
                return false;
            }

            // Получаем master_profile_id для целевого пользователя
            $masterProfileId = DB::table('master_profiles')
                ->where('user_id', $targetUserId)
                ->value('id');
                
            if (!$masterProfileId) {
                return false;
            }

            // Создаем отзыв в БД
            $reviewId = DB::table('reviews')->insertGetId([
                'client_id' => $reviewerId,
                'master_profile_id' => $masterProfileId,
                'booking_id' => $reviewData['booking_id'] ?? null,
                'rating_overall' => $validatedData['rating'],
                'comment' => $validatedData['comment'] ?? '',
                'is_anonymous' => $validatedData['is_anonymous'] ?? false,
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
                ->where('client_id', $userId)
                ->whereNull('deleted_at')
                ->first();

            if (!$review) {
                return false;
            }

            // Проверяем что отзыв можно редактировать (например, в течение 24 часов)
            if (!$this->canEditReview($review)) {
                return false;
            }

            // Валидируем данные
            $validatedData = $this->validator->validateReviewData($reviewData);
            if (!$validatedData) {
                return false;
            }

            // Обновляем отзыв
            $updated = DB::table('reviews')
                ->where('id', $reviewId)
                ->update([
                    'rating_overall' => $validatedData['rating'],
                    'comment' => $validatedData['comment'] ?? $review->comment,
                    'is_anonymous' => $validatedData['is_anonymous'] ?? $review->is_anonymous,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                // Отправляем событие
                // Получаем user_id мастера из master_profile_id
                $masterUserId = DB::table('master_profiles')
                    ->where('id', $review->master_profile_id)
                    ->value('user_id');
                    
                Event::dispatch(new ReviewUpdated($reviewId, $userId, $masterUserId, $validatedData));

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
                ->where('client_id', $userId)
                ->whereNull('deleted_at')
                ->first();

            if (!$review) {
                return false;
            }

            // Мягкое удаление
            $deleted = DB::table('reviews')
                ->where('id', $reviewId)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($deleted) {
                // Отправляем событие
                // Получаем user_id мастера из master_profile_id
                $masterUserId = DB::table('master_profiles')
                    ->where('id', $review->master_profile_id)
                    ->value('user_id');
                    
                Event::dispatch(new ReviewDeleted($reviewId, $userId, $masterUserId));

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
     * Проверить можно ли редактировать отзыв
     */
    private function canEditReview($review): bool
    {
        // Можно редактировать в течение 24 часов после создания
        return now()->diffInHours($review->created_at) <= 24;
    }
}