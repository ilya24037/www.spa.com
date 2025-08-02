<?php

namespace App\Infrastructure\Notification;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Models\Payment;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Устаревший сервис уведомлений (оставлен для совместимости)
 * @deprecated Используйте новый NotificationService
 */
class LegacyNotificationService
{
    /**
     * Отправить уведомление о новом бронировании
     */
    public function sendBookingCreated(Booking $booking): void
    {
        try {
            // Уведомление мастеру
            $this->sendEmailToMaster($booking);
            
            // Уведомление клиенту
            $this->sendEmailToClient($booking);
            
            // SMS уведомления (опционально)
            if (config('notifications.sms_enabled')) {
                $this->sendSmsToMaster($booking);
                $this->sendSmsToClient($booking);
            }
            
            Log::info('Booking notifications sent', ['booking_id' => $booking->id]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send booking notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Отправить уведомление о подтверждении бронирования
     */
    public function sendBookingConfirmed(Booking $booking): void
    {
        try {
            $this->sendEmail($booking->client_email, 'Бронирование подтверждено', $this->getConfirmationTemplate($booking));
            
            if (config('notifications.sms_enabled')) {
                $this->sendSms($booking->client_phone, "Ваше бронирование #{$booking->booking_number} подтверждено мастером");
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to send confirmation notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Отправить уведомление об отмене бронирования
     */
    public function sendBookingCancelled(Booking $booking, User $cancelledBy): void
    {
        try {
            $recipientEmail = $cancelledBy->id === $booking->client_id 
                ? $booking->masterProfile->user->email 
                : $booking->client_email;
                
            $this->sendEmail($recipientEmail, 'Бронирование отменено', $this->getCancellationTemplate($booking, $cancelledBy));
            
        } catch (\Exception $e) {
            Log::error('Failed to send cancellation notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Отправить запрос на отзыв
     */
    public function sendReviewRequest(Booking $booking): void
    {
        try {
            $this->sendEmail($booking->client_email, 'Оставьте отзыв о визите', $this->getReviewRequestTemplate($booking));
            
        } catch (\Exception $e) {
            Log::error('Failed to send review request', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Отправить уведомление об успешной оплате
     */
    public function sendPaymentCompleted(Payment $payment): void
    {
        try {
            $this->sendEmail($payment->user->email, 'Платеж успешно завершен', $this->getPaymentCompletedTemplate($payment));
            
        } catch (\Exception $e) {
            Log::error('Failed to send payment notification', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Отправить email мастеру о новом бронировании
     */
    private function sendEmailToMaster(Booking $booking): void
    {
        $masterEmail = $booking->masterProfile->user->email;
        $subject = 'Новое бронирование #' . $booking->booking_number;
        $template = $this->getNewBookingMasterTemplate($booking);
        
        $this->sendEmail($masterEmail, $subject, $template);
    }

    /**
     * Отправить email клиенту о создании бронирования
     */
    private function sendEmailToClient(Booking $booking): void
    {
        if (!$booking->client_email) return;
        
        $subject = 'Ваше бронирование #' . $booking->booking_number;
        $template = $this->getNewBookingClientTemplate($booking);
        
        $this->sendEmail($booking->client_email, $subject, $template);
    }

    /**
     * Базовый метод отправки email
     */
    private function sendEmail(string $to, string $subject, string $content): void
    {
        // В продакшене здесь будет реальная отправка email
        // Пока логируем для тестирования
        Log::info('Email notification sent', [
            'to' => $to,
            'subject' => $subject,
            'content_preview' => substr($content, 0, 100) . '...'
        ]);
        
        // Для разработки выводим в лог
        if (config('app.debug')) {
            \Log::channel('single')->info("📧 EMAIL TO: {$to}\nSUBJECT: {$subject}\nCONTENT:\n{$content}");
        }
    }

    /**
     * Отправить SMS
     */
    private function sendSms(string $phone, string $message): void
    {
        // В продакшене здесь будет интеграция с SMS провайдером
        Log::info('SMS notification sent', [
            'phone' => $phone,
            'message' => $message
        ]);
        
        if (config('app.debug')) {
            \Log::channel('single')->info("📱 SMS TO: {$phone}\nMESSAGE: {$message}");
        }
    }

    /**
     * Отправить SMS мастеру
     */
    private function sendSmsToMaster(Booking $booking): void
    {
        $phone = $booking->masterProfile->user->phone ?? $booking->masterProfile->phone;
        if (!$phone) return;
        
        $message = "Новое бронирование #{$booking->booking_number} от {$booking->client_name} на {$booking->booking_date->format('d.m.Y')} в {$booking->start_time->format('H:i')}";
        $this->sendSms($phone, $message);
    }

    /**
     * Отправить SMS клиенту
     */
    private function sendSmsToClient(Booking $booking): void
    {
        $message = "Ваше бронирование #{$booking->booking_number} создано. Ожидайте подтверждения от мастера.";
        $this->sendSms($booking->client_phone, $message);
    }

    // =================== ШАБЛОНЫ EMAIL ===================

    private function getNewBookingMasterTemplate(Booking $booking): string
    {
        return "
Новое бронирование #{$booking->booking_number}

Клиент: {$booking->client_name}
Телефон: {$booking->client_phone}
Email: {$booking->client_email}

Услуга: {$booking->service->name}
Дата: {$booking->booking_date->format('d.m.Y')}
Время: {$booking->start_time->format('H:i')} - {$booking->end_time->format('H:i')}

Место: " . ($booking->is_home_service ? "Выезд на дом ({$booking->address})" : 'В салоне') . "

Комментарий: {$booking->client_comment}

Стоимость: {$booking->total_price} ₽

Для подтверждения перейдите в личный кабинет.
        ";
    }

    private function getNewBookingClientTemplate(Booking $booking): string
    {
        return "
Ваше бронирование #{$booking->booking_number} создано

Мастер: {$booking->masterProfile->user->name}
Услуга: {$booking->service->name}
Дата: {$booking->booking_date->format('d.m.Y')}
Время: {$booking->start_time->format('H:i')} - {$booking->end_time->format('H:i')}

Место: " . ($booking->is_home_service ? "Выезд на дом ({$booking->address})" : 'В салоне') . "

Стоимость: {$booking->total_price} ₽

Ожидайте подтверждения от мастера.
        ";
    }

    private function getConfirmationTemplate(Booking $booking): string
    {
        return "
Ваше бронирование #{$booking->booking_number} подтверждено!

Мастер: {$booking->masterProfile->user->name}
Дата: {$booking->booking_date->format('d.m.Y')}
Время: {$booking->start_time->format('H:i')}
        ";
    }

    private function getCancellationTemplate(Booking $booking, User $cancelledBy): string
    {
        $who = $cancelledBy->id === $booking->client_id ? 'клиентом' : 'мастером';
        
        return "
Бронирование #{$booking->booking_number} отменено {$who}

Дата: {$booking->booking_date->format('d.m.Y')}
Время: {$booking->start_time->format('H:i')}

Причина: {$booking->cancellation_reason}
        ";
    }

    private function getReviewRequestTemplate(Booking $booking): string
    {
        return "
Как прошел ваш визит к мастеру {$booking->masterProfile->user->name}?

Оставьте отзыв о качестве услуг.
        ";
    }

    private function getPaymentCompletedTemplate(Payment $payment): string
    {
        return "
Платеж #{$payment->payment_id} успешно завершен

Сумма: " . ($payment->metadata['final_amount'] ?? $payment->amount) . " ₽
Описание: {$payment->description}
Дата: " . $payment->paid_at->format('d.m.Y H:i') . "

Спасибо за использование нашего сервиса!
        ";
    }
}