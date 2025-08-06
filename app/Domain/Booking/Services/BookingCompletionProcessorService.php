<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;

/**
 * Сервис обработки завершения бронирований
 */
class BookingCompletionProcessorService
{
    /**
     * Выполнение завершения
     */
    public function performCompletion(Booking $booking, User $master, array $options): array
    {
        $previousStatus = $booking->status;

        // Обновляем статус бронирования
        if ($booking->status instanceof BookingStatus) {
            $booking->status = BookingStatus::COMPLETED;
        } else {
            $booking->status = Booking::STATUS_COMPLETED;
        }

        $booking->completed_at = now();
        
        $this->updateBookingMetadata($booking, $master, $options);
        $this->updateBookingNotes($booking, $options);
        
        $booking->save();

        $this->completeRelatedEntities($booking);

        return [
            'booking' => $booking,
            'previous_status' => $previousStatus,
            'earnings' => $booking->total_price,
        ];
    }

    /**
     * Обновить метаданные бронирования
     */
    private function updateBookingMetadata(Booking $booking, User $master, array $options): void
    {
        if (!empty($options['actual_duration'])) {
            $booking->metadata = array_merge($booking->metadata ?? [], [
                'completion' => [
                    'actual_duration_minutes' => $options['actual_duration'],
                    'planned_duration_minutes' => $booking->duration_minutes,
                    'completed_by' => $master->id,
                    'completed_at' => now()->toISOString(),
                    'notes' => $options['completion_notes'] ?? null,
                ],
            ]);
        }
    }

    /**
     * Обновить заметки бронирования
     */
    private function updateBookingNotes(Booking $booking, array $options): void
    {
        if (!empty($options['service_notes'])) {
            $existingNotes = $booking->internal_notes ? $booking->internal_notes . "\n\n" : '';
            $booking->internal_notes = $existingNotes . "Завершение услуги: " . $options['service_notes'];
        }
    }

    /**
     * Завершить связанные сущности
     */
    private function completeRelatedEntities(Booking $booking): void
    {
        $this->completeBookingServices($booking);
        $this->completeBookingSlots($booking);
    }

    /**
     * Завершение связанных услуг
     */
    private function completeBookingServices(Booking $booking): void
    {
        $booking->bookingServices()->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    /**
     * Завершение временных слотов
     */
    private function completeBookingSlots(Booking $booking): void
    {
        $booking->slots()->update([
            'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' - Услуга завершена')"),
        ]);
    }
}