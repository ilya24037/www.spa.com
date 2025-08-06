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
 */
class BookingService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private AvailabilityService $availabilityService,
        private PricingService $pricingService,
        private ValidationService $validationService,
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
        $this->validationService->validateBookingData($data);

        if (isset($data['type'])) {
            $type = BookingType::from($data['type']);
            $this->availabilityService->validateTimeSlotAvailability($data, $type);
        } else {
            $this->availabilityService->validateTimeSlot(
                $data['master_profile_id'] ?? $data['master_id'],
                $data['booking_date'] ?? Carbon::parse($data['start_time'])->format('Y-m-d'),
                $data['booking_time'] ?? Carbon::parse($data['start_time'])->format('H:i'),
                $data['service_id']
            );
        }

        $bookingData = BookingData::fromArray($data);
        $booking = $this->createBookingAction->execute($bookingData);
        
        BookingCreated::dispatch($booking);
        
        return $booking;
    }

    /**
     * Подтвердить бронирование
     */
    public function confirmBooking(Booking $booking, User $master): Booking
    {
        $this->validationService->validateMasterPermission($booking, $master);
        $this->validationService->validateConfirmationAbility($booking);

        $confirmedBooking = $this->confirmBookingAction->execute($booking, $master);
        
        $this->sendNotificationSafely(
            fn() => $this->notificationService->sendBookingConfirmation($confirmedBooking),
            'booking confirmation',
            $confirmedBooking->id
        );
        
        return $confirmedBooking;
    }

    /**
     * Отменить бронирование
     */
    public function cancelBooking(Booking $booking, User $user, ?string $reason = null): Booking
    {
        $this->validationService->validateCancellationPermission($booking, $user);
        
        if (!$this->availabilityService->canCancelBooking($booking)) {
            throw new \Exception('Это бронирование нельзя отменить');
        }

        $cancelledBooking = $this->cancelBookingAction->execute($booking, $user, $reason);
        
        $this->sendNotificationSafely(
            fn() => $this->notificationService->sendBookingCancellation($cancelledBooking),
            'booking cancellation',
            $cancelledBooking->id
        );
        
        return $cancelledBooking;
    }

    /**
     * Завершить бронирование
     */
    public function completeBooking(Booking $booking, User $master): Booking
    {
        $this->validationService->validateMasterPermission($booking, $master);
        $this->validationService->validateCompletionAbility($booking);

        $completedBooking = $this->completeBookingAction->execute($booking, $master);
        
        $this->sendNotificationSafely(
            fn() => $this->notificationService->sendBookingCompleted($completedBooking),
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
        if (!$this->availabilityService->canRescheduleBooking($booking)) {
            throw new \Exception('Это бронирование нельзя перенести');
        }

        $masterId = $booking->master_id ?? $booking->master->id;
        $duration = $newDuration ?? $booking->duration_minutes;
        
        if (!$this->availabilityService->isMasterAvailable($masterId, $newStartTime, $duration)) {
            throw new \Exception('Выбранное время недоступно');
        }

        $oldDateTime = [
            'date' => $booking->start_time->format('d.m.Y'),
            'time' => $booking->start_time->format('H:i'),
        ];
        
        $rescheduledBooking = $this->rescheduleBookingAction->execute($booking, $newStartTime, $newDuration);
        
        $this->sendNotificationSafely(
            fn() => $this->notificationService->sendBookingRescheduled($rescheduledBooking, $oldDateTime),
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
}