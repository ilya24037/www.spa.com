<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use Illuminate\Support\Facades\Log;

/**
 * Сервис обработки завершения бронирования
 */
class BookingCompletionProcessorService
{
    public function __construct(
        private BookingCompletionValidationService $validationService
    ) {}

    /**
     * Обработать завершение бронирования
     */
    public function processCompletion(Booking $booking, array $data): bool
    {
        try {
            // Валидируем данные
            $errors = $this->validationService->validateCompletionData($data);
            if (!empty($errors)) {
                Log::warning('Booking completion validation failed', [
                    'booking_id' => $booking->id,
                    'errors' => $errors
                ]);
                return false;
            }

            // Обновляем статус бронирования
            $booking->update([
                'status' => 'completed',
                'completion_notes' => $data['completion_notes'] ?? null,
                'completion_rating' => $data['rating'] ?? null,
                'completed_at' => now()
            ]);

            Log::info('Booking completed successfully', [
                'booking_id' => $booking->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Booking completion failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
