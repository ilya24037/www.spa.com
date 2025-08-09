<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\BookingHistory;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Services\CancellationValidationService;
use App\Domain\Booking\Services\CancellationFeeService;
use App\Domain\Booking\Services\BookingRefundService;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для отмены бронирования
 * Использует специализированные сервисы для валидации, расчета штрафов и возвратов
 */
class CancelBookingAction
{
    private BookingRepository $bookingRepository;
    private CancellationValidationService $validationService;
    private CancellationFeeService $feeService;
    private BookingRefundService $refundService;

    public function __construct(
        BookingRepository $bookingRepository,
        CancellationValidationService $validationService,
        CancellationFeeService $feeService,
        BookingRefundService $refundService
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->validationService = $validationService;
        $this->feeService = $feeService;
        $this->refundService = $refundService;
    }

    /**
     * Отменить бронирование
     */
    public function execute(int $bookingId, int $userId, string $reason = ''): array
    {
        try {
            return DB::transaction(function () use ($bookingId, $userId, $reason) {
                // Получаем бронирование и пользователя
                $booking = $this->bookingRepository->findById($bookingId);
                if (!$booking) {
                    return $this->errorResponse('Бронирование не найдено');
                }

                $user = User::find($userId);
                if (!$user) {
                    return $this->errorResponse('Пользователь не найден');
                }

                // Валидация возможности отмены
                $validation = $this->validationService->validate($booking, $user);
                if (!$validation['valid']) {
                    return $this->errorResponse($validation['message']);
                }

                // Расчет штрафа
                $feeCalculation = $this->feeService->calculate($booking, $user);
                
                // Обработка возврата
                $refundResult = $this->refundService->processRefund(
                    $booking, 
                    $feeCalculation['fee_amount']
                );

                // Выполнение отмены
                $this->performCancellation($booking, $user, $reason, $feeCalculation);

                // Логирование
                $this->logCancellation($booking, $user, $reason, $feeCalculation, $refundResult);

                return $this->successResponse($booking, $feeCalculation, $refundResult);
            });
        } catch (\Exception $e) {
            Log::error('Failed to cancel booking', [
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Ошибка при отмене бронирования: ' . $e->getMessage());
        }
    }

    /**
     * Выполнить отмену бронирования
     */
    private function performCancellation(
        Booking $booking, 
        User $user, 
        string $reason, 
        array $feeCalculation
    ): void {
        $isClient = $booking->client_id === $user->id;
        $previousStatus = $booking->status;

        // Определяем новый статус
        if ($booking->status instanceof BookingStatus) {
            $booking->status = $isClient 
                ? BookingStatus::CANCELLED_BY_CLIENT 
                : BookingStatus::CANCELLED_BY_MASTER;
        } else {
            // Совместимость со старым кодом
            $booking->status = Booking::STATUS_CANCELLED ?? 'cancelled';
        }

        // Обновляем поля отмены
        $booking->cancellation_reason = $reason;
        $booking->cancelled_at = now();
        $booking->cancelled_by = $user->id;
        
        // Сохраняем информацию о штрафе
        $metadata = $booking->metadata ?? [];
        $metadata['cancellation'] = [
            'fee_amount' => $feeCalculation['fee_amount'],
            'fee_percent' => $feeCalculation['fee_percent'],
            'hours_before_start' => $feeCalculation['hours_until_start'],
            'cancelled_by_role' => $isClient ? 'client' : 'master',
            'reason' => $reason,
        ];
        $booking->metadata = $metadata;
        
        $booking->save();

        // Освобождаем слоты
        $this->releaseBookingSlots($booking);

        // Записываем в историю
        $this->recordHistory($booking, $previousStatus, $user, $reason);
    }

    /**
     * Освободить временные слоты
     */
    private function releaseBookingSlots(Booking $booking): void
    {
        // Удаляем связанные слоты
        if ($booking->slots()->exists()) {
            $booking->slots()->delete();
        }

        // Отменяем дополнительные услуги
        if ($booking->bookingServices()->exists()) {
            $booking->bookingServices()->update([
                'is_completed' => false,
                'notes' => 'Отменено в связи с отменой основного бронирования',
            ]);
        }
    }

    /**
     * Записать в историю
     */
    private function recordHistory(Booking $booking, $previousStatus, User $user, string $reason): void
    {
        try {
            BookingHistory::create([
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'action' => 'cancelled',
                'old_status' => $previousStatus,
                'new_status' => $booking->status,
                'reason' => $reason,
                'metadata' => [
                    'cancelled_by' => $user->id,
                    'cancellation_fee' => $booking->metadata['cancellation']['fee_amount'] ?? 0,
                ],
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record booking history', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логирование отмены
     */
    private function logCancellation(
        Booking $booking, 
        User $user, 
        string $reason, 
        array $feeCalculation, 
        array $refundResult
    ): void {
        Log::info('Booking cancelled', [
            'booking_id' => $booking->id,
            'cancelled_by' => $user->id,
            'reason' => $reason,
            'fee' => $feeCalculation,
            'refund' => $refundResult,
        ]);
    }

    /**
     * Успешный ответ
     */
    private function successResponse(Booking $booking, array $feeCalculation, array $refundResult): array
    {
        return [
            'success' => true,
            'message' => 'Бронирование успешно отменено',
            'booking' => $booking,
            'cancellation_fee' => $feeCalculation,
            'refund' => $refundResult,
        ];
    }

    /**
     * Ответ с ошибкой
     */
    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
        ];
    }

    /**
     * Массовая отмена бронирований
     * Делегирует работу в отдельный action
     * 
     * @deprecated Используйте BulkCancelBookingsAction
     */
    public function bulkCancel(array $bookingIds, User $user, string $reason): array
    {
        $bulkAction = app(BulkCancelBookingsAction::class);
        return $bulkAction->execute($bookingIds, $user->id, $reason);
    }
}