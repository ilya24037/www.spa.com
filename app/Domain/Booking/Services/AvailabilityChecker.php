<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Проверка доступности временных слотов
 */
class AvailabilityChecker
{
    public function __construct(
        private BookingRepository $bookingRepository
    ) {}

    /**
     * Проверить доступность временного слота
     */
    public function isSlotAvailable(
        Carbon $startTime,
        Carbon $endTime,
        int $masterId,
        ?int $excludeBookingId = null
    ): bool {
        return $this->findOverlappingBookings($startTime, $endTime, $masterId, $excludeBookingId)->isEmpty();
    }

    /**
     * Найти пересекающиеся бронирования
     */
    public function findOverlappingBookings(
        Carbon $startTime,
        Carbon $endTime,
        int $masterId,
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
     * Проверить доступность мастера в указанное время
     */
    public function isMasterAvailable(
        int $masterId,
        Carbon $startTime,
        int $durationMinutes,
        ?int $excludeBookingId = null
    ): bool {
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        // Проверяем пересечения с бронированиями
        if (!$this->isSlotAvailable($startTime, $endTime, $masterId, $excludeBookingId)) {
            return false;
        }

        // Проверяем рабочее время мастера
        return $this->isMasterWorkingTime($masterId, $startTime, $durationMinutes);
    }

    /**
     * Проверить рабочее время мастера
     */
    public function isMasterWorkingTime(int $masterId, Carbon $startTime, int $durationMinutes): bool
    {
        $master = User::find($masterId);
        if (!$master || !$master->masterProfile) {
            return true; // Если нет профиля мастера, считаем доступным
        }

        $dayOfWeek = $startTime->dayOfWeek;
        $schedule = $master->masterProfile->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        if (!$schedule) {
            return false; // Мастер не работает в этот день
        }

        $workStart = Carbon::parse($startTime->format('Y-m-d') . ' ' . $schedule->start_time);
        $workEnd = Carbon::parse($startTime->format('Y-m-d') . ' ' . $schedule->end_time);
        $bookingEnd = $startTime->copy()->addMinutes($durationMinutes);

        return $startTime->gte($workStart) && $bookingEnd->lte($workEnd);
    }

    /**
     * Найти ближайший доступный слот
     */
    public function findNextAvailableSlot(
        int $masterId,
        int $durationMinutes,
        ?Carbon $startFrom = null,
        ?int $excludeBookingId = null
    ): ?Carbon {
        $startDate = $startFrom ?? now()->addHours(2);
        $endDate = $startDate->copy()->addWeeks(4); // Ищем в пределах 4 недель

        $master = User::find($masterId);
        if (!$master || !$master->masterProfile) {
            return null;
        }

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $availableSlot = $this->findAvailableSlotInDay($master, $date, $durationMinutes, $excludeBookingId);
            if ($availableSlot) {
                return $availableSlot;
            }
        }

        return null;
    }

    /**
     * Найти доступный слот в конкретный день
     */
    public function findAvailableSlotInDay(
        User $master,
        Carbon $date,
        int $durationMinutes,
        ?int $excludeBookingId = null
    ): ?Carbon {
        $dayOfWeek = $date->dayOfWeek;
        $schedule = $master->masterProfile->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        if (!$schedule) {
            return null;
        }

        $workStart = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $workEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);

        // Если дата сегодня, начинаем с текущего времени + минимальный интервал
        if ($date->isToday()) {
            $minTime = now()->addHours(2);
            if ($workStart->lt($minTime)) {
                $workStart = $minTime;
            }
        }

        $currentTime = $workStart->copy();
        
        while ($currentTime->copy()->addMinutes($durationMinutes) <= $workEnd) {
            $slotEnd = $currentTime->copy()->addMinutes($durationMinutes);
            
            if ($this->isSlotAvailable($currentTime, $slotEnd, $master->id, $excludeBookingId)) {
                return $currentTime;
            }

            $currentTime->addMinutes(30); // Проверяем каждые 30 минут
        }

        return null;
    }

    /**
     * Получить все доступные слоты на день
     */
    public function getAvailableSlotsForDay(
        int $masterId,
        Carbon $date,
        int $durationMinutes = 60,
        int $slotIntervalMinutes = 30
    ): array {
        $master = User::find($masterId);
        if (!$master || !$master->masterProfile) {
            return [];
        }

        $dayOfWeek = $date->dayOfWeek;
        $schedule = $master->masterProfile->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        if (!$schedule) {
            return [];
        }

        $workStart = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $workEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);

        // Если дата сегодня, начинаем с текущего времени + минимальный интервал
        if ($date->isToday()) {
            $minTime = now()->addHours(2);
            if ($workStart->lt($minTime)) {
                $workStart = $minTime;
            }
        }

        $availableSlots = [];
        $currentTime = $workStart->copy();
        
        while ($currentTime->copy()->addMinutes($durationMinutes) <= $workEnd) {
            $slotEnd = $currentTime->copy()->addMinutes($durationMinutes);
            
            if ($this->isSlotAvailable($currentTime, $slotEnd, $masterId)) {
                $availableSlots[] = [
                    'start_time' => $currentTime->copy(),
                    'end_time' => $slotEnd->copy(),
                    'duration_minutes' => $durationMinutes,
                ];
            }

            $currentTime->addMinutes($slotIntervalMinutes);
        }

        return $availableSlots;
    }

    /**
     * Проверить доступность на несколько дней вперед
     */
    public function getAvailabilityForPeriod(
        int $masterId,
        Carbon $startDate,
        Carbon $endDate,
        int $durationMinutes = 60
    ): array {
        $availability = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $slots = $this->getAvailableSlotsForDay($masterId, $date, $durationMinutes);
            
            $availability[$date->format('Y-m-d')] = [
                'date' => $date->copy(),
                'available_slots_count' => count($slots),
                'slots' => $slots,
                'is_working_day' => !empty($slots),
            ];
        }

        return $availability;
    }

    /**
     * Проверить конфликт с существующим бронированием
     */
    public function hasConflictWithBooking(
        Booking $booking,
        Carbon $newStartTime,
        int $newDurationMinutes
    ): ?Booking {
        $newEndTime = $newStartTime->copy()->addMinutes($newDurationMinutes);
        
        $conflicts = $this->findOverlappingBookings(
            $newStartTime,
            $newEndTime,
            $booking->master_id,
            $booking->id
        );

        return $conflicts->first();
    }

    /**
     * Получить причину недоступности слота
     */
    public function getUnavailabilityReason(
        int $masterId,
        Carbon $startTime,
        int $durationMinutes
    ): ?string {
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        // Проверяем время в прошлом
        if ($startTime->isPast()) {
            return 'Время в прошлом';
        }

        // Проверяем рабочее время
        if (!$this->isMasterWorkingTime($masterId, $startTime, $durationMinutes)) {
            return 'Мастер не работает в это время';
        }

        // Проверяем конфликты с бронированиями
        $conflicts = $this->findOverlappingBookings($startTime, $endTime, $masterId);
        if ($conflicts->isNotEmpty()) {
            $conflict = $conflicts->first();
            return "Время занято бронированием #{$conflict->booking_number}";
        }

        return null; // Слот доступен
    }

    /**
     * Получить статистику доступности мастера
     */
    public function getMasterAvailabilityStats(int $masterId, Carbon $date): array
    {
        $master = User::find($masterId);
        if (!$master || !$master->masterProfile) {
            return [];
        }

        $dayOfWeek = $date->dayOfWeek;
        $schedule = $master->masterProfile->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        if (!$schedule) {
            return [
                'is_working_day' => false,
                'total_hours' => 0,
                'available_hours' => 0,
                'booked_hours' => 0,
                'utilization_rate' => 0,
            ];
        }

        $workStart = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
        $workEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);
        
        $totalMinutes = $workStart->diffInMinutes($workEnd);
        $totalHours = $totalMinutes / 60;

        // Получаем бронирования на день
        $bookings = $this->bookingRepository->getBookingsForDate($date, $masterId);
        $bookedMinutes = $bookings->sum('duration_minutes');
        $bookedHours = $bookedMinutes / 60;

        $availableHours = $totalHours - $bookedHours;
        $utilizationRate = $totalHours > 0 ? ($bookedHours / $totalHours) * 100 : 0;

        return [
            'is_working_day' => true,
            'work_start' => $workStart,
            'work_end' => $workEnd,
            'total_hours' => round($totalHours, 1),
            'available_hours' => round(max(0, $availableHours), 1),
            'booked_hours' => round($bookedHours, 1),
            'utilization_rate' => round($utilizationRate, 1),
            'bookings_count' => $bookings->count(),
        ];
    }
}