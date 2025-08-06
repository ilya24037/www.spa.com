<?php

namespace App\Domain\Review\Services;

use App\Domain\Review\Models\Review;
use App\Domain\Review\DTOs\CreateReviewDTO;
use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use App\Domain\Review\Repositories\ReviewRepository;

/**
 * Обработчик валидации отзывов
 */
class ReviewValidationHandler
{
    public function __construct(
        private ReviewRepository $repository
    ) {}

    /**
     * Проверить, оставлял ли пользователь отзыв
     */
    public function hasUserReviewed(int $userId, string $type, int $id): bool
    {
        return Review::where('user_id', $userId)
            ->where('reviewable_type', $type)
            ->where('reviewable_id', $id)
            ->exists();
    }

    /**
     * Определить начальный статус отзыва
     */
    public function determineInitialStatus(CreateReviewDTO $dto): ReviewStatus
    {
        // Жалобы требуют обязательной модерации
        if ($dto->type === ReviewType::COMPLAINT) {
            return ReviewStatus::PENDING;
        }

        // Отзывы с низким рейтингом требуют модерации
        if ($dto->rating && $dto->rating->value <= 2) {
            return ReviewStatus::PENDING;
        }

        // Отзывы с подозрительным контентом
        if ($this->hasSuspiciousContent($dto)) {
            return ReviewStatus::PENDING;
        }

        // Пользователи с высокой репутацией могут публиковать сразу
        if ($this->isUserTrusted($dto->userId)) {
            return ReviewStatus::APPROVED;
        }

        // По умолчанию - требует модерации
        return ReviewStatus::PENDING;
    }

    /**
     * Проверить, является ли покупка подтвержденной
     */
    public function isVerifiedPurchase(CreateReviewDTO $dto): bool
    {
        return $dto->bookingId !== null;
    }

    /**
     * Валидировать данные создания отзыва
     */
    public function validateCreateData(CreateReviewDTO $dto): array
    {
        $errors = [];

        // Проверка обязательных полей
        if (empty($dto->userId)) {
            $errors[] = 'User ID is required';
        }

        if (empty($dto->reviewableType)) {
            $errors[] = 'Reviewable type is required';
        }

        if (empty($dto->reviewableId)) {
            $errors[] = 'Reviewable ID is required';
        }

        // Проверка рейтинга
        if ($dto->rating && !$this->isValidRating($dto->rating->value)) {
            $errors[] = 'Invalid rating value';
        }

        // Проверка длины комментария
        if ($dto->comment && mb_strlen($dto->comment) > 5000) {
            $errors[] = 'Comment is too long (max 5000 characters)';
        }

        if ($dto->comment && mb_strlen($dto->comment) < 10) {
            $errors[] = 'Comment is too short (min 10 characters)';
        }

        // Проверка заголовка
        if ($dto->title && mb_strlen($dto->title) > 200) {
            $errors[] = 'Title is too long (max 200 characters)';
        }

        // Проверка на дублирование
        if ($this->hasUserReviewed($dto->userId, $dto->reviewableType, $dto->reviewableId)) {
            $errors[] = 'User has already reviewed this item';
        }

        // Проверка на спам
        if ($this->isSpamContent($dto)) {
            $errors[] = 'Content appears to be spam';
        }

        // Проверка лимитов пользователя
        if (!$this->checkUserLimits($dto->userId)) {
            $errors[] = 'Daily review limit exceeded';
        }

        return $errors;
    }

    /**
     * Валидировать обновление отзыва
     */
    public function validateUpdateData(Review $review, int $userId): array
    {
        $errors = [];

        // Проверка прав
        if ($review->user_id !== $userId) {
            $errors[] = 'You can only edit your own reviews';
        }

        // Проверка возможности редактирования
        if (!$review->canBeEdited()) {
            $errors[] = 'Review cannot be edited in current status';
        }

        // Проверка времени редактирования
        if (!$this->canEditByTime($review)) {
            $errors[] = 'Review edit time limit exceeded';
        }

        return $errors;
    }

    /**
     * Проверка валидности рейтинга
     */
    public function isValidRating(int $rating): bool
    {
        return $rating >= 1 && $rating <= 5;
    }

    /**
     * Проверка на подозрительный контент
     */
    public function hasSuspiciousContent(CreateReviewDTO $dto): bool
    {
        $suspiciousPatterns = [
            // Ссылки
            '/https?:\/\//',
            '/www\./i',
            
            // Контактная информация
            '/\b\d{10,}\b/', // Телефоны
            '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', // Email
            
            // Повторяющиеся символы
            '/(.)\1{5,}/',
            
            // Caps lock
            '/[A-ZА-Я]{20,}/',
        ];

        $text = ($dto->title ?? '') . ' ' . ($dto->comment ?? '');

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверка на спам
     */
    public function isSpamContent(CreateReviewDTO $dto): bool
    {
        $spamWords = [
            'купить дешево',
            'скидка до',
            'жми сюда',
            'переходи по ссылке',
            'лучшая цена',
            'акция только сегодня',
        ];

        $text = mb_strtolower(($dto->title ?? '') . ' ' . ($dto->comment ?? ''));

        foreach ($spamWords as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }

        // Проверка на повторяющийся текст
        if ($this->isDuplicateContent($text, $dto->userId)) {
            return true;
        }

        return false;
    }

    /**
     * Проверка, является ли пользователь доверенным
     */
    public function isUserTrusted(int $userId): bool
    {
        // Получаем статистику пользователя
        $userStats = $this->repository->getUserStats($userId);

        // Доверенные критерии:
        // - более 10 одобренных отзывов
        // - менее 10% отклоненных отзывов
        // - аккаунт старше 30 дней
        return $userStats['approved_reviews'] >= 10 &&
               $userStats['rejection_rate'] < 0.1 &&
               $userStats['account_age_days'] >= 30;
    }

    /**
     * Проверка лимитов пользователя
     */
    public function checkUserLimits(int $userId): bool
    {
        // Лимит: 5 отзывов в день
        $dailyCount = $this->repository->getUserDailyReviewCount($userId);
        if ($dailyCount >= 5) {
            return false;
        }

        // Лимит: 20 отзывов в неделю
        $weeklyCount = $this->repository->getUserWeeklyReviewCount($userId);
        if ($weeklyCount >= 20) {
            return false;
        }

        return true;
    }

    /**
     * Проверка возможности редактирования по времени
     */
    public function canEditByTime(Review $review): bool
    {
        // Можно редактировать в течение 24 часов после создания
        $editDeadline = $review->created_at->addHours(24);
        return now()->lte($editDeadline);
    }

    /**
     * Проверка на дублирование контента
     */
    private function isDuplicateContent(string $text, int $userId): bool
    {
        // Ищем похожий текст у того же пользователя
        $recentReviews = $this->repository->getUserRecentReviews($userId, 10);
        
        foreach ($recentReviews as $review) {
            $existingText = mb_strtolower(($review->title ?? '') . ' ' . ($review->comment ?? ''));
            
            // Проверяем на 80% схожесть
            if ($this->calculateSimilarity($text, $existingText) > 0.8) {
                return true;
            }
        }

        return false;
    }

    /**
     * Рассчитать схожесть текстов
     */
    private function calculateSimilarity(string $text1, string $text2): float
    {
        if (empty($text1) || empty($text2)) {
            return 0;
        }

        similar_text($text1, $text2, $percent);
        return $percent / 100;
    }

    /**
     * Проверить права на модерацию
     */
    public function canModerate(int $userId): bool
    {
        // Проверяем роли пользователя
        $user = \App\Domain\User\Models\User::find($userId);
        
        return $user && $user->hasRole(['moderator', 'admin']);
    }

    /**
     * Проверить права на официальный ответ
     */
    public function canReplyOfficially(int $userId, Review $review): bool
    {
        // Официальный ответ могут давать:
        // 1. Владелец объекта отзыва
        // 2. Модераторы
        // 3. Администраторы
        
        if ($this->canModerate($userId)) {
            return true;
        }

        // Проверяем, является ли пользователь владельцем объекта
        if ($review->reviewable && method_exists($review->reviewable, 'getOwnerId')) {
            return $review->reviewable->getOwnerId() === $userId;
        }

        return false;
    }
}