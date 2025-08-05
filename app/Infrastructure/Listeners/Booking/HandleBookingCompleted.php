<?php

namespace App\Infrastructure\Listeners\Booking;

use App\Domain\Booking\Events\BookingCompleted;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Booking\Services\NotificationService;
use App\Domain\Booking\Services\PaymentService;
use App\Domain\Booking\Services\ReviewService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик завершения бронирования
 * 
 * ФУНКЦИИ:
 * - Финализация платежа
 * - Обновление статистики мастера и клиента
 * - Создание возможности для отзыва
 * - Начисление бонусов/кэшбека
 * - Отправка уведомлений о завершении
 */
class HandleBookingCompleted
{
    private BookingRepository $bookingRepository;
    private NotificationService $notificationService;
    private PaymentService $paymentService;
    private ReviewService $reviewService;

    public function __construct(
        BookingRepository $bookingRepository,
        NotificationService $notificationService,
        PaymentService $paymentService,
        ReviewService $reviewService
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->notificationService = $notificationService;
        $this->paymentService = $paymentService;
        $this->reviewService = $reviewService;
    }

    /**
     * Обработка события BookingCompleted
     */
    public function handle(BookingCompleted $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Получаем бронирование
                $booking = $this->bookingRepository->findById($event->bookingId);
                if (!$booking) {
                    throw new Exception("Бронирование с ID {$event->bookingId} не найдено");
                }

                // 2. Валидируем завершение
                $this->validateCompletion($booking);

                // 3. Финализируем платеж
                $this->finalizePayment($booking, $event);

                // 4. Обновляем статистику
                $this->updateCompletionStats($booking, $event);

                // 5. Создаем возможность для отзыва
                $this->enableReviewCreation($booking);

                // 6. Начисляем бонусы
                $bonusResult = $this->processBonuses($booking, $event);

                // 7. Создаем запись о завершении
                $this->createCompletionRecord($booking, $event, $bonusResult);

                // 8. Отправляем уведомления
                $this->sendCompletionNotifications($booking, $event, $bonusResult);

                Log::info('Booking completed successfully', [
                    'booking_id' => $event->bookingId,
                    'completed_at' => $event->completedAt->format('Y-m-d H:i:s'),
                    'client_id' => $event->clientId,
                    'master_id' => $event->masterId,
                    'service_quality' => $event->serviceQuality,
                    'bonus_earned' => $bonusResult['client_bonus'] ?? 0,
                ]);

            } catch (Exception $e) {
                Log::error('Failed to handle BookingCompleted event', [
                    'booking_id' => $event->bookingId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Валидация завершения бронирования
     */
    private function validateCompletion($booking): void
    {
        if ($booking->status === 'completed') {
            throw new Exception("Бронирование уже завершено");
        }

        if ($booking->status === 'cancelled') {
            throw new Exception("Нельзя завершить отмененное бронирование");
        }

        // Проверяем, что услуга действительно была оказана
        if ($booking->started_at === null) {
            Log::warning('Booking completed without started_at timestamp', [
                'booking_id' => $booking->id,
            ]);
        }
    }

    /**
     * Финализировать платеж
     */
    private function finalizePayment($booking, BookingCompleted $event): void
    {
        // Обновляем статус платежа на завершенный
        $booking->update([
            'payment_status' => 'completed',
            'completed_at' => $event->completedAt,
        ]);

        // Если платеж был предавторизован, подтверждаем его
        if ($booking->payment_status === 'authorized') {
            $this->paymentService->capturePayment($booking);
        }

        // Переводим средства мастеру (за вычетом комиссии платформы)
        $this->paymentService->transferToMaster($booking);

        Log::info('Payment finalized for completed booking', [
            'booking_id' => $booking->id,
            'amount' => $booking->total_price,
        ]);
    }

    /**
     * Обновить статистику завершения
     */
    private function updateCompletionStats($booking, BookingCompleted $event): void
    {
        // Обновляем статистику клиента
        $this->bookingRepository->updateClientStats($booking->client_id, [
            'completed_bookings' => DB::raw('completed_bookings + 1'),
            'total_spent' => DB::raw("total_spent + {$booking->total_price}"),
            'last_booking_at' => $event->completedAt,
        ]);

        // Обновляем статистику мастера
        $this->bookingRepository->updateMasterStats($booking->master_id, [
            'completed_bookings' => DB::raw('completed_bookings + 1'),
            'total_earned' => DB::raw("total_earned + {$booking->master_earnings}"),
            'last_service_at' => $event->completedAt,
        ]);

        // Обновляем рейтинг мастера если указано качество услуги
        if ($event->serviceQuality !== null) {
            $this->updateMasterRating($booking->master_id, $event->serviceQuality);
        }
    }

    /**
     * Обновить рейтинг мастера
     */
    private function updateMasterRating(int $masterId, int $serviceQuality): void
    {
        // Получаем текущий рейтинг
        $currentStats = $this->bookingRepository->getMasterRatingStats($masterId);
        
        // Рассчитываем новый рейтинг
        $totalRatings = $currentStats['total_ratings'] + 1;
        $totalScore = ($currentStats['average_rating'] * $currentStats['total_ratings']) + $serviceQuality;
        $newAverage = round($totalScore / $totalRatings, 2);

        // Обновляем рейтинг
        $this->bookingRepository->updateMasterRating($masterId, [
            'average_rating' => $newAverage,
            'total_ratings' => $totalRatings,
        ]);
    }

    /**
     * Создать возможность для отзыва
     */
    private function enableReviewCreation($booking): void
    {
        // Создаем запись для возможности оставить отзыв
        $this->reviewService->enableReviewForBooking($booking, [
            'expires_at' => now()->addDays(30), // 30 дней на отзыв
            'can_review_master' => true,
            'can_review_service' => true,
        ]);

        Log::info('Review creation enabled for booking', [
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'master_id' => $booking->master_id,
        ]);
    }

    /**
     * Обработать начисление бонусов
     */
    private function processBonuses($booking, BookingCompleted $event): array
    {
        $bonusResult = [
            'client_bonus' => 0,
            'master_bonus' => 0,
            'loyalty_points' => 0,
        ];

        try {
            // Начисляем бонусы клиенту (кэшбек)
            $clientBonusAmount = $this->calculateClientBonus($booking);
            if ($clientBonusAmount > 0) {
                $this->paymentService->addBonusToClient($booking->client_id, $clientBonusAmount, 'booking_completed');
                $bonusResult['client_bonus'] = $clientBonusAmount;
            }

            // Начисляем баллы лояльности
            $loyaltyPoints = $this->calculateLoyaltyPoints($booking);
            if ($loyaltyPoints > 0) {
                $this->paymentService->addLoyaltyPoints($booking->client_id, $loyaltyPoints);
                $bonusResult['loyalty_points'] = $loyaltyPoints;
            }

            // Начисляем бонус мастеру за качество услуги
            if ($event->serviceQuality >= 4) {
                $masterBonus = $this->calculateMasterQualityBonus($booking, $event->serviceQuality);
                if ($masterBonus > 0) {
                    $this->paymentService->addBonusToMaster($booking->master_id, $masterBonus, 'quality_bonus');
                    $bonusResult['master_bonus'] = $masterBonus;
                }
            }

        } catch (Exception $e) {
            Log::warning('Failed to process bonuses for completed booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $bonusResult;
    }

    /**
     * Рассчитать бонус клиента (кэшбек)
     */
    private function calculateClientBonus($booking): float
    {
        $cashbackRate = config('bonuses.client_cashback_rate', 0.02); // 2%
        return round($booking->total_price * $cashbackRate, 2);
    }

    /**
     * Рассчитать баллы лояльности
     */
    private function calculateLoyaltyPoints($booking): int
    {
        $pointsPerRuble = config('bonuses.loyalty_points_per_ruble', 0.1);
        return (int) round($booking->total_price * $pointsPerRuble);
    }

    /**
     * Рассчитать бонус мастера за качество
     */
    private function calculateMasterQualityBonus($booking, int $serviceQuality): float
    {
        if ($serviceQuality === 5) {
            return round($booking->master_earnings * 0.05, 2); // 5% за отличное качество
        }

        if ($serviceQuality === 4) {
            return round($booking->master_earnings * 0.02, 2); // 2% за хорошее качество
        }

        return 0;
    }

    /**
     * Создать запись о завершении
     */
    private function createCompletionRecord($booking, BookingCompleted $event, array $bonusResult): void
    {
        $this->bookingRepository->createCompletionRecord([
            'booking_id' => $booking->id,
            'completed_at' => $event->completedAt,
            'service_quality' => $event->serviceQuality,
            'completion_notes' => $event->notes,
            'client_bonus_earned' => $bonusResult['client_bonus'],
            'master_bonus_earned' => $bonusResult['master_bonus'],
            'loyalty_points_earned' => $bonusResult['loyalty_points'],
            'actual_duration' => $this->calculateActualDuration($booking, $event->completedAt),
        ]);
    }

    /**
     * Рассчитать фактическую продолжительность услуги
     */
    private function calculateActualDuration($booking, \DateTime $completedAt): ?int
    {
        if (!$booking->started_at) {
            return null;
        }

        $startedAt = new \DateTime($booking->started_at);
        $diff = $startedAt->diff($completedAt);
        
        return ($diff->h * 60) + $diff->i; // в минутах
    }

    /**
     * Отправить уведомления о завершении
     */
    private function sendCompletionNotifications($booking, BookingCompleted $event, array $bonusResult): void
    {
        try {
            // Уведомление клиенту с информацией о бонусах
            $this->notificationService->sendCompletionToClient($booking, [
                'service_quality' => $event->serviceQuality,
                'bonus_earned' => $bonusResult['client_bonus'],
                'loyalty_points' => $bonusResult['loyalty_points'],
                'can_leave_review' => true,
                'review_deadline' => now()->addDays(30)->format('d.m.Y'),
            ]);

            // Уведомление мастеру
            $this->notificationService->sendCompletionToMaster($booking, [
                'service_quality' => $event->serviceQuality,
                'bonus_earned' => $bonusResult['master_bonus'],
                'earnings' => $booking->master_earnings,
            ]);

            // SMS уведомления
            if (config('notifications.sms.enabled', false)) {
                $this->notificationService->sendSmsToClient($booking, 'booking_completed');
            }

        } catch (Exception $e) {
            Log::warning('Failed to send completion notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(BookingCompleted::class, [self::class, 'handle']);
    }
}