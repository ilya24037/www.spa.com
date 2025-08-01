<?php

namespace App\Domain\Review\Actions;

use App\Domain\Review\DTOs\ReviewData;
use App\Domain\Review\Repositories\ReviewRepository;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Enums\ReviewStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для создания отзыва
 */
class CreateReviewAction
{
    private ReviewRepository $reviewRepository;
    private MasterRepository $masterRepository;
    private BookingRepository $bookingRepository;

    public function __construct(
        ReviewRepository $reviewRepository,
        MasterRepository $masterRepository,
        BookingRepository $bookingRepository
    ) {
        $this->reviewRepository = $reviewRepository;
        $this->masterRepository = $masterRepository;
        $this->bookingRepository = $bookingRepository;
    }

    /**
     * Создать новый отзыв
     */
    public function execute(ReviewData $reviewData): array
    {
        try {
            return DB::transaction(function () use ($reviewData) {
                // Проверяем, можно ли оставить отзыв
                $validation = $this->validateReview($reviewData);
                if (!$validation['valid']) {
                    return [
                        'success' => false,
                        'message' => $validation['message'],
                    ];
                }

                // Создаем отзыв
                $review = $this->reviewRepository->create($reviewData->toArray());

                // Обновляем рейтинг мастера
                $master = $this->masterRepository->findById($reviewData->masterId);
                if ($master) {
                    $this->masterRepository->updateRating($master);
                }

                Log::info('Review created', [
                    'review_id' => $review->id,
                    'master_id' => $reviewData->masterId,
                    'client_id' => $reviewData->clientId,
                    'rating' => $reviewData->rating,
                ]);

                return [
                    'success' => true,
                    'message' => 'Отзыв успешно создан',
                    'review' => $review,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to create review', [
                'data' => $reviewData->toArray(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при создании отзыва',
            ];
        }
    }

    /**
     * Валидация возможности создания отзыва
     */
    private function validateReview(ReviewData $reviewData): array
    {
        // Проверяем, есть ли завершенное бронирование
        if ($reviewData->bookingId) {
            $booking = $this->bookingRepository->findById($reviewData->bookingId);
            
            if (!$booking) {
                return [
                    'valid' => false,
                    'message' => 'Бронирование не найдено',
                ];
            }

            if ($booking->client_id !== $reviewData->clientId) {
                return [
                    'valid' => false,
                    'message' => 'Вы не являетесь клиентом этого бронирования',
                ];
            }

            if (!$booking->isCompleted()) {
                return [
                    'valid' => false,
                    'message' => 'Можно оставить отзыв только после завершения услуги',
                ];
            }
        }

        // Проверяем, не оставлял ли уже отзыв
        $existingReview = $this->reviewRepository->findByBooking($reviewData->bookingId);
        if ($existingReview) {
            return [
                'valid' => false,
                'message' => 'Вы уже оставили отзыв на это бронирование',
            ];
        }

        // Проверяем мастера
        $master = $this->masterRepository->findById($reviewData->masterId);
        if (!$master || !$master->isActive()) {
            return [
                'valid' => false,
                'message' => 'Мастер не найден или недоступен',
            ];
        }

        return [
            'valid' => true,
            'message' => '',
        ];
    }
}