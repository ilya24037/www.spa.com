<?php

namespace App\Services\Adapters;

use App\Services\BookingService as LegacyBookingService;
use App\Domain\Booking\Services\BookingService as ModernBookingService;
use App\Domain\Booking\DTOs\CreateBookingDTO;
use App\Domain\Booking\DTOs\UpdateBookingDTO;
use App\Domain\Booking\Models\Booking;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Адаптер для постепенной миграции с legacy BookingService на модульный
 */
class BookingServiceAdapter
{
    private LegacyBookingService $legacyService;
    private ModernBookingService $modernService;
    private bool $useModern;

    public function __construct(
        LegacyBookingService $legacyService,
        ModernBookingService $modernService
    ) {
        $this->legacyService = $legacyService;
        $this->modernService = $modernService;
        $this->useModern = config('features.use_modern_booking_service', false);
    }

    /**
     * Создать бронирование
     */
    public function createBooking(array $data): Booking
    {
        if ($this->useModern) {
            try {
                $dto = $this->convertToCreateDTO($data);
                return $this->modernService->create($dto);
            } catch (Throwable $e) {
                Log::error('Modern booking service failed, falling back to legacy', [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);
                
                // Fallback to legacy
                return $this->legacyService->createBooking($data);
            }
        }

        return $this->legacyService->createBooking($data);
    }

    /**
     * Обновить бронирование
     */
    public function updateBooking(int $bookingId, array $data): Booking
    {
        if ($this->useModern) {
            try {
                $dto = $this->convertToUpdateDTO($data);
                return $this->modernService->update($bookingId, $dto);
            } catch (Throwable $e) {
                Log::error('Modern booking service failed on update', [
                    'error' => $e->getMessage(),
                    'booking_id' => $bookingId
                ]);
                
                return $this->legacyService->updateBooking($bookingId, $data);
            }
        }

        return $this->legacyService->updateBooking($bookingId, $data);
    }

    /**
     * Отменить бронирование
     */
    public function cancelBooking(int $bookingId, string $reason = null): Booking
    {
        if ($this->useModern) {
            try {
                return $this->modernService->cancel($bookingId, $reason);
            } catch (Throwable $e) {
                Log::error('Modern booking service failed on cancel', [
                    'error' => $e->getMessage(),
                    'booking_id' => $bookingId
                ]);
                
                return $this->legacyService->cancelBooking($bookingId, $reason);
            }
        }

        return $this->legacyService->cancelBooking($bookingId, $reason);
    }

    /**
     * Подтвердить бронирование
     */
    public function confirmBooking(int $bookingId): Booking
    {
        if ($this->useModern) {
            try {
                return $this->modernService->confirm($bookingId);
            } catch (Throwable $e) {
                Log::error('Modern booking service failed on confirm', [
                    'error' => $e->getMessage(),
                    'booking_id' => $bookingId
                ]);
                
                return $this->legacyService->confirmBooking($bookingId);
            }
        }

        return $this->legacyService->confirmBooking($bookingId);
    }

    /**
     * Получить бронирования мастера
     */
    public function getMasterBookings(int $masterId, array $filters = [])
    {
        if ($this->useModern) {
            try {
                return $this->modernService->getMasterBookings($masterId, $filters);
            } catch (Throwable $e) {
                Log::error('Modern booking service failed on getMasterBookings', [
                    'error' => $e->getMessage(),
                    'master_id' => $masterId
                ]);
                
                return $this->legacyService->getMasterBookings($masterId, $filters);
            }
        }

        return $this->legacyService->getMasterBookings($masterId, $filters);
    }

    /**
     * Получить бронирования клиента
     */
    public function getClientBookings(int $clientId, array $filters = [])
    {
        if ($this->useModern) {
            try {
                return $this->modernService->getClientBookings($clientId, $filters);
            } catch (Throwable $e) {
                Log::error('Modern booking service failed on getClientBookings', [
                    'error' => $e->getMessage(),
                    'client_id' => $clientId
                ]);
                
                return $this->legacyService->getClientBookings($clientId, $filters);
            }
        }

        return $this->legacyService->getClientBookings($clientId, $filters);
    }

    /**
     * Проверить доступность времени
     */
    public function checkAvailability(int $masterId, string $date, string $startTime, string $endTime): bool
    {
        if ($this->useModern) {
            try {
                return $this->modernService->isAvailable(
                    $masterId,
                    \Carbon\Carbon::parse($date),
                    $startTime,
                    $endTime
                );
            } catch (Throwable $e) {
                Log::error('Modern booking service failed on checkAvailability', [
                    'error' => $e->getMessage(),
                    'master_id' => $masterId
                ]);
                
                return $this->legacyService->checkAvailability($masterId, $date, $startTime, $endTime);
            }
        }

        return $this->legacyService->checkAvailability($masterId, $date, $startTime, $endTime);
    }

    /**
     * Получить доступные слоты
     */
    public function getAvailableSlots(int $masterId, string $date): array
    {
        if ($this->useModern) {
            try {
                return $this->modernService->getAvailableSlots(
                    $masterId,
                    \Carbon\Carbon::parse($date)
                );
            } catch (Throwable $e) {
                Log::error('Modern booking service failed on getAvailableSlots', [
                    'error' => $e->getMessage(),
                    'master_id' => $masterId
                ]);
                
                return $this->legacyService->getAvailableSlots($masterId, $date);
            }
        }

        return $this->legacyService->getAvailableSlots($masterId, $date);
    }

    /**
     * Конвертировать данные в CreateBookingDTO
     */
    private function convertToCreateDTO(array $data): CreateBookingDTO
    {
        return new CreateBookingDTO(
            masterId: $data['master_id'],
            clientId: $data['client_id'] ?? auth()->id(),
            serviceId: $data['service_id'],
            date: \Carbon\Carbon::parse($data['date']),
            startTime: $data['start_time'],
            endTime: $data['end_time'],
            price: $data['price'] ?? 0,
            duration: $data['duration'] ?? 60,
            clientName: $data['client_name'] ?? null,
            clientPhone: $data['client_phone'] ?? null,
            clientEmail: $data['client_email'] ?? null,
            comment: $data['comment'] ?? null,
            isFirstTime: $data['is_first_time'] ?? false,
            source: $data['source'] ?? 'website'
        );
    }

    /**
     * Конвертировать данные в UpdateBookingDTO
     */
    private function convertToUpdateDTO(array $data): UpdateBookingDTO
    {
        return new UpdateBookingDTO(
            date: isset($data['date']) ? \Carbon\Carbon::parse($data['date']) : null,
            startTime: $data['start_time'] ?? null,
            endTime: $data['end_time'] ?? null,
            price: $data['price'] ?? null,
            comment: $data['comment'] ?? null
        );
    }

    /**
     * Проксирование других методов к legacy сервису
     */
    public function __call($method, $arguments)
    {
        // Логируем использование не адаптированных методов
        Log::info("BookingServiceAdapter: Calling legacy method {$method}");
        
        return $this->legacyService->$method(...$arguments);
    }
}