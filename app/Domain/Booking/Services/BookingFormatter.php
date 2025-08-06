<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Enums\BookingStatus;
use Carbon\Carbon;

/**
 * Форматирование и представление данных бронирования
 */
class BookingFormatter
{
    /**
     * Форматировать продолжительность
     */
    public function formatDuration(Booking $booking): string
    {
        $minutes = $booking->duration_minutes ?? $booking->duration ?? 0;
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours} ч {$remainingMinutes} мин";
        } elseif ($hours > 0) {
            return "{$hours} ч";
        } else {
            return "{$remainingMinutes} мин";
        }
    }

    /**
     * Получить текст статуса на русском языке
     */
    public function getStatusText(Booking $booking): string
    {
        if ($booking->status instanceof BookingStatus) {
            return $booking->status->getLabel();
        }

        return match($booking->status) {
            Booking::STATUS_PENDING => 'Ожидает подтверждения',
            Booking::STATUS_CONFIRMED => 'Подтверждено',
            Booking::STATUS_IN_PROGRESS => 'Выполняется',
            Booking::STATUS_COMPLETED => 'Завершено',
            Booking::STATUS_CANCELLED => 'Отменено',
            Booking::STATUS_NO_SHOW => 'Клиент не пришел',
            default => 'Неизвестно'
        };
    }

    /**
     * Получить цвет статуса для UI
     */
    public function getStatusColor(Booking $booking): string
    {
        if ($booking->status instanceof BookingStatus) {
            return $booking->status->getColor();
        }

        return match($booking->status) {
            Booking::STATUS_PENDING => 'yellow',
            Booking::STATUS_CONFIRMED => 'blue',
            Booking::STATUS_IN_PROGRESS => 'indigo',
            Booking::STATUS_COMPLETED => 'green',
            Booking::STATUS_CANCELLED => 'red',
            Booking::STATUS_NO_SHOW => 'gray',
            default => 'gray'
        };
    }

    /**
     * Форматировать полную дату и время
     */
    public function getFullDateTime(Booking $booking): ?Carbon
    {
        if (!$booking->booking_date || !$booking->start_time) {
            return null;
        }

        return Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time);
    }

    /**
     * Форматировать дату для отображения
     */
    public function formatDate(Booking $booking, string $format = 'd.m.Y'): string
    {
        if (!$booking->booking_date) {
            return 'Дата не указана';
        }

        return $booking->booking_date->format($format);
    }

    /**
     * Форматировать время для отображения
     */
    public function formatTime(Booking $booking): string
    {
        if (!$booking->start_time || !$booking->end_time) {
            return 'Время не указано';
        }

        $startTime = Carbon::parse($booking->start_time)->format('H:i');
        $endTime = Carbon::parse($booking->end_time)->format('H:i');

        return "{$startTime} - {$endTime}";
    }

    /**
     * Форматировать цену
     */
    public function formatPrice(float $price): string
    {
        return number_format($price, 0, '.', ' ') . ' ₽';
    }

    /**
     * Получить краткое описание бронирования
     */
    public function getShortDescription(Booking $booking): string
    {
        $parts = [];

        if ($booking->service) {
            $parts[] = $booking->service->name;
        }

        if ($booking->master) {
            $parts[] = "мастер: {$booking->master->name}";
        }

        if ($booking->booking_date) {
            $parts[] = $this->formatDate($booking, 'd.m');
        }

        if ($booking->start_time) {
            $parts[] = Carbon::parse($booking->start_time)->format('H:i');
        }

        return implode(', ', $parts);
    }

    /**
     * Получить полное описание бронирования
     */
    public function getFullDescription(Booking $booking): string
    {
        $description = [];

        // Услуга
        if ($booking->service) {
            $description[] = "Услуга: {$booking->service->name}";
        }

        // Мастер
        if ($booking->master) {
            $description[] = "Мастер: {$booking->master->name}";
        }

        // Дата и время
        if ($booking->booking_date && $booking->start_time) {
            $description[] = "Дата и время: {$this->formatDate($booking, 'd.m.Y')} в {$this->formatTime($booking)}";
        }

        // Длительность
        if ($booking->duration_minutes || $booking->duration) {
            $description[] = "Длительность: {$this->formatDuration($booking)}";
        }

        // Цена
        if ($booking->total_price) {
            $description[] = "Стоимость: {$this->formatPrice($booking->total_price)}";
        }

        // Статус
        $description[] = "Статус: {$this->getStatusText($booking)}";

        return implode("\n", $description);
    }

    /**
     * Форматировать адрес
     */
    public function formatAddress(Booking $booking): string
    {
        $addressParts = [];

        if ($booking->address) {
            $addressParts[] = $booking->address;
        }

        if ($booking->address_details) {
            $addressParts[] = $booking->address_details;
        }

        return implode(', ', $addressParts) ?: 'Адрес не указан';
    }

    /**
     * Получить информацию об оплате
     */
    public function getPaymentInfo(Booking $booking): array
    {
        return [
            'total_price' => $this->formatPrice($booking->total_price ?? 0),
            'paid_amount' => $this->formatPrice($booking->paid_amount ?? 0),
            'remaining_amount' => $this->formatPrice(($booking->total_price ?? 0) - ($booking->paid_amount ?? 0)),
            'deposit_amount' => $this->formatPrice($booking->deposit_amount ?? 0),
            'payment_method' => $this->getPaymentMethodText($booking->payment_method),
            'payment_status' => $this->getPaymentStatusText($booking->payment_status),
        ];
    }

    /**
     * Получить текст способа оплаты
     */
    private function getPaymentMethodText(?string $paymentMethod): string
    {
        return match($paymentMethod) {
            Booking::PAYMENT_CASH => 'Наличные',
            Booking::PAYMENT_CARD => 'Карта',
            Booking::PAYMENT_ONLINE => 'Онлайн',
            Booking::PAYMENT_TRANSFER => 'Перевод',
            default => 'Не указан'
        };
    }

    /**
     * Получить текст статуса оплаты
     */
    private function getPaymentStatusText(?string $paymentStatus): string
    {
        return match($paymentStatus) {
            'pending' => 'Ожидает оплаты',
            'paid' => 'Оплачено',
            'partially_paid' => 'Частично оплачено',
            'refunded' => 'Возвращено',
            'failed' => 'Ошибка оплаты',
            default => 'Не указан'
        };
    }

    /**
     * Получить иконку статуса
     */
    public function getStatusIcon(Booking $booking): string
    {
        if ($booking->status instanceof BookingStatus) {
            return $booking->status->getIcon();
        }

        return match($booking->status) {
            Booking::STATUS_PENDING => '⏳',
            Booking::STATUS_CONFIRMED => '✅',
            Booking::STATUS_IN_PROGRESS => '🔄',
            Booking::STATUS_COMPLETED => '✅',
            Booking::STATUS_CANCELLED => '❌',
            Booking::STATUS_NO_SHOW => '🚫',
            default => '❓'
        };
    }

    /**
     * Форматировать для календаря
     */
    public function formatForCalendar(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'title' => $this->getShortDescription($booking),
            'start' => $this->getFullDateTime($booking)?->toISOString(),
            'end' => $booking->end_time ? Carbon::parse($booking->end_time)->toISOString() : null,
            'color' => $this->getStatusColor($booking),
            'status' => $this->getStatusText($booking),
            'client' => $booking->client?->name,
            'master' => $booking->master?->name,
            'service' => $booking->service?->name,
            'price' => $this->formatPrice($booking->total_price ?? 0),
        ];
    }
}