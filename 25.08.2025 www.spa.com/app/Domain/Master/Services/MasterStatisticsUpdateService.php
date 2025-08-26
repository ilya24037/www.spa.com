<?php

namespace App\Domain\Master\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;

/**
 * Сервис обновления статистики мастера
 */
class MasterStatisticsUpdateService
{
    /**
     * Обновление статистики мастера
     */
    public function updateMasterStatistics(User $master, Booking $booking): void
    {
        if (!$master->masterProfile) {
            return;
        }

        $master->masterProfile->increment('completed_bookings_count');
        $master->masterProfile->increment('total_earnings', $booking->total_price);
        
        $master->masterProfile->update([
            'last_booking_completed_at' => now(),
            'last_service_date' => now()->toDateString(),
        ]);

        $this->updateMasterRating($master);
    }

    /**
     * Обновление рейтинга мастера
     */
    public function updateMasterRating(User $master): void
    {
        if (!$master->masterProfile) {
            return;
        }

        $completedCount = $master->masterProfile->completed_bookings_count;
        
        $rating = $this->calculateRating($completedCount);
        $master->masterProfile->update(['rating' => $rating]);
    }

    /**
     * Расчет рейтинга на основе количества выполненных услуг
     */
    private function calculateRating(int $completedCount): float
    {
        return match(true) {
            $completedCount >= 100 => 5.0,
            $completedCount >= 50 => 4.8,
            $completedCount >= 20 => 4.5,
            $completedCount >= 10 => 4.2,
            $completedCount >= 5 => 4.0,
            default => 3.8,
        };
    }

    /**
     * Получить статистику мастера
     */
    public function getMasterStatistics(User $master): array
    {
        if (!$master->masterProfile) {
            return [];
        }

        return [
            'completed_bookings' => $master->masterProfile->completed_bookings_count,
            'total_earnings' => $master->masterProfile->total_earnings,
            'rating' => $master->masterProfile->rating,
            'last_service_date' => $master->masterProfile->last_service_date,
        ];
    }
}