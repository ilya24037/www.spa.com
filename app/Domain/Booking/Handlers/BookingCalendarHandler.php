<?php

namespace App\Domain\Booking\Handlers;

use App\Domain\Booking\Models\Booking;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Обработчик календарных функций бронирований
 */
class BookingCalendarHandler
{
    public function __construct(
        protected Booking $model
    ) {}

    /**
     * Получить ближайшие бронирования
     */
    public function getUpcoming(int $limit = 10): Collection
    {
        return $this->model->upcoming()
                          ->active()
                          ->orderBy('start_time')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Получить ближайшие бронирования для мастера
     */
    public function getUpcomingForMaster(int $masterId, int $limit = 10): Collection
    {
        return $this->model->forMaster($masterId)
                          ->upcoming()
                          ->active()
                          ->orderBy('start_time')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Получить ближайшие бронирования для клиента
     */
    public function getUpcomingForClient(int $clientId, int $limit = 10): Collection
    {
        return $this->model->forClient($clientId)
                          ->upcoming()
                          ->active()
                          ->orderBy('start_time')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Получить бронирования на сегодня
     */
    public function getTodayBookings(?int $masterId = null): Collection
    {
        $query = $this->model->today()->active();
        
        if ($masterId) {
            $query->forMaster($masterId);
        }
        
        return $query->orderBy('start_time')->get();
    }

    /**
     * Получить бронирования на конкретную дату
     */
    public function getBookingsForDate(Carbon $date, ?int $masterId = null): Collection
    {
        $query = $this->model->whereDate('start_time', $date);
        
        if ($masterId) {
            $query->forMaster($masterId);
        }
        
        return $query->orderBy('start_time')->get();
    }

    /**
     * Получить бронирования за период
     */
    public function getBookingsForDateRange(Carbon $startDate, Carbon $endDate, ?int $masterId = null): Collection
    {
        $query = $this->model->whereBetween('start_time', [$startDate, $endDate]);
        
        if ($masterId) {
            $query->forMaster($masterId);
        }
        
        return $query->orderBy('start_time')->get();
    }

    /**
     * Получить календарь бронирований в формате для frontend
     */
    public function getBookingCalendar(Carbon $startDate, Carbon $endDate, ?int $masterId = null): array
    {
        $query = $this->model->whereBetween('start_time', [$startDate, $endDate]);
        
        if ($masterId) {
            $query->forMaster($masterId);
        }

        $bookings = $query->with(['client', 'service'])->get();

        $calendar = [];
        
        foreach ($bookings as $booking) {
            $date = $booking->start_time->format('Y-m-d');
            
            if (!isset($calendar[$date])) {
                $calendar[$date] = [];
            }
            
            $calendar[$date][] = [
                'id' => $booking->id,
                'title' => $booking->service->name ?? 'Услуга',
                'client' => $booking->client->name ?? $booking->client_name,
                'start' => $booking->start_time->format('c'),
                'end' => $booking->end_time->format('c'),
                'status' => $booking->status,
                'color' => $booking->status instanceof BookingStatus ? $booking->status->getColor() : '#6B7280',
            ];
        }

        return $calendar;
    }

    /**
     * Получить доступность мастера на дату
     */
    public function getMasterAvailability(int $masterId, Carbon $date): array
    {
        $bookings = $this->getBookingsForDate($date, $masterId);
        
        $availability = [];
        $workingHours = $this->getWorkingHours($masterId, $date);
        
        // Создаем слоты по 30 минут
        $currentTime = $workingHours['start'];
        while ($currentTime < $workingHours['end']) {
            $slotEnd = $currentTime->copy()->addMinutes(30);
            
            $isAvailable = !$this->isSlotBooked($bookings, $currentTime, $slotEnd);
            
            $availability[] = [
                'start' => $currentTime->format('H:i'),
                'end' => $slotEnd->format('H:i'),
                'available' => $isAvailable,
            ];
            
            $currentTime = $slotEnd;
        }
        
        return $availability;
    }

    /**
     * Проверить, занят ли временной слот
     */
    protected function isSlotBooked(Collection $bookings, Carbon $slotStart, Carbon $slotEnd): bool
    {
        foreach ($bookings as $booking) {
            // Проверяем пересечение временных интервалов
            if ($booking->start_time < $slotEnd && $booking->end_time > $slotStart) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Получить рабочие часы мастера на дату
     */
    protected function getWorkingHours(int $masterId, Carbon $date): array
    {
        // Заглушка - в реальном проекте будет запрос к расписанию мастера
        return [
            'start' => $date->copy()->setTime(9, 0),
            'end' => $date->copy()->setTime(18, 0),
        ];
    }

    /**
     * Получить статистику занятости по дням недели
     */
    public function getWeeklyOccupancy(int $masterId, Carbon $startDate): array
    {
        $endDate = $startDate->copy()->addWeek();
        
        $bookings = $this->getBookingsForDateRange($startDate, $endDate, $masterId)
                        ->groupBy(function ($booking) {
                            return $booking->start_time->format('Y-m-d');
                        });
        
        $occupancy = [];
        
        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateKey = $date->format('Y-m-d');
            
            $dayBookings = $bookings->get($dateKey, collect());
            
            $occupancy[] = [
                'date' => $dateKey,
                'day' => $date->format('l'),
                'bookings_count' => $dayBookings->count(),
                'total_hours' => $dayBookings->sum(function ($booking) {
                    return $booking->start_time->diffInHours($booking->end_time);
                }),
                'occupancy_rate' => $this->calculateOccupancyRate($dayBookings, $date),
            ];
        }
        
        return $occupancy;
    }

    /**
     * Рассчитать коэффициент занятости на день
     */
    protected function calculateOccupancyRate(Collection $bookings, Carbon $date): float
    {
        $workingHours = $this->getWorkingHours(1, $date); // Заглушка
        $totalWorkingMinutes = $workingHours['start']->diffInMinutes($workingHours['end']);
        
        $bookedMinutes = $bookings->sum(function ($booking) {
            return $booking->start_time->diffInMinutes($booking->end_time);
        });
        
        return $totalWorkingMinutes > 0 
            ? round(($bookedMinutes / $totalWorkingMinutes) * 100, 1)
            : 0;
    }
}