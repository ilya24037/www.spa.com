<?php

namespace App\Infrastructure\Notification\Booking;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Infrastructure\Notification\NotificationService;
use App\Enums\NotificationType;
use App\Enums\NotificationChannel;

/**
 * Сервис уведомлений для модуля бронирования
 * Инкапсулирует всю логику отправки уведомлений связанных с бронированиями
 */
class BookingNotificationService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Отправить уведомления о новом бронировании
     */
    public function sendBookingCreated(Booking $booking): void
    {
        // Уведомление мастеру
        if ($booking->master) {
            $this->notificationService->send(
                user: $booking->master,
                type: NotificationType::BOOKING_CREATED,
                data: [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'client_name' => $booking->client_name,
                    'service_name' => $booking->service->name,
                    'date' => $booking->start_time->format('d.m.Y'),
                    'time' => $booking->start_time->format('H:i'),
                    'action_url' => route('master.bookings.show', $booking),
                    'action_text' => 'Просмотреть бронирование'
                ],
                channels: [NotificationChannel::DATABASE, NotificationChannel::PUSH]
            );

            // SMS мастеру
            $this->sendSmsToMaster($booking, 'new_booking');
        }

        // Уведомление клиенту
        if ($booking->client) {
            $this->notificationService->send(
                user: $booking->client,
                type: NotificationType::BOOKING_CREATED,
                data: [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'master_name' => $booking->master->name,
                    'service_name' => $booking->service->name,
                    'date' => $booking->start_time->format('d.m.Y'),
                    'time' => $booking->start_time->format('H:i'),
                    'total_price' => $booking->total_price,
                    'action_url' => route('bookings.show', $booking),
                    'action_text' => 'Детали бронирования'
                ],
                channels: [NotificationChannel::DATABASE, NotificationChannel::EMAIL]
            );
        }

        // SMS клиенту с подтверждением
        $this->sendSmsToClient($booking, 'booking_confirmation');
    }

    /**
     * Отправить уведомление о подтверждении бронирования
     */
    public function sendBookingConfirmed(Booking $booking): void
    {
        if ($booking->client) {
            $this->notificationService->send(
                user: $booking->client,
                type: NotificationType::BOOKING_CONFIRMED,
                data: [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'master_name' => $booking->master->name,
                    'date' => $booking->start_time->format('d.m.Y'),
                    'time' => $booking->start_time->format('H:i'),
                    'address' => $booking->getAddress(),
                    'action_url' => route('bookings.show', $booking),
                    'action_text' => 'Посмотреть детали'
                ],
                channels: [NotificationChannel::DATABASE, NotificationChannel::PUSH, NotificationChannel::SMS]
            );
        }
    }

    /**
     * Отправить уведомление об отмене
     */
    public function sendBookingCancelled(Booking $booking, User $cancelledBy): void
    {
        $isCancelledByClient = $cancelledBy->id === $booking->client_id;
        
        // Уведомляем другую сторону
        if ($isCancelledByClient && $booking->master) {
            // Клиент отменил - уведомляем мастера
            $this->notificationService->send(
                user: $booking->master,
                type: NotificationType::BOOKING_CANCELLED,
                data: [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'client_name' => $booking->client_name,
                    'date' => $booking->start_time->format('d.m.Y'),
                    'time' => $booking->start_time->format('H:i'),
                    'reason' => $booking->cancellation_reason,
                    'cancelled_by' => 'клиентом'
                ],
                channels: [NotificationChannel::DATABASE, NotificationChannel::PUSH, NotificationChannel::SMS]
            );
        } elseif (!$isCancelledByClient && $booking->client) {
            // Мастер отменил - уведомляем клиента
            $this->notificationService->send(
                user: $booking->client,
                type: NotificationType::BOOKING_CANCELLED,
                data: [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'master_name' => $booking->master->name,
                    'date' => $booking->start_time->format('d.m.Y'),
                    'time' => $booking->start_time->format('H:i'),
                    'reason' => $booking->cancellation_reason,
                    'cancelled_by' => 'мастером',
                    'refund_info' => $this->getRefundInfo($booking)
                ],
                channels: [NotificationChannel::DATABASE, NotificationChannel::EMAIL, NotificationChannel::SMS]
            );
        }
    }

    /**
     * Отправить напоминание о предстоящем бронировании
     */
    public function sendBookingReminder(Booking $booking): void
    {
        // Напоминание клиенту за день
        if ($booking->client) {
            $this->notificationService->send(
                user: $booking->client,
                type: NotificationType::BOOKING_REMINDER,
                data: [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'master_name' => $booking->master->name,
                    'service_name' => $booking->service->name,
                    'date' => $booking->start_time->format('d.m.Y'),
                    'time' => $booking->start_time->format('H:i'),
                    'address' => $booking->getAddress(),
                    'master_phone' => $booking->master->phone
                ],
                channels: [NotificationChannel::PUSH, NotificationChannel::SMS]
            );
        }

        // Напоминание мастеру
        if ($booking->master) {
            $this->notificationService->send(
                user: $booking->master,
                type: NotificationType::BOOKING_REMINDER,
                data: [
                    'booking_id' => $booking->id,
                    'client_name' => $booking->client_name,
                    'client_phone' => $booking->client_phone,
                    'service_name' => $booking->service->name,
                    'time' => $booking->start_time->format('H:i')
                ],
                channels: [NotificationChannel::PUSH]
            );
        }
    }

    /**
     * Отправить запрос на отзыв
     */
    public function sendReviewRequest(Booking $booking): void
    {
        if ($booking->client) {
            $this->notificationService->send(
                user: $booking->client,
                type: NotificationType::REVIEW_REQUEST,
                data: [
                    'booking_id' => $booking->id,
                    'master_name' => $booking->master->name,
                    'service_name' => $booking->service->name,
                    'action_url' => route('bookings.review', $booking),
                    'action_text' => 'Оставить отзыв'
                ],
                channels: [NotificationChannel::DATABASE, NotificationChannel::PUSH]
            );

            // Email через 2 часа после услуги
            $this->notificationService->sendDelayed(
                user: $booking->client,
                type: NotificationType::REVIEW_REQUEST,
                data: [
                    'booking_id' => $booking->id,
                    'master_name' => $booking->master->name,
                    'service_name' => $booking->service->name,
                    'action_url' => route('bookings.review', $booking),
                    'action_text' => 'Оставить отзыв'
                ],
                channels: [NotificationChannel::EMAIL],
                delay: now()->addHours(2)
            );
        }
    }

    /**
     * Уведомление о переносе бронирования
     */
    public function sendBookingRescheduled(Booking $booking): void
    {
        // Уведомляем клиента
        if ($booking->client) {
            $this->notificationService->send(
                user: $booking->client,
                type: NotificationType::BOOKING_RESCHEDULED,
                data: [
                    'booking_number' => $booking->booking_number,
                    'old_time' => $booking->getOriginal('start_time'),
                    'new_date' => $booking->start_time->format('d.m.Y'),
                    'new_time' => $booking->start_time->format('H:i')
                ],
                channels: [NotificationChannel::DATABASE, NotificationChannel::SMS]
            );
        }

        // Уведомляем мастера
        if ($booking->master) {
            $this->notificationService->send(
                user: $booking->master,
                type: NotificationType::BOOKING_RESCHEDULED,
                data: [
                    'booking_number' => $booking->booking_number,
                    'client_name' => $booking->client_name,
                    'new_date' => $booking->start_time->format('d.m.Y'),
                    'new_time' => $booking->start_time->format('H:i')
                ],
                channels: [NotificationChannel::DATABASE, NotificationChannel::PUSH]
            );
        }
    }

    /**
     * Отправить SMS мастеру
     */
    private function sendSmsToMaster(Booking $booking, string $template): void
    {
        if (!$booking->master->phone) {
            return;
        }

        $message = match($template) {
            'new_booking' => sprintf(
                "Новое бронирование №%s на %s в %s. Клиент: %s",
                $booking->booking_number,
                $booking->start_time->format('d.m'),
                $booking->start_time->format('H:i'),
                $booking->client_name
            ),
            default => ''
        };

        if ($message) {
            $this->notificationService->sendSms($booking->master->phone, $message);
        }
    }

    /**
     * Отправить SMS клиенту
     */
    private function sendSmsToClient(Booking $booking, string $template): void
    {
        if (!$booking->client_phone) {
            return;
        }

        $message = match($template) {
            'booking_confirmation' => sprintf(
                "Бронирование №%s подтверждено на %s в %s. Мастер: %s",
                $booking->booking_number,
                $booking->start_time->format('d.m'),
                $booking->start_time->format('H:i'),
                $booking->master->name
            ),
            default => ''
        };

        if ($message) {
            $this->notificationService->sendSms($booking->client_phone, $message);
        }
    }

    /**
     * Получить информацию о возврате
     */
    private function getRefundInfo(Booking $booking): string
    {
        if ($booking->payment_status === 'paid') {
            return 'Оплаченная сумма будет возвращена в течение 3-5 рабочих дней.';
        }
        
        return '';
    }
}