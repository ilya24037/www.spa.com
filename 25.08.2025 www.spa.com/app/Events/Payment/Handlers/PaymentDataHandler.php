<?php

namespace App\Events\Payment\Handlers;

use App\Domain\Payment\Models\Payment;

/**
 * Обработчик данных платежа для события
 */
class PaymentDataHandler
{
    /**
     * Получить данные связанной сущности
     */
    public function getPayableData(Payment $payment): ?array
    {
        if (!$payment->payable) {
            return null;
        }

        return match($payment->payable_type) {
            'App\Models\Booking' => $this->getBookingData($payment),
            'App\Models\Subscription' => $this->getSubscriptionData($payment),
            'App\Models\Order' => $this->getOrderData($payment),
            default => [
                'type' => 'other',
                'id' => $payment->payable->id,
            ],
        };
    }

    /**
     * Получить данные бронирования
     */
    private function getBookingData(Payment $payment): array
    {
        $booking = $payment->payable;
        
        return [
            'type' => 'booking',
            'id' => $booking->id,
            'booking_number' => $booking->booking_number ?? null,
            'service_name' => $booking->service?->name,
            'start_time' => $booking->start_time?->toISOString(),
            'master_name' => $booking->master?->name,
            'status' => $booking->status,
            'at_risk' => $this->isBookingAtRisk($booking),
        ];
    }

    /**
     * Получить данные подписки
     */
    private function getSubscriptionData(Payment $payment): array
    {
        $subscription = $payment->payable;
        
        return [
            'type' => 'subscription',
            'id' => $subscription->id,
            'plan_name' => $subscription->plan?->name,
            'expires_at' => $subscription->expires_at?->toISOString(),
            'status' => $subscription->status,
        ];
    }

    /**
     * Получить данные заказа
     */
    private function getOrderData(Payment $payment): array
    {
        $order = $payment->payable;
        
        return [
            'type' => 'order',
            'id' => $order->id,
            'order_number' => $order->order_number,
            'items_count' => $order->items_count ?? 0,
            'status' => $order->status,
        ];
    }

    /**
     * Находится ли бронирование под угрозой отмены
     */
    public function isBookingAtRisk($booking): bool
    {
        if (!$booking || !$booking->start_time) {
            return false;
        }

        // Бронирование под угрозой если до начала меньше 4 часов
        return now()->diffInHours($booking->start_time, false) <= 4;
    }

    /**
     * Получить каналы для мастера
     */
    public function getMasterChannels(Payment $payment): array
    {
        if ($payment->payable_type === 'App\Models\Booking') {
            $booking = $payment->payable;
            if ($booking && $booking->master_id) {
                return [new \Illuminate\Broadcasting\PrivateChannel('master.' . $booking->master_id)];
            }
        }
        
        return [];
    }
}