<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Service\Models\Service;
// use App\Domain\Service\Repositories\ServiceRepository;
use App\Enums\BookingStatus;
use App\Enums\BookingType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Сервис проверки доступности для бронирований
 * Отвечает за валидацию временных слотов и проверку конфликтов
 */
class AvailabilityService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private MasterRepository $masterRepository
        // private ServiceRepository $serviceRepository
    ) {}

    /**
     * Проверить доступность временного слота
     */
    public function validateTimeSlot(
        int $masterProfileId, 
        string $date, 
        string $time, 
        int $serviceId
    ): void {
        // $service = $this->serviceRepository->findById($serviceId);
        $service = Service::find($serviceId); // Временно через модель
        if (!$service) {
            throw new \Exception('Услуга не найдена');
        }
        
        $startTime = Carbon::parse($time);
        $endTime = $startTime->copy()->addMinutes($service->duration_minutes ?? 60);

        // Проверяем пересечения с другими бронированиями через репозиторий
        $dateTime = Carbon::parse($date . ' ' . $time);
        $conflicts = $this->bookingRepository->findOverlapping(
            $dateTime, 
            $dateTime->copy()->addMinutes($service->duration_minutes ?? 60), 
            $masterProfileId
        );
        
        $conflict = $conflicts->isNotEmpty();

        if ($conflict) {
            throw new \Exception('Выбранное время уже занято');
        }

        // Проверяем, что бронирование не в прошлом
        $bookingDateTime = Carbon::parse($date . ' ' . $time);
        if ($bookingDateTime->isPast()) {
            throw new \Exception('Нельзя забронировать время в прошлом');
        }
    }

    /**
     * Проверить доступность слота с учетом типа бронирования
     */
    public function validateTimeSlotAvailability(
        array $data, 
        BookingType $type
    ): void {
        $startTime = Carbon::parse($data['start_time']);
        $minAdvanceHours = $type->getMinAdvanceHours();
        
        if ($startTime->lt(now()->addHours($minAdvanceHours))) {
            throw new \Exception("Для типа {$type->getLabel()} необходимо бронировать минимум за {$minAdvanceHours} часов");
        }

        $duration = $data['duration_minutes'] ?? $type->getDefaultDurationMinutes();
        $endTime = $startTime->copy()->addMinutes($duration);
        
        $masterId = $data['master_id'];
        $overlapping = $this->bookingRepository->findOverlapping($startTime, $endTime, $masterId);
        
        if ($overlapping->isNotEmpty()) {
            throw new \Exception('Выбранное время уже занято');
        }
    }

    /**
     * Проверить, можно ли отменить бронирование
     */
    public function canCancelBooking(Booking $booking): bool {
        // Проверяем статус
        if (!in_array($booking->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED])) {
            return false;
        }

        // Проверяем время (за 2 часа до начала)
        $bookingDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->start_time);
        if (now()->diffInHours($bookingDateTime, false) < 2) {
            return false;
        }

        return true;
    }

    /**
     * Проверить, можно ли перенести бронирование
     */
    public function canRescheduleBooking(Booking $booking): bool {
        if ($booking->status instanceof BookingStatus) {
            return $booking->status->canBeRescheduled();
        }
        
        return $booking->canCancelBooking();
    }

    /**
     * Найти конфликтующие бронирования
     */
    public function findConflictingBookings(
        int $masterId,
        Carbon $startTime,
        Carbon $endTime,
        ?int $excludeBookingId = null
    ): Collection {
        return $this->bookingRepository->findOverlapping(
            $startTime,
            $endTime,
            $masterId,
            $excludeBookingId
        );
    }

    /**
     * Получить доступные слоты
     */
    public function getAvailableSlots(
        int $masterProfileId, 
        int $serviceId, 
        int $days = 14
    ): array {
        return $this->slotService->getAvailableSlots(
            $masterProfileId,
            $serviceId,
            null,
            $days
        );
    }

    /**
     * Получить доступные слоты с учетом типа
     */
    public function getAvailableSlotsForType(
        int $masterId, 
        int $serviceId, 
        BookingType $type, 
        int $days = 14
    ): array {
        return $this->slotService->getAvailableSlots(
            $masterId,
            $serviceId,
            $type,
            $days
        );
    }

    /**
     * Проверить доступность мастера в конкретное время
     */
    public function isMasterAvailable(
        int $masterId,
        Carbon $dateTime,
        int $duration
    ): bool {
        $endTime = $dateTime->copy()->addMinutes($duration);
        
        // Проверяем существующие бронирования
        $conflicts = $this->findConflictingBookings($masterId, $dateTime, $endTime);
        
        if ($conflicts->isNotEmpty()) {
            return false;
        }

        // Проверяем расписание мастера через репозиторий
        $master = $this->masterRepository->findById($masterId);
        if (!$master) {
            return false;
        }

        $dayOfWeek = $dateTime->dayOfWeek;
        $schedule = $master->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        if (!$schedule) {
            return false;
        }

        // Проверяем рабочее время
        $workStart = Carbon::parse($dateTime->format('Y-m-d') . ' ' . $schedule->start_time);
        $workEnd = Carbon::parse($dateTime->format('Y-m-d') . ' ' . $schedule->end_time);

        return $dateTime >= $workStart && $endTime <= $workEnd;
    }

    /**
     * Найти ближайший доступный слот
     */
    public function findNextAvailableSlot(
        int $masterId,
        int $serviceId,
        ?Carbon $preferredTime = null,
        ?BookingType $type = null
    ): ?array {
        return $this->slotService->findNextAvailableSlot(
            $masterId,
            $serviceId,
            $preferredTime,
            $type
        );
    }
}