<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Actions\CancelBookingAction;
use App\Domain\Booking\Actions\CompleteBookingAction;
use App\Domain\Booking\Actions\ConfirmBookingAction;
use App\Domain\Booking\Actions\CreateBookingAction;
use App\Domain\Booking\Actions\RescheduleBookingAction;
use App\Domain\Booking\DTOs\BookingData;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\User\Models\User;
use App\Enums\BookingType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Главный сервис бронирования - координатор
 * Делегирует работу специализированным сервисам и actions
 */
class BookingService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private AvailabilityService $availabilityService,
        private PricingService $pricingService,
        private ValidationService $validationService,
        private BookingSlotService $slotService,
        private CreateBookingAction $createBookingAction,
        private ConfirmBookingAction $confirmBookingAction,
        private CancelBookingAction $cancelBookingAction,
        private CompleteBookingAction $completeBookingAction,
        private RescheduleBookingAction $rescheduleBookingAction
    ) {}

    /**
     * Создать новое бронирование
     */
    public function createBooking(array $data): Booking
    {
        // Валидация данных
        $this->validationService->validateBookingData($data);

        // Проверка доступности
        if (isset($data['type'])) {
            $type = BookingType::from($data['type']);
            $this->availabilityService->validateTimeSlotAvailability($data, $type);
        } else {
            // Старая логика для совместимости
            $this->availabilityService->validateTimeSlot(
                $data['master_profile_id'] ?? $data['master_id'],
                $data['booking_date'] ?? Carbon::parse($data['start_time'])->format('Y-m-d'),
                $data['booking_time'] ?? Carbon::parse($data['start_time'])->format('H:i'),
                $data['service_id']
            );
        }

        // Создание через Action
        $bookingData = BookingData::fromArray($data);
        return $this->createBookingAction->execute($bookingData);
    }

    /**
     * Подтвердить бронирование
     */
    public function confirmBooking(Booking $booking, User $master): Booking
    {
        // Валидация прав
        $this->validationService->validateMasterPermission($booking, $master);
        
        // Валидация возможности подтверждения
        $this->validationService->validateConfirmationAbility($booking);

        // Выполнение через Action
        return $this->confirmBookingAction->execute($booking, $master);
    }

    /**
     * Отменить бронирование
     */
    public function cancelBooking(Booking $booking, User $user, ?string $reason = null): Booking
    {
        // Валидация прав
        $this->validationService->validateCancellationPermission($booking, $user);
        
        // Проверка возможности отмены
        if (!$this->availabilityService->canCancelBooking($booking)) {
            throw new \Exception('Это бронирование нельзя отменить');
        }

        // Выполнение через Action
        return $this->cancelBookingAction->execute($booking, $user, $reason);
    }

    /**
     * Завершить бронирование
     */
    public function completeBooking(Booking $booking, User $master): Booking
    {
        // Валидация прав
        $this->validationService->validateMasterPermission($booking, $master);
        
        // Валидация возможности завершения
        $this->validationService->validateCompletionAbility($booking);

        // Выполнение через Action
        return $this->completeBookingAction->execute($booking, $master);
    }

    /**
     * Перенести бронирование
     */
    public function rescheduleBooking(
        Booking $booking, 
        Carbon $newStartTime, 
        int $newDuration = null
    ): Booking {
        // Проверка возможности переноса
        if (!$this->availabilityService->canRescheduleBooking($booking)) {
            throw new \Exception('Это бронирование нельзя перенести');
        }

        // Проверка доступности нового времени
        $masterId = $booking->master_id ?? $booking->master->id;
        $duration = $newDuration ?? $booking->duration_minutes;
        
        if (!$this->availabilityService->isMasterAvailable($masterId, $newStartTime, $duration)) {
            throw new \Exception('Выбранное время недоступно');
        }

        // Выполнение через Action
        return $this->rescheduleBookingAction->execute($booking, $newStartTime, $newDuration);
    }

    /**
     * Получить доступные слоты
     */
    public function getAvailableSlots(
        int $masterProfileId, 
        int $serviceId, 
        int $days = 14
    ): array {
        return $this->availabilityService->getAvailableSlots($masterProfileId, $serviceId, $days);
    }

    /**
     * Получить доступные слоты с учетом типа
     */
    public function getAvailableSlotsForType(
        int $masterId, 
        int $serviceId, 
        BookingType $type, 
        int $days = 14
    ): array {
        return $this->availabilityService->getAvailableSlotsForType($masterId, $serviceId, $type, $days);
    }

    /**
     * Найти бронирование по номеру
     */
    public function findByNumber(string $bookingNumber): ?Booking
    {
        return $this->bookingRepository->findByNumber($bookingNumber);
    }

    /**
     * Получить бронирования пользователя
     */
    public function getUserBookings(
        User $user, 
        array $filters = []
    ): Collection {
        if ($user->isMaster()) {
            return $this->getMasterBookings($user, $filters);
        }
        
        return $this->getClientBookings($user, $filters);
    }

    /**
     * Получить бронирования клиента
     */
    public function getClientBookings(
        User $client, 
        array $filters = []
    ): Collection {
        $query = $this->bookingRepository->query()
            ->where('client_id', $client->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('start_time', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('start_time', '<=', $filters['to_date']);
        }

        return $query->with(['master', 'service'])->orderBy('start_time', 'desc')->get();
    }

    /**
     * Получить бронирования мастера
     */
    public function getMasterBookings(
        User $master, 
        array $filters = []
    ): Collection {
        $query = $this->bookingRepository->query()
            ->where('master_id', $master->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('start_time', $filters['date']);
        }

        if (isset($filters['from_date'])) {
            $query->where('start_time', '>=', $filters['from_date']);
        }

        return $query->with(['client', 'service'])->orderBy('start_time')->get();
    }

    /**
     * Получить статистику бронирований
     */
    public function getBookingStats(User $user, string $period = 'month'): array
    {
        $query = $user->isMaster() 
            ? $this->bookingRepository->query()->where('master_id', $user->id)
            : $this->bookingRepository->query()->where('client_id', $user->id);

        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()
        };

        return [
            'total' => (clone $query)->where('created_at', '>=', $startDate)->count(),
            'completed' => (clone $query)->where('created_at', '>=', $startDate)
                ->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('created_at', '>=', $startDate)
                ->where('status', 'cancelled')->count(),
            'revenue' => (clone $query)->where('created_at', '>=', $startDate)
                ->where('status', 'completed')->sum('total_price'),
        ];
    }

    /**
     * Рассчитать стоимость бронирования
     */
    public function calculatePrice(int $serviceId, array $options = []): array
    {
        $service = app(\App\Domain\Service\Models\Service::class)->findOrFail($serviceId);
        
        if (isset($options['type'])) {
            $type = BookingType::from($options['type']);
            return $this->pricingService->calculatePricingWithType($service, $options, $type);
        }
        
        return $this->pricingService->calculatePricing($service, $options);
    }

    /**
     * Применить промокод
     */
    public function applyPromoCode(float $totalPrice, string $promoCode): array
    {
        return $this->pricingService->applyPromoCode($totalPrice, $promoCode);
    }

    /**
     * Найти следующий доступный слот
     */
    public function findNextAvailableSlot(
        int $masterId,
        int $serviceId,
        ?Carbon $preferredTime = null,
        ?BookingType $type = null
    ): ?array {
        return $this->availabilityService->findNextAvailableSlot(
            $masterId, 
            $serviceId, 
            $preferredTime, 
            $type
        );
    }
}