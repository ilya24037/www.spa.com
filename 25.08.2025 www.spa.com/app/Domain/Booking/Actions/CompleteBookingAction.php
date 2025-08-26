<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Services\BookingCompletionValidationService;
use App\Domain\Booking\Services\BookingCompletionProcessorService;
use App\Domain\Booking\Services\BookingPaymentProcessorService;
use App\Domain\Booking\Services\BookingNotificationService;
use App\Domain\Booking\Services\BookingBulkOperationsService;
use App\Domain\Master\Services\MasterStatisticsUpdateService;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для завершения бронирования - координатор
 */
class CompleteBookingAction
{
    public function __construct(
        private BookingCompletionValidationService $validationService,
        private BookingCompletionProcessorService $processorService,
        private BookingPaymentProcessorService $paymentProcessorService,
        private BookingNotificationService $notificationService,
        private MasterStatisticsUpdateService $masterStatisticsService
    ) {}

    /**
     * Выполнить завершение бронирования
     */
    public function execute(
        Booking $booking, 
        User $master, 
        array $options = []
    ): Booking {
        Log::info('Completing booking', [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'master_id' => $master->id,
            'options' => $options,
        ]);

        // Валидация через сервис
        $this->validationService->validateCompletion($booking, $master);

        // Выполнение завершения в транзакции
        $result = DB::transaction(function () use ($booking, $master, $options) {
            $result = $this->processorService->performCompletion($booking, $master, $options);
            $this->masterStatisticsService->updateMasterStatistics($master, $booking);
            return $result;
        });

        // Обработка оплаты через сервис
        $this->paymentProcessorService->processPayment($booking, $options);

        // Отправка уведомлений через сервис
        $this->notificationService->sendCompletionNotifications($booking, $options);

        Log::info('Booking completed successfully', [
            'booking_id' => $booking->id,
            'total_earned' => $booking->total_price,
        ]);

        return $booking->fresh();
    }

    /**
     * Массовое завершение бронирований (делегируем в сервис)
     */
    public function bulkComplete(array $bookingIds, User $master, array $options = []): array
    {
        return app(BookingBulkOperationsService::class)->bulkComplete($bookingIds, $master, $options);
    }

    /**
     * Автозавершение просроченных бронирований (делегируем в сервис)
     */
    public function autoCompleteOverdue(): array
    {
        return app(BookingBulkOperationsService::class)->autoCompleteOverdue();
    }
}