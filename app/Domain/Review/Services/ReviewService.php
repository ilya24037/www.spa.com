<?php

declare(strict_types=1);

namespace App\Domain\Review\Services;

use App\Domain\Review\DTOs\CreateReviewDTO;
use App\Domain\Review\DTOs\UpdateReviewDTO;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Repositories\ReviewRepositoryNew as ReviewRepository;
use App\Domain\User\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReviewService
{
    public function __construct(
        private readonly ReviewRepository $repository
    ) {}

    /**
     * Получить список отзывов для пользователя
     */
    public function getUserReviews(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->getUserReviews($userId, $perPage);
    }

    /**
     * Получить отзыв по ID
     */
    public function getById(int $id): ?Review
    {
        return $this->repository->findById($id);
    }

    /**
     * Создать новый отзыв
     */
    public function create(CreateReviewDTO $dto): Review
    {
        // Проверка, что пользователь не оставляет отзыв самому себе
        if ($dto->userId === $dto->reviewableUserId) {
            throw new \InvalidArgumentException('Нельзя оставить отзыв самому себе');
        }

        // Проверка на дублирование отзыва
        $existingReview = $this->repository->findByUserAndReviewable(
            $dto->userId,
            $dto->reviewableUserId,
            $dto->adId
        );

        if ($existingReview) {
            throw new \InvalidArgumentException('Вы уже оставляли отзыв этому пользователю');
        }

        return DB::transaction(function () use ($dto) {
            $review = $this->repository->create($dto->toArray());
            
            // Здесь можно добавить отправку уведомления
            // $this->notificationService->notifyNewReview($review);
            
            return $review;
        });
    }

    /**
     * Обновить отзыв
     */
    public function update(int $id, UpdateReviewDTO $dto, User $user): Review
    {
        $review = $this->repository->findById($id);
        
        if (!$review) {
            throw new \InvalidArgumentException('Отзыв не найден');
        }

        // Проверка прав на редактирование (только автор или админ)
        if (!$review->isAuthor($user) && !$user->isAdmin()) {
            throw new \InvalidArgumentException('Недостаточно прав для редактирования');
        }

        return $this->repository->update($id, $dto->toArray());
    }

    /**
     * Удалить отзыв
     */
    public function delete(int $id, User $user): bool
    {
        $review = $this->repository->findById($id);
        
        if (!$review) {
            throw new \InvalidArgumentException('Отзыв не найден');
        }

        // Проверка прав на удаление (только автор или админ)
        if (!$review->isAuthor($user) && !$user->isAdmin()) {
            throw new \InvalidArgumentException('Недостаточно прав для удаления');
        }

        return $this->repository->delete($id);
    }

    /**
     * Получить статистику отзывов пользователя
     */
    public function getUserReviewStats(int $userId): array
    {
        $reviews = $this->repository->getUserReviewsCollection($userId);
        $avgRating = $reviews->avg('rating');
        
        return [
            'total_count' => $reviews->count(),
            'average_rating' => $avgRating ? round($avgRating, 1) : 0.0,
            'rating_distribution' => $this->getRatingDistribution($reviews),
        ];
    }

    /**
     * Получить распределение рейтингов
     */
    private function getRatingDistribution(Collection $reviews): array
    {
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $reviews->where('rating', $i)->count();
        }
        return $distribution;
    }
}