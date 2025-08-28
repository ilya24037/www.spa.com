<?php

namespace App\Infrastructure\Notification;

use App\Domain\Notification\Contracts\NotificationServiceInterface;
use App\Domain\Booking\Models\Booking;
use Illuminate\Support\Facades\Log;

/**
 * Простая реализация сервиса уведомлений
 * Пока только логирует, в будущем можно добавить реальную отправку
 */
class SimpleNotificationService implements NotificationServiceInterface
{
    /**
     * Отправить уведомление пользователю
     */
    public function send($user, string $type, array $data = []): void
    {
        Log::info('Notification sent', [
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'type' => $type,
            'data' => $data
        ]);
        
        // В будущем здесь может быть:
        // - Отправка email через Laravel Mail
        // - Отправка SMS через внешний сервис
        // - Push уведомления
        // - Уведомления в Telegram Bot
    }
    
    /**
     * Отправить уведомление о завершении бронирования
     */
    public function sendBookingCompleted(Booking $booking): void
    {
        Log::info('Booking completed notification', [
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'master_id' => $booking->master_id
        ]);
        
        // В будущем: отправка реального уведомления
    }
    
    /**
     * Отправить запрос на отзыв
     */
    public function sendReviewRequest(Booking $booking): void
    {
        Log::info('Review request sent', [
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id
        ]);
        
        // В будущем: отправка email с ссылкой на форму отзыва
    }
    
    /**
     * Отправить SMS о завершении
     */
    public function sendCompletionSMS(Booking $booking): void
    {
        Log::info('Completion SMS sent', [
            'booking_id' => $booking->id,
            'client_phone' => $booking->client_phone
        ]);
        
        // В будущем: интеграция с SMS сервисом
    }
}