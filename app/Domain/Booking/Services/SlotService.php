<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\BookingSlot;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Master\Repositories\MasterRepository;
// use App\Domain\Service\Repositories\ServiceRepository;
use App\Enums\BookingType;
use App\Enums\BookingStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с временными слотами (согласно плану)
 * Управляет расписанием и доступностью времени
 */
class SlotService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private MasterRepository $masterRepository,
        // private ServiceRepository $serviceRepository
    ) {}

    /**
     * Получить доступные слоты для мастера и услуги
     */
    public function getAvailableSlots(
        int $masterId, 
        int $serviceId, 
        ?BookingType $type = null,
        int $days = 14
    ): array {
        $master = $this->masterRepository->findById($masterId);
        // $service = $this->serviceRepository->findById($serviceId);
        $service = \App\Domain\Service\Models\Service::find($serviceId); // Временно
        
        if (!$master || !$service) {
            return [];
        }

        $type = $type ?? BookingType::INCALL;
        $duration = $service->duration_minutes ?? $type->getDefaultDurationMinutes();
        $minAdvanceHours = $type->getMinAdvanceHours();
        
        $slots = [];
        $startDate = now()->addHours($minAdvanceHours);
        $endDate = now()->addDays($days);

        for ($date = $startDate->copy()->startOfDay(); $date <= $endDate; $date->addDay()) {
            $daySlots = $this->generateDaySlots($date, $master, $service, $type, $duration);
            
            if (!empty($daySlots)) {
                $slots[$date->format('Y-m-d')] = [
                    'date' => $date->format('Y-m-d'),
                    'day_name' => $date->locale('ru')->dayName,
                    'is_weekend' => $date->isWeekend(),
                    'slots' => $daySlots,
                ];
            }
        }

        return $slots;
    }

    /**
     * Генерация доступных слотов на конкретный день
     */
    public function generateDaySlots(
        Carbon $date, 
        $master, 
        $service, 
        BookingType $type,
        int $duration
    ): array {
        $schedule = $this->getMasterScheduleForDate($master, $date);
        
        if (!$schedule || !$schedule->is_active) {
            return [];
        }

        $slots = [];
        $workStart = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $workEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);
        $breakStart = $schedule->break_start ? Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->break_start) : null;
        $breakEnd = $schedule->break_end ? Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->break_end) : null;
        
        // Получаем занятые слоты через репозиторий
        $busySlots = $this->bookingRepository->getBookingsForDate($date, $master->id);
        
        $currentTime = $workStart->copy();
        $slotInterval = $this->getSlotInterval($type);
        
        while ($currentTime->copy()->addMinutes($duration) <= $workEnd) {
            // Пропускаем перерыв
            if ($this->isBreakTime($currentTime, $duration, $breakStart, $breakEnd)) {
                $currentTime = $breakEnd ? $breakEnd->copy() : $currentTime->addMinutes($slotInterval);
                continue;
            }
            
            // Проверяем доступность
            if ($this->isSlotAvailable($currentTime, $duration, $busySlots, $type)) {
                $slots[] = $this->formatSlot($currentTime, $duration, $type);
            }
            
            $currentTime->addMinutes($slotInterval);
        }

        return $slots;
    }

    /**
     * Проверить доступность конкретного слота
     */
    public function checkSlotAvailability(
        int $masterId,
        Carbon $startTime,
        int $duration,
        ?int $excludeBookingId = null
    ): bool {
        $endTime = $startTime->copy()->addMinutes($duration);
        
        $overlapping = $this->bookingRepository->findOverlapping(
            $startTime,
            $endTime,
            $masterId,
            $excludeBookingId
        );
        
        return $overlapping->isEmpty();
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
        $master = $this->masterRepository->findById($masterId);
        // $service = $this->serviceRepository->findById($serviceId);
        $service = \App\Domain\Service\Models\Service::find($serviceId); // Временно
        
        if (!$master || !$service) {
            return null;
        }

        $type = $type ?? BookingType::INCALL;
        $startSearch = $preferredTime ?? now()->addHours($type->getMinAdvanceHours());
        $duration = $service->duration_minutes ?? $type->getDefaultDurationMinutes();
        
        // Ищем в течение 2 недель
        for ($i = 0; $i < 14; $i++) {
            $date = $startSearch->copy()->addDays($i);
            $daySlots = $this->generateDaySlots($date, $master, $service, $type, $duration);
            
            if (!empty($daySlots)) {
                return $daySlots[0]; // Возвращаем первый доступный
            }
        }
        
        return null;
    }

    /**
     * Создать слоты для бронирования
     */
    public function createBookingSlots(int $bookingId, array $slots): Collection
    {
        $booking = $this->bookingRepository->findById($bookingId);
        
        if (!$booking) {
            throw new \Exception('Бронирование не найдено');
        }

        $createdSlots = collect();

        foreach ($slots as $slotData) {
            $slot = BookingSlot::create([
                'booking_id' => $booking->id,
                'master_id' => $booking->master_id,
                'start_time' => $slotData['start_time'],
                'end_time' => $slotData['end_time'],
                'duration_minutes' => $slotData['duration_minutes'],
                'is_blocked' => $slotData['is_blocked'] ?? false,
                'is_break' => $slotData['is_break'] ?? false,
                'is_preparation' => $slotData['is_preparation'] ?? false,
                'notes' => $slotData['notes'] ?? null,
                'resource_type' => $slotData['resource_type'] ?? null,
                'resource_id' => $slotData['resource_id'] ?? null,
            ]);
            
            $createdSlots->push($slot);
        }

        Log::info('Booking slots created', [
            'booking_id' => $bookingId,
            'slots_count' => $createdSlots->count()
        ]);

        return $createdSlots;
    }

    /**
     * Обновить слоты бронирования при переносе
     */
    public function updateBookingSlots(int $bookingId, Carbon $newStartTime, int $newDuration): void
    {
        $booking = $this->bookingRepository->findById($bookingId);
        
        if (!$booking) {
            throw new \Exception('Бронирование не найдено');
        }
        
        // Удаляем старые слоты через связь
        $booking->slots()->delete();
        
        // Создаем новый основной слот
        $this->createBookingSlots($bookingId, [[
            'start_time' => $newStartTime,
            'end_time' => $newStartTime->copy()->addMinutes($newDuration),
            'duration_minutes' => $newDuration,
            'notes' => 'Перенесенное бронирование',
        ]]);

        Log::info('Booking slots updated', [
            'booking_id' => $bookingId,
            'new_start_time' => $newStartTime->toISOString(),
            'new_duration' => $newDuration
        ]);
    }

    /**
     * Заблокировать слоты мастера (например, для перерыва или обслуживания)
     */
    public function blockMasterSlots(
        int $masterId,
        Carbon $startTime,
        Carbon $endTime,
        string $reason
    ): BookingSlot {
        $slot = BookingSlot::create([
            'master_id' => $masterId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $startTime->diffInMinutes($endTime),
            'is_blocked' => true,
            'notes' => $reason,
        ]);

        Log::info('Master slots blocked', [
            'master_id' => $masterId,
            'start_time' => $startTime->toISOString(),
            'end_time' => $endTime->toISOString(),
            'reason' => $reason
        ]);

        return $slot;
    }

    /**
     * Получить расписание мастера на дату
     */
    protected function getMasterScheduleForDate($master, Carbon $date)
    {
        if (!$master->schedules) {
            return null;
        }

        return $master->schedules()
            ->where('day_of_week', $date->dayOfWeekIso)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Проверить время перерыва
     */
    protected function isBreakTime(
        Carbon $time, 
        int $duration, 
        ?Carbon $breakStart, 
        ?Carbon $breakEnd
    ): bool {
        if (!$breakStart || !$breakEnd) {
            return false;
        }

        $slotEnd = $time->copy()->addMinutes($duration);
        
        // Слот пересекается с перерывом
        return ($time >= $breakStart && $time < $breakEnd) ||
               ($slotEnd > $breakStart && $slotEnd <= $breakEnd) ||
               ($time < $breakStart && $slotEnd > $breakEnd);
    }

    /**
     * Проверить доступность слота
     */
    protected function isSlotAvailable(
        Carbon $time,
        int $duration,
        Collection $busySlots,
        BookingType $type
    ): bool {
        // Проверяем минимальное время заранее
        $minAdvanceTime = now()->addHours($type->getMinAdvanceHours());
        if ($time->lt($minAdvanceTime)) {
            return false;
        }

        $slotEnd = $time->copy()->addMinutes($duration);
        
        // Проверяем пересечения с занятыми слотами
        foreach ($busySlots as $booking) {
            if ($time->lt($booking->end_time) && $slotEnd->gt($booking->start_time)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Форматировать слот для вывода
     */
    protected function formatSlot(Carbon $time, int $duration, BookingType $type): array
    {
        return [
            'start_time' => $time->format('H:i'),
            'end_time' => $time->copy()->addMinutes($duration)->format('H:i'),
            'datetime' => $time->toISOString(),
            'duration' => $duration,
            'type' => $type->value,
            'available' => true,
        ];
    }

    /**
     * Получить интервал между слотами в зависимости от типа
     */
    protected function getSlotInterval(BookingType $type): int
    {
        return match($type) {
            BookingType::ONLINE => 15,    // 15 минут для онлайн
            BookingType::PACKAGE => 60,   // 60 минут для пакетов
            default => 30,                // 30 минут стандарт
        };
    }

    /**
     * Получить статистику использования слотов
     */
    public function getSlotStats(int $masterId, Carbon $startDate, Carbon $endDate): array
    {
        $totalSlots = $this->bookingRepository->query()
            ->where('master_id', $masterId)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->count();

        $bookedSlots = $this->bookingRepository->query()
            ->where('master_id', $masterId)
            ->whereIn('status', [BookingStatus::CONFIRMED->value, BookingStatus::COMPLETED->value])
            ->whereBetween('start_time', [$startDate, $endDate])
            ->count();

        $occupancyRate = $totalSlots > 0 ? round(($bookedSlots / $totalSlots) * 100, 2) : 0;

        return [
            'total_slots' => $totalSlots,
            'booked_slots' => $bookedSlots,
            'free_slots' => $totalSlots - $bookedSlots,
            'occupancy_rate' => $occupancyRate,
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
        ];
    }
}