<?php

namespace App\Application\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\MasterProfile;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Основной контроллер для управления бронированиями
 * Отвечает за создание, просмотр и управление бронированиями
 */
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
     * Сохранение бронирования
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
        $this->authorize('view', $booking);

        $booking->load(['masterProfile.user', 'service', 'client']);

        $user = Auth::user();
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
     * Подтверждение бронирования мастером
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
     * Завершение услуги мастером
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
     * Изменение бронирования
     */
    public function reschedule(Booking $booking, Request $request)
    {
        $this->authorize('update', $booking);

        $request->validate([
            'booking_date' => 'required|date|after:today',
            'start_time' => 'required|date_format:H:i',
        ]);

        try {
            $this->bookingService->rescheduleBooking(
                $booking,
                $request->booking_date,
                $request->start_time
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Время бронирования изменено'
                ]);
            }

            return back()->with('success', 'Время бронирования изменено');

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
}