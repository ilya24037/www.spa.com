<?php

namespace App\Domain\Notification\Contracts;

use App\Domain\Booking\Models\Booking;

/**
 * Интерфейс для сервиса уведомлений
 * Domain слой определяет контракт, Infrastructure его реализует
 */
interface NotificationServiceInterface
{
    /**
     * Отправить уведомление пользователю
     */
    public function send($user, string $type, array $data = []): void;
    
    /**
     * Отправить уведомление о завершении бронирования
     */
    public function sendBookingCompleted(Booking $booking): void;
    
    /**
     * Отправить запрос на отзыв
     */
    public function sendReviewRequest(Booking $booking): void;
    
    /**
     * Отправить SMS о завершении
     */
    public function sendCompletionSMS(Booking $booking): void;
}