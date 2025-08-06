<?php

namespace App\Infrastructure\Listeners\Master\Services;

use App\Domain\Master\Events\MasterStatusChanged;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Master\Services\MasterNotificationService;
use App\Domain\Booking\Events\BookingCancelled;

/**
 * Обработчик бронирований при изменении статуса мастера
 */
class BookingsHandler
{
    private MasterRepository $masterRepository;
    private MasterNotificationService $notificationService;

    public function __construct(
        MasterRepository $masterRepository,
        MasterNotificationService $notificationService
    ) {
        $this->masterRepository = $masterRepository;
        $this->notificationService = $notificationService;
    }

    /**
     * Обработать активные бронирования
     */
    public function handleActiveBookings($masterProfile, MasterStatusChanged $event): void
    {
        // Получаем активные бронирования
        $activeBookings = $this->masterRepository->getActiveBookings($masterProfile->id);

        if ($activeBookings->isEmpty()) {
            return;
        }

        switch ($event->newStatus) {
            case 'suspended':
            case 'banned':
                // Отменяем будущие бронирования с возвратом средств
                $this->cancelFutureBookings($activeBookings, $event->reason);
                break;

            case 'inactive':
                // Уведомляем клиентов о временной недоступности
                $this->notifyClientsAboutUnavailability($activeBookings);
                break;
        }
    }

    /**
     * Отменить будущие бронирования
     */
    private function cancelFutureBookings($bookings, ?string $reason): void
    {
        foreach ($bookings as $booking) {
            if ($booking->scheduled_at > now()) {
                // Используем событие для отмены бронирования
                event(new BookingCancelled(
                    bookingId: $booking->id,
                    clientId: $booking->client_id,
                    masterId: $booking->master_id,
                    cancelledBy: null,
                    cancelledByRole: 'system',
                    reason: "Мастер недоступен: " . ($reason ?: 'Профиль заблокирован'),
                    cancelledAt: now()
                ));
            }
        }
    }

    /**
     * Уведомить клиентов о недоступности
     */
    private function notifyClientsAboutUnavailability($bookings): void
    {
        foreach ($bookings as $booking) {
            if ($booking->scheduled_at > now()) {
                $this->notificationService->sendMasterUnavailableNotification($booking);
            }
        }
    }
}