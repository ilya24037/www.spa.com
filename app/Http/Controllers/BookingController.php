<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Список бронирований пользователя
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $bookings = Booking::with(['masterProfile.user', 'service', 'client'])
            ->where(function($query) use ($user) {
                // Показываем бронирования где пользователь - клиент
                $query->where('client_id', $user->id);
                
                // Или где пользователь - мастер
                if ($user->masterProfile) {
                    $query->orWhere('master_profile_id', $user->masterProfile->id);
                }
            })
            ->orderBy('booking_date', 'desc')
            ->orderBy('booking_time', 'desc')
            ->paginate(10);

        return Inertia::render('Bookings/Index', [
            'bookings' => $bookings,
            'isMaster' => $user->role === 'master'
        ]);
    }

    /**
     * Форма создания бронирования
     */
    public function create(Request $request)
    {
        $masterProfileId = $request->get('master_id');
        $serviceId = $request->get('service_id');
        
        if (!$masterProfileId || !$serviceId) {
            return redirect()->route('home')
                ->with('error', 'Выберите мастера и услугу');
        }

        $masterProfile = MasterProfile::with(['user', 'services', 'schedules'])
            ->findOrFail($masterProfileId);
            
        $service = Service::findOrFail($serviceId);
        
        // Проверяем, что услуга принадлежит мастеру
        if (!$masterProfile->services->contains($service)) {
            return redirect()->route('masters.show', $masterProfile)
                ->with('error', 'Выбранная услуга не доступна у этого мастера');
        }

        return Inertia::render('Bookings/Create', [
            'masterProfile' => $masterProfile,
            'service' => $service,
            'availableSlots' => $this->getAvailableSlots($masterProfile, $service)
        ]);
    }

    /**
     * Сохранение бронирования
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'master_profile_id' => 'required|exists:master_profiles,id',
            'service_id' => 'required|exists:services,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required|date_format:H:i',
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:20',
            'client_email' => 'nullable|email|max:255',
            'client_comment' => 'nullable|string|max:500',
            'address' => 'nullable|string|max:500',
            'address_details' => 'nullable|string|max:255',
            'service_location' => 'required|in:home,salon',
            'payment_method' => 'required|in:cash,card,online,transfer'
        ]);

        // Проверяем доступность слота
        $isAvailable = $this->checkSlotAvailability(
            $validated['master_profile_id'],
            $validated['booking_date'],
            $validated['booking_time'],
            $validated['service_id']
        );

        if (!$isAvailable) {
            return back()->withErrors([
                'booking_time' => 'Выбранное время уже занято'
            ]);
        }

        // Получаем информацию об услуге
        $service = Service::find($validated['service_id']);
        $endTime = Carbon::parse($validated['booking_time'])
            ->addMinutes($service->duration)
            ->format('H:i');

        // Рассчитываем стоимость
        $servicePrice = $service->price;
        $travelFee = $validated['service_location'] === 'home' ? 500 : 0;
        $discountAmount = 0; // Можно добавить логику скидок
        $totalPrice = $servicePrice + $travelFee - $discountAmount;

        // Создаём бронирование
        $booking = Booking::create([
            'client_id' => Auth::id(),
            'master_profile_id' => $validated['master_profile_id'],
            'service_id' => $validated['service_id'],
            'booking_date' => $validated['booking_date'],
            'start_time' => $validated['booking_time'],
            'end_time' => $endTime,
            'booking_time' => $validated['booking_time'], // для совместимости
            'booking_end_time' => $endTime, // для совместимости
            'duration' => $service->duration,
            'address' => $validated['address'] ?? null,
            'address_details' => $validated['address_details'] ?? null,
            'is_home_service' => $validated['service_location'] === 'home',
            'service_location' => $validated['service_location'],
            'service_price' => $servicePrice,
            'travel_fee' => $travelFee,
            'discount_amount' => $discountAmount,
            'total_price' => $totalPrice,
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'status' => 'pending',
            'client_name' => $validated['client_name'],
            'client_phone' => $validated['client_phone'],
            'client_email' => $validated['client_email'] ?? null,
            'client_comment' => $validated['client_comment'] ?? null,
            'source' => 'website'
        ]);

        // Отправляем уведомления (в будущем)
        // $this->sendNotifications($booking);

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Бронирование успешно создано! Ожидайте подтверждения от мастера.');
    }

    /**
     * Просмотр бронирования
     */
    public function show(Booking $booking)
    {
        // Проверяем доступ
        $user = Auth::user();
        if ($booking->client_id !== $user->id && 
            (!$user->masterProfile || $booking->master_profile_id !== $user->masterProfile->id)) {
            abort(403, 'У вас нет доступа к этому бронированию');
        }

        $booking->load(['masterProfile.user', 'service', 'client']);

        return Inertia::render('Bookings/Show', [
            'booking' => $booking,
            'canManage' => $user->masterProfile && 
                          $booking->master_profile_id === $user->masterProfile->id
        ]);
    }

    /**
     * Отмена бронирования
     */
    public function cancel(Booking $booking, Request $request)
    {
        $user = Auth::user();
        
        // Проверяем права на отмену
        if ($booking->client_id !== $user->id && 
            (!$user->masterProfile || $booking->master_profile_id !== $user->masterProfile->id)) {
            abort(403);
        }

        // Проверяем статус
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Это бронирование нельзя отменить');
        }

        // Проверяем время (за 2 часа до начала)
        $bookingDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->booking_time);
        if (now()->diffInHours($bookingDateTime, false) < 2) {
            return back()->with('error', 'Отмена возможна не позднее чем за 2 часа до начала');
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => $request->input('reason')
        ]);

        // Отправляем уведомление другой стороне
        // $this->sendCancellationNotification($booking);

        return back()->with('success', 'Бронирование отменено');
    }

    /**
     * Подтверждение бронирования мастером
     */
    public function confirm(Booking $booking)
    {
        $user = Auth::user();
        
        // Только мастер может подтвердить
        if (!$user->masterProfile || $booking->master_profile_id !== $user->masterProfile->id) {
            abort(403);
        }

        if ($booking->status !== 'pending') {
            return back()->with('error', 'Это бронирование уже обработано');
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmed_at' => now()
        ]);

        // Отправить уведомление клиенту
        // $this->notifyClient($booking);

        return back()->with('success', 'Бронирование подтверждено');
    }

    /**
     * Завершение услуги мастером
     */
    public function complete(Booking $booking)
    {
        $user = Auth::user();
        
        // Только мастер может завершить
        if (!$user->masterProfile || $booking->master_profile_id !== $user->masterProfile->id) {
            abort(403, 'У вас нет прав для выполнения этого действия');
        }

        // Проверяем статус
        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Можно завершить только подтверждённое бронирование');
        }

        // Проверяем, что время услуги уже наступило
        $bookingDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->booking_time);
        if ($bookingDateTime->isFuture()) {
            return back()->with('error', 'Нельзя завершить услугу до её начала');
        }

        // Обновляем статус
        $booking->update([
            'status' => 'completed',
            'payment_status' => 'paid',
            'paid_at' => now()
        ]);

        // Увеличиваем счётчик выполненных услуг у мастера
        $user->masterProfile->increment('completed_bookings_count');

        // Отправляем запрос на отзыв
        $booking->update([
            'review_requested' => true,
            'review_requested_at' => now()
        ]);

        // Отправляем запрос на отзыв (в будущем)
        // $this->requestReview($booking);

        return back()->with('success', 'Услуга успешно завершена! Клиенту отправлен запрос на отзыв.');
    }

    /**
     * Метод для получения доступных слотов через API (AJAX)
     */
    public function availableSlots(Request $request)
    {
        $validated = $request->validate([
            'master_profile_id' => 'required|exists:master_profiles,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after_or_equal:today'
        ]);

        $masterProfile = MasterProfile::with('schedules')->find($validated['master_profile_id']);
        $service = Service::find($validated['service_id']);
        $date = Carbon::parse($validated['date']);
        
        // Получаем расписание на этот день
        $dayOfWeek = $date->dayOfWeek;
        $schedule = $masterProfile->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();
            
        if (!$schedule) {
            return response()->json(['slots' => []]);
        }

        // Генерируем слоты
        $slots = $this->generateDaySlots(
            $date->format('Y-m-d'),
            $schedule,
            $service,
            $masterProfile
        );

        return response()->json(['slots' => $slots]);
    }

    /**
     * Получение доступных слотов для календаря
     */
    private function getAvailableSlots(MasterProfile $masterProfile, Service $service)
    {
        $slots = [];
        $startDate = now();
        $endDate = now()->addDays(14); // Показываем слоты на 2 недели вперёд

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek;
            
            // Получаем расписание на этот день недели
            $schedule = $masterProfile->schedules()
                ->where('day_of_week', $dayOfWeek)
                ->where('is_working_day', true)
                ->first();
                
            if (!$schedule) continue;

            // Генерируем слоты с учётом длительности услуги
            $daySlots = $this->generateDaySlots(
                $date->format('Y-m-d'),
                $schedule,
                $service,
                $masterProfile
            );

            if (!empty($daySlots)) {
                $slots[$date->format('Y-m-d')] = $daySlots;
            }
        }

        return $slots;
    }

    /**
     * Генерация слотов на день
     */
    private function generateDaySlots($date, $schedule, $service, $masterProfile)
    {
        $slots = [];
        $serviceDuration = $service->duration; // в минутах
        
        $startTime = Carbon::parse($date . ' ' . $schedule->start_time);
        $endTime = Carbon::parse($date . ' ' . $schedule->end_time);
        $breakStart = $schedule->break_start ? Carbon::parse($date . ' ' . $schedule->break_start) : null;
        $breakEnd = $schedule->break_end ? Carbon::parse($date . ' ' . $schedule->break_end) : null;

        // Получаем существующие бронирования на этот день
        $existingBookings = Booking::where('master_profile_id', $masterProfile->id)
            ->where('booking_date', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        $currentSlot = $startTime->copy();
        
        while ($currentSlot->copy()->addMinutes($serviceDuration) <= $endTime) {
            // Пропускаем время обеда
            if ($breakStart && $breakEnd) {
                if ($currentSlot >= $breakStart && $currentSlot < $breakEnd) {
                    $currentSlot = $breakEnd->copy();
                    continue;
                }
                if ($currentSlot->copy()->addMinutes($serviceDuration) > $breakStart && 
                    $currentSlot < $breakStart) {
                    $currentSlot = $breakEnd->copy();
                    continue;
                }
            }

            // Проверяем, не занят ли слот
            $isAvailable = true;
            foreach ($existingBookings as $booking) {
                $bookingStart = Carbon::parse($date . ' ' . $booking->booking_time);
                $bookingEnd = Carbon::parse($date . ' ' . $booking->booking_end_time);
                
                // Проверяем пересечения
                if ($currentSlot < $bookingEnd && $currentSlot->copy()->addMinutes($serviceDuration) > $bookingStart) {
                    $isAvailable = false;
                    break;
                }
            }

            // Проверяем, что слот в будущем (минимум за 2 часа)
            $slotDateTime = Carbon::parse($date . ' ' . $currentSlot->format('H:i'));
            if ($isAvailable && $slotDateTime > now()->addHours(2)) {
                $slots[] = [
                    'time' => $currentSlot->format('H:i'),
                    'available' => true
                ];
            }

            $currentSlot->addMinutes(30); // Слоты каждые 30 минут
        }

        return $slots;
    }

    /**
     * Проверка доступности слота
     */
    private function checkSlotAvailability($masterProfileId, $date, $time, $serviceId)
    {
        $service = Service::find($serviceId);
        $startTime = Carbon::parse($time);
        $endTime = $startTime->copy()->addMinutes($service->duration);

        // Проверяем пересечения с другими бронированиями
        $conflict = Booking::where('master_profile_id', $masterProfileId)
            ->where('booking_date', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function($query) use ($startTime, $endTime) {
                $query->where(function($q) use ($startTime, $endTime) {
                    // Проверяем все возможные пересечения
                    $q->whereTime('start_time', '<', $endTime->format('H:i:s'))
                      ->whereTime('end_time', '>', $startTime->format('H:i:s'));
                });
            })
            ->exists();

        return !$conflict;
    }
}