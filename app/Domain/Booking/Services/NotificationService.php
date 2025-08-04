<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Infrastructure\Notification\NotificationService as BaseNotificationService;

class NotificationService
{
    public function __construct(
        private BaseNotificationService $notificationService
    ) {}

    public function sendBookingCreated(Booking $booking): void
    {
        // Уведомление клиенту о создании бронирования
        $this->notificationService->sendByTemplate(
            $booking->client,
            'booking.created',
            [
                'booking_number' => $booking->booking_number,
                'master_name' => $booking->master->name ?? 'Мастер',
                'service_name' => $booking->service->name ?? 'Услуга',
                'date' => $booking->start_time->format('d.m.Y'),
                'time' => $booking->start_time->format('H:i'),
                'status' => 'Ожидает подтверждения',
            ]
        );
        
        // Уведомление мастеру о новом бронировании
        if ($booking->master && $booking->master->user) {
            $this->notificationService->sendByTemplate(
                $booking->master->user,
                'booking.new.master',
                [
                    'client_name' => $booking->client->name,
                    'service_name' => $booking->service->name ?? 'Услуга',
                    'date' => $booking->start_time->format('d.m.Y'),
                    'time' => $booking->start_time->format('H:i'),
                    'phone' => $booking->client_phone ?? '',
                ]
            );
        }
    }

    public function sendBookingConfirmation(Booking $booking): void
    {
        $this->notificationService->sendByTemplate(
            $booking->client,
            'booking.confirmed',
            [
                'booking_number' => $booking->booking_number,
                'master_name' => $booking->master->name,
                'date' => $booking->start_time->format('d.m.Y'),
                'time' => $booking->start_time->format('H:i'),
            ]
        );
    }

    public function sendBookingCancellation(Booking $booking): void
    {
        $this->notificationService->sendByTemplate(
            $booking->client,
            'booking.cancelled',
            [
                'booking_number' => $booking->booking_number,
                'master_name' => $booking->master->name,
                'date' => $booking->start_time->format('d.m.Y'),
                'time' => $booking->start_time->format('H:i'),
                'reason' => $booking->cancellation_reason ?? 'Не указана',
            ]
        );

        // Уведомляем мастера о отмене
        $this->notificationService->sendByTemplate(
            $booking->master->user,
            'booking.cancelled.master',
            [
                'client_name' => $booking->client->name,
                'date' => $booking->start_time->format('d.m.Y'),
                'time' => $booking->start_time->format('H:i'),
            ]
        );
    }

    public function sendBookingReminder(Booking $booking): void
    {
        // Напоминание клиенту за день до визита
        $this->notificationService->sendByTemplate(
            $booking->client,
            'booking.reminder',
            [
                'booking_number' => $booking->booking_number,
                'master_name' => $booking->master->name,
                'date' => $booking->start_time->format('d.m.Y'),
                'time' => $booking->start_time->format('H:i'),
                'address' => $booking->address ?? $booking->master->address,
                'phone' => $booking->master->phone,
            ]
        );
    }

    public function sendBookingCompleted(Booking $booking): void
    {
        // Уведомление после завершения услуги
        $this->notificationService->sendByTemplate(
            $booking->client,
            'booking.completed',
            [
                'booking_number' => $booking->booking_number,
                'master_name' => $booking->master->name,
                'review_url' => route('bookings.review', $booking->id),
            ]
        );
    }

    public function sendBookingRescheduled(Booking $booking, array $oldDateTime): void
    {
        // Уведомление о переносе времени
        $this->notificationService->sendByTemplate(
            $booking->client,
            'booking.rescheduled',
            [
                'booking_number' => $booking->booking_number,
                'master_name' => $booking->master->name,
                'old_date' => $oldDateTime['date'],
                'old_time' => $oldDateTime['time'],
                'new_date' => $booking->start_time->format('d.m.Y'),
                'new_time' => $booking->start_time->format('H:i'),
            ]
        );
    }
}