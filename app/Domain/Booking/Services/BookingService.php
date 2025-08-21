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
use App\Domain\Booking\Contracts\BookingServiceInterface;
use App\Domain\User\Models\User;
use App\Enums\BookingType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Главный сервис бронирования - координатор
 */
class BookingService implements BookingServiceInterface
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private BookingSlotService $slotService,
        private BookingValidationService $validationService,
        private BookingNotificationService $notificationService,
        private BookingPaymentService $paymentService,
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
        $this->validationService->validateCreate($data);

        if (isset($data['type'])) {
            $type = BookingType::from($data['type']);
            $this->validationService->validateTimeSlotAvailability($data, $type);
        } else {
            $this->validationService->validateTimeSlot(
                $data['master_profile_id'] ?? $data['master_id'],
                $data['booking_date'] ?? Carbon::parse($data['start_time'])->format('Y-m-d'),
                $data['booking_time'] ?? Carbon::parse($data['start_time'])->format('H:i'),
                $data['service_id']
            );
        }

        $bookingData = BookingData::fromArray($data);
        $booking = $this->createBookingAction->execute($bookingData);
        
        BookingCreated::dispatch($booking);
        
        $this->notificationService->sendCreatedNotification($booking);
        
        return $booking;
    }

    /**
     * Подтвердить бронирование (основной метод)
     */
    public function confirmBookingByMaster(Booking $booking, User $master): Booking
    {
        $this->validationService->validateConfirmation($booking);

        $confirmedBooking = $this->confirmBookingAction->execute($booking, $master);
        
        $this->sendNotificationSafely(
            fn() => $this->notificationService->sendConfirmationNotification($confirmedBooking),
            'booking confirmation',
            $confirmedBooking->id
        );
        
        return $confirmedBooking;
    }

    /**
     * Отменить бронирование (основной метод)
     */
    public function cancelBookingByUser(Booking $booking, User $user, ?string $reason = null): Booking
    {
        $this->validationService->validateCancellation($booking, $user);
        
        if (!$this->validationService->canCancelBooking($booking)) {
            throw new \Exception('Это бронирование нельзя отменить');
        }

        $cancelledBooking = $this->cancelBookingAction->execute($booking, $user, $reason);
        
        $this->sendNotificationSafely(
            fn() => $this->notificationService->sendCancellationNotification($cancelledBooking, $reason),
            'booking cancellation',
            $cancelledBooking->id
        );
        
        return $cancelledBooking;
    }

    /**
     * Завершить бронирование (основной метод)
     */
    public function completeBookingByMaster(Booking $booking, User $master): Booking
    {
        $this->validationService->validateCompletion($booking);

        $completedBooking = $this->completeBookingAction->execute($booking, $master);
        
        $this->sendNotificationSafely(
            fn() => $this->notificationService->sendCompletionNotifications($completedBooking),
            'booking completed',
            $completedBooking->id
        );
        
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
        $user = auth()->user() ?? $booking->user;
        $this->validationService->validateReschedule($booking, $newStartTime, $user);
        
        $masterId = $booking->master_id ?? $booking->master->id;
        $duration = $newDuration ?? $booking->duration_minutes;
        
        if (!$this->slotService->canRescheduleBooking($booking, $newStartTime, $duration)) {
            throw new \Exception('Это бронирование нельзя перенести');
        }
        
        if (!$this->slotService->isMasterAvailable($masterId, $newStartTime, $duration)) {
            throw new \Exception('Выбранное время недоступно');
        }

        $oldDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->start_time);
        
        $rescheduledBooking = $this->rescheduleBookingAction->execute($booking, $newStartTime, $newDuration);
        
        $this->sendNotificationSafely(
            fn() => $this->notificationService->sendRescheduleNotification($rescheduledBooking, $oldDateTime),
            'booking rescheduled',
            $rescheduledBooking->id
        );
        
        return $rescheduledBooking;
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
     * Безопасная отправка уведомления
     */
    private function sendNotificationSafely(callable $notificationCallback, string $type, int $bookingId): void
    {
        try {
            $notificationCallback();
        } catch (\Exception $e) {
            Log::error("Failed to send {$type} notification", [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
        }
    }

    // === МЕТОДЫ ИНТЕРФЕЙСА BookingServiceInterface ===

    /**
     * Подтвердить бронирование (адаптер для интерфейса)
     */
    public function confirmBooking(int $bookingId): bool
    {
        try {
            $booking = $this->bookingRepository->find($bookingId);
            if (!$booking) {
                return false;
            }
            $master = $booking->master->user ?? null;
            if (!$master) {
                return false;
            }
            $this->confirmBookingByMaster($booking, $master);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to confirm booking', ['booking_id' => $bookingId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Отменить бронирование (адаптер для интерфейса)
     */
    public function cancelBooking(int $bookingId, int $cancelledBy, ?string $reason = null): bool
    {
        try {
            $booking = $this->bookingRepository->find($bookingId);
            if (!$booking) {
                return false;
            }
            $user = app(\App\Domain\User\Models\User::class)->find($cancelledBy);
            if (!$user) {
                return false;
            }
            $this->cancelBookingByUser($booking, $user, $reason);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel booking', ['booking_id' => $bookingId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Завершить бронирование (адаптер для интерфейса)
     */
    public function completeBooking(int $bookingId, array $completionData = []): bool
    {
        try {
            $booking = $this->bookingRepository->find($bookingId);
            if (!$booking) {
                return false;
            }
            $master = $booking->master->user ?? null;
            if (!$master) {
                return false;
            }
            $this->completeBookingByMaster($booking, $master);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to complete booking', ['booking_id' => $bookingId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Получить доступные слоты для бронирования
     */
    public function getAvailableSlots(int $masterId, string $date): array
    {
        $dateCarbon = Carbon::parse($date);
        return $this->slotService->getAvailableSlots($masterId, 1, $dateCarbon, 1);
    }

    /**
     * Проверить возможность создания бронирования
     */
    public function canCreateBooking(int $clientId, int $masterId, array $bookingData): bool
    {
        try {
            $bookingData['client_id'] = $clientId;
            $bookingData['master_profile_id'] = $masterId;
            $this->validationService->validateCreate($bookingData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получить бронирования с фильтрами
     */
    public function getBookingsWithFilters(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $items = $this->bookingRepository->search($filters)->items();
        return new \Illuminate\Database\Eloquent\Collection($items);
    }

    /**
     * Отправить уведомления о бронировании
     */
    public function sendBookingNotifications(Booking $booking, string $eventType): void
    {
        switch ($eventType) {
            case 'created':
                $this->notificationService->sendCreatedNotification($booking);
                break;
            case 'confirmed':
                $this->notificationService->sendConfirmationNotification($booking);
                break;
            case 'cancelled':
                $this->notificationService->sendCancellationNotification($booking);
                break;
            case 'completed':
                $this->notificationService->sendCompletionNotifications($booking);
                break;
        }
    }

    /**
     * Получить статистику бронирований
     */
    public function getBookingStatistics(array $filters = []): array
    {
        return $this->bookingRepository->getStatistics($filters);
    }

    /**
     * Валидировать данные бронирования
     */
    public function validateBookingData(array $data): array
    {
        $this->validationService->validateCreate($data);
        return ['valid' => true, 'errors' => []];
    }
}