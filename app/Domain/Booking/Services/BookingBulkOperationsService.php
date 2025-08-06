<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Enums\BookingStatus;
use Illuminate\Support\Facades\Log;

/**
 * Сервис массовых операций с бронированиями
 */
class BookingBulkOperationsService
{

    /**
     * Массовое завершение бронирований
     */
    public function bulkComplete(array $bookingIds, User $master, array $options = []): array
    {
        $results = [];
        $totalEarnings = 0;

        foreach ($bookingIds as $bookingId) {
            $result = $this->completeSingleBooking($bookingId, $master, $options);
            $results[] = $result;
            
            if ($result['success']) {
                $totalEarnings += $result['earnings'];
            }
        }

        Log::info('Bulk completion completed', [
            'total_bookings' => count($bookingIds),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
            'total_earnings' => $totalEarnings,
        ]);

        return [
            'results' => $results,
            'total_earnings' => $totalEarnings,
        ];
    }

    /**
     * Автозавершение просроченных бронирований
     */
    public function autoCompleteOverdue(): array
    {
        $overdueBookings = $this->getOverdueBookings();
        $results = [];

        foreach ($overdueBookings as $booking) {
            if ($booking->master) {
                $result = $this->autoCompleteBooking($booking);
                $results[] = $result;
            }
        }

        Log::info('Auto-completion of overdue bookings completed', [
            'processed' => count($results),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
        ]);

        return $results;
    }

    /**
     * Завершить одно бронирование
     */
    private function completeSingleBooking(int $bookingId, User $master, array $options): array
    {
        try {
            $booking = Booking::findOrFail($bookingId);
            $completeAction = app(\App\Domain\Booking\Actions\CompleteBookingAction::class);
            $completedBooking = $completeAction->execute($booking, $master, $options);
            
            return [
                'booking_id' => $bookingId,
                'success' => true,
                'earnings' => $completedBooking->total_price,
            ];
        } catch (\Exception $e) {
            return [
                'booking_id' => $bookingId,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить просроченные бронирования
     */
    private function getOverdueBookings()
    {
        return Booking::where('status', BookingStatus::IN_PROGRESS)
            ->where('end_time', '<', now()->subHours(2))
            ->with(['master'])
            ->get();
    }

    /**
     * Автоматически завершить бронирование
     */
    private function autoCompleteBooking(Booking $booking): array
    {
        try {
            $completeAction = app(\App\Domain\Booking\Actions\CompleteBookingAction::class);
            $completeAction->execute($booking, $booking->master, [
                'auto_complete' => true,
                'completion_notes' => 'Автоматическое завершение просроченного бронирования',
                'request_review' => false,
            ]);
            
            return [
                'booking_id' => $booking->id,
                'success' => true,
            ];
        } catch (\Exception $e) {
            return [
                'booking_id' => $booking->id,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}