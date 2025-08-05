<?php

namespace App\Infrastructure\Listeners\Booking;

use App\Domain\Booking\Events\BookingCancelled;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Booking\Services\NotificationService;
use App\Domain\Booking\Services\PaymentService;
use App\Domain\Booking\Services\RefundService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик отмены бронирования
 * 
 * ФУНКЦИИ:
 * - Освобождение временного слота
 * - Обработка возврата средств
 * - Обновление статистики отмен
 * - Отправка уведомлений об отмене
 * - Применение штрафов за позднюю отмену
 */
class HandleBookingCancelled
{
    private BookingRepository $bookingRepository;
    private NotificationService $notificationService;
    private PaymentService $paymentService;
    private RefundService $refundService;

    public function __construct(
        BookingRepository $bookingRepository,
        NotificationService $notificationService,
        PaymentService $paymentService,
        RefundService $refundService
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->notificationService = $notificationService;
        $this->paymentService = $paymentService;
        $this->refundService = $refundService;
    }

    /**
     * Обработка события BookingCancelled
     */
    public function handle(BookingCancelled $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Получаем бронирование
                $booking = $this->bookingRepository->findById($event->bookingId);
                if (!$booking) {
                    throw new Exception("Бронирование с ID {$event->bookingId} не найдено");
                }

                // 2. Проверяем, можно ли отменить
                $this->validateCancellation($booking, $event);

                // 3. Освобождаем временной слот
                $this->releaseTimeSlot($booking);

                // 4. Обрабатываем возврат средств
                $refundResult = $this->processRefund($booking, $event);

                // 5. Обновляем статистику
                $this->updateCancellationStats($booking, $event);

                // 6. Создаем запись об отмене
                $this->createCancellationRecord($booking, $event, $refundResult);

                // 7. Отправляем уведомления
                $this->sendCancellationNotifications($booking, $event, $refundResult);

                Log::info('Booking cancelled successfully', [
                    'booking_id' => $event->bookingId,
                    'cancelled_by' => $event->cancelledBy,
                    'reason' => $event->reason,
                    'refund_amount' => $refundResult['amount'],
                    'penalty_applied' => $refundResult['penalty_applied'],
                ]);

            } catch (Exception $e) {
                Log::error('Failed to handle BookingCancelled event', [
                    'booking_id' => $event->bookingId,
                    'cancelled_by' => $event->cancelledBy,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Валидация возможности отмены
     */
    private function validateCancellation($booking, BookingCancelled $event): void
    {
        // Проверяем, что бронирование можно отменить
        if (in_array($booking->status, ['completed', 'cancelled'])) {
            throw new Exception("Нельзя отменить бронирование со статусом {$booking->status}");
        }

        // Проверяем, что бронирование не началось
        if ($booking->status === 'in_progress') {
            throw new Exception("Нельзя отменить бронирование, которое уже началось");
        }

        // Проверяем права на отмену
        if (!$this->canUserCancelBooking($booking, $event->cancelledBy, $event->cancelledByRole)) {
            throw new Exception("У пользователя нет прав на отмену этого бронирования");
        }
    }

    /**
     * Проверить права пользователя на отмену
     */
    private function canUserCancelBooking($booking, ?int $userId, ?string $userRole): bool
    {
        // Админ может отменить любое бронирование
        if ($userRole === 'admin') {
            return true;
        }

        // Клиент может отменить свое бронирование
        if ($userRole === 'client' && $booking->client_id === $userId) {
            return true;
        }

        // Мастер может отменить бронирование к себе
        if ($userRole === 'master' && $booking->master_id === $userId) {
            return true;
        }

        return false;
    }

    /**
     * Освободить временной слот
     */
    private function releaseTimeSlot($booking): void
    {
        // Освобождаем слот в календаре мастера
        $this->bookingRepository->releaseTimeSlot($booking);

        // Помечаем слот как доступный для новых бронирований
        $this->bookingRepository->makeTimeSlotAvailable(
            $booking->master_id,
            $booking->scheduled_at,
            $booking->service_duration
        );

        Log::info('Time slot released', [
            'booking_id' => $booking->id,
            'master_id' => $booking->master_id,
            'scheduled_at' => $booking->scheduled_at,
        ]);
    }

    /**
     * Обработать возврат средств
     */
    private function processRefund($booking, BookingCancelled $event): array
    {
        // Если платеж не был произведен, возвращать нечего
        if ($booking->payment_status !== 'paid') {
            return [
                'amount' => 0,
                'penalty_applied' => false,
                'refund_processed' => false,
            ];
        }

        // Рассчитываем сумму возврата с учетом штрафов
        $refundCalculation = $this->refundService->calculateRefundAmount(
            $booking,
            $event->cancelledAt,
            $event->cancelledByRole
        );

        // Обрабатываем возврат средств
        if ($refundCalculation['refund_amount'] > 0) {
            $refundResult = $this->paymentService->processRefund(
                $booking,
                $refundCalculation['refund_amount'],
                "Отмена бронирования: {$event->reason}"
            );

            return [
                'amount' => $refundCalculation['refund_amount'],
                'penalty_applied' => $refundCalculation['penalty_applied'],
                'penalty_amount' => $refundCalculation['penalty_amount'],
                'refund_processed' => $refundResult['success'],
                'refund_transaction_id' => $refundResult['transaction_id'] ?? null,
            ];
        }

        return [
            'amount' => 0,
            'penalty_applied' => $refundCalculation['penalty_applied'],
            'penalty_amount' => $refundCalculation['penalty_amount'],
            'refund_processed' => false,
        ];
    }

    /**
     * Обновить статистику отмен
     */
    private function updateCancellationStats($booking, BookingCancelled $event): void
    {
        // Обновляем статистику клиента
        $this->bookingRepository->incrementClientCancellations($booking->client_id);

        // Обновляем статистику мастера
        $this->bookingRepository->incrementMasterCancellations($booking->master_id);

        // Обновляем общую статистику по отменам
        $this->bookingRepository->updateCancellationStats([
            'cancelled_by_role' => $event->cancelledByRole,
            'hours_before_booking' => $this->calculateHoursBeforeBooking($booking, $event->cancelledAt),
            'reason_category' => $this->categorizeCancellationReason($event->reason),
        ]);
    }

    /**
     * Рассчитать количество часов до бронирования
     */
    private function calculateHoursBeforeBooking($booking, \DateTime $cancelledAt): int
    {
        $scheduledAt = new \DateTime($booking->scheduled_at);
        $diff = $cancelledAt->diff($scheduledAt);
        
        return ($diff->days * 24) + $diff->h;
    }

    /**
     * Категоризировать причину отмены
     */
    private function categorizeCancellationReason(?string $reason): string
    {
        if (!$reason) {
            return 'no_reason';
        }

        $reason = strtolower($reason);

        if (str_contains($reason, 'болезнь') || str_contains($reason, 'здоровье')) {
            return 'health';
        }

        if (str_contains($reason, 'работа') || str_contains($reason, 'дел')) {
            return 'work';
        }

        if (str_contains($reason, 'погода') || str_contains($reason, 'транспорт')) {
            return 'external';
        }

        return 'other';
    }

    /**
     * Создать запись об отмене
     */
    private function createCancellationRecord($booking, BookingCancelled $event, array $refundResult): void
    {
        $this->bookingRepository->createCancellationRecord([
            'booking_id' => $booking->id,
            'cancelled_by' => $event->cancelledBy,
            'cancelled_by_role' => $event->cancelledByRole,
            'reason' => $event->reason,
            'cancelled_at' => $event->cancelledAt,
            'refund_amount' => $refundResult['amount'],
            'penalty_applied' => $refundResult['penalty_applied'],
            'penalty_amount' => $refundResult['penalty_amount'] ?? 0,
            'hours_before_booking' => $this->calculateHoursBeforeBooking($booking, $event->cancelledAt),
        ]);
    }

    /**
     * Отправить уведомления об отмене
     */
    private function sendCancellationNotifications($booking, BookingCancelled $event, array $refundResult): void
    {
        try {
            // Уведомление клиенту
            $this->notificationService->sendCancellationToClient($booking, [
                'cancelled_by_role' => $event->cancelledByRole,
                'reason' => $event->reason,
                'refund_amount' => $refundResult['amount'],
                'penalty_applied' => $refundResult['penalty_applied'],
            ]);

            // Уведомление мастеру
            $this->notificationService->sendCancellationToMaster($booking, [
                'cancelled_by_role' => $event->cancelledByRole,
                'reason' => $event->reason,
            ]);

            // SMS уведомления
            if (config('notifications.sms.enabled', false)) {
                $this->notificationService->sendSmsToClient($booking, 'booking_cancelled');
                $this->notificationService->sendSmsToMaster($booking, 'booking_cancelled');
            }

        } catch (Exception $e) {
            Log::warning('Failed to send cancellation notifications', [
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
        $events->listen(BookingCancelled::class, [self::class, 'handle']);
    }
}