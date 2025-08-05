<?php

namespace App\Infrastructure\Listeners\Booking;

use App\Domain\Booking\Events\BookingRequested;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Booking\Services\NotificationService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик события создания бронирования
 * 
 * ФУНКЦИИ:
 * - Создание записи бронирования в БД
 * - Отправка уведомлений клиенту и мастеру
 * - Блокировка временного слота
 * - Логирование действий
 */
class HandleBookingRequested
{
    private BookingRepository $bookingRepository;
    private BookingService $bookingService;
    private NotificationService $notificationService;

    public function __construct(
        BookingRepository $bookingRepository,
        BookingService $bookingService,
        NotificationService $notificationService
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->bookingService = $bookingService;
        $this->notificationService = $notificationService;
    }

    /**
     * Обработка события BookingRequested
     */
    public function handle(BookingRequested $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Создаем бронирование
                $booking = $this->createBooking($event);

                // 2. Блокируем временной слот у мастера
                $this->blockTimeSlot($event->masterId, $booking);

                // 3. Отправляем уведомления
                $this->sendNotifications($booking, $event);

                // 4. Логируем успешное создание
                Log::info('Booking created successfully', [
                    'booking_id' => $booking->id,
                    'client_id' => $event->clientId,
                    'master_id' => $event->masterId,
                    'service_type' => $event->bookingData['service_type'] ?? null,
                ]);

            } catch (Exception $e) {
                Log::error('Failed to handle BookingRequested event', [
                    'client_id' => $event->clientId,
                    'master_id' => $event->masterId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Перебрасываем исключение для rollback транзакции
                throw $e;
            }
        });
    }

    /**
     * Создать запись бронирования
     */
    private function createBooking(BookingRequested $event)
    {
        $bookingData = [
            'client_id' => $event->clientId,
            'master_id' => $event->masterId,
            'service_type' => $event->bookingData['service_type'] ?? 'massage',
            'service_duration' => $event->bookingData['service_duration'] ?? 60,
            'scheduled_at' => $event->bookingData['scheduled_at'],
            'price' => $event->bookingData['price'] ?? 0,
            'status' => 'pending',
            'payment_status' => 'pending',
            'notes' => $event->bookingData['notes'] ?? null,
            'location_address' => $event->bookingData['location_address'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return $this->bookingRepository->create($bookingData);
    }

    /**
     * Заблокировать временной слот у мастера
     */
    private function blockTimeSlot(int $masterId, $booking): void
    {
        // Проверяем доступность слота перед блокировкой
        if (!$this->bookingService->isTimeSlotAvailable($masterId, $booking->scheduled_at, $booking->service_duration)) {
            throw new Exception("Временной слот уже занят для мастера {$masterId}");
        }

        // Блокируем слот
        $this->bookingService->blockTimeSlot($masterId, $booking->scheduled_at, $booking->service_duration, $booking->id);
    }

    /**
     * Отправить уведомления всем участникам
     */
    private function sendNotifications($booking, BookingRequested $event): void
    {
        try {
            // Уведомление клиенту
            $this->notificationService->sendBookingConfirmationToClient($booking);

            // Уведомление мастеру
            $this->notificationService->sendNewBookingNotificationToMaster($booking);

            // SMS уведомления (если включены)
            if ($this->shouldSendSmsNotifications()) {
                $this->notificationService->sendSmsToClient($booking, 'booking_requested');
                $this->notificationService->sendSmsToMaster($booking, 'new_booking');
            }

        } catch (Exception $e) {
            // Логируем ошибку уведомлений, но не прерываем основной процесс
            Log::warning('Failed to send booking notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Проверить, нужно ли отправлять SMS уведомления
     */
    private function shouldSendSmsNotifications(): bool
    {
        return config('notifications.sms.enabled', false) && 
               config('notifications.booking.sms_enabled', true);
    }

    /**
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(BookingRequested::class, [self::class, 'handle']);
    }
}