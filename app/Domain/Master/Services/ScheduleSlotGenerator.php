<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Repositories\ScheduleRepository;
use Carbon\Carbon;

/**
 * Сервис генерации временных слотов для записи
 */
class ScheduleSlotGenerator
{
    private ScheduleRepository $scheduleRepository;
    private ScheduleAvailabilityChecker $availabilityChecker;

    public function __construct(
        ScheduleRepository $scheduleRepository,
        ScheduleAvailabilityChecker $availabilityChecker
    ) {
        $this->scheduleRepository = $scheduleRepository;
        $this->availabilityChecker = $availabilityChecker;
    }

    /**
     * Получить доступные слоты для записи
     */
    public function getAvailableSlots(int $masterId, Carbon $date, int $durationMinutes = 60, int $slotInterval = 30): array
    {
        $dayOfWeek = $date->dayOfWeekIso;
        
        $schedule = $this->scheduleRepository->getByMasterAndDay($masterId, $dayOfWeek);

        if (!$schedule) {
            return [];
        }

        return $this->generateSlots($masterId, $date, $schedule, $durationMinutes, $slotInterval);
    }

    /**
     * Сгенерировать слоты для указанного дня
     */
    public function generateSlots(int $masterId, Carbon $date, $schedule, int $durationMinutes, int $slotInterval): array
    {
        $slots = [];
        $startTime = Carbon::createFromFormat('H:i', $schedule->start_time)->setDate($date->year, $date->month, $date->day);
        $endTime = Carbon::createFromFormat('H:i', $schedule->end_time)->setDate($date->year, $date->month, $date->day);

        $current = $startTime->copy();

        while ($current->copy()->addMinutes($durationMinutes) <= $endTime) {
            // Пропускаем время перерыва
            if ($this->availabilityChecker->isInBreakTime($current, $schedule)) {
                $current->addMinutes($slotInterval);
                continue;
            }

            // Проверяем доступность
            $isAvailable = $this->availabilityChecker->isAvailable($masterId, $current->copy(), $durationMinutes);
            
            $slots[] = [
                'time' => $current->format('H:i'),
                'datetime' => $current->toISOString(),
                'available' => $isAvailable,
                'duration_minutes' => $durationMinutes,
                'end_time' => $current->copy()->addMinutes($durationMinutes)->format('H:i')
            ];

            $current->addMinutes($slotInterval);
        }

        return $slots;
    }

    /**
     * Получить слоты на несколько дней
     */
    public function getSlotsForDateRange(int $masterId, Carbon $startDate, int $days, int $durationMinutes = 60, int $slotInterval = 30): array
    {
        $slotsRange = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $slots = $this->getAvailableSlots($masterId, $date, $durationMinutes, $slotInterval);
            
            if (!empty($slots)) {
                $slotsRange[$date->toDateString()] = [
                    'date' => $date->toDateString(),
                    'day_name' => $date->translatedFormat('l'),
                    'slots' => $slots,
                    'available_count' => count(array_filter($slots, fn($slot) => $slot['available'])),
                    'total_count' => count($slots)
                ];
            }
        }
        
        return $slotsRange;
    }

    /**
     * Найти ближайший доступный слот
     */
    public function findNextAvailableSlot(int $masterId, Carbon $fromDate, int $durationMinutes = 60, int $searchDays = 30): ?array
    {
        for ($i = 0; $i < $searchDays; $i++) {
            $date = $fromDate->copy()->addDays($i);
            $slots = $this->getAvailableSlots($masterId, $date, $durationMinutes);
            
            foreach ($slots as $slot) {
                if ($slot['available']) {
                    return [
                        'date' => $date->toDateString(),
                        'slot' => $slot,
                        'days_from_now' => $i
                    ];
                }
            }
        }
        
        return null;
    }

    /**
     * Получить слоты с фильтром по времени
     */
    public function getSlotsInTimeRange(int $masterId, Carbon $date, string $fromTime, string $toTime, int $durationMinutes = 60): array
    {
        $allSlots = $this->getAvailableSlots($masterId, $date, $durationMinutes);
        
        return array_filter($allSlots, function($slot) use ($fromTime, $toTime) {
            return $slot['time'] >= $fromTime && $slot['time'] <= $toTime;
        });
    }
}