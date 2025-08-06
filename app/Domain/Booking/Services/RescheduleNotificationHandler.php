<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Infrastructure\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик уведомлений о переносе бронирований
 */
class RescheduleNotificationHandler
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Отправить уведомления о переносе
     */
    public function sendRescheduleNotifications(
        Booking $booking, 
        User $rescheduledBy, 
        Carbon $oldTime
    ): void {
        try {
            $isClient = $booking->client_id === $rescheduledBy->id;

            if ($isClient) {
                // Клиент перенес - уведомляем мастера
                $this->sendMasterNotification($booking, $oldTime);
            } else {
                // Мастер перенес - уведомляем клиента
                $this->sendClientNotification($booking, $oldTime);
            }

            // SMS уведомление
            $this->sendSmsNotification($booking, $oldTime, $isClient);

            // Email уведомление
            $this->sendEmailNotification($booking, $oldTime, $isClient);

            Log::info('Reschedule notifications sent', [
                'booking_id' => $booking->id,
                'rescheduled_by' => $isClient ? 'client' : 'master',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send reschedule notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Уведомить мастера о переносе клиентом
     */
    private function sendMasterNotification(Booking $booking, Carbon $oldTime): void
    {
        if (!$booking->master) {
            return;
        }

        $this->notificationService->sendBookingRescheduledByClient($booking, $oldTime);
    }

    /**
     * Уведомить клиента о переносе мастером
     */
    private function sendClientNotification(Booking $booking, Carbon $oldTime): void
    {
        if (!$booking->client) {
            return;
        }

        $this->notificationService->sendBookingRescheduledByMaster($booking, $oldTime);
    }

    /**
     * Отправить SMS уведомление
     */
    private function sendSmsNotification(Booking $booking, Carbon $oldTime, bool $rescheduledByClient): void
    {
        if (!$booking->client_phone) {
            return;
        }

        $this->notificationService->sendRescheduleSMS($booking, $oldTime);
    }

    /**
     * Отправить Email уведомление
     */
    private function sendEmailNotification(Booking $booking, Carbon $oldTime, bool $rescheduledByClient): void
    {
        if (!$booking->client_email) {
            return;
        }

        $this->notificationService->sendRescheduleEmail($booking, $oldTime, $rescheduledByClient);
    }

    /**
     * Отправить уведомление об автоматическом переносе
     */
    public function sendAutoRescheduleNotification(Booking $booking, Carbon $oldTime, string $reason): void
    {
        try {
            // Уведомляем клиента
            if ($booking->client) {
                $this->notificationService->sendAutoRescheduleNotification($booking, $oldTime, $reason);
            }

            // Уведомляем мастера
            if ($booking->master) {
                $this->notificationService->sendMasterAutoRescheduleNotification($booking, $oldTime, $reason);
            }

            // SMS клиенту
            if ($booking->client_phone) {
                $this->notificationService->sendAutoRescheduleSMS($booking, $oldTime, $reason);
            }

            Log::info('Auto reschedule notifications sent', [
                'booking_id' => $booking->id,
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send auto reschedule notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомления о массовом переносе
     */
    public function sendBulkRescheduleNotifications(array $bookings, User $user, string $reason): void
    {
        foreach ($bookings as $bookingData) {
            if (!$bookingData['success']) {
                continue;
            }

            try {
                $booking = Booking::find($bookingData['booking_id']);
                if (!$booking) {
                    continue;
                }

                $oldTime = Carbon::parse($bookingData['old_time'] ?? $booking->created_at);
                $this->sendRescheduleNotifications($booking, $user, $oldTime);

            } catch (\Exception $e) {
                Log::error('Failed to send bulk reschedule notification', [
                    'booking_id' => $bookingData['booking_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Отправить напоминание о предстоящем бронировании после переноса
     */
    public function sendRescheduleReminder(Booking $booking): void
    {
        try {
            // Напоминание клиенту
            if ($booking->client && $booking->client_phone) {
                $this->notificationService->sendBookingReminder($booking);
            }

            // Напоминание мастеру
            if ($booking->master) {
                $this->notificationService->sendMasterBookingReminder($booking);
            }

            Log::info('Reschedule reminder sent', [
                'booking_id' => $booking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send reschedule reminder', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление о невозможности переноса
     */
    public function sendRescheduleFailedNotification(
        Booking $booking, 
        User $user, 
        string $errorMessage
    ): void {
        try {
            $this->notificationService->sendRescheduleFailedNotification($booking, $user, $errorMessage);

            Log::info('Reschedule failed notification sent', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'error' => $errorMessage,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send reschedule failed notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление о превышении лимита переносов
     */
    public function sendRescheduleLimitExceededNotification(Booking $booking, User $user): void
    {
        try {
            $this->notificationService->sendRescheduleLimitExceededNotification($booking, $user);

            Log::info('Reschedule limit exceeded notification sent', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send reschedule limit exceeded notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Получить шаблон уведомления для клиента
     */
    public function getClientNotificationTemplate(Booking $booking, Carbon $oldTime): array
    {
        return [
            'title' => 'Бронирование перенесено',
            'message' => sprintf(
                'Ваше бронирование №%s перенесено с %s на %s',
                $booking->booking_number,
                $oldTime->format('d.m.Y H:i'),
                $booking->start_time->format('d.m.Y H:i')
            ),
            'data' => [
                'booking_id' => $booking->id,
                'old_time' => $oldTime->toISOString(),
                'new_time' => $booking->start_time->toISOString(),
                'master_name' => $booking->master->name ?? 'Не указан',
                'service_name' => $booking->service->name ?? 'Не указана',
            ],
        ];
    }

    /**
     * Получить шаблон уведомления для мастера
     */
    public function getMasterNotificationTemplate(Booking $booking, Carbon $oldTime): array
    {
        return [
            'title' => 'Клиент перенес бронирование',
            'message' => sprintf(
                'Бронирование №%s клиента %s перенесено с %s на %s',
                $booking->booking_number,
                $booking->client->name ?? 'Не указан',
                $oldTime->format('d.m.Y H:i'),
                $booking->start_time->format('d.m.Y H:i')
            ),
            'data' => [
                'booking_id' => $booking->id,
                'old_time' => $oldTime->toISOString(),
                'new_time' => $booking->start_time->toISOString(),
                'client_name' => $booking->client->name ?? 'Не указан',
                'service_name' => $booking->service->name ?? 'Не указана',
            ],
        ];
    }
}