<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

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
            ->orderBy('start_time', 'desc')
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

        // Получаем доступные слоты через сервис
        $availableSlots = $this->bookingService->getAvailableSlots(
            $masterProfile->id,
            $service->id
        );

        return Inertia::render('Bookings/NewBooking', [
            'masterProfile' => $masterProfile,
            'service' => $service,
            'availableSlots' => $availableSlots
        ]);
    }

    /**
     * Сохранение бронирования (НОВЫЙ РЕФАКТОРЕННЫЙ МЕТОД)
     */
    public function store(StoreBookingRequest $request)
    {
        try {
            // Создаем бронирование через сервис
            $booking = $this->bookingService->createBooking(
                $request->getBookingData()
            );

            // Возвращаем JSON для API или редирект для веб
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Бронирование успешно создано',
                    'booking' => $booking,
                    'booking_number' => $booking->booking_number
                ], 201);
            }

            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Бронирование успешно создано! Ожидайте подтверждения от мастера.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->withErrors(['booking' => $e->getMessage()]);
        }
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
     * Отмена бронирования (РЕФАКТОРЕННЫЙ МЕТОД)
     */
    public function cancel(Booking $booking, Request $request)
    {
        try {
            $this->bookingService->cancelBooking(
                $booking, 
                Auth::user(), 
                $request->input('reason')
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Бронирование отменено'
                ]);
            }

            return back()->with('success', 'Бронирование отменено');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Подтверждение бронирования мастером (РЕФАКТОРЕННЫЙ МЕТОД)
     */
    public function confirm(Booking $booking, Request $request)
    {
        try {
            $this->bookingService->confirmBooking($booking, Auth::user());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Бронирование подтверждено'
                ]);
            }

            return back()->with('success', 'Бронирование подтверждено');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Завершение услуги мастером (РЕФАКТОРЕННЫЙ МЕТОД)
     */
    public function complete(Booking $booking, Request $request)
    {
        try {
            $this->bookingService->completeBooking($booking, Auth::user());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Услуга успешно завершена! Клиенту отправлен запрос на отзыв.'
                ]);
            }

            return back()->with('success', 'Услуга успешно завершена! Клиенту отправлен запрос на отзыв.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * API метод для получения доступных слотов (УЛУЧШЕННЫЙ)
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
                // Получаем слоты для конкретной даты
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
            } else {
                // Получаем слоты на несколько дней
                $slots = $this->bookingService->getAvailableSlots(
                    $request->master_profile_id,
                    $request->service_id
                );

                return response()->json(['slots' => $slots]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка при получении доступных слотов: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================= УДАЛЕННЫЕ СТАРЫЕ МЕТОДЫ =================
    // Убрал дублированную логику, которая теперь в BookingService:
    // - getAvailableSlots()
    // - generateDaySlots() 
    // - checkSlotAvailability()
    // Вся бизнес-логика теперь в сервисе!
}