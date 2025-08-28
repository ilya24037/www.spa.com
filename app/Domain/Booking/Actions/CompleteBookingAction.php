<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Services\BookingValidationService;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Booking\Services\BookingNotificationService;
use App\Domain\Master\Services\MasterStatisticsUpdateService;
use App\Domain\User\Models\User;
use App\Domain\Booking\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для завершения бронирования (упрощенная версия)
 * Без несуществующих сервисов, только базовый функционал
 */
class CompleteBookingAction
{
    public function __construct(
        private BookingValidationService $validationService,
        private BookingService $bookingService,
        private BookingNotificationService $notificationService,
        private MasterStatisticsUpdateService $masterStatisticsService
    ) {}

    /**
     * Выполнить завершение бронирования (упрощенная версия)
     */
    public function execute(
        Booking $booking, 
        User $master, 
        array $options = []
    ): Booking {
        Log::info('Completing booking', [
            'booking_id' => $booking->id,
            'master_id' => $master->id,
            'options' => $options,
        ]);

        // Базовая валидация
        try {
            $this->validationService->validateCompletion($booking, $master);
        } catch (\Exception $e) {
            throw new \Exception('Нельзя завершить бронирование: ' . $e->getMessage());
        }

        // Простое выполнение завершения в транзакции
        return DB::transaction(function () use ($booking, $master, $options) {
            // Простое завершение бронирования
            $booking = $this->performSimpleCompletion($booking, $master, $options);
            
            // Обновление статистики мастера
            $this->masterStatisticsService->updateMasterStatistics($master, $booking);
            
            // Отправка уведомлений
            try {
                $this->notificationService->sendCompletionNotifications($booking, $options);
            } catch (\Exception $e) {
                Log::warning('Failed to send completion notifications', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Booking completed successfully', [
                'booking_id' => $booking->id,
                'master_id' => $master->id,
            ]);

            return $booking;
        });
    }

    /**
     * Простое завершение бронирования
     */
    private function performSimpleCompletion(
        Booking $booking, 
        User $master, 
        array $options = []
    ): Booking {
        // Обновляем статус бронирования
        if ($booking->status instanceof BookingStatus) {
            $booking->status = BookingStatus::COMPLETED;
        } else {
            // Совместимость со старым кодом
            $booking->status = Booking::STATUS_COMPLETED ?? 'completed';
        }

        // Обновляем временные метки
        $booking->completed_at = now();
        $booking->completed_by = $master->id;

        // Простые метаданные
        $metadata = $booking->metadata ?? [];
        $metadata['completion'] = [
            'completed_by' => $master->id,
            'completed_at' => now()->toDateTimeString(),
            'options' => $options,
        ];
        $booking->metadata = $metadata;

        $booking->save();

        return $booking;
    }
}