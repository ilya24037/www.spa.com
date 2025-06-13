<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'master_profile_id' => 'required|exists:master_profiles,id',
            'service_id' => 'required|exists:services,id',
            'booking_date' => 'required|date|after:today',
            'start_time' => 'required|date_format:H:i',
            'address' => 'required_if:is_home_service,true',
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:20',
            'client_email' => 'nullable|email',
            'client_comment' => 'nullable|string|max:500',
            'is_home_service' => 'boolean',
            'payment_method' => 'required|in:cash,card,online'
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $masterProfile = MasterProfile::findOrFail($validated['master_profile_id']);

        // Проверяем доступность времени
        $existingBooking = Booking::where('master_profile_id', $validated['master_profile_id'])
            ->where('booking_date', $validated['booking_date'])
            ->where('start_time', $validated['start_time'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($existingBooking) {
            return back()->withErrors(['start_time' => 'Это время уже занято']);
        }

        // Рассчитываем цены
        $travelFee = $validated['is_home_service'] ? 500 : 0; // Фиксированная доплата за выезд
        $totalPrice = $service->price + $travelFee;

        // Создаем бронирование
        $booking = Booking::create([
            ...$validated,
            'client_id' => auth()->id(),
            'duration' => $service->duration,
            'end_time' => Carbon::parse($validated['start_time'])->addMinutes($service->duration)->format('H:i'),
            'service_price' => $service->price,
            'travel_fee' => $travelFee,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'payment_status' => 'pending'
        ]);

        // TODO: Отправить уведомление мастеру

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Заявка отправлена! Ожидайте подтверждения мастера.');
    }

    public function show(Booking $booking)
    {
        // Проверяем доступ
        if ($booking->client_id !== auth()->id() && 
            $booking->masterProfile->user_id !== auth()->id()) {
            abort(403);
        }

        $booking->load(['service', 'masterProfile.user', 'client']);

        return Inertia::render('Bookings/Show', [
            'booking' => $booking
        ]);
    }

    public function index()
    {
        $bookings = Booking::where('client_id', auth()->id())
            ->with(['service', 'masterProfile.user'])
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(10);

        return Inertia::render('Bookings/Index', [
            'bookings' => $bookings
        ]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        // Проверяем права
        if ($booking->client_id !== auth()->id() && 
            $booking->masterProfile->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        if (!$booking->canCancel()) {
            return back()->withErrors(['error' => 'Невозможно отменить это бронирование']);
        }

        $booking->cancel($request->reason, auth()->id());

        return back()->with('success', 'Бронирование отменено');
    }

    public function confirm(Booking $booking)
    {
        // Только мастер может подтвердить
        if ($booking->masterProfile->user_id !== auth()->id()) {
            abort(403);
        }

        $booking->confirm();

        return back()->with('success', 'Бронирование подтверждено');
    }

    // API метод для получения доступных слотов
    public function availableSlots(Request $request)
    {
        $request->validate([
            'master_profile_id' => 'required|exists:master_profiles,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after_or_equal:today'
        ]);

        $date = Carbon::parse($request->date);
        $dayOfWeek = $date->dayOfWeekIso;
        
        $masterProfile = MasterProfile::findOrFail($request->master_profile_id);
        $service = Service::findOrFail($request->service_id);
        
        // Получаем расписание на этот день
        $schedule = $masterProfile->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        if (!$schedule) {
            return response()->json(['slots' => []]);
        }

        // Получаем все слоты
        $allSlots = $schedule->getAvailableSlots($date);

        // Получаем занятые слоты
        $bookedSlots = Booking::where('master_profile_id', $request->master_profile_id)
            ->where('booking_date', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('start_time')
            ->map(fn($time) => Carbon::parse($time)->format('H:i'))
            ->toArray();

        // Фильтруем доступные слоты
        $availableSlots = array_values(array_diff($allSlots, $bookedSlots));

        return response()->json(['slots' => $availableSlots]);
    }
}