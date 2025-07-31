<?php

namespace App\Application\Http\Controllers\Booking;

use App\Application\Http\Controllers\Controller;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Service\Models\Service;
use App\Domain\Booking\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Контроллер для работы со слотами бронирования
 * Отвечает за получение доступных слотов и проверку доступности
 */
class BookingSlotController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Получить доступные слоты
     */
    public function availableSlots(Request $request)
    {
        $request->validate([
            'master_profile_id' => 'required|exists:master_profiles,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'nullable|date|after_or_equal:today'
        ]);

        try {
            if ($request->has('date')) {
                return $this->getSlotsForDate($request);
            } else {
                return $this->getSlotsForMultipleDays($request);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при получении доступных слотов: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить слоты для конкретной даты
     */
    protected function getSlotsForDate(Request $request)
    {
        $date = Carbon::parse($request->date);
        $masterProfile = MasterProfile::with('schedules')->findOrFail($request->master_profile_id);
        $service = Service::findOrFail($request->service_id);
        
        $dayOfWeek = $date->dayOfWeek;
        $schedule = $masterProfile->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();
            
        if (!$schedule) {
            return response()->json(['slots' => []]);
        }

        // Используем метод из сервиса для генерации слотов
        $slots = $this->bookingService->generateDaySlots(
            $date->format('Y-m-d'),
            $schedule,
            $service,
            $masterProfile
        );

        return response()->json(['slots' => $slots]);
    }

    /**
     * Получить слоты на несколько дней
     */
    protected function getSlotsForMultipleDays(Request $request)
    {
        $slots = $this->bookingService->getAvailableSlots(
            $request->master_profile_id,
            $request->service_id
        );

        return response()->json(['slots' => $slots]);
    }

    /**
     * Проверить доступность слота
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'master_profile_id' => 'required|exists:master_profiles,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i'
        ]);

        try {
            $isAvailable = $this->bookingService->checkSlotAvailability(
                $request->master_profile_id,
                $request->date,
                $request->time,
                $request->service_id
            );

            return response()->json([
                'available' => $isAvailable,
                'message' => $isAvailable ? 'Слот доступен' : 'Слот недоступен'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при проверке доступности: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить занятые слоты мастера
     */
    public function busySlots(Request $request)
    {
        $request->validate([
            'master_profile_id' => 'required|exists:master_profiles,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        try {
            $busySlots = $this->bookingService->getBusySlots(
                $request->master_profile_id,
                $request->start_date,
                $request->end_date
            );

            return response()->json(['busy_slots' => $busySlots]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при получении занятых слотов: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить следующий доступный слот
     */
    public function nextAvailable(Request $request)
    {
        $request->validate([
            'master_profile_id' => 'required|exists:master_profiles,id',
            'service_id' => 'required|exists:services,id',
        ]);

        try {
            $nextSlot = $this->bookingService->getNextAvailableSlot(
                $request->master_profile_id,
                $request->service_id
            );

            if (!$nextSlot) {
                return response()->json([
                    'message' => 'Нет доступных слотов в ближайшее время'
                ], 404);
            }

            return response()->json([
                'next_slot' => $nextSlot,
                'message' => 'Ближайший доступный слот найден'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при поиске слота: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить расписание мастера
     */
    public function masterSchedule(Request $request)
    {
        $request->validate([
            'master_profile_id' => 'required|exists:master_profiles,id',
            'week_offset' => 'nullable|integer|min:0|max:4'
        ]);

        try {
            $weekOffset = $request->get('week_offset', 0);
            $schedule = $this->bookingService->getMasterWeekSchedule(
                $request->master_profile_id,
                $weekOffset
            );

            return response()->json([
                'schedule' => $schedule,
                'week_start' => Carbon::now()->addWeeks($weekOffset)->startOfWeek()->format('Y-m-d'),
                'week_end' => Carbon::now()->addWeeks($weekOffset)->endOfWeek()->format('Y-m-d')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при получении расписания: ' . $e->getMessage()
            ], 500);
        }
    }
}