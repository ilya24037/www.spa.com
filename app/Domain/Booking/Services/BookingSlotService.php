<?php

namespace App\Domain\Booking\Services;

use App\Domain\User\Models\User;
use App\Domain\Service\Models\Service;
use App\Domain\Booking\Models\BookingSlot;
use App\Enums\BookingType;
use App\Domain\Booking\Repositories\BookingRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Сервис для работы с временными слотами
 * Отвечает только за расчет и управление слотами
 */
class BookingSlotService
{
    public function __construct(
        private BookingRepository $bookingRepository
    ) {}

    /**
     * Получить доступные слоты для мастера
     */
    public function getAvailableSlots(
        int $masterId, 
        int $serviceId, 
        ?BookingType $type = null,
        int $days = 14
    ): array {
        $master = User::with('masterProfile.schedules')->findOrFail($masterId);
        $service = Service::findOrFail($serviceId);
        
        if (!$master->masterProfile) {
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
     * Генерация слотов на день
     */
    public function generateDaySlots(
        Carbon $date, 
        User $master, 
        Service $service, 
        BookingType $type,
        int $duration
    ): array {
        $schedule = $this->getMasterSchedule($master, $date);
        
        if (!$schedule || !$schedule->is_working_day) {
            return [];
        }

        $slots = [];
        $workStart = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $workEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);
        $breakStart = $schedule->break_start ? Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->break_start) : null;
        $breakEnd = $schedule->break_end ? Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->break_end) : null;
        
        // Получаем занятые слоты
        $busySlots = $this->getBusySlots($master->id, $date);
        
        $currentTime = $workStart->copy();
        $slotInterval = $this->getSlotInterval($type);
        
        while ($currentTime->copy()->addMinutes($duration) <= $workEnd) {
            // Пропускаем перерыв
            if ($this->isBreakTime($currentTime, $duration, $breakStart, $breakEnd)) {
                $currentTime = $breakEnd->copy();
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
        $master = User::find($masterId);
        $service = Service::find($serviceId);
        
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
     * Получить занятые слоты мастера на дату
     */
    public function getBusySlots(int $masterId, Carbon $date): Collection
    {
        return $this->bookingRepository->getBookingsForDate($date, $masterId);
    }

    /**
     * Получить расписание мастера на дату
     */
    protected function getMasterSchedule(User $master, Carbon $date)
    {
        if (!$master->masterProfile) {
            return null;
        }

        return $master->masterProfile->schedules()
            ->where('day_of_week', $date->dayOfWeek)
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
     * Получить интервал между слотами
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
     * Создать слоты для бронирования
     */
    public function createBookingSlots(int $bookingId, array $slots): Collection
    {
        $booking = $this->bookingRepository->findOrFail($bookingId);
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

        return $createdSlots;
    }

    /**
     * Обновить слоты бронирования
     */
    public function updateBookingSlots(int $bookingId, Carbon $newStartTime, int $newDuration): void
    {
        $booking = $this->bookingRepository->findOrFail($bookingId);
        
        // Удаляем старые слоты
        BookingSlot::where('booking_id', $booking->id)->delete();
        
        // Создаем новый основной слот
        $this->createBookingSlots($bookingId, [[
            'start_time' => $newStartTime,
            'end_time' => $newStartTime->copy()->addMinutes($newDuration),
            'duration_minutes' => $newDuration,
            'notes' => 'Перенесенное бронирование',
        ]]);
    }

    /**
     * Заблокировать слоты мастера
     */
    public function blockMasterSlots(
        int $masterId,
        Carbon $startTime,
        Carbon $endTime,
        string $reason
    ): BookingSlot {
        return BookingSlot::create([
            'master_id' => $masterId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $startTime->diffInMinutes($endTime),
            'is_blocked' => true,
            'notes' => $reason,
        ]);
    }
}