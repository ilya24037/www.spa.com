<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\DTOs\CreateBookingDTO;
use App\DTOs\BookingFilterDTO;
use App\Domain\Booking\Services\BookingService as ModernBookingService;
use App\Domain\Booking\Services\BookingSlotService;
use App\Enums\BookingType;
use Carbon\Carbon;

/**
 * Адаптер для постепенной миграции на новую архитектуру
 * Преобразует старые вызовы в новые
 */
class BookingServiceAdapter
{
    public function __construct(
        private ModernBookingService $modernService,
        private BookingSlotService $slotService,
        private BookingService $legacyService
    ) {}

    /**
     * Адаптер для createBooking (старый метод)
     */
    public function createBooking(array $data): Booking
    {
        try {
            // Преобразуем старый формат в DTO
            $dto = $this->convertToCreateDTO($data);
            
            // Используем новый сервис
            return $this->modernService->create($dto);
        } catch (\Exception $e) {
            // Fallback на старый сервис при ошибках
            return $this->legacyService->createBooking($data);
        }
    }

    /**
     * Адаптер для getAvailableSlots
     */
    public function getAvailableSlots(int $masterProfileId, int $serviceId, int $days = 14): array
    {
        try {
            // Находим master_id по master_profile_id (legacy)
            $masterProfile = \App\Models\MasterProfile::find($masterProfileId);
            if (!$masterProfile) {
                return [];
            }

            // Используем новый сервис слотов
            return $this->slotService->getAvailableSlots(
                $masterProfile->user_id,
                $serviceId,
                BookingType::INCALL,
                $days
            );
        } catch (\Exception $e) {
            // Fallback на старый сервис
            return $this->legacyService->getAvailableSlots($masterProfileId, $serviceId, $days);
        }
    }

    /**
     * Адаптер для confirmBooking
     */
    public function confirmBooking(Booking $booking, User $master): bool
    {
        try {
            $this->modernService->confirm($booking->id, $master);
            return true;
        } catch (\Exception $e) {
            return $this->legacyService->confirmBooking($booking, $master);
        }
    }

    /**
     * Адаптер для cancelBooking
     */
    public function cancelBooking(Booking $booking, User $user, ?string $reason = null): bool
    {
        try {
            $this->modernService->cancel($booking->id, $user, $reason ?? 'Отменено пользователем');
            return true;
        } catch (\Exception $e) {
            return $this->legacyService->cancelBooking($booking, $user, $reason);
        }
    }

    /**
     * Адаптер для completeBooking
     */
    public function completeBooking(Booking $booking, User $master): bool
    {
        try {
            $this->modernService->complete($booking->id, $master);
            return true;
        } catch (\Exception $e) {
            return $this->legacyService->completeBooking($booking, $master);
        }
    }

    /**
     * Новые методы с Enums - прокси к модерн сервису
     */
    public function createBookingWithEnums(array $data): Booking
    {
        $dto = CreateBookingDTO::fromArray($data);
        return $this->modernService->create($dto);
    }

    /**
     * Конвертация старого формата в DTO
     */
    private function convertToCreateDTO(array $data): CreateBookingDTO
    {
        // Конвертация master_profile_id в master_id
        $masterId = $data['master_id'] ?? null;
        if (!$masterId && isset($data['master_profile_id'])) {
            $masterProfile = \App\Models\MasterProfile::find($data['master_profile_id']);
            $masterId = $masterProfile?->user_id;
        }

        // Конвертация даты и времени
        $startTime = Carbon::parse($data['booking_date'] . ' ' . $data['booking_time']);
        
        // Определение типа
        $type = BookingType::INCALL;
        if (($data['service_location'] ?? '') === 'home' || ($data['is_home_service'] ?? false)) {
            $type = BookingType::OUTCALL;
        }

        return CreateBookingDTO::fromArray([
            'client_id' => $data['client_id'] ?? auth()->id(),
            'master_id' => $masterId,
            'service_id' => $data['service_id'],
            'type' => $type->value,
            'start_time' => $startTime->toISOString(),
            'duration_minutes' => $data['duration'] ?? 90,
            'client_name' => $data['client_name'],
            'client_phone' => $data['client_phone'],
            'client_email' => $data['client_email'] ?? null,
            'client_address' => $data['address'] ?? null,
            'notes' => $data['client_comment'] ?? null,
            'source' => $data['source'] ?? 'website',
        ]);
    }

    /**
     * Делегирование остальных методов в legacy сервис
     */
    public function __call($method, $arguments)
    {
        // Проверяем, есть ли метод в новом сервисе
        if (method_exists($this->modernService, $method)) {
            return $this->modernService->$method(...$arguments);
        }

        // Иначе используем старый
        return $this->legacyService->$method(...$arguments);
    }
}