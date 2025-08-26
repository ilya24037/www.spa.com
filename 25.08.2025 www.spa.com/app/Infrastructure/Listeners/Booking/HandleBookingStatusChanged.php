<?php

namespace App\Infrastructure\Listeners\Booking;

use App\Domain\Booking\Events\BookingStatusChanged;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Booking\Services\NotificationService;
use App\Domain\Booking\Services\PaymentService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик изменения статуса бронирования
 * 
 * ФУНКЦИИ:
 * - Обновление статуса в БД
 * - Отправка уведомлений о смене статуса
 * - Обработка платежей при подтверждении
 * - Освобождение слотов при отмене
 * - Создание истории изменений
 */
class HandleBookingStatusChanged
{
    private BookingRepository $bookingRepository;
    private NotificationService $notificationService;
    private PaymentService $paymentService;

    public function __construct(
        BookingRepository $bookingRepository,
        NotificationService $notificationService,
        PaymentService $paymentService
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->notificationService = $notificationService;
        $this->paymentService = $paymentService;
    }

    /**
     * Обработка события BookingStatusChanged
     */
    public function handle(BookingStatusChanged $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Получаем бронирование
                $booking = $this->bookingRepository->findById($event->bookingId);
                if (!$booking) {
                    throw new Exception("Бронирование с ID {$event->bookingId} не найдено");
                }

                // 2. Обновляем статус
                $this->updateBookingStatus($booking, $event->newStatus);

                // 3. Выполняем действия в зависимости от нового статуса
                $this->handleStatusSpecificActions($booking, $event);

                // 4. Создаем запись в истории
                $this->createStatusHistory($booking, $event);

                // 5. Отправляем уведомления
                $this->sendStatusChangeNotifications($booking, $event);

                Log::info('Booking status changed successfully', [
                    'booking_id' => $event->bookingId,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                    'client_id' => $event->clientId,
                    'master_id' => $event->masterId,
                ]);

            } catch (Exception $e) {
                Log::error('Failed to handle BookingStatusChanged event', [
                    'booking_id' => $event->bookingId,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Обновить статус бронирования
     */
    private function updateBookingStatus($booking, string $newStatus): void
    {
        $booking->update([
            'status' => $newStatus,
            'status_updated_at' => now(),
        ]);
    }

    /**
     * Выполнить действия специфичные для статуса
     */
    private function handleStatusSpecificActions($booking, BookingStatusChanged $event): void
    {
        switch ($event->newStatus) {
            case 'confirmed':
                $this->handleConfirmedStatus($booking);
                break;

            case 'cancelled':
                $this->handleCancelledStatus($booking);
                break;

            case 'completed':
                $this->handleCompletedStatus($booking);
                break;

            case 'no_show':
                $this->handleNoShowStatus($booking);
                break;

            case 'in_progress':
                $this->handleInProgressStatus($booking);
                break;
        }
    }

    /**
     * Обработка подтвержденного бронирования
     */
    private function handleConfirmedStatus($booking): void
    {
        // Обновляем статус платежа если нужно
        if ($booking->payment_status === 'pending') {
            $this->paymentService->processBookingPayment($booking);
        }

        // Добавляем в календарь мастера
        $this->bookingRepository->addToMasterCalendar($booking);
    }

    /**
     * Обработка отмененного бронирования
     */
    private function handleCancelledStatus($booking): void
    {
        // Освобождаем временной слот
        $this->bookingRepository->releaseTimeSlot($booking);

        // Обрабатываем возврат средств если платеж был произведен
        if ($booking->payment_status === 'paid') {
            $this->paymentService->processRefund($booking);
        }

        // Удаляем из календаря мастера
        $this->bookingRepository->removeFromMasterCalendar($booking);
    }

    /**
     * Обработка завершенного бронирования
     */
    private function handleCompletedStatus($booking): void
    {
        // Обновляем статус платежа на завершенный
        $booking->update(['payment_status' => 'completed']);

        // Создаем возможность для отзыва
        $this->bookingRepository->enableReviewCreation($booking);

        // Обновляем статистику мастера
        $this->bookingRepository->updateMasterStats($booking->master_id);
    }

    /**
     * Обработка статуса "не явился"
     */
    private function handleNoShowStatus($booking): void
    {
        // Освобождаем слот, но сохраняем платеж (штраф)
        $this->bookingRepository->releaseTimeSlot($booking);
        
        // Обновляем статистику клиента (negative)
        $this->bookingRepository->updateClientReliabilityScore($booking->client_id, -1);
    }

    /**
     * Обработка статуса "в процессе"
     */
    private function handleInProgressStatus($booking): void
    {
        // Отмечаем время начала услуги
        $booking->update(['started_at' => now()]);
    }

    /**
     * Создать запись в истории изменений статуса
     */
    private function createStatusHistory($booking, BookingStatusChanged $event): void
    {
        $this->bookingRepository->createStatusHistory([
            'booking_id' => $booking->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'changed_by' => $event->changedBy ?? null,
            'reason' => $event->reason ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * Отправить уведомления об изменении статуса
     */
    private function sendStatusChangeNotifications($booking, BookingStatusChanged $event): void
    {
        try {
            // Определяем тип уведомления по новому статусу
            $notificationType = $this->getNotificationTypeByStatus($event->newStatus);

            // Уведомление клиенту
            $this->notificationService->sendStatusChangeToClient($booking, $notificationType, $event->oldStatus);

            // Уведомление мастеру
            $this->notificationService->sendStatusChangeToMaster($booking, $notificationType, $event->oldStatus);

            // SMS уведомления для важных статусов
            if ($this->shouldSendSmsForStatus($event->newStatus)) {
                $this->notificationService->sendSmsToClient($booking, "status_{$event->newStatus}");
                $this->notificationService->sendSmsToMaster($booking, "status_{$event->newStatus}");
            }

        } catch (Exception $e) {
            Log::warning('Failed to send status change notifications', [
                'booking_id' => $booking->id,
                'new_status' => $event->newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Получить тип уведомления по статусу
     */
    private function getNotificationTypeByStatus(string $status): string
    {
        return match ($status) {
            'confirmed' => 'booking_confirmed',
            'cancelled' => 'booking_cancelled',
            'completed' => 'booking_completed',
            'no_show' => 'booking_no_show',
            'in_progress' => 'booking_started',
            default => 'booking_status_changed',
        };
    }

    /**
     * Проверить, нужно ли отправлять SMS для данного статуса
     */
    private function shouldSendSmsForStatus(string $status): bool
    {
        $smsStatuses = ['confirmed', 'cancelled', 'completed'];
        
        return config('notifications.sms.enabled', false) && 
               in_array($status, $smsStatuses);
    }

    /**
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(BookingStatusChanged::class, [self::class, 'handle']);
    }
}