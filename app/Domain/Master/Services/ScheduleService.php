<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterSchedule;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Master\Repositories\ScheduleRepository;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Сервис для управления расписанием мастеров - координатор
 */
class ScheduleService
{
    private MasterRepository $masterRepository;
    private ScheduleRepository $scheduleRepository;
    private ScheduleAvailabilityChecker $availabilityChecker;
    private ScheduleSlotGenerator $slotGenerator;
    private ScheduleCacheManager $cacheManager;
    private ScheduleStatsCalculator $statsCalculator;

    public function __construct(
        MasterRepository $masterRepository,
        ScheduleRepository $scheduleRepository,
        ScheduleAvailabilityChecker $availabilityChecker,
        ScheduleSlotGenerator $slotGenerator,
        ScheduleCacheManager $cacheManager,
        ScheduleStatsCalculator $statsCalculator
    ) {
        $this->masterRepository = $masterRepository;
        $this->scheduleRepository = $scheduleRepository;
        $this->availabilityChecker = $availabilityChecker;
        $this->slotGenerator = $slotGenerator;
        $this->cacheManager = $cacheManager;
        $this->statsCalculator = $statsCalculator;
    }

    /**
     * Создать расписание для мастера
     */
    public function createSchedule(int $masterId, array $scheduleData): MasterSchedule
    {
        $master = $this->masterRepository->findById($masterId);
        
        if (!$master) {
            throw new \Exception('Мастер не найден');
        }

        $scheduleData['master_profile_id'] = $masterId;
        $scheduleData['is_active'] = $scheduleData['is_active'] ?? true;
        
        $schedule = $this->scheduleRepository->create($scheduleData);

        $this->cacheManager->clearMasterSchedule($masterId);

        Log::info('Schedule created for master', [
            'master_id' => $masterId,
            'day_of_week' => $scheduleData['day_of_week']
        ]);

        return $schedule;
    }

    /**
     * Обновить расписание
     */
    public function updateSchedule(int $scheduleId, array $scheduleData): MasterSchedule
    {
        $schedule = $this->scheduleRepository->update($scheduleId, $scheduleData);
        
        $this->cacheManager->clearMasterSchedule($schedule->master_profile_id);

        Log::info('Schedule updated', [
            'schedule_id' => $scheduleId,
            'master_id' => $schedule->master_profile_id
        ]);

        return $schedule;
    }

    /**
     * Установить еженедельное расписание для мастера
     */
    public function setWeeklySchedule(int $masterId, array $weeklySchedule): array
    {
        $master = $this->masterRepository->findById($masterId);
        
        if (!$master) {
            throw new \Exception('Мастер не найден');
        }

        // Удаляем старое расписание
        $this->scheduleRepository->deleteByMasterId($masterId);

        // Подготавливаем данные для массового создания
        $schedulesToCreate = [];
        foreach ($weeklySchedule as $daySchedule) {
            if (!empty($daySchedule['start_time']) && !empty($daySchedule['end_time'])) {
                $daySchedule['is_active'] = $daySchedule['is_active'] ?? true;
                $schedulesToCreate[] = $daySchedule;
            }
        }

        $createdSchedules = $this->scheduleRepository->createMultiple($masterId, $schedulesToCreate);
        
        $this->cacheManager->clearMasterSchedule($masterId);

        Log::info('Weekly schedule set for master', [
            'master_id' => $masterId,
            'days_count' => count($createdSchedules)
        ]);

        return $createdSchedules;
    }

    /**
     * Получить расписание мастера
     */
    public function getMasterSchedule(int $masterId): array
    {
        return $this->cacheManager->getMasterSchedule($masterId, function () use ($masterId) {
            $schedules = $this->scheduleRepository->getByMasterId($masterId, true);

            $weekSchedule = [];
            $days = ['понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'];

            foreach ($schedules as $schedule) {
                $weekSchedule[$schedule->day_of_week] = [
                    'id' => $schedule->id,
                    'day_name' => $days[$schedule->day_of_week - 1] ?? 'День ' . $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'break_start' => $schedule->break_start,
                    'break_end' => $schedule->break_end,
                    'is_active' => $schedule->is_active,
                    'notes' => $schedule->notes,
                ];
            }

            return $weekSchedule;
        });
    }

    /**
     * Проверить доступность мастера в указанное время
     */
    public function isAvailable(int $masterId, Carbon $dateTime, int $durationMinutes = 60): bool
    {
        return $this->availabilityChecker->isAvailable($masterId, $dateTime, $durationMinutes);
    }

    /**
     * Получить доступные слоты для записи
     */
    public function getAvailableSlots(int $masterId, Carbon $date, int $durationMinutes = 60): array
    {
        return $this->slotGenerator->getAvailableSlots($masterId, $date, $durationMinutes);
    }

    /**
     * Установить исключение в расписании (отпуск, больничный)
     */
    public function setScheduleException(int $masterId, Carbon $fromDate, Carbon $toDate, string $reason, string $type = 'vacation'): void
    {
        $master = $this->masterRepository->findById($masterId);
        
        if (!$master) {
            throw new \Exception('Мастер не найден');
        }

        // Логика для исключений в расписании может быть реализована через отдельную таблицу
        // Например schedule_exceptions
        
        $this->clearScheduleCache($masterId);

        Log::info('Schedule exception set', [
            'master_id' => $masterId,
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'type' => $type,
            'reason' => $reason
        ]);
    }

    /**
     * Получить рабочие дни мастера на неделю
     */
    public function getWorkingDays(int $masterId): array
    {
        return $this->cacheManager->getWorkingDays($masterId, function () use ($masterId) {
            $schedules = $this->scheduleRepository->getWorkingDays($masterId);

            $dayNames = [
                1 => 'Понедельник',
                2 => 'Вторник', 
                3 => 'Среда',
                4 => 'Четверг',
                5 => 'Пятница',
                6 => 'Суббота',
                7 => 'Воскресенье'
            ];

            $workingDays = [];
            foreach ($schedules as $dayNumber) {
                $workingDays[$dayNumber] = $dayNames[$dayNumber];
            }

            return $workingDays;
        });
    }

    /**
     * Активировать/деактивировать день расписания
     */
    public function toggleScheduleDay(int $scheduleId, bool $isActive): MasterSchedule
    {
        $schedule = $this->scheduleRepository->toggleActive($scheduleId, $isActive);

        $this->cacheManager->clearMasterSchedule($schedule->master_profile_id);

        Log::info('Schedule day toggled', [
            'schedule_id' => $scheduleId,
            'master_id' => $schedule->master_profile_id,
            'is_active' => $isActive
        ]);

        return $schedule;
    }

    /**
     * Удалить расписание
     */
    public function deleteSchedule(int $scheduleId): bool
    {
        $schedule = $this->scheduleRepository->findById($scheduleId);
        
        if (!$schedule) {
            return false;
        }
        
        $masterId = $schedule->master_profile_id;
        $deleted = $this->scheduleRepository->delete($scheduleId);
        
        if ($deleted) {
            $this->cacheManager->clearMasterSchedule($masterId);
            
            Log::info('Schedule deleted', [
                'schedule_id' => $scheduleId,
                'master_id' => $masterId
            ]);
        }

        return $deleted;
    }


    /**
     * Получить статистику расписания мастера
     */
    public function getScheduleStats(int $masterId): array
    {
        return $this->cacheManager->getScheduleStats($masterId, function () use ($masterId) {
            return $this->statsCalculator->calculateStats($masterId);
        });
    }
}