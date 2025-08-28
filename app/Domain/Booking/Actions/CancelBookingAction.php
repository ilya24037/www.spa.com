<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\BookingHistory;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Services\BookingValidationService;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для простой отмены бронирования
 * Упрощенная версия без сложной валидации, штрафов и возвратов
 */
class CancelBookingAction
{
    private BookingRepository $bookingRepository;
    private BookingValidationService $validationService;

    public function __construct(
        BookingRepository $bookingRepository,
        BookingValidationService $validationService
    ) {
        $this->bookingRepository = $bookingRepository;
        $this->validationService = $validationService;
    }

    /**
     * Отменить бронирование (упрощенная версия)
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

                // Базовая валидация отмены (без сложной логики)
                try {
                    $this->validationService->validateCancellation($booking, $user);
                } catch (\Exception $e) {
                    return $this->errorResponse('Нельзя отменить бронирование: ' . $e->getMessage());
                }

                // Простое выполнение отмены
                $this->performCancellation($booking, $user, $reason);

                // Простое логирование
                $this->logCancellation($booking, $user, $reason);

                return $this->successResponse($booking);
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
     * Выполнить простую отмену бронирования
     */
    private function performCancellation(
        Booking $booking, 
        User $user, 
        string $reason
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
        
        // Простые метаданные (без штрафов)
        $metadata = $booking->metadata ?? [];
        $metadata['cancellation'] = [
            'cancelled_by_role' => $isClient ? 'client' : 'master',
            'reason' => $reason,
            'cancelled_at' => now()->toDateTimeString(),
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
                    'cancelled_at' => now()->toDateTimeString(),
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
     * Простое логирование отмены
     */
    private function logCancellation(
        Booking $booking, 
        User $user, 
        string $reason
    ): void {
        Log::info('Booking cancelled', [
            'booking_id' => $booking->id,
            'cancelled_by' => $user->id,
            'reason' => $reason,
            'cancelled_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Успешный ответ (упрощенный)
     */
    private function successResponse(Booking $booking): array
    {
        return [
            'success' => true,
            'message' => 'Бронирование успешно отменено',
            'booking' => $booking,
            'cancelled_at' => $booking->cancelled_at,
            'cancelled_by' => $booking->cancelled_by,
            'reason' => $booking->cancellation_reason,
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

}