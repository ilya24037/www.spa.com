<?php

namespace App\Domain\Booking\Actions\Services;

use App\Domain\Booking\Models\Booking;
use Illuminate\Support\Facades\Log;

/**
 * Сервис уведомлений о подтверждении бронирований
 */
class BookingConfirmationNotifier
{
    /**
     * Настройка напоминаний
     */
    public function scheduleReminders(Booking $booking): void
    {
        if (!$booking->type) {
            return;
        }

        $reminderHours = $booking->type->getReminderHours();
        
        foreach ($reminderHours as $hours) {
            $reminderTime = $booking->start_time->copy()->subHours($hours);
            
            if ($reminderTime->isPast()) {
                continue;
            }

            // TODO: Создаем задачу напоминания
            // dispatch(function () use ($booking) {
            //     $this->notificationService->sendBookingReminder($booking);
            // })->delay($reminderTime);
        }
    }

    /**
     * Отправка уведомлений о подтверждении
     */
    public function sendConfirmationNotifications(Booking $booking, array $options): void
    {
        try {
            // TODO: Уведомления через NotificationService
            // $this->notificationService->sendBookingConfirmed($booking);
            // 
            // if ($booking->client_phone && ($options['send_sms'] ?? true)) {
            //     $this->notificationService->sendConfirmationSMS($booking);
            // }
            //
            // if ($booking->client_email && ($options['send_email'] ?? true)) {
            //     $this->notificationService->sendConfirmationEmail($booking);
            // }
            //
            // if ($booking->type === \App\Enums\BookingType::ONLINE && $booking->meeting_link) {
            //     $this->notificationService->sendMeetingLink($booking);
            // }

            Log::info('Confirmation notifications sent', [
                'booking_id' => $booking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send confirmation notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обработка предоплаты
     */
    public function handlePrepayment(Booking $booking, array $options): void
    {
        if (!$booking->type || !$booking->type->supportsPrepayment()) {
            return;
        }

        $depositAmount = $booking->deposit_amount ?? ($booking->total_price * 0.3);

        if ($depositAmount <= 0) {
            return;
        }

        try {
            // TODO: Отправляем ссылку на оплату предоплаты через PaymentService
            // $paymentLink = $this->paymentService->createDepositPaymentLink($booking, $depositAmount);
            // 
            // if ($paymentLink) {
            //     $this->notificationService->sendDepositPaymentLink($booking, $paymentLink);
            //     
            //     Log::info('Deposit payment link sent', [
            //         'booking_id' => $booking->id,
            //         'deposit_amount' => $depositAmount,
            //     ]);
            // }
        } catch (\Exception $e) {
            Log::error('Failed to create deposit payment link', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}