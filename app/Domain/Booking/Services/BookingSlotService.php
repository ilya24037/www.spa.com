<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\BookingSlot;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Service\Models\Service;
use App\Domain\Booking\Enums\BookingStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Единый сервис для работы со слотами и доступностью
 * Консолидирует: AvailabilityCheckService, SlotManagementService, 
 * AvailabilityService, SlotService
 */
class BookingSlotService
{
    private const SLOT_DURATION = 30; // минут
    private const CACHE_TTL = 300; // 5 минут

    public function __construct(
        private BookingRepository $bookingRepository,
        private MasterRepository $masterRepository
    ) {}

    /**
     * ==========================================
     * ПРОВЕРКА ДОСТУПНОСТИ
     * ==========================================
     */

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
        $master = $this->masterRepository->findWithSchedule($masterId);
        if (!$master) {
            return false;
        }

        $dayOfWeek = $startTime->dayOfWeek;
        $schedule = $this->getMasterScheduleForDay($master, $dayOfWeek);

        if (!$schedule || !$schedule->is_working_day) {
            return false;
        }

        $endTime = $startTime->copy()->addMinutes($durationMinutes);
        $workStart = Carbon::parse($schedule->work_start_time);
        $workEnd = Carbon::parse($schedule->work_end_time);

        // Устанавливаем правильную дату для сравнения
        $workStart->setDate($startTime->year, $startTime->month, $startTime->day);
        $workEnd->setDate($startTime->year, $startTime->month, $startTime->day);

        return $startTime->gte($workStart) && $endTime->lte($workEnd);
    }

    /**
     * Проверить возможность переноса бронирования
     */
    public function canRescheduleBooking(
        Booking $booking,
        Carbon $newStartTime,
        int $durationMinutes
    ): bool {
        // Проверяем доступность нового времени для мастера
        if (!$this->isMasterAvailable(
            $booking->master_id,
            $newStartTime,
            $durationMinutes,
            $booking->id
        )) {
            return false;
        }

        // Проверяем доступность нового времени для клиента
        if ($booking->user_id && !$this->isUserAvailableForBooking(
            $booking->user_id,
            $newStartTime,
            $durationMinutes,
            $booking->id
        )) {
            return false;
        }

        return true;
    }

    /**
     * Проверить доступность пользователя для бронирования
     */
    public function isUserAvailableForBooking(
        int $userId,
        Carbon $startTime,
        int $durationMinutes,
        ?int $excludeBookingId = null
    ): bool {
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        $overlappingBookings = Booking::where('user_id', $userId)
            ->where('status', '!=', BookingStatus::CANCELLED)
            ->when($excludeBookingId, fn($q) => $q->where('id', '!=', $excludeBookingId))
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('booking_date', [$startTime->toDateString(), $endTime->toDateString()])
                    ->where(function ($q) use ($startTime, $endTime) {
                        // Проверяем пересечение времени
                        $q->where(function ($subQ) use ($startTime, $endTime) {
                            $subQ->whereRaw("CONCAT(booking_date, ' ', start_time) < ?", [$endTime->toDateTimeString()])
                                 ->whereRaw("DATE_ADD(CONCAT(booking_date, ' ', start_time), INTERVAL duration_minutes MINUTE) > ?", [$startTime->toDateTimeString()]);
                        });
                    });
            })
            ->exists();

        return !$overlappingBookings;
    }

    /**
     * Получить занятые слоты мастера на день
     */
    public function getMasterBusySlots(int $masterId, Carbon $date): Collection
    {
        $bookings = $this->bookingRepository->getMasterBookingsForDate($masterId, $date);

        return $bookings->map(function ($booking) {
            $start = Carbon::parse($booking->booking_date . ' ' . $booking->start_time);
            return [
                'start' => $start->format('H:i'),
                'end' => $start->copy()->addMinutes($booking->duration_minutes)->format('H:i'),
                'booking_id' => $booking->id,
                'status' => $booking->status,
                'service' => $booking->service?->name ?? 'Услуга'
            ];
        });
    }

    /**
     * ==========================================
     * УПРАВЛЕНИЕ СЛОТАМИ
     * ==========================================
     */

    /**
     * Получить доступные слоты для мастера
     */
    public function getAvailableSlots(
        int $masterId,
        int $serviceId,
        ?Carbon $date = null,
        int $daysAhead = 7
    ): array {
        $startDate = $date ?? Carbon::now();
        $endDate = $startDate->copy()->addDays($daysAhead);
        
        $service = Service::find($serviceId);
        $duration = $service?->duration_minutes ?? 60;

        $slots = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $daySlots = $this->generateDaySlots($masterId, $currentDate, $duration);
            if (!empty($daySlots)) {
                $slots[$currentDate->toDateString()] = $daySlots;
            }
            $currentDate->addDay();
        }

        return $slots;
    }

    /**
     * Генерировать слоты для конкретного дня
     */
    public function generateDaySlots(int $masterId, Carbon $date, int $serviceDuration): array
    {
        // Кешируем результат
        $cacheKey = "slots:{$masterId}:{$date->toDateString()}:{$serviceDuration}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($masterId, $date, $serviceDuration) {
            $master = $this->masterRepository->findWithSchedule($masterId);
            if (!$master) {
                return [];
            }

            $schedule = $this->getMasterScheduleForDay($master, $date->dayOfWeek);
            if (!$schedule || !$schedule->is_working_day) {
                return [];
            }

            return $this->generateSlotsForSchedule($masterId, $date, $schedule, $serviceDuration);
        });
    }

    /**
     * Генерировать слоты на основе расписания
     */
    private function generateSlotsForSchedule(
        int $masterId,
        Carbon $date,
        object $schedule,
        int $serviceDuration
    ): array {
        $slots = [];
        
        $workStart = Carbon::parse($date->toDateString() . ' ' . $schedule->work_start_time);
        $workEnd = Carbon::parse($date->toDateString() . ' ' . $schedule->work_end_time);

        // Учитываем перерывы
        $breaks = $this->parseBreaks($schedule, $date);

        $currentTime = $workStart->copy();
        
        while ($currentTime->copy()->addMinutes($serviceDuration)->lte($workEnd)) {
            // Пропускаем прошедшее время
            if ($currentTime->isPast()) {
                $currentTime->addMinutes(self::SLOT_DURATION);
                continue;
            }

            // Проверяем, не попадает ли слот в перерыв
            if ($this->isTimeInBreak($currentTime, $serviceDuration, $breaks)) {
                $currentTime->addMinutes(self::SLOT_DURATION);
                continue;
            }

            // Проверяем доступность слота
            $isAvailable = $this->isMasterAvailable(
                $masterId,
                $currentTime,
                $serviceDuration
            );

            $slots[] = [
                'time' => $currentTime->format('H:i'),
                'datetime' => $currentTime->toDateTimeString(),
                'available' => $isAvailable,
                'duration' => $serviceDuration
            ];

            $currentTime->addMinutes(self::SLOT_DURATION);
        }

        return $slots;
    }

    /**
     * Зарезервировать слот для бронирования
     */
    public function reserveSlot(
        int $masterId,
        Carbon $startTime,
        int $durationMinutes,
        int $userId
    ): ?BookingSlot {
        return DB::transaction(function () use ($masterId, $startTime, $durationMinutes, $userId) {
            // Проверяем доступность перед резервированием
            if (!$this->isMasterAvailable($masterId, $startTime, $durationMinutes)) {
                Log::warning('Slot not available for reservation', [
                    'master_id' => $masterId,
                    'start_time' => $startTime->toDateTimeString(),
                    'duration' => $durationMinutes
                ]);
                return null;
            }

            // Создаем резервирование слота
            $slot = BookingSlot::create([
                'master_id' => $masterId,
                'user_id' => $userId,
                'slot_date' => $startTime->toDateString(),
                'slot_time' => $startTime->format('H:i:s'),
                'duration_minutes' => $durationMinutes,
                'is_reserved' => true,
                'reserved_until' => Carbon::now()->addMinutes(15), // Резерв на 15 минут
                'status' => 'reserved'
            ]);

            // Инвалидируем кеш
            $this->invalidateSlotCache($masterId, $startTime);

            Log::info('Slot reserved', [
                'slot_id' => $slot->id,
                'master_id' => $masterId,
                'user_id' => $userId
            ]);

            return $slot;
        });
    }

    /**
     * Освободить зарезервированный слот
     */
    public function releaseSlot(int $slotId): bool
    {
        $slot = BookingSlot::find($slotId);
        if (!$slot) {
            return false;
        }

        $slot->update([
            'is_reserved' => false,
            'reserved_until' => null,
            'status' => 'available'
        ]);

        // Инвалидируем кеш
        $this->invalidateSlotCache(
            $slot->master_id,
            Carbon::parse($slot->slot_date . ' ' . $slot->slot_time)
        );

        Log::info('Slot released', ['slot_id' => $slotId]);

        return true;
    }

    /**
     * Найти ближайший доступный слот
     */
    public function findNearestAvailableSlot(
        int $masterId,
        Carbon $preferredTime,
        int $durationMinutes,
        int $searchDays = 7
    ): ?array {
        $searchEnd = $preferredTime->copy()->addDays($searchDays);
        $currentTime = $preferredTime->copy();

        while ($currentTime->lte($searchEnd)) {
            // Пропускаем прошедшее время
            if ($currentTime->isPast()) {
                $currentTime = Carbon::now()->addMinutes(30);
            }

            // Проверяем доступность
            if ($this->isMasterAvailable($masterId, $currentTime, $durationMinutes)) {
                return [
                    'date' => $currentTime->toDateString(),
                    'time' => $currentTime->format('H:i'),
                    'available' => true
                ];
            }

            // Переходим к следующему 30-минутному интервалу
            $currentTime->addMinutes(30);

            // Если дошли до конца рабочего дня, переходим на следующий
            if ($currentTime->hour >= 20) {
                $currentTime->addDay()->hour(9)->minute(0);
            }
        }

        return null;
    }

    /**
     * Получить статистику доступности мастера
     */
    public function getMasterAvailabilityStats(int $masterId, Carbon $startDate, Carbon $endDate): array
    {
        $totalSlots = 0;
        $availableSlots = 0;
        $busySlots = 0;

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $daySlots = $this->calculateDaySlots($masterId, $currentDate);
            $totalSlots += $daySlots['total'];
            $availableSlots += $daySlots['available'];
            $busySlots += $daySlots['busy'];
            
            $currentDate->addDay();
        }

        return [
            'total_slots' => $totalSlots,
            'available_slots' => $availableSlots,
            'busy_slots' => $busySlots,
            'occupancy_rate' => $totalSlots > 0 ? round(($busySlots / $totalSlots) * 100, 2) : 0,
            'availability_rate' => $totalSlots > 0 ? round(($availableSlots / $totalSlots) * 100, 2) : 0
        ];
    }

    /**
     * ==========================================
     * ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     * ==========================================
     */

    private function getMasterScheduleForDay(MasterProfile $master, int $dayOfWeek): ?object
    {
        if (!$master->schedule) {
            return null;
        }

        $scheduleData = is_string($master->schedule) 
            ? json_decode($master->schedule, true) 
            : $master->schedule;

        if (!$scheduleData || !isset($scheduleData[$dayOfWeek])) {
            return null;
        }

        return (object) $scheduleData[$dayOfWeek];
    }

    private function parseBreaks(object $schedule, Carbon $date): array
    {
        $breaks = [];
        
        if (isset($schedule->break_start_time) && isset($schedule->break_end_time)) {
            $breaks[] = [
                'start' => Carbon::parse($date->toDateString() . ' ' . $schedule->break_start_time),
                'end' => Carbon::parse($date->toDateString() . ' ' . $schedule->break_end_time)
            ];
        }

        return $breaks;
    }

    private function isTimeInBreak(Carbon $time, int $duration, array $breaks): bool
    {
        $endTime = $time->copy()->addMinutes($duration);

        foreach ($breaks as $break) {
            if (
                ($time->gte($break['start']) && $time->lt($break['end'])) ||
                ($endTime->gt($break['start']) && $endTime->lte($break['end'])) ||
                ($time->lte($break['start']) && $endTime->gte($break['end']))
            ) {
                return true;
            }
        }

        return false;
    }

    private function calculateDaySlots(int $masterId, Carbon $date): array
    {
        $master = $this->masterRepository->findWithSchedule($masterId);
        if (!$master) {
            return ['total' => 0, 'available' => 0, 'busy' => 0];
        }

        $schedule = $this->getMasterScheduleForDay($master, $date->dayOfWeek);
        if (!$schedule || !$schedule->is_working_day) {
            return ['total' => 0, 'available' => 0, 'busy' => 0];
        }

        $workStart = Carbon::parse($date->toDateString() . ' ' . $schedule->work_start_time);
        $workEnd = Carbon::parse($date->toDateString() . ' ' . $schedule->work_end_time);
        
        // Считаем 30-минутные слоты
        $totalMinutes = $workStart->diffInMinutes($workEnd);
        $totalSlots = intval($totalMinutes / 30);

        // Получаем занятые слоты
        $busySlots = $this->getMasterBusySlots($masterId, $date);
        $busyCount = $busySlots->count();

        return [
            'total' => $totalSlots,
            'available' => $totalSlots - $busyCount,
            'busy' => $busyCount
        ];
    }

    private function invalidateSlotCache(int $masterId, Carbon $date): void
    {
        $pattern = "slots:{$masterId}:{$date->toDateString()}:*";
        $keys = Cache::getRedis()->keys($pattern);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}