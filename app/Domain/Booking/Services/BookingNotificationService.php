<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Infrastructure\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Сервис уведомлений для завершения бронирований
 */
class BookingNotificationService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Отправка уведомлений о завершении
     */
    public function sendCompletionNotifications(Booking $booking, array $options): void
    {
        try {
            $this->sendBasicCompletionNotification($booking);
            $this->scheduleReviewRequest($booking, $options);
            $this->sendOptionalNotifications($booking, $options);

            Log::info('Completion notifications sent', [
                'booking_id' => $booking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send completion notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Базовое уведомление о завершении
     */
    private function sendBasicCompletionNotification(Booking $booking): void
    {
        $this->notificationService->sendBookingCompleted($booking);
    }

    /**
     * Запланировать запрос отзыва
     */
    private function scheduleReviewRequest(Booking $booking, array $options): void
    {
        if ($options['request_review'] ?? true) {
            dispatch(function () use ($booking) {
                $this->notificationService->sendReviewRequest($booking);
            })->delay(now()->addHours(2)); // Через 2 часа после завершения
        }
    }

    /**
     * Отправить опциональные уведомления
     */
    private function sendOptionalNotifications(Booking $booking, array $options): void
    {
        // SMS с благодарностью
        if ($booking->client_phone && ($options['send_sms'] ?? true)) {
            $this->notificationService->sendCompletionSMS($booking);
        }

        // Email чек об оказанной услуге
        if ($booking->client_email && ($options['send_receipt'] ?? true)) {
            $this->notificationService->sendServiceReceipt($booking);
        }
    }
}