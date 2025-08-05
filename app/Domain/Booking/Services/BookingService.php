<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Actions\CancelBookingAction;
use App\Domain\Booking\Actions\CompleteBookingAction;
use App\Domain\Booking\Actions\ConfirmBookingAction;
use App\Domain\Booking\Actions\CreateBookingAction;
use App\Domain\Booking\Actions\RescheduleBookingAction;
use App\Domain\Booking\Events\BookingCreated;
use App\Domain\Booking\DTOs\BookingData;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Booking\Services\NotificationService;
use App\Domain\User\Models\User;
use App\Enums\BookingType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
        private NotificationService $notificationService,
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
        $booking = $this->createBookingAction->execute($bookingData);
        
        // Запускаем событие о создании бронирования (вместо прямой отправки уведомлений)
        BookingCreated::dispatch($booking);
        
        return $booking;
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
        $confirmedBooking = $this->confirmBookingAction->execute($booking, $master);
        
        // Отправляем уведомление о подтверждении
        try {
            $this->notificationService->sendBookingConfirmation($confirmedBooking);
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем процесс
            \Log::error('Failed to send booking confirmation notification', [
                'booking_id' => $confirmedBooking->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return $confirmedBooking;
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
        $cancelledBooking = $this->cancelBookingAction->execute($booking, $user, $reason);
        
        // Отправляем уведомление об отмене
        try {
            $this->notificationService->sendBookingCancellation($cancelledBooking);
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем процесс
            \Log::error('Failed to send booking cancellation notification', [
                'booking_id' => $cancelledBooking->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return $cancelledBooking;
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
        $completedBooking = $this->completeBookingAction->execute($booking, $master);
        
        // Отправляем уведомление о завершении
        try {
            $this->notificationService->sendBookingCompleted($completedBooking);
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем процесс
            \Log::error('Failed to send booking completed notification', [
                'booking_id' => $completedBooking->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return $completedBooking;
    }

    /**
     * Перенести бронирование
     */
    public function rescheduleBooking(
        Booking $booking, 
        Carbon $newStartTime, 
        ?int $newDuration = null
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

        // Сохраняем старые дату и время для уведомления
        $oldDateTime = [
            'date' => $booking->start_time->format('d.m.Y'),
            'time' => $booking->start_time->format('H:i'),
        ];
        
        // Выполнение через Action
        $rescheduledBooking = $this->rescheduleBookingAction->execute($booking, $newStartTime, $newDuration);
        
        // Отправляем уведомление о переносе
        try {
            $this->notificationService->sendBookingRescheduled($rescheduledBooking, $oldDateTime);
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем процесс
            \Log::error('Failed to send booking rescheduled notification', [
                'booking_id' => $rescheduledBooking->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return $rescheduledBooking;
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
     * Получить список бронирований для контроллера
     */
    public function getBookingsForUser(User $user, int $perPage = 10)
    {
        return $this->bookingRepository->getBookingsForUser($user, $perPage);
    }

    /**
     * Получить мастера и услугу для создания бронирования
     */
    public function validateBookingRequest(int $masterProfileId, int $serviceId): array
    {
        return $this->bookingRepository->validateBookingRequest($masterProfileId, $serviceId);
    }

    /**
     * Найти бронирование с загруженными связями
     */
    public function findBookingWithRelations(int $bookingId): ?Booking
    {
        return $this->bookingRepository->findWithRelations($bookingId);
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
                ->where('status', BookingStatus::COMPLETED->value)->count(),
            'cancelled' => (clone $query)->where('created_at', '>=', $startDate)
                ->where('status', BookingStatus::CANCELLED->value)->count(),
            'revenue' => (clone $query)->where('created_at', '>=', $startDate)
                ->where('status', BookingStatus::COMPLETED->value)->sum('total_price'),
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

    /**
     * Отправить напоминание о бронировании
     */
    public function sendBookingReminder(Booking $booking): void
    {
        try {
            $this->notificationService->sendBookingReminder($booking);
        } catch (\Exception $e) {
            Log::error('Failed to send booking reminder', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Отправить напоминания о предстоящих бронированиях
     * Используется в scheduled задачах
     */
    public function sendUpcomingBookingReminders(): int
    {
        $tomorrow = Carbon::tomorrow();
        $bookings = $this->bookingRepository->query()
            ->where('status', BookingStatus::CONFIRMED->value)
            ->whereDate('start_time', $tomorrow->toDateString())
            ->whereNull('reminder_sent_at')
            ->get();

        $sentCount = 0;
        foreach ($bookings as $booking) {
            try {
                $this->sendBookingReminder($booking);
                
                // Отмечаем, что напоминание отправлено через репозиторий
                $this->bookingRepository->update($booking, [
                    'reminder_sent_at' => now()
                ]);
                
                $sentCount++;
            } catch (\Exception $e) {
                Log::error('Failed to send reminder for booking', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $sentCount;
    }

    /**
     * Получить слоты для конкретной даты
     */
    public function getSlotsForDate(int $masterProfileId, int $serviceId, string $date): array
    {
        return $this->bookingRepository->getSlotsForDate($masterProfileId, $serviceId, $date);
    }

    /**
     * Проверить доступность слота
     */
    public function checkSlotAvailability(int $masterProfileId, string $date, string $time, int $serviceId): bool
    {
        return $this->availabilityService->validateTimeSlot($masterProfileId, $date, $time, $serviceId);
    }

    /**
     * Получить занятые слоты мастера
     */
    public function getBusySlots(int $masterProfileId, string $startDate, string $endDate): array
    {
        return $this->bookingRepository->getBusySlots($masterProfileId, $startDate, $endDate);
    }

    /**
     * Получить следующий доступный слот
     */
    public function getNextAvailableSlot(int $masterProfileId, int $serviceId): ?array
    {
        return $this->availabilityService->findNextAvailableSlot($masterProfileId, $serviceId);
    }

    /**
     * Получить расписание мастера на неделю
     */
    public function getMasterWeekSchedule(int $masterProfileId, int $weekOffset = 0): array
    {
        return $this->bookingRepository->getMasterWeekSchedule($masterProfileId, $weekOffset);
    }

    /**
     * Генерировать слоты для дня (метод уже существует в сервисе)
     */
    public function generateDaySlots(string $date, $schedule, $service, $masterProfile): array
    {
        return $this->availabilityService->generateDaySlots($date, $schedule, $service, $masterProfile);
    }
}