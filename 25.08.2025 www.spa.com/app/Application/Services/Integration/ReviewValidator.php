<?php

namespace App\Application\Services\Integration;

/**
 * Валидатор данных отзывов
 * Следует принципу Single Responsibility
 */
class ReviewValidator
{
    /**
     * Валидировать данные отзыва
     */
    public function validateReviewData(array $data): ?array
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

    /**
     * Проверить, может ли пользователь написать отзыв
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
            $bookingCompleted = \DB::table('bookings')
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
        $todayReviewsCount = \DB::table('reviews')
            ->where('client_id', $reviewerId)
            ->whereDate('created_at', today())
            ->count();

        return $todayReviewsCount < 5;
    }

    /**
     * Проверить, писал ли пользователь отзыв другому пользователю
     */
    public function userHasReviewedUser(int $reviewerId, int $targetUserId): bool
    {
        // Получаем master_profile_id для целевого пользователя
        $masterProfileId = \DB::table('master_profiles')
            ->where('user_id', $targetUserId)
            ->value('id');
            
        if (!$masterProfileId) {
            return false;
        }
        
        return \DB::table('reviews')
            ->where('client_id', $reviewerId)
            ->where('master_profile_id', $masterProfileId)
            ->whereNull('deleted_at')
            ->exists();
    }
}