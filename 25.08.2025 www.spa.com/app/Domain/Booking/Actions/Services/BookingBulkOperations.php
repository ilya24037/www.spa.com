<?php

namespace App\Domain\Booking\Actions\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Сервис массовых операций с бронированиями
 */
class BookingBulkOperations
{
    private BookingConfirmationValidator $validator;
    private BookingConfirmationProcessor $processor;

    public function __construct(
        BookingConfirmationValidator $validator,
        BookingConfirmationProcessor $processor
    ) {
        $this->validator = $validator;
        $this->processor = $processor;
    }

    /**
     * Автоподтверждение бронирований
     */
    public function autoConfirm(Booking $booking): bool
    {
        try {
            $master = User::find($booking->master_id);
            
            if (!$master || !$master->masterProfile) {
                return false;
            }

            $autoConfirm = $master->masterProfile->auto_confirm_bookings ?? false;
            
            if (!$autoConfirm) {
                return false;
            }

            $this->validator->validateConfirmation($booking, $master);
            $this->validator->checkScheduleConflicts($booking, $master);

            $this->processor->performConfirmation($booking, $master, [
                'auto_confirm' => true,
                'method' => 'automatic',
                'create_slots' => true,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Auto-confirmation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Массовое подтверждение бронирований
     */
    public function bulkConfirm(array $bookingIds, User $master, array $options = []): array
    {
        $results = [];

        foreach ($bookingIds as $bookingId) {
            try {
                $booking = Booking::findOrFail($bookingId);
                
                $this->validator->validateConfirmation($booking, $master);
                $this->validator->checkScheduleConflicts($booking, $master);
                $this->processor->performConfirmation($booking, $master, $options);
                
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Bulk confirmation completed', [
            'total_bookings' => count($bookingIds),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
        ]);

        return $results;
    }
}