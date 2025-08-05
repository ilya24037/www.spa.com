<?php

namespace App\Domain\Booking\Listeners;

use App\Domain\Booking\Events\BookingCreated;
use App\Domain\Booking\Events\BookingCancelled;
use App\Domain\Booking\Events\BookingCompleted;
use App\Domain\Booking\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener для отправки уведомлений о бронированиях
 * Соответствует DDD архитектуре - размещен в Domain\Booking\Listeners
 */
class SendBookingNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Отправить уведомление о создании бронирования
     */
    public function handleBookingCreated(BookingCreated $event): void
    {
        try {
            $this->notificationService->sendBookingCreated($event->booking);
            
            Log::info('Booking created notification sent', [
                'booking_id' => $event->booking->id,
                'client_id' => $event->booking->client_id,
                'master_id' => $event->booking->master_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking created notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Если это критическая ошибка, можем перебросить для retry
            if ($this->shouldRetry($e)) {
                throw $e;
            }
        }
    }

    /**
     * Отправить уведомление об отмене бронирования
     */
    public function handleBookingCancelled(BookingCancelled $event): void
    {
        try {
            $this->notificationService->sendBookingCancellation($event->booking);
            
            Log::info('Booking cancelled notification sent', [
                'booking_id' => $event->booking->id,
                'reason' => $event->reason ?? 'No reason provided',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking cancelled notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);

            if ($this->shouldRetry($e)) {
                throw $e;
            }
        }
    }

    /**
     * Отправить уведомление о завершении бронирования
     */
    public function handleBookingCompleted(BookingCompleted $event): void
    {
        try {
            $this->notificationService->sendBookingCompleted($event->booking);
            
            Log::info('Booking completed notification sent', [
                'booking_id' => $event->booking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking completed notification', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
            ]);

            if ($this->shouldRetry($e)) {
                throw $e;
            }
        }
    }

    /**
     * Определить следует ли повторить отправку при ошибке
     */
    private function shouldRetry(\Exception $e): bool
    {
        // Повторяем при временных ошибках сети, не повторяем при ошибках валидации
        return !($e instanceof \InvalidArgumentException) &&
               !str_contains($e->getMessage(), 'validation') &&
               !str_contains($e->getMessage(), 'invalid');
    }

    /**
     * Максимальное количество попыток
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Количество попыток до провала
     */
    public int $tries = 3;

    /**
     * Время между попытками в секундах
     */
    public int $backoff = 30;
}