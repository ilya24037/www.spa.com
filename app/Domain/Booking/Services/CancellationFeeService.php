<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;

/**
 * Сервис расчета штрафов за отмену бронирования
 */
class CancellationFeeService
{
    /**
     * Рассчитать штраф за отмену
     */
    public function calculate(Booking $booking, User $user): array
    {
        $baseAmount = $booking->total_price ?? 0;
        
        if ($baseAmount <= 0) {
            return [
                'fee_amount' => 0,
                'fee_percent' => 0,
                'description' => 'Штраф не взимается - бронирование бесплатное'
            ];
        }

        $hoursUntilStart = now()->diffInHours($booking->start_at ?? $booking->start_time, false);
        $isClient = $booking->client_id === $user->id;

        // Расчет процента штрафа
        $feePercent = $this->calculateFeePercent($hoursUntilStart, $isClient, $booking);
        $feeAmount = ($baseAmount * $feePercent) / 100;

        return [
            'fee_amount' => round($feeAmount, 2),
            'fee_percent' => $feePercent,
            'hours_until_start' => $hoursUntilStart,
            'base_amount' => $baseAmount,
            'is_client_cancellation' => $isClient,
            'description' => $this->getFeeDescription($feePercent, $hoursUntilStart, $isClient)
        ];
    }

    /**
     * Рассчитать процент штрафа
     */
    private function calculateFeePercent(float $hoursUntilStart, bool $isClient, Booking $booking): float
    {
        // Без штрафа при отмене более чем за сутки
        if ($hoursUntilStart >= 24) {
            return 0;
        }

        // Базовая таблица штрафов для клиентов
        if ($isClient) {
            if ($hoursUntilStart >= 12) {
                return 10; // 10% при отмене за 12-24 часа
            } elseif ($hoursUntilStart >= 6) {
                return 25; // 25% при отмене за 6-12 часов
            } elseif ($hoursUntilStart >= 3) {
                return 50; // 50% при отмене за 3-6 часов
            } elseif ($hoursUntilStart >= 1) {
                return 75; // 75% при отмене за 1-3 часа
            } else {
                return 100; // 100% при отмене менее чем за час
            }
        }

        // Штрафы для мастеров (увеличенные, так как они подводят клиентов)
        if ($hoursUntilStart >= 12) {
            return 20; // 20% при отмене за 12-24 часа
        } elseif ($hoursUntilStart >= 6) {
            return 40; // 40% при отмене за 6-12 часов
        } elseif ($hoursUntilStart >= 3) {
            return 70; // 70% при отмене за 3-6 часов
        } elseif ($hoursUntilStart >= 1) {
            return 90; // 90% при отмене за 1-3 часа
        } else {
            return 100; // 100% при отмене менее чем за час
        }
    }

    /**
     * Получить описание штрафа
     */
    private function getFeeDescription(float $feePercent, float $hoursUntilStart, bool $isClient): string
    {
        if ($feePercent === 0) {
            return 'Отмена без штрафа (более 24 часов до начала)';
        }

        $role = $isClient ? 'клиентом' : 'мастером';
        $hours = round($hoursUntilStart, 1);
        
        if ($feePercent === 100) {
            return "Полный штраф за позднюю отмену {$role} (менее 1 часа до начала)";
        }

        return "Штраф {$feePercent}% за отмену {$role} за {$hours} часов до начала";
    }

    /**
     * Рассчитать штраф для группового бронирования
     */
    public function calculateGroupFee(array $bookings, User $user): array
    {
        $totalFee = 0;
        $details = [];

        foreach ($bookings as $booking) {
            $fee = $this->calculate($booking, $user);
            $totalFee += $fee['fee_amount'];
            $details[] = [
                'booking_id' => $booking->id,
                'fee' => $fee
            ];
        }

        return [
            'total_fee' => $totalFee,
            'bookings_count' => count($bookings),
            'details' => $details,
            'average_fee_percent' => count($bookings) > 0 
                ? round(array_sum(array_column(array_column($details, 'fee'), 'fee_percent')) / count($bookings), 2)
                : 0
        ];
    }

    /**
     * Проверить, применяется ли льготная отмена
     */
    public function hasGracePeriod(Booking $booking, User $user): bool
    {
        // Новые пользователи получают одну бесплатную отмену
        if ($user->bookings()->count() <= 1) {
            return true;
        }

        // VIP клиенты имеют льготные условия
        if ($user->hasRole('vip')) {
            return true;
        }

        // Форс-мажорные обстоятельства (нужна дополнительная проверка)
        if ($booking->has_force_majeure) {
            return true;
        }

        return false;
    }
}